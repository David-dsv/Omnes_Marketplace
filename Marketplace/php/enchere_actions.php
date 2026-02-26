<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$action = $_POST['action'] ?? '';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../pages/connexion.php');
    exit;
}

$uid = $_SESSION['user_id'];

switch ($action) {
    case 'bid':
        $article_id = (int)($_POST['article_id'] ?? 0);
        $montant_max = (float)($_POST['montant_max'] ?? 0);

        if ($article_id <= 0 || $montant_max <= 0) {
            header('Location: ../pages/enchere.php?article_id=' . $article_id . '&error=' . urlencode('Données invalides.'));
            exit;
        }

        try {
            // Vérifier l'article
            $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = :id AND type_vente = 'enchere' AND statut = 'disponible'");
            $stmt->execute([':id' => $article_id]);
            $article = $stmt->fetch();

            if (!$article) {
                header('Location: ../pages/tout_parcourir.php');
                exit;
            }

            // Vérifier que l'enchère n'est pas terminée
            if ($article['date_fin_enchere'] && strtotime($article['date_fin_enchere']) < time()) {
                header('Location: ../pages/enchere.php?article_id=' . $article_id . '&error=' . urlencode('Enchère terminée.'));
                exit;
            }

            // Récupérer l'enchère actuelle la plus haute
            $stmt = $pdo->prepare("SELECT MAX(montant) AS max_bid FROM encheres WHERE article_id = :id");
            $stmt->execute([':id' => $article_id]);
            $current = $stmt->fetch();
            $current_bid = $current['max_bid'] ?? $article['prix'];

            // Le montant max doit être supérieur à l'enchère actuelle
            if ($montant_max <= $current_bid) {
                header('Location: ../pages/enchere.php?article_id=' . $article_id . '&error=' . urlencode('Votre enchère doit être supérieure à l\'enchère actuelle.'));
                exit;
            }

            // Enregistrer l'enchère (auto-bid : prix actuel + 1€)
            $new_bid = $current_bid + 1;
            $stmt = $pdo->prepare("INSERT INTO encheres (article_id, acheteur_id, montant, montant_max) VALUES (:aid, :uid, :montant, :max)");
            $stmt->execute([
                ':aid'    => $article_id,
                ':uid'    => $uid,
                ':montant' => $new_bid,
                ':max'    => $montant_max,
            ]);

            header('Location: ../pages/enchere.php?article_id=' . $article_id);
            exit;
        } catch (PDOException $e) {
            header('Location: ../pages/enchere.php?article_id=' . $article_id . '&error=' . urlencode('Erreur serveur.'));
            exit;
        }
        break;

    default:
        header('Location: ../pages/tout_parcourir.php');
        exit;
}
