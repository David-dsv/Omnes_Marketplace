<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/mail_service.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../pages/connexion.php');
    exit;
}

$action = $_POST['action'] ?? '';
$uid = (int)$_SESSION['user_id'];

if ($action !== 'process') {
    header('Location: ../pages/panier.php');
    exit;
}

$prenom = trim($_POST['prenom'] ?? '');
$nom = trim($_POST['nom'] ?? '');
$adresse = trim($_POST['adresse'] ?? '');
$adresse2 = trim($_POST['adresse2'] ?? '');
$code_postal = trim($_POST['code_postal'] ?? '');
$ville = trim($_POST['ville'] ?? '');
$pays = trim($_POST['pays'] ?? 'France');
$telephone = trim($_POST['telephone'] ?? '');
$type_carte = trim($_POST['type_carte'] ?? '');
$nom_carte = trim($_POST['nom_carte'] ?? '');
$numero_carte = trim($_POST['numero_carte'] ?? '');
$expiration = trim($_POST['expiration'] ?? '');
$cvv = trim($_POST['cvv'] ?? '');

if (!$prenom || !$nom || !$adresse || !$code_postal || !$ville || !$pays || !$telephone || !$type_carte || !$nom_carte || !$numero_carte || !$expiration || !$cvv) {
    header('Location: ../pages/paiement.php?error=' . urlencode('Veuillez remplir tous les champs obligatoires.'));
    exit;
}

if (strlen($adresse) > MAX_ADDRESS_LENGTH) {
    header('Location: ../pages/paiement.php?error=' . urlencode('L\'adresse est trop longue (maximum ' . MAX_ADDRESS_LENGTH . ' caractères).'));
    exit;
}

if (!is_valid_postal_code($code_postal)) {
    header('Location: ../pages/paiement.php?error=' . urlencode('Code postal invalide (5 chiffres requis).'));
    exit;
}

if (!is_valid_phone($telephone)) {
    header('Location: ../pages/paiement.php?error=' . urlencode('Numéro de téléphone invalide.'));
    exit;
}

$numero_carte = preg_replace('/\D+/', '', $numero_carte);
$expiration = preg_replace('/\s+/', '', $expiration);
$cvv = preg_replace('/\D+/', '', $cvv);

if (!is_valid_credit_card($numero_carte)) {
    header('Location: ../pages/paiement.php?error=' . urlencode('Numéro de carte bancaire invalide.'));
    exit;
}
if (!preg_match('/^\d{3,4}$/', $cvv)) {
    header('Location: ../pages/paiement.php?error=' . urlencode('CVV invalide (3 ou 4 chiffres requis).'));
    exit;
}
if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiration)) {
    header('Location: ../pages/paiement.php?error=' . urlencode('Date d\'expiration invalide (format MM/AA).'));
    exit;
}

