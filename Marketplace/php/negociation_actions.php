<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    json_error('Non connecté.');
}

$action = $_POST['action'] ?? '';
$uid = (int)$_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? '';

function get_last_message(PDO $pdo, int $negociation_id): ?array
{
    $stmt = $pdo->prepare("SELECT id, auteur_id, statut
                           FROM negociation_messages
                           WHERE negociation_id = :nid
                           ORDER BY date_creation DESC, id DESC
                           LIMIT 1");
    $stmt->execute([':nid' => $negociation_id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function upsert_negotiation_cart(PDO $pdo, int $acheteur_id, int $article_id, int $negociation_id, float $prix): void
{
    $stmt = $pdo->prepare('SELECT id FROM panier WHERE utilisateur_id = :uid AND article_id = :aid FOR UPDATE');
    $stmt->execute([':uid' => $acheteur_id, ':aid' => $article_id]);
    $panier_id = $stmt->fetchColumn();

    if ($panier_id) {
        $stmt = $pdo->prepare("UPDATE panier
                               SET quantite = 1,
                                   prix_negocie = :prix,
                                   negociation_id = :nid,
                                   enchere_id = NULL,
                                   date_ajout = NOW()
                               WHERE id = :pid");
        $stmt->execute([
            ':prix' => $prix,
            ':nid' => $negociation_id,
            ':pid' => $panier_id,
        ]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO panier (utilisateur_id, article_id, quantite, prix_negocie, negociation_id)
                               VALUES (:uid, :aid, 1, :prix, :nid)");
        $stmt->execute([
            ':uid' => $acheteur_id,
            ':aid' => $article_id,
            ':prix' => $prix,
            ':nid' => $negociation_id,
        ]);
    }
}

try {
    switch ($action) {
        case 'send_offer':
            if ($user_role !== 'acheteur') {
                json_error('Accès réservé aux acheteurs.');
            }

            $article_id = (int)($_POST['article_id'] ?? 0);
            $montant = (float)($_POST['montant'] ?? 0);
            $message = trim($_POST['message'] ?? '');

            if ($article_id <= 0 || $montant <= 0) {
                json_error('Données invalides.');
            }

            if ($montant < 0.01 || $montant > 999999.99) {
                json_error('Le montant doit être entre 0,01 € et 999 999,99 €.');
            }

            if ($message && (strlen($message) < 3 || strlen($message) > 500)) {
                json_error('Le message doit contenir entre 3 et 500 caractères.');
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT *
                                   FROM articles
                                   WHERE id = :id AND type_vente = 'negociation' AND statut = 'disponible'
                                   FOR UPDATE");
            $stmt->execute([':id' => $article_id]);
            $article = $stmt->fetch();

            if (!$article) {
                throw new RuntimeException('Article non disponible.');
            }
            if ((int)$article['vendeur_id'] === $uid) {
                throw new RuntimeException('Vous ne pouvez pas négocier sur votre propre article.');
            }

            $stmt = $pdo->prepare("SELECT *
                                   FROM negociations
                                   WHERE article_id = :aid AND acheteur_id = :uid
                                   FOR UPDATE");
            $stmt->execute([':aid' => $article_id, ':uid' => $uid]);
            $negociation = $stmt->fetch();

            if (!$negociation) {
                $stmt = $pdo->prepare("SELECT id
                                       FROM negociations
                                       WHERE article_id = :aid AND statut = 'accepte'
                                       LIMIT 1
                                       FOR UPDATE");
                $stmt->execute([':aid' => $article_id]);
                if ($stmt->fetchColumn()) {
                    throw new RuntimeException('Cet article a déjà une négociation acceptée.');
                }

                $stmt = $pdo->prepare("INSERT INTO negociations (article_id, acheteur_id, vendeur_id, statut)
                                       VALUES (:aid, :uid, :vid, 'en_cours')");
                $stmt->execute([
                    ':aid' => $article_id,
                    ':uid' => $uid,
                    ':vid' => (int)$article['vendeur_id'],
                ]);
                $nego_id = (int)$pdo->lastInsertId();
            } else {
                if ($negociation['statut'] !== 'en_cours') {
                    throw new RuntimeException('Négociation terminée.');
                }
                $nego_id = (int)$negociation['id'];
            }

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM negociation_messages WHERE negociation_id = :nid');
            $stmt->execute([':nid' => $nego_id]);
            $nb_messages = (int)$stmt->fetchColumn();
            if ($nb_messages >= 10) {
                throw new RuntimeException('Nombre maximum de rounds atteint (5).');
            }

            $last_message = get_last_message($pdo, $nego_id);
            if ($last_message && (int)$last_message['auteur_id'] === $uid && $last_message['statut'] === 'en_attente') {
                throw new RuntimeException('Vous devez attendre la réponse du vendeur avant de renvoyer une offre.');
            }
            if ($last_message && (int)$last_message['auteur_id'] !== $uid && $last_message['statut'] === 'en_attente') {
                $stmt = $pdo->prepare("UPDATE negociation_messages
                                       SET statut = 'refuse'
                                       WHERE id = :id");
                $stmt->execute([':id' => (int)$last_message['id']]);
            }

            $stmt = $pdo->prepare("INSERT INTO negociation_messages (negociation_id, auteur_id, montant_propose, message, statut)
                                   VALUES (:nid, :uid, :montant, :msg, 'en_attente')");
            $stmt->execute([
                ':nid' => $nego_id,
                ':uid' => $uid,
                ':montant' => $montant,
                ':msg' => $message,
            ]);

            $prix_str = number_format($montant, 2, ',', ' ');
            insert_notification(
                $pdo,
                (int)$article['vendeur_id'],
                'Nouvelle offre de ' . $prix_str . ' € pour votre article "' . $article['titre'] . '". Consultez vos négociations.'
            );

            $pdo->commit();
            echo json_encode(['success' => true]);
            break;

        case 'respond':
            if ($user_role !== 'vendeur') {
                json_error('Accès réservé aux vendeurs.');
            }

            $message_id = (int)($_POST['message_id'] ?? 0);
            $response = $_POST['response'] ?? '';
            if ($message_id <= 0 || !in_array($response, ['accepte', 'refuse'], true)) {
                json_error('Données invalides.');
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT nm.id, nm.negociation_id, nm.auteur_id, nm.montant_propose, nm.statut,
                                          n.id AS nego_id, n.article_id, n.acheteur_id, n.vendeur_id, n.statut AS nego_statut,
                                          a.titre, a.statut AS article_statut
                                   FROM negociation_messages nm
                                   JOIN negociations n ON nm.negociation_id = n.id
                                   JOIN articles a ON n.article_id = a.id
                                   WHERE nm.id = :id
                                   FOR UPDATE");
            $stmt->execute([':id' => $message_id]);
            $msg = $stmt->fetch();

            if (!$msg) {
                throw new RuntimeException('Message introuvable.');
            }
            if ((int)$msg['vendeur_id'] !== $uid) {
                throw new RuntimeException('Accès refusé.');
            }
            if ((int)$msg['auteur_id'] === $uid) {
                throw new RuntimeException('Vous ne pouvez pas répondre à votre propre message.');
            }
            if ((int)$msg['auteur_id'] !== (int)$msg['acheteur_id']) {
                throw new RuntimeException('Le message ciblé doit provenir de l\'acheteur.');
            }
            if ($msg['nego_statut'] !== 'en_cours') {
                throw new RuntimeException('Négociation terminée.');
            }
            if ($msg['statut'] !== 'en_attente') {
                throw new RuntimeException('Ce message a déjà été traité.');
            }

            $last_message = get_last_message($pdo, (int)$msg['negociation_id']);
            if (!$last_message || (int)$last_message['id'] !== $message_id) {
                throw new RuntimeException('Vous ne pouvez répondre qu\'à la dernière offre en attente.');
            }

            $stmt = $pdo->prepare('UPDATE negociation_messages SET statut = :statut WHERE id = :id');
            $stmt->execute([':statut' => $response, ':id' => $message_id]);

            if ($response === 'accepte') {
                $prix_accorde = (float)$msg['montant_propose'];

                $stmt = $pdo->prepare("UPDATE negociations
                                       SET statut = 'accepte', prix_accorde = :prix, date_resolution = NOW()
                                       WHERE id = :id");
                $stmt->execute([':prix' => $prix_accorde, ':id' => (int)$msg['nego_id']]);

                $stmt = $pdo->prepare("UPDATE negociations
                                       SET statut = 'refuse', date_resolution = NOW()
                                       WHERE article_id = :aid AND id != :nid AND statut = 'en_cours'");
                $stmt->execute([
                    ':aid' => (int)$msg['article_id'],
                    ':nid' => (int)$msg['nego_id'],
                ]);

                $stmt = $pdo->prepare("UPDATE articles
                                       SET statut = 'vendu'
                                       WHERE id = :id AND statut = 'disponible'");
                $stmt->execute([':id' => (int)$msg['article_id']]);
                if ($stmt->rowCount() === 0) {
                    throw new RuntimeException('Article déjà verrouillé.');
                }

                upsert_negotiation_cart(
                    $pdo,
                    (int)$msg['acheteur_id'],
                    (int)$msg['article_id'],
                    (int)$msg['nego_id'],
                    $prix_accorde
                );

                $prix_str = number_format($prix_accorde, 2, ',', ' ');
                insert_notification(
                    $pdo,
                    (int)$msg['acheteur_id'],
                    'Votre offre de ' . $prix_str . ' € pour "' . $msg['titre'] . '" a été acceptée. L\'article est maintenant dans votre panier.'
                );
            } else {
                insert_notification(
                    $pdo,
                    (int)$msg['acheteur_id'],
                    'Votre offre pour "' . $msg['titre'] . '" a été refusée par le vendeur.'
                );
            }

            $pdo->commit();
            echo json_encode(['success' => true]);
            break;

        case 'send_counter_offer':
            if ($user_role !== 'vendeur') {
                json_error('Accès réservé aux vendeurs.');
            }

            $negociation_id = (int)($_POST['negociation_id'] ?? 0);
            $montant = (float)($_POST['montant'] ?? 0);
            $message = trim($_POST['message'] ?? '');
            $parent_message_id = (int)($_POST['parent_message_id'] ?? 0);

            if ($negociation_id <= 0 || $montant <= 0) {
                json_error('Données invalides.');
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT n.id, n.article_id, n.acheteur_id, n.vendeur_id, n.statut,
                                          a.titre, a.statut AS article_statut
                                   FROM negociations n
                                   JOIN articles a ON n.article_id = a.id
                                   WHERE n.id = :id
                                   FOR UPDATE");
            $stmt->execute([':id' => $negociation_id]);
            $nego = $stmt->fetch();

            if (!$nego || (int)$nego['vendeur_id'] !== $uid) {
                throw new RuntimeException('Accès refusé.');
            }
            if ($nego['statut'] !== 'en_cours') {
                throw new RuntimeException('Négociation terminée.');
            }
            if ($nego['article_statut'] !== 'disponible') {
                throw new RuntimeException('Article non disponible.');
            }

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM negociation_messages WHERE negociation_id = :nid');
            $stmt->execute([':nid' => $negociation_id]);
            $nb_messages = (int)$stmt->fetchColumn();
            if ($nb_messages >= 10) {
                throw new RuntimeException('Nombre maximum de rounds atteint (5).');
            }

            $last_message = get_last_message($pdo, $negociation_id);
            if (!$last_message) {
                throw new RuntimeException('Aucune offre acheteur à contre-proposer.');
            }
            if ((int)$last_message['auteur_id'] === $uid) {
                throw new RuntimeException('Vous devez attendre une offre acheteur avant de contre-proposer.');
            }
            if ($last_message['statut'] !== 'en_attente') {
                throw new RuntimeException('Le dernier message n\'est plus en attente.');
            }
            if ($parent_message_id > 0 && (int)$last_message['id'] !== $parent_message_id) {
                throw new RuntimeException('Message parent invalide.');
            }

            $stmt = $pdo->prepare("UPDATE negociation_messages
                                   SET statut = 'refuse'
                                   WHERE id = :id");
            $stmt->execute([':id' => (int)$last_message['id']]);

            $stmt = $pdo->prepare("INSERT INTO negociation_messages (negociation_id, auteur_id, montant_propose, message, statut)
                                   VALUES (:nid, :uid, :montant, :msg, 'en_attente')");
            $stmt->execute([
                ':nid' => $negociation_id,
                ':uid' => $uid,
                ':montant' => $montant,
                ':msg' => $message,
            ]);

            $prix_str = number_format($montant, 2, ',', ' ');
            insert_notification(
                $pdo,
                (int)$nego['acheteur_id'],
                'Le vendeur vous propose une contre-offre de ' . $prix_str . ' € pour "' . $nego['titre'] . '". Consultez la négociation.'
            );

            $pdo->commit();
            echo json_encode(['success' => true]);
            break;

        case 'accept_counter_offer':
            if ($user_role !== 'acheteur') {
                json_error('Accès réservé aux acheteurs.');
            }

            $message_id = (int)($_POST['message_id'] ?? 0);
            if ($message_id <= 0) {
                json_error('Données invalides.');
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT nm.id, nm.negociation_id, nm.auteur_id, nm.montant_propose, nm.statut,
                                          n.id AS nego_id, n.article_id, n.acheteur_id, n.vendeur_id, n.statut AS nego_statut,
                                          a.titre, a.statut AS article_statut
                                   FROM negociation_messages nm
                                   JOIN negociations n ON nm.negociation_id = n.id
                                   JOIN articles a ON n.article_id = a.id
                                   WHERE nm.id = :id
                                   FOR UPDATE");
            $stmt->execute([':id' => $message_id]);
            $msg = $stmt->fetch();

            if (!$msg || (int)$msg['acheteur_id'] !== $uid) {
                throw new RuntimeException('Accès refusé.');
            }
            if ((int)$msg['auteur_id'] === $uid) {
                throw new RuntimeException('Vous ne pouvez pas accepter votre propre offre.');
            }
            if ((int)$msg['auteur_id'] !== (int)$msg['vendeur_id']) {
                throw new RuntimeException('Le message ciblé doit provenir du vendeur.');
            }
            if ($msg['nego_statut'] !== 'en_cours') {
                throw new RuntimeException('Négociation terminée.');
            }
            if ($msg['statut'] !== 'en_attente') {
                throw new RuntimeException('Cette contre-offre a déjà été traitée.');
            }

            $last_message = get_last_message($pdo, (int)$msg['negociation_id']);
            if (!$last_message || (int)$last_message['id'] !== $message_id) {
                throw new RuntimeException('Vous ne pouvez accepter que la dernière contre-offre en attente.');
            }

            $stmt = $pdo->prepare("UPDATE negociation_messages
                                   SET statut = 'accepte'
                                   WHERE id = :id");
            $stmt->execute([':id' => $message_id]);

            $prix_accorde = (float)$msg['montant_propose'];

            $stmt = $pdo->prepare("UPDATE negociations
                                   SET statut = 'accepte', prix_accorde = :prix, date_resolution = NOW()
                                   WHERE id = :id");
            $stmt->execute([':prix' => $prix_accorde, ':id' => (int)$msg['nego_id']]);

            $stmt = $pdo->prepare("UPDATE negociations
                                   SET statut = 'refuse', date_resolution = NOW()
                                   WHERE article_id = :aid AND id != :nid AND statut = 'en_cours'");
            $stmt->execute([
                ':aid' => (int)$msg['article_id'],
                ':nid' => (int)$msg['nego_id'],
            ]);

            $stmt = $pdo->prepare("UPDATE articles
                                   SET statut = 'vendu'
                                   WHERE id = :id AND statut = 'disponible'");
            $stmt->execute([':id' => (int)$msg['article_id']]);
            if ($stmt->rowCount() === 0) {
                throw new RuntimeException('Article déjà verrouillé.');
            }

            upsert_negotiation_cart(
                $pdo,
                $uid,
                (int)$msg['article_id'],
                (int)$msg['nego_id'],
                $prix_accorde
            );

            $prix_str = number_format($prix_accorde, 2, ',', ' ');
            insert_notification(
                $pdo,
                (int)$msg['vendeur_id'],
                'L\'acheteur a accepté votre contre-offre de ' . $prix_str . ' € pour "' . $msg['titre'] . '".'
            );
            insert_notification(
                $pdo,
                $uid,
                'Vous avez accepté la contre-offre pour "' . $msg['titre'] . '". L\'article est dans votre panier.'
            );

            $pdo->commit();
            echo json_encode([
                'success' => true,
                'prix_accepte' => $prix_accorde,
            ]);
            break;

        default:
            json_error('Action inconnue.');
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_error($e->getMessage() ?: 'Erreur serveur.');
}
