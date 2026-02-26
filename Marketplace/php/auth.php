<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        handleLogin($pdo);
        break;
    case 'register':
        handleRegister($pdo);
        break;
    case 'update_profile':
        handleUpdateProfile($pdo);
        break;
    case 'logout':
        handleLogout();
        break;
    default:
        header('Location: ../pages/connexion.php');
        exit;
}

function handleLogin(PDO $pdo): void
{
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        header('Location: ../pages/connexion.php?error=' . urlencode('Veuillez remplir tous les champs.'));
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = :email AND actif = 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['mot_de_passe'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_prenom'] = $user['prenom'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_role'] = $user['role'];

            // Redirection selon le rôle
            switch ($user['role']) {
                case 'administrateur':
                    header('Location: ../pages/admin/dashboard.php');
                    break;
                case 'vendeur':
                    header('Location: ../pages/vendeur/dashboard.php');
                    break;
                default:
                    header('Location: ../pages/compte.php');
            }
            exit;
        }

        header('Location: ../pages/connexion.php?error=' . urlencode('Email ou mot de passe incorrect.'));
        exit;
    } catch (PDOException $e) {
        header('Location: ../pages/connexion.php?error=' . urlencode('Erreur serveur.'));
        exit;
    }
}

function handleRegister(PDO $pdo): void
{
    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (!$prenom || !$nom || !$email || !$password) {
        header('Location: ../pages/inscription.php?error=' . urlencode('Veuillez remplir tous les champs obligatoires.'));
        exit;
    }

    if ($password !== $password_confirm) {
        header('Location: ../pages/inscription.php?error=' . urlencode('Les mots de passe ne correspondent pas.'));
        exit;
    }

    if (strlen($password) < 6) {
        header('Location: ../pages/inscription.php?error=' . urlencode('Le mot de passe doit contenir au moins 6 caractères.'));
        exit;
    }

    try {
        // Vérifier si l'email existe déjà
        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            header('Location: ../pages/inscription.php?error=' . urlencode('Cet email est déjà utilisé.'));
            exit;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO utilisateurs (prenom, nom, email, mot_de_passe, telephone, adresse, role, actif)
                               VALUES (:prenom, :nom, :email, :mdp, :tel, :adresse, 'acheteur', 1)");
        $stmt->execute([
            ':prenom'  => $prenom,
            ':nom'     => $nom,
            ':email'   => $email,
            ':mdp'     => $hash,
            ':tel'     => $telephone,
            ':adresse' => $adresse,
        ]);

        header('Location: ../pages/connexion.php?success=' . urlencode('Compte créé avec succès ! Connectez-vous.'));
        exit;
    } catch (PDOException $e) {
        header('Location: ../pages/inscription.php?error=' . urlencode('Erreur lors de la création du compte.'));
        exit;
    }
}

function handleUpdateProfile(PDO $pdo): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../pages/connexion.php');
        exit;
    }

    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');

    try {
        $stmt = $pdo->prepare("UPDATE utilisateurs SET prenom = :prenom, nom = :nom, telephone = :tel, adresse = :adresse WHERE id = :id");
        $stmt->execute([
            ':prenom'  => $prenom,
            ':nom'     => $nom,
            ':tel'     => $telephone,
            ':adresse' => $adresse,
            ':id'      => $_SESSION['user_id'],
        ]);

        $_SESSION['user_prenom'] = $prenom;
        $_SESSION['user_nom'] = $nom;

        header('Location: ../pages/compte.php?success=' . urlencode('Profil mis à jour.'));
        exit;
    } catch (PDOException $e) {
        header('Location: ../pages/compte.php');
        exit;
    }
}

function handleLogout(): void
{
    session_destroy();
    header('Location: ../index.php');
    exit;
}
