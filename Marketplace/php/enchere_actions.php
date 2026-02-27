<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté.']);
    exit;
}

$action = $_POST['action'] ?? '';
$uid = (int)$_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? '';

function json_error_auction(string $message): void
{
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

function insert_auction_notification(PDO $pdo, int $utilisateur_id, string $message): void
{
    $stmt = $pdo->prepare('INSERT INTO notifications (utilisateur_id, message) VALUES (:uid, :msg)');
    $stmt->execute([':uid' => $utilisateur_id, ':msg' => $message]);
}

try {
    switch ($action) {
        case 'place_bid':
            if ($user_role !== 'acheteur') {
                json_error_auction('Accès réservé aux acheteurs.');
            }

            $article_id = (int)($_POST['article_id'] ?? 0);
            $montant_max = (float)($_POST['montant_max'] ?? 0);

            if ($article_id <= 0 || $montant_max <= 0) {
                json_error_auction('Données invalides.');
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT *
                                   FROM articles
                                   WHERE id = :id AND type_vente = 'meilleure_offre' AND statut = 'disponible'
                                   FOR UPDATE");
            $stmt->execute([':id' => $article_id]);
            $article = $stmt->fetch();

            if (!$article) {
                throw new RuntimeException('Article non disponible.');
            }
            if ((int)$article['vendeur_id'] === $uid) {
                throw new RuntimeException('Vous ne pouvez pas enchérir sur votre propre article.');
            }
            if (empty($article['date_fin_enchere'])) {
                throw new RuntimeException('Période d\'enchère invalide.');
            }

            $now = new DateTime();
            $date_debut = !empty($article['date_debut_enchere'])
                ? new DateTime($article['date_debut_enchere'])
                : $now;
            $date_fin = new DateTime($article['date_fin_enchere']);

            if ($now < $date_debut) {
                throw new RuntimeException('Les enchères n\'ont pas encore commencé.');
            }
            if ($now >= $date_fin) {
                throw new RuntimeException('Les enchères sont terminées.');
            }
            if ($montant_max < (float)$article['prix']) {
                throw new RuntimeException(
                    'L\'enchère doit être supérieure ou égale au prix de réserve (' . number_format((float)$article['prix'], 2, ',', ' ') . ' €).'
                );
            }

            $stmt = $pdo->prepare("SELECT id, montant_max
                                   FROM encheres
                                   WHERE article_id = :aid AND acheteur_id = :uid
                                   FOR UPDATE");
            $stmt->execute([':aid' => $article_id, ':uid' => $uid]);
            $existing = $stmt->fetch();

            if ($existing) {
                if ($montant_max <= (float)$existing['montant_max']) {
                    throw new RuntimeException(
                        'Votre nouvelle enchère doit être supérieure à votre enchère actuelle ('
                        . number_format((float)$existing['montant_max'], 2, ',', ' ')
                        . ' €).'
                    );
                }

                $stmt = $pdo->prepare("UPDATE encheres
                                       SET montant_max = :montant,
                                           statut = 'en_attente',
                                           prix_paye = NULL,
                                           date_creation = NOW()
                                       WHERE id = :id");
                $stmt->execute([
                    ':montant' => $montant_max,
                    ':id' => (int)$existing['id'],
                ]);

                $pdo->commit();
                echo json_encode([
                    'success' => true,
                    'updated' => true,
                    'message' => 'Enchère mise à jour avec succès.',
                ]);
                break;
            }

            $stmt = $pdo->prepare("INSERT INTO encheres (article_id, acheteur_id, montant_max, statut)
                                   VALUES (:aid, :uid, :montant, 'en_attente')");
            $stmt->execute([
                ':aid' => $article_id,
                ':uid' => $uid,
                ':montant' => $montant_max,
            ]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Enchère placée avec succès.']);
            break;

        case 'resolve_auction':
            if ($user_role !== 'administrateur') {
                json_error_auction('Accès refusé.');
            }

            $article_id = (int)($_POST['article_id'] ?? 0);
            if ($article_id <= 0) {
                json_error_auction('Article invalide.');
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT *
                                   FROM articles
                                   WHERE id = :id AND type_vente = 'meilleure_offre' AND statut = 'disponible'
                                   FOR UPDATE");
            $stmt->execute([':id' => $article_id]);
            $article = $stmt->fetch();

            if (!$article) {
                throw new RuntimeException('Article non trouvé ou déjà vendu.');
            }
            if (empty($article['date_fin_enchere'])) {
                throw new RuntimeException('Date de fin d\'enchère invalide.');
            }

            $now = new DateTime();
            $date_fin = new DateTime($article['date_fin_enchere']);
            if ($now < $date_fin) {
                throw new RuntimeException('Impossible de clôturer avant la date de fin.');
            }

            $stmt = $pdo->prepare("SELECT e.id, e.article_id, e.acheteur_id, e.montant_max, e.date_creation,
                                          u.prenom, u.nom
                                   FROM encheres e
                                   JOIN utilisateurs u ON u.id = e.acheteur_id
                                   WHERE e.article_id = :aid
                                   ORDER BY e.montant_max DESC, e.date_creation ASC, e.id ASC
                                   FOR UPDATE");
            $stmt->execute([':aid' => $article_id]);
            $bids = $stmt->fetchAll();

            if (empty($bids)) {
                $pdo->commit();
                echo json_encode([
                    'success' => true,
                    'no_bids' => true,
                    'message' => 'Aucune enchère reçue. L\'article reste disponible.',
                ]);
                break;
            }

            $winner = $bids[0];
            $reserve_price = (float)$article['prix'];

            if (count($bids) === 1) {
                $winning_price = $reserve_price;
            } else {
                $second_highest = (float)$bids[1]['montant_max'];
                $winning_price = max($reserve_price, $second_highest + 1);
                $winning_price = min($winning_price, (float)$winner['montant_max']);
            }

            $stmt = $pdo->prepare("UPDATE encheres
                                   SET statut = 'gagnant', prix_paye = :prix
                                   WHERE id = :id");
            $stmt->execute([
                ':prix' => $winning_price,
                ':id' => (int)$winner['id'],
            ]);

            $stmt = $pdo->prepare("UPDATE encheres
                                   SET statut = 'perdant'
                                   WHERE article_id = :aid AND id != :winner_id");
            $stmt->execute([
                ':aid' => $article_id,
                ':winner_id' => (int)$winner['id'],
            ]);

            $stmt = $pdo->prepare('SELECT id FROM panier WHERE utilisateur_id = :uid AND article_id = :aid FOR UPDATE');
            $stmt->execute([
                ':uid' => (int)$winner['acheteur_id'],
                ':aid' => $article_id,
            ]);
            $panier_id = $stmt->fetchColumn();

            if ($panier_id) {
                $stmt = $pdo->prepare("UPDATE panier
                                       SET quantite = 1,
                                           prix_negocie = :prix,
                                           enchere_id = :eid,
                                           negociation_id = NULL,
                                           date_ajout = NOW()
                                       WHERE id = :pid");
                $stmt->execute([
                    ':prix' => $winning_price,
                    ':eid' => (int)$winner['id'],
                    ':pid' => (int)$panier_id,
                ]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO panier (utilisateur_id, article_id, quantite, prix_negocie, enchere_id)
                                       VALUES (:uid, :aid, 1, :prix, :eid)");
                $stmt->execute([
                    ':uid' => (int)$winner['acheteur_id'],
                    ':aid' => $article_id,
                    ':prix' => $winning_price,
                    ':eid' => (int)$winner['id'],
                ]);
            }

            $stmt = $pdo->prepare("UPDATE articles
                                   SET statut = 'vendu'
                                   WHERE id = :id AND statut = 'disponible'");
            $stmt->execute([':id' => $article_id]);
            if ($stmt->rowCount() === 0) {
                throw new RuntimeException('Article déjà verrouillé.');
            }

            $prix_str = number_format($winning_price, 2, ',', ' ');
            insert_auction_notification(
                $pdo,
                (int)$winner['acheteur_id'],
                'Vous avez remporté l\'enchère pour "' . $article['titre'] . '" au prix de ' . $prix_str . ' €. L\'article a été ajouté à votre panier.'
            );
            insert_auction_notification(
                $pdo,
                (int)$article['vendeur_id'],
                'L\'enchère pour votre article "' . $article['titre'] . '" est terminée. Vendu à ' . $prix_str . ' €.'
            );

            for ($i = 1, $len = count($bids); $i < $len; $i++) {
                insert_auction_notification(
                    $pdo,
                    (int)$bids[$i]['acheteur_id'],
                    'L\'enchère pour "' . $article['titre'] . '" est terminée. Vous n\'avez malheureusement pas remporté cet article.'
                );
            }

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Enchère clôturée avec succès.',
                'winner_name' => trim($winner['prenom'] . ' ' . $winner['nom']),
                'winning_price' => $winning_price,
                'nb_bids' => count($bids),
            ]);
            break;

        default:
            json_error_auction('Action inconnue.');
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_error_auction($e->getMessage() ?: 'Erreur serveur.');
}