$month = (int)substr($expiration, 0, 2);
$year = (int)('20' . substr($expiration, 3, 2));
$currentYear = (int)date('Y');
$currentMonth = (int)date('n');
if ($year < $currentYear || ($year === $currentYear && $month < $currentMonth)) {
    header('Location: ../pages/paiement.php?error=' . urlencode('Carte expirée.'));
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT p.article_id,
                                  p.quantite,
                                  p.prix_negocie,
                                  p.negociation_id,
                                  p.enchere_id,
                                  COALESCE(p.prix_negocie, a.prix) AS prix_final,
                                  a.titre,
                                  a.statut,
                                  a.type_vente
                           FROM panier p
                           JOIN articles a ON p.article_id = a.id
                           WHERE p.utilisateur_id = :uid");
    $stmt->execute([':uid' => $uid]);
    $items = $stmt->fetchAll();

    if (empty($items)) {
        header('Location: ../pages/panier.php');
        exit;
    }

    $total = 0.0;
    foreach ($items as $item) {
        $article_id = (int)$item['article_id'];
        $prix_final = (float)$item['prix_final'];

        if ($item['type_vente'] === 'achat_immediat') {
            if (($item['statut'] ?? '') !== 'disponible') {
                header('Location: ../pages/panier.php?error=' . urlencode('Un article de votre panier n\'est plus disponible.'));
                exit;
            }
        } elseif ($item['type_vente'] === 'negociation') {
            if (empty($item['negociation_id']) || $item['prix_negocie'] === null) {
                header('Location: ../pages/panier.php?error=' . urlencode('Panier incohérent pour un article négocié.'));
                exit;
            }

            $stmt = $pdo->prepare("SELECT prix_accorde
                                   FROM negociations
                                   WHERE id = :nid
                                     AND article_id = :aid
                                     AND acheteur_id = :uid
                                     AND statut = 'accepte'");
            $stmt->execute([
                ':nid' => (int)$item['negociation_id'],
                ':aid' => $article_id,
                ':uid' => $uid,
            ]);
            $prix_accorde = $stmt->fetchColumn();

            if ($prix_accorde === false || abs((float)$prix_accorde - $prix_final) > 0.009) {
                header('Location: ../pages/panier.php?error=' . urlencode('Validation de négociation impossible pour un article de votre panier.'));
                exit;
            }

            if (($item['statut'] ?? '') !== 'vendu') {
                header('Location: ../pages/panier.php?error=' . urlencode('Un article négocié n\'est pas verrouillé correctement.'));
                exit;
            }
        } elseif ($item['type_vente'] === 'meilleure_offre') {
            if (empty($item['enchere_id']) || $item['prix_negocie'] === null) {
                header('Location: ../pages/panier.php?error=' . urlencode('Panier incohérent pour un article aux enchères.'));
                exit;
            }

            $stmt = $pdo->prepare("SELECT prix_paye
                                   FROM encheres
                                   WHERE id = :eid
                                     AND article_id = :aid
                                     AND acheteur_id = :uid
                                     AND statut = 'gagnant'");
            $stmt->execute([
                ':eid' => (int)$item['enchere_id'],
                ':aid' => $article_id,
                ':uid' => $uid,
            ]);
            $prix_paye = $stmt->fetchColumn();

            if ($prix_paye === false || abs((float)$prix_paye - $prix_final) > 0.009) {
                header('Location: ../pages/panier.php?error=' . urlencode('Validation d\'enchère impossible pour un article de votre panier.'));
                exit;
            }

            if (($item['statut'] ?? '') !== 'vendu') {
                header('Location: ../pages/panier.php?error=' . urlencode('Un article remporté aux enchères n\'est pas verrouillé correctement.'));
                exit;
            }
        } else {
            header('Location: ../pages/panier.php?error=' . urlencode('Type d\'article non pris en charge.'));
            exit;
        }

        $total += $prix_final * (int)$item['quantite'];
    }

    if ($total <= 0) {
        header('Location: ../pages/panier.php');
        exit;
    }

    $pdo->beginTransaction();

    $titulaire = $nom_carte;
    $stmt = $pdo->prepare("SELECT id
                           FROM cartes_bancaires
                           WHERE numero_carte = :num AND expiration = :exp AND titulaire = :titulaire
                           LIMIT 1");
    $stmt->execute([
        ':num' => $numero_carte,
        ':exp' => $expiration,
        ':titulaire' => $titulaire,
    ]);
    $cardId = $stmt->fetchColumn();

    if (!$cardId) {
        $stmt = $pdo->prepare("INSERT INTO cartes_bancaires (numero_carte, expiration, cvv, titulaire)
                               VALUES (:num, :exp, :cvv, :titulaire)");
        $stmt->execute([
            ':num' => $numero_carte,
            ':exp' => $expiration,
            ':cvv' => $cvv,
            ':titulaire' => $titulaire,
        ]);
    }

    $adresse_complete = $adresse . ($adresse2 ? ", $adresse2" : '') . ", $code_postal $ville, $pays";
    $stmt = $pdo->prepare("INSERT INTO commandes (acheteur_id, total, adresse_livraison, statut)
                           VALUES (:uid, :total, :adresse, 'confirmee')");
    $stmt->execute([
        ':uid' => $uid,
        ':total' => $total,
        ':adresse' => $adresse_complete,
    ]);
    $commande_id = (int)$pdo->lastInsertId();

    $insertItem = $pdo->prepare("INSERT INTO commande_articles (commande_id, article_id, prix)
                                  VALUES (:cid, :aid, :prix)");
    $markSold = $pdo->prepare("UPDATE articles SET statut = 'vendu'
                                WHERE id = :id AND statut = 'disponible'");
    $checkSold = $pdo->prepare('SELECT statut FROM articles WHERE id = :id FOR UPDATE');

    foreach ($items as $item) {
        $article_id = (int)$item['article_id'];
        $prix_final = (float)$item['prix_final'];

        $insertItem->execute([
            ':cid' => $commande_id,
            ':aid' => $article_id,
            ':prix' => $prix_final,
        ]);

        if ($item['type_vente'] === 'achat_immediat') {
            $markSold->execute([':id' => $article_id]);
            if ($markSold->rowCount() === 0) {
                throw new RuntimeException('Article déjà vendu.');
            }
        } else {
            $checkSold->execute([':id' => $article_id]);
            $statut = $checkSold->fetchColumn();
            if ($statut !== 'vendu') {
                throw new RuntimeException('État d\'article invalide pour finalisation.');
            }
        }
    }

    $stmt = $pdo->prepare('DELETE FROM panier WHERE utilisateur_id = :uid');
    $stmt->execute([':uid' => $uid]);

    insert_notification($pdo, $uid, "Votre commande #$commande_id a été confirmée. Montant : " . number_format($total, 2, ',', ' ') . ' €');

    if ($total > 100) {
        $reduction = $total > 200 ? 20 : 10;
        $stmt = $pdo->prepare("INSERT INTO cartes_reduction (utilisateur_id, pourcentage, commande_id)
                               VALUES (:uid, :pct, :cid)");
        $stmt->execute([
            ':uid' => $uid,
            ':pct' => $reduction,
            ':cid' => $commande_id,
        ]);
    }

    $pdo->commit();

    // Envoi de l'email de confirmation via MailHog
    envoyer_email_confirmation_commande($pdo, $commande_id, $uid);

    header('Location: ../pages/compte.php?tab=orders&success=' . urlencode("Commande #$commande_id confirmée ! Merci pour votre achat."));
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header('Location: ../pages/paiement.php?error=' . urlencode('Erreur lors du paiement.'));
    exit;
}
