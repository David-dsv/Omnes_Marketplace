<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrateur') {
    header('Location: ../pages/connexion.php');
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create_vendor':
        $prenom = trim($_POST['prenom'] ?? '');
        $nom = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$prenom || !$nom || !$email || !$password) {
            header('Location: ../pages/admin/gestion_vendeurs.php?error=' . urlencode('Veuillez remplir tous les champs.'));
            exit;
        }

        try {
            // Vérifier si l'email existe
            $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = :email");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                header('Location: ../pages/admin/gestion_vendeurs.php?error=' . urlencode('Cet email est déjà utilisé.'));
                exit;
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO utilisateurs (prenom, nom, email, mot_de_passe, role, actif) VALUES (:prenom, :nom, :email, :mdp, 'vendeur', 1)");
            $stmt->execute([':prenom' => $prenom, ':nom' => $nom, ':email' => $email, ':mdp' => $hash]);

            header('Location: ../pages/admin/gestion_vendeurs.php?success=' . urlencode('Vendeur créé avec succès.'));
            exit;
        } catch (PDOException $e) {
            header('Location: ../pages/admin/gestion_vendeurs.php?error=' . urlencode('Erreur serveur.'));
            exit;
        }
        break;

    case 'toggle_vendor':
        $vendor_id = (int)($_POST['vendor_id'] ?? 0);
        try {
            $stmt = $pdo->prepare("UPDATE utilisateurs SET actif = NOT actif WHERE id = :id AND role = 'vendeur'");
            $stmt->execute([':id' => $vendor_id]);
            header('Location: ../pages/admin/gestion_vendeurs.php?success=' . urlencode('Statut du vendeur mis à jour.'));
            exit;
        } catch (PDOException $e) {
            header('Location: ../pages/admin/gestion_vendeurs.php?error=' . urlencode('Erreur serveur.'));
            exit;
        }
        break;

    case 'delete_vendor':
        $vendor_id = (int)($_POST['vendor_id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id = :id AND role = 'vendeur'");
            $stmt->execute([':id' => $vendor_id]);
            header('Location: ../pages/admin/gestion_vendeurs.php?success=' . urlencode('Vendeur supprimé.'));
            exit;
        } catch (PDOException $e) {
            header('Location: ../pages/admin/gestion_vendeurs.php?error=' . urlencode('Erreur : le vendeur a peut-être des articles associés.'));
            exit;
        }
        break;

    case 'delete_article':
        $article_id = (int)($_POST['article_id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM articles WHERE id = :id");
            $stmt->execute([':id' => $article_id]);
            header('Location: ../pages/admin/gestion_articles.php?success=' . urlencode('Article supprimé.'));
            exit;
        } catch (PDOException $e) {
            header('Location: ../pages/admin/gestion_articles.php');
            exit;
        }
        break;

    default:
        header('Location: ../pages/admin/dashboard.php');
        exit;
}
