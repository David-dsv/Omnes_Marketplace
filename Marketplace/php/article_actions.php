<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';

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
        $description_qualite = trim($_POST['description_qualite'] ?? '');
        $description_defaut = trim($_POST['description_defaut'] ?? '');
        $description = $description_qualite . ($description_defaut ? "\n\nDéfauts : " . $description_defaut : '');
        $prix = (float)($_POST['prix'] ?? 0);
        $categorie = $_POST['categorie'] ?? '';
        $type_vente = $_POST['type_vente'] ?? '';
        $gamme = $_POST['gamme'] ?? '';
        $video_url = trim($_POST['video_url'] ?? '') ?: null;

        if (!$titre || !$description_qualite || $prix <= 0 || !$categorie || !$type_vente || !$gamme) {
            header('Location: ../pages/vendeur/ajouter_article.php?error=' . urlencode('Veuillez remplir tous les champs.'));
            exit;
        }

        if (strlen($titre) < 5 || strlen($titre) > 100) {
            header('Location: ../pages/vendeur/ajouter_article.php?error=' . urlencode('Le titre doit contenir entre 5 et 100 caractères.'));
            exit;
        }

        if (strlen($description_qualite) < 10 || strlen($description_qualite) > 2000) {
            header('Location: ../pages/vendeur/ajouter_article.php?error=' . urlencode('La description doit contenir entre 10 et 2000 caractères.'));
            exit;
        }

        if ($prix < 0.01 || $prix > 999999.99) {
            header('Location: ../pages/vendeur/ajouter_article.php?error=' . urlencode('Le prix doit être entre 0,01 € et 999 999,99 €.'));
            exit;
        }

        if ($video_url && !is_valid_url($video_url)) {
            header('Location: ../pages/vendeur/ajouter_article.php?error=' . urlencode('L\'URL vidéo est invalide.'));
            exit;
        }

        // Gestion de l'image
        $image_url = 'images/articles/placeholder.png';
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $image_errors = is_valid_image_file($_FILES['image'], 5);
            if (count($image_errors) === 0) {
                $upload_dir = __DIR__ . '/../images/articles/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
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
            $stmt = $pdo->prepare("INSERT INTO articles (vendeur_id, titre, description, description_qualite, description_defaut, prix, categorie, type_vente, gamme, image_url, video_url, statut, date_debut_enchere, date_fin_enchere)
                                   VALUES (:vid, :titre, :desc, :desc_q, :desc_d, :prix, :cat, :type, :gamme, :img, :video, 'disponible', :date_debut, :date_fin)");
            $stmt->execute([
                ':vid'        => $uid,
                ':titre'      => $titre,
                ':desc'       => $description,
                ':desc_q'     => $description_qualite,
                ':desc_d'     => $description_defaut ?: null,
                ':prix'       => $prix,
                ':cat'        => $categorie,
                ':type'       => $type_vente,
                ':gamme'      => $gamme,
                ':img'        => $image_url,
                ':video'      => $video_url,
                ':date_debut' => $date_debut_enchere,
                ':date_fin'   => $date_fin_enchere,
            ]);

            $new_article_id = (int)$pdo->lastInsertId();

            // Upload photos supplémentaires
            if (!empty($_FILES['images_supplementaires']['name'][0])) {
                $upload_dir = __DIR__ . '/../images/articles/';
                $position = 1;
                foreach ($_FILES['images_supplementaires']['tmp_name'] as $i => $tmp) {
                    if ($_FILES['images_supplementaires']['error'][$i] === UPLOAD_ERR_NO_FILE || $position > 4) continue;
                    
                    $file_info = [
                        'name' => $_FILES['images_supplementaires']['name'][$i],
                        'tmp_name' => $tmp,
                        'error' => $_FILES['images_supplementaires']['error'][$i],
                        'size' => $_FILES['images_supplementaires']['size'][$i],
                    ];
                    
                    $img_errors = is_valid_image_file($file_info, 5);
                    if (count($img_errors) > 0) continue;
                    
                    $ext = strtolower(pathinfo($_FILES['images_supplementaires']['name'][$i], PATHINFO_EXTENSION));
                    $fn = uniqid('article_extra_') . '.' . $ext;
                    if (move_uploaded_file($tmp, $upload_dir . $fn)) {
                        $stmt_img = $pdo->prepare("INSERT INTO article_images (article_id, image_url, position) VALUES (:aid, :url, :pos)");
                        $stmt_img->execute([':aid' => $new_article_id, ':url' => 'images/articles/' . $fn, ':pos' => $position]);
                        $position++;
                    }
                }
            }

            // Vérifier les alertes de recherche
            try {
                $stmt_alerts = $pdo->query("SELECT id, utilisateur_id, mot_cle, categorie, prix_max FROM alertes_recherche");
                $alerts = $stmt_alerts->fetchAll();
                $notif_stmt = $pdo->prepare("INSERT INTO notifications (utilisateur_id, message) VALUES (:uid, :msg)");
                foreach ($alerts as $alert) {
                    if ((int)$alert['utilisateur_id'] === (int)$uid) continue;
                    $match = true;
                    if ($alert['mot_cle'] && stripos($titre . ' ' . $description, $alert['mot_cle']) === false) $match = false;
                    if ($alert['categorie'] && $alert['categorie'] !== $categorie) $match = false;
                    if ($alert['prix_max'] && $prix > (float)$alert['prix_max']) $match = false;
                    if ($match) {
                        $notif_stmt->execute([
                            ':uid' => $alert['utilisateur_id'],
                            ':msg' => "Un article correspondant à votre alerte est disponible : \"$titre\" à " . number_format($prix, 2, ',', ' ') . " €",
                        ]);
                    }
                }
            } catch (PDOException $e) {
                // Silencieux — les alertes ne doivent pas bloquer la création
            }

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
        $description_qualite = trim($_POST['description_qualite'] ?? '');
        $description_defaut = trim($_POST['description_defaut'] ?? '');
        $description = $description_qualite . ($description_defaut ? "\n\nDéfauts : " . $description_defaut : '');
        $prix_post = (float)($_POST['prix'] ?? 0);
        $categorie = $_POST['categorie'] ?? '';
        $gamme = $_POST['gamme'] ?? '';
        $statut_cible = $_POST['statut'] ?? '';
        $video_url = trim($_POST['video_url'] ?? '') ?: null;

        $allowed_categories = ['Électronique', 'Vêtements', 'Maison', 'Livres', 'Sports', 'Divers'];
        $allowed_gammes = ['regulier', 'haut_de_gamme', 'rare'];
        $allowed_status = ['disponible', 'retire'];

        if (!$titre || !$description_qualite || !$categorie || !$gamme) {
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

        if ($video_url && !is_valid_url($video_url)) {
            header('Location: ../pages/vendeur/editer_article.php?id=' . $article_id . '&error=' . urlencode('L\'URL vidéo est invalide.'));
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
                $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $mime = mime_content_type($_FILES['image']['tmp_name']);
                if (!in_array($ext, $allowed, true) || !in_array($mime, $allowed_mimes, true) || $_FILES['image']['size'] > 5 * 1024 * 1024) {
                    $pdo->rollBack();
                    header('Location: ../pages/vendeur/editer_article.php?id=' . $article_id . '&error=' . urlencode('Format image non supporté ou fichier trop volumineux (max 5 Mo).'));
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
                                              description_qualite = :desc_q,
                                              description_defaut = :desc_d,
                                              prix = :prix,
                                              categorie = :categorie,
                                              gamme = :gamme,
                                              statut = :statut,
                                              image_url = :image_url,
                                              video_url = :video_url,
                                              date_debut_enchere = :date_debut,
                                              date_fin_enchere = :date_fin
                                          WHERE id = :id AND vendeur_id = :uid");
            $stmt_update->execute([
                ':titre' => $titre,
                ':description' => $description,
                ':desc_q' => $description_qualite,
                ':desc_d' => $description_defaut ?: null,
                ':prix' => $prix_final,
                ':categorie' => $categorie,
                ':gamme' => $gamme,
                ':statut' => $statut_cible,
                ':image_url' => $image_url,
                ':video_url' => $video_url,
                ':date_debut' => $date_debut_finale,
                ':date_fin' => $date_fin_finale,
                ':id' => $article_id,
                ':uid' => $uid,
            ]);

            // Upload photos supplémentaires
            if (!empty($_FILES['images_supplementaires']['name'][0])) {
                $upload_dir = __DIR__ . '/../images/articles/';
                $allowed_img = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $pos_stmt = $pdo->prepare("SELECT COALESCE(MAX(position), 0) + 1 FROM article_images WHERE article_id = :aid");
                $pos_stmt->execute([':aid' => $article_id]);
                $position = (int)$pos_stmt->fetchColumn();
                foreach ($_FILES['images_supplementaires']['tmp_name'] as $i => $tmp) {
                    if ($_FILES['images_supplementaires']['error'][$i] !== UPLOAD_ERR_OK || $position > 5) continue;
                    $ext = strtolower(pathinfo($_FILES['images_supplementaires']['name'][$i], PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowed_img, true)) continue;
                    $fn = uniqid('article_extra_') . '.' . $ext;
                    if (move_uploaded_file($tmp, $upload_dir . $fn)) {
                        $ins_img = $pdo->prepare("INSERT INTO article_images (article_id, image_url, position) VALUES (:aid, :url, :pos)");
                        $ins_img->execute([':aid' => $article_id, ':url' => 'images/articles/' . $fn, ':pos' => $position]);
                        $position++;
                    }
                }
            }

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
