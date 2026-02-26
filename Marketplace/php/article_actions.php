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
    case 'create':
        if ($_SESSION['user_role'] !== 'vendeur') {
            header('Location: ../pages/compte.php');
            exit;
        }

        $titre = trim($_POST['titre'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $prix = (float)($_POST['prix'] ?? 0);
        $categorie = $_POST['categorie'] ?? '';
        $type_vente = $_POST['type_vente'] ?? '';
        $gamme = $_POST['gamme'] ?? '';

        if (!$titre || !$description || $prix <= 0 || !$categorie || !$type_vente || !$gamme) {
            header('Location: ../pages/vendeur/ajouter_article.php?error=' . urlencode('Veuillez remplir tous les champs.'));
            exit;
        }

        // Gestion de l'image
        $image_url = 'images/placeholder.png';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../images/articles/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array(strtolower($ext), $allowed)) {
                $filename = uniqid('article_') . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                    $image_url = 'images/articles/' . $filename;
                }
            }
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO articles (vendeur_id, titre, description, prix, categorie, type_vente, gamme, image_url, statut)
                                   VALUES (:vid, :titre, :desc, :prix, :cat, :type, :gamme, :img, 'disponible')");
            $stmt->execute([
                ':vid'   => $uid,
                ':titre' => $titre,
                ':desc'  => $description,
                ':prix'  => $prix,
                ':cat'   => $categorie,
                ':type'  => $type_vente,
                ':gamme' => $gamme,
                ':img'   => $image_url,
            ]);

            header('Location: ../pages/vendeur/mes_articles.php?success=' . urlencode('Article publié avec succès !'));
            exit;
        } catch (PDOException $e) {
            header('Location: ../pages/vendeur/ajouter_article.php?error=' . urlencode('Erreur lors de la publication.'));
            exit;
        }
        break;

    case 'delete':
        $article_id = (int)($_POST['article_id'] ?? 0);

        try {
            // Vérifier que l'article appartient au vendeur (ou admin)
            if ($_SESSION['user_role'] === 'administrateur') {
                $stmt = $pdo->prepare("DELETE FROM articles WHERE id = :id");
                $stmt->execute([':id' => $article_id]);
            } else {
                $stmt = $pdo->prepare("DELETE FROM articles WHERE id = :id AND vendeur_id = :uid");
                $stmt->execute([':id' => $article_id, ':uid' => $uid]);
            }

            $redirect = $_SESSION['user_role'] === 'administrateur'
                ? '../pages/admin/gestion_articles.php?success=' . urlencode('Article supprimé.')
                : '../pages/vendeur/mes_articles.php?success=' . urlencode('Article supprimé.');
            header('Location: ' . $redirect);
            exit;
        } catch (PDOException $e) {
            header('Location: ../pages/vendeur/mes_articles.php');
            exit;
        }
        break;

    case 'create_alert':
        $mot_cle = trim($_POST['mot_cle'] ?? '');
        $categorie = $_POST['categorie'] ?? '';
        $prix_max = (float)($_POST['prix_max'] ?? 0);

        try {
            $stmt = $pdo->prepare("INSERT INTO alertes_recherche (utilisateur_id, mot_cle, categorie, prix_max)
                                   VALUES (:uid, :mot, :cat, :prix)");
            $stmt->execute([
                ':uid'  => $uid,
                ':mot'  => $mot_cle,
                ':cat'  => $categorie ?: null,
                ':prix' => $prix_max > 0 ? $prix_max : null,
            ]);
            header('Location: ../pages/notifications.php');
            exit;
        } catch (PDOException $e) {
            header('Location: ../pages/notifications.php');
            exit;
        }
        break;

    default:
        header('Location: ../pages/tout_parcourir.php');
        exit;
}
