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
        $image_url = 'images/articles/placeholder.png';
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

        // Validation spécifique meilleure_offre
        $date_debut_enchere = null;
        $date_fin_enchere = null;

        if ($type_vente === 'meilleure_offre') {
            $date_fin = trim($_POST['date_fin_enchere'] ?? '');
            $date_debut = trim($_POST['date_debut_enchere'] ?? '');

            if (!$date_fin) {
                header('Location: ../pages/vendeur/ajouter_article.php?error=' . urlencode('La date de fin d\'enchères est obligatoire.'));
                exit;
            }

            $date_fin_ts = strtotime($date_fin);
            if ($date_fin_ts === false || $date_fin_ts <= time()) {
                header('Location: ../pages/vendeur/ajouter_article.php?error=' . urlencode('La date de fin doit être dans le futur.'));
                exit;
            }

            $date_debut_ts = $date_debut ? strtotime($date_debut) : time();
            if ($date_debut && $date_debut_ts === false) {
                header('Location: ../pages/vendeur/ajouter_article.php?error=' . urlencode('La date de début d\'enchères est invalide.'));
                exit;
            }
            if ($date_debut_ts >= $date_fin_ts) {
                header('Location: ../pages/vendeur/ajouter_article.php?error=' . urlencode('La date de fin doit être postérieure à la date de début.'));
                exit;
            }

            $date_fin_enchere = date('Y-m-d H:i:s', $date_fin_ts);
            $date_debut_enchere = date('Y-m-d H:i:s', $date_debut_ts);
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO articles (vendeur_id, titre, description, prix, categorie, type_vente, gamme, image_url, statut, date_debut_enchere, date_fin_enchere)
                                   VALUES (:vid, :titre, :desc, :prix, :cat, :type, :gamme, :img, 'disponible', :date_debut, :date_fin)");
            $stmt->execute([
                ':vid'        => $uid,
                ':titre'      => $titre,
                ':desc'       => $description,
                ':prix'       => $prix,
                ':cat'        => $categorie,
                ':type'       => $type_vente,
                ':gamme'      => $gamme,
                ':img'        => $image_url,
                ':date_debut' => $date_debut_enchere,
                ':date_fin'   => $date_fin_enchere,
            ]);

            header('Location: ../pages/vendeur/mes_articles.php?success=' . urlencode('Article publié avec succès !'));
            exit;
        } catch (PDOException $e) {
            header('Location: ../pages/vendeur/ajouter_article.php?error=' . urlencode('Erreur lors de la publication.'));
            exit;
        }
        break;

    case 'update':
        if ($_SESSION['user_role'] !== 'vendeur') {
            header('Location: ../pages/compte.php');
            exit;
        }

        $article_id = (int)($_POST['article_id'] ?? 0);
        if ($article_id <= 0) {
            header('Location: ../pages/vendeur/mes_articles.php?error=' . urlencode('Article invalide.'));
            exit;
        }

        $titre = trim($_POST['titre'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $prix_post = (float)($_POST['prix'] ?? 0);
        $categorie = $_POST['categorie'] ?? '';
        $gamme = $_POST['gamme'] ?? '';
        $statut_cible = $_POST['statut'] ?? '';

        $allowed_categories = ['Électronique', 'Vêtements', 'Maison', 'Livres', 'Sports', 'Divers'];
        $allowed_gammes = ['regulier', 'haut_de_gamme', 'rare'];
        $allowed_status = ['disponible', 'retire'];

        if (!$titre || !$description || !$categorie || !$gamme) {
            header('Location: ../pages/vendeur/editer_article.php?id=' . $article_id . '&error=' . urlencode('Veuillez remplir tous les champs obligatoires.'));
            exit;
        }

        if (!in_array($categorie, $allowed_categories, true) || !in_array($gamme, $allowed_gammes, true)) {
            header('Location: ../pages/vendeur/editer_article.php?id=' . $article_id . '&error=' . urlencode('Catégorie ou gamme invalide.'));
            exit;
        }

        if (!in_array($statut_cible, $allowed_status, true)) {
            header('Location: ../pages/vendeur/editer_article.php?id=' . $article_id . '&error=' . urlencode('Statut invalide. Seuls "disponible" et "retire" sont autorisés.'));
            exit;
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT id, vendeur_id, type_vente, statut, prix, image_url, date_debut_enchere, date_fin_enchere
                                   FROM articles
                                   WHERE id = :id
                                   FOR UPDATE");
            $stmt->execute([':id' => $article_id]);
            $article = $stmt->fetch();

            if (!$article || (int)$article['vendeur_id'] !== (int)$uid) {
                $pdo->rollBack();
                header('Location: ../pages/vendeur/mes_articles.php?error=' . urlencode('Article introuvable ou accès refusé.'));
                exit;
            }

            if ($article['statut'] === 'vendu') {
                $pdo->rollBack();
                header('Location: ../pages/vendeur/editer_article.php?id=' . $article_id . '&error=' . urlencode('Un article vendu ne peut pas être modifié.'));
                exit;
            }

            $type_vente = $article['type_vente']; // Ne jamais faire confiance au client pour ce champ.
            $prix_final = (float)$article['prix'];
            $date_debut_finale = $article['date_debut_enchere'];
            $date_fin_finale = $article['date_fin_enchere'];

            if ($type_vente === 'meilleure_offre') {
                $stmt_bid_count = $pdo->prepare("SELECT COUNT(*) FROM encheres WHERE article_id = :id");
                $stmt_bid_count->execute([':id' => $article_id]);
                $has_bids = (int)$stmt_bid_count->fetchColumn() > 0;

                if (!$has_bids) {
                    if ($prix_post <= 0) {
                        $pdo->rollBack();
                        header('Location: ../pages/vendeur/editer_article.php?id=' . $article_id . '&error=' . urlencode('Le prix doit être strictement supérieur à 0.'));
                        exit;
                    }

                    $date_fin_input = trim($_POST['date_fin_enchere'] ?? '');
                    $date_debut_input = trim($_POST['date_debut_enchere'] ?? '');

                    if ($date_fin_input === '') {
                        $pdo->rollBack();
                        header('Location: ../pages/vendeur/editer_article.php?id=' . $article_id . '&error=' . urlencode('La date de fin d\'enchères est obligatoire.'));
                        exit;
                    }

                    $date_fin_ts = strtotime($date_fin_input);
                    if ($date_fin_ts === false || $date_fin_ts <= time()) {
                        $pdo->rollBack();
                        header('Location: ../pages/vendeur/editer_article.php?id=' . $article_id . '&error=' . urlencode('La date de fin doit être dans le futur.'));
                        exit;
                    }

                    $date_debut_ts = $date_debut_input !== '' ? strtotime($date_debut_input) : time();
                    if ($date_debut_ts === false) {
                        $pdo->rollBack();
                        header('Location: ../pages/vendeur/editer_article.php?id=' . $article_id . '&error=' . urlencode('La date de début d\'enchères est invalide.'));
                        exit;
                    }

                    if ($date_debut_ts >= $date_fin_ts) {
                        $pdo->rollBack();
                        header('Location: ../pages/vendeur/editer_article.php?id=' . $article_id . '&error=' . urlencode('La date de fin doit être postérieure à la date de début.'));
                        exit;
                    }

                    $prix_final = $prix_post;
                    $date_debut_finale = date('Y-m-d H:i:s', $date_debut_ts);
                    $date_fin_finale = date('Y-m-d H:i:s', $date_fin_ts);
                }
            } else {
                if ($prix_post <= 0) {
                    $pdo->rollBack();
                    header('Location: ../pages/vendeur/editer_article.php?id=' . $article_id . '&error=' . urlencode('Le prix doit être strictement supérieur à 0.'));
                    exit;
                }

                $prix_final = $prix_post;
                $date_debut_finale = null;
                $date_fin_finale = null;
            }

            $image_url = $article['image_url'] ?: 'images/articles/placeholder.png';
            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                    $pdo->rollBack();
                    header('Location: ../pages/vendeur/editer_article.php?id=' . $article_id . '&error=' . urlencode('Erreur lors de l\'upload de l\'image.'));
                    exit;
                }

                $upload_dir = __DIR__ . '/../images/articles/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array($ext, $allowed, true)) {
                    $pdo->rollBack();
                    header('Location: ../pages/vendeur/editer_article.php?id=' . $article_id . '&error=' . urlencode('Format image non supporté.'));
                    exit;
                }

                $filename = uniqid('article_') . '.' . $ext;
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                    $pdo->rollBack();
                    header('Location: ../pages/vendeur/editer_article.php?id=' . $article_id . '&error=' . urlencode('Impossible d\'enregistrer l\'image.'));
                    exit;
                }

                $image_url = 'images/articles/' . $filename;
            }

            $stmt_update = $pdo->prepare("UPDATE articles
                                          SET titre = :titre,
                                              description = :description,
                                              prix = :prix,
                                              categorie = :categorie,
                                              gamme = :gamme,
                                              statut = :statut,
                                              image_url = :image_url,
                                              date_debut_enchere = :date_debut,
                                              date_fin_enchere = :date_fin
                                          WHERE id = :id AND vendeur_id = :uid");
            $stmt_update->execute([
                ':titre' => $titre,
                ':description' => $description,
                ':prix' => $prix_final,
                ':categorie' => $categorie,
                ':gamme' => $gamme,
                ':statut' => $statut_cible,
                ':image_url' => $image_url,
                ':date_debut' => $date_debut_finale,
                ':date_fin' => $date_fin_finale,
                ':id' => $article_id,
                ':uid' => $uid,
            ]);

            $pdo->commit();
            header('Location: ../pages/vendeur/mes_articles.php?success=' . urlencode('Article mis à jour avec succès.'));
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            header('Location: ../pages/vendeur/editer_article.php?id=' . $article_id . '&error=' . urlencode('Erreur lors de la mise à jour de l\'article.'));
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
