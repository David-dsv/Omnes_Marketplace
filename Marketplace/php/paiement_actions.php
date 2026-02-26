<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../pages/connexion.php');
    exit;
}

$action = $_POST['action'] ?? '';
$uid = $_SESSION['user_id'];

if ($action === 'process') {
    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $code_postal = trim($_POST['code_postal'] ?? '');
    $ville = trim($_POST['ville'] ?? '');
    $numero_carte = trim($_POST['numero_carte'] ?? '');
    $expiration = trim($_POST['expiration'] ?? '');
    $cvv = trim($_POST['cvv'] ?? '');

    // Validation basique
    if (!$prenom || !$nom || !$adresse || !$code_postal || !$ville || !$numero_carte || !$expiration || !$cvv) {
        header('Location: ../pages/paiement.php?error=' . urlencode('Veuillez remplir tous les champs.'));
        exit;
    }

    try {
        // Vérifier que les infos de carte existent en BDD (simulation)
        $stmt = $pdo->prepare("SELECT id FROM cartes_bancaires WHERE numero_carte = :num AND expiration = :exp AND cvv = :cvv");
        $stmt->execute([':num' => str_replace(' ', '', $numero_carte), ':exp' => $expiration, ':cvv' => $cvv]);
        $carte = $stmt->fetch();

        if (!$carte) {
            header('Location: ../pages/paiement.php?error=' . urlencode('Informations de carte invalides.'));
            exit;
        }

        // Récupérer les articles du panier
        $stmt = $pdo->prepare("SELECT p.*, a.prix, a.titre FROM panier p JOIN articles a ON p.article_id = a.id WHERE p.utilisateur_id = :uid");
        $stmt->execute([':uid' => $uid]);
        $items = $stmt->fetchAll();

        if (empty($items)) {
            header('Location: ../pages/panier.php');
            exit;
        }

        $total = 0;
        foreach ($items as $item) {
            $total += $item['prix'] * $item['quantite'];
        }

        $pdo->beginTransaction();

        // Créer la commande
        $adresse_complete = "$adresse, $code_postal $ville";
        $stmt = $pdo->prepare("INSERT INTO commandes (acheteur_id, total, adresse_livraison, statut) VALUES (:uid, :total, :adresse, 'confirmee')");
        $stmt->execute([':uid' => $uid, ':total' => $total, ':adresse' => $adresse_complete]);
        $commande_id = $pdo->lastInsertId();

        // Ajouter les articles à la commande
        foreach ($items as $item) {
            $stmt = $pdo->prepare("INSERT INTO commande_articles (commande_id, article_id, prix) VALUES (:cid, :aid, :prix)");
            $stmt->execute([':cid' => $commande_id, ':aid' => $item['article_id'], ':prix' => $item['prix']]);

            // Marquer l'article comme vendu
            $stmt = $pdo->prepare("UPDATE articles SET statut = 'vendu' WHERE id = :id");
            $stmt->execute([':id' => $item['article_id']]);
        }

        // Vider le panier
        $stmt = $pdo->prepare("DELETE FROM panier WHERE utilisateur_id = :uid");
        $stmt->execute([':uid' => $uid]);

        // Créer une notification
        $stmt = $pdo->prepare("INSERT INTO notifications (utilisateur_id, message) VALUES (:uid, :msg)");
        $stmt->execute([':uid' => $uid, ':msg' => "Votre commande #$commande_id a été confirmée. Montant : " . number_format($total, 2, ',', ' ') . " €"]);

        // Carte de réduction si achat > 100€
        if ($total > 100) {
            $reduction = $total > 200 ? 20 : 10;
            $stmt = $pdo->prepare("INSERT INTO cartes_reduction (utilisateur_id, pourcentage, commande_id) VALUES (:uid, :pct, :cid)");
            $stmt->execute([':uid' => $uid, ':pct' => $reduction, ':cid' => $commande_id]);
        }

        $pdo->commit();

        header('Location: ../pages/compte.php?success=' . urlencode("Commande #$commande_id confirmée ! Merci pour votre achat."));
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        header('Location: ../pages/paiement.php?error=' . urlencode('Erreur lors du paiement.'));
        exit;
    }
} else {
    header('Location: ../pages/panier.php');
    exit;
}
