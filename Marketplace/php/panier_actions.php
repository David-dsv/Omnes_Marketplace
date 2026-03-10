<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté.']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$uid = (int)$_SESSION['user_id'];

switch ($action) {
    case 'count':
        try {
            $stmt = $pdo->prepare('SELECT COALESCE(SUM(quantite), 0) AS count FROM panier WHERE utilisateur_id = :uid');
            $stmt->execute([':uid' => $uid]);
            $result = $stmt->fetch();
            echo json_encode(['count' => (int)$result['count']]);
        } catch (PDOException $e) {
            echo json_encode(['count' => 0]);
        }
        break;

    case 'add':
        $article_id = (int)($_POST['article_id'] ?? 0);
        $negotiation_id = (int)($_POST['negotiation_id'] ?? 0);
        $quantite = max(1, (int)($_POST['quantite'] ?? 1));

        if ($article_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Article invalide.']);
            exit;
        }

        if ($quantite <= 0 || $quantite > 999) {
            echo json_encode(['success' => false, 'message' => 'Quantité invalide.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare('SELECT * FROM articles WHERE id = :id');
            $stmt->execute([':id' => $article_id]);
            $article = $stmt->fetch();

            if (!$article) {
                echo json_encode(['success' => false, 'message' => 'Article introuvable.']);
                exit;
            }

            if ($article['statut'] !== 'disponible') {
                json_error('Article non disponible.');
            }

            $stmt = $pdo->prepare('SELECT id FROM panier WHERE utilisateur_id = :uid AND article_id = :aid');
            $stmt->execute([':uid' => $uid, ':aid' => $article_id]);
            $existing_panier_id = $stmt->fetchColumn();

            if ($article['type_vente'] === 'achat_immediat') {
                if ($existing_panier_id) {
                    echo json_encode(['success' => false, 'message' => 'Article déjà dans le panier.']);
                    exit;
                }

                $stmt = $pdo->prepare('INSERT INTO panier (utilisateur_id, article_id, quantite) VALUES (:uid, :aid, 1)');
                $stmt->execute([':uid' => $uid, ':aid' => $article_id]);
                echo json_encode(['success' => true]);
                exit;
            }

            if ($article['type_vente'] === 'negociation') {
                if ($negotiation_id <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Négociation invalide.']);
                    exit;
                }

                $stmt = $pdo->prepare("SELECT id, prix_accorde
                                       FROM negociations
                                       WHERE id = :nid
                                         AND article_id = :aid
                                         AND acheteur_id = :uid
                                         AND statut = 'accepte'
                                       LIMIT 1");
                $stmt->execute([
                    ':nid' => $negotiation_id,
                    ':aid' => $article_id,
                    ':uid' => $uid,
                ]);
                $negociation = $stmt->fetch();

                if (!$negociation || $negociation['prix_accorde'] === null) {
                    echo json_encode(['success' => false, 'message' => 'Négociation non valide ou non acceptée.']);
                    exit;
                }

                if ($article['statut'] !== 'vendu') {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Article négocié non verrouillé. Réessayez après confirmation vendeur.',
                    ]);
                    exit;
                }

                $prix_final = (float)$negociation['prix_accorde'];

                if ($existing_panier_id) {
                    $stmt = $pdo->prepare("UPDATE panier
                                           SET quantite = 1,
                                               prix_negocie = :prix,
                                               negociation_id = :nid,
                                               enchere_id = NULL,
                                               date_ajout = NOW()
                                           WHERE id = :pid");
                    $stmt->execute([
                        ':prix' => $prix_final,
                        ':nid' => (int)$negociation['id'],
                        ':pid' => (int)$existing_panier_id,
                    ]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO panier (utilisateur_id, article_id, quantite, prix_negocie, negociation_id)
                                           VALUES (:uid, :aid, 1, :prix, :nid)");
                    $stmt->execute([
                        ':uid' => $uid,
                        ':aid' => $article_id,
                        ':prix' => $prix_final,
                        ':nid' => (int)$negociation['id'],
                    ]);
                }

                echo json_encode(['success' => true]);
                exit;
            }

            echo json_encode([
                'success' => false,
                'message' => 'Ce type d\'article ne peut pas être ajouté directement au panier.',
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur serveur.']);
        }
        break;

    case 'remove':
        $article_id = (int)($_POST['article_id'] ?? 0);

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT p.id, p.enchere_id, p.negociation_id,
                                          a.type_vente, a.statut, a.titre, a.vendeur_id
                                   FROM panier p
                                   JOIN articles a ON a.id = p.article_id
                                   WHERE p.utilisateur_id = :uid AND p.article_id = :aid
                                   FOR UPDATE");
            $stmt->execute([':uid' => $uid, ':aid' => $article_id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$item) {
                $pdo->commit();
                echo json_encode(['success' => true]);
                exit;
            }

            if (!empty($item['enchere_id'])) {
                $pdo->rollBack();
                echo json_encode([
                    'success' => false,
                    'message' => 'Impossible de retirer un article remporté aux enchères depuis le panier.',
                ]);
                exit;
            }

            $is_negotiated_item = !empty($item['negociation_id']) && $item['type_vente'] === 'negociation';

            if ($is_negotiated_item) {
                $stmt = $pdo->prepare("SELECT id, statut
                                       FROM negociations
                                       WHERE id = :nid
                                         AND article_id = :aid
                                         AND acheteur_id = :uid
                                       FOR UPDATE");
                $stmt->execute([
                    ':nid' => (int)$item['negociation_id'],
                    ':aid' => $article_id,
                    ':uid' => $uid,
                ]);
                $negociation = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$negociation) {
                    throw new RuntimeException('Négociation liée introuvable.');
                }

                $stmt = $pdo->prepare('DELETE FROM panier WHERE id = :id AND utilisateur_id = :uid');
                $stmt->execute([':id' => (int)$item['id'], ':uid' => $uid]);

                if ($negociation['statut'] === 'accepte') {
                    $stmt = $pdo->prepare("UPDATE negociations
                                           SET statut = 'expire',
                                               date_resolution = NOW()
                                           WHERE id = :nid");
                    $stmt->execute([':nid' => (int)$negociation['id']]);

                    $stmt = $pdo->prepare("UPDATE articles
                                           SET statut = 'disponible'
                                           WHERE id = :aid
                                             AND type_vente = 'negociation'
                                             AND statut = 'vendu'
                                             AND NOT EXISTS (
                                                SELECT 1
                                                FROM commande_articles ca
                                                WHERE ca.article_id = :aid_check
                                             )");
                    $stmt->execute([
                        ':aid' => $article_id,
                        ':aid_check' => $article_id,
                    ]);

                    insert_notification($pdo, (int)$item['vendeur_id'], 'L\'acheteur a annulé l\'accord pour "' . $item['titre'] . '". L\'article a été remis en vente.');
                    insert_notification($pdo, $uid, 'Vous avez retiré "' . $item['titre'] . '" de votre panier. L\'article est remis en vente.');
                }

                $pdo->commit();
                echo json_encode([
                    'success' => true,
                    'relisted' => true,
                    'message' => 'Article retiré. L\'article a été remis en vente.',
                ]);
                exit;
            }

            $stmt = $pdo->prepare('DELETE FROM panier WHERE id = :id AND utilisateur_id = :uid');
            $stmt->execute([':id' => (int)$item['id'], ':uid' => $uid]);

            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(['success' => false, 'message' => 'Erreur serveur.']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action inconnue.']);
}
