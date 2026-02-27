<?php
session_start();
$base_url = '../';

if (isset($_SESSION['user_id'])) {
    header('Location: notifications.php');
    exit;
}

$page_title = 'Connexion requise - Notifications';
include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';
?>

<main class="access-required-page py-5">
    <div class="container">
        <div class="access-required-card access-required-notifications">
            <div class="access-required-visual">
                <div class="access-required-icon">
                    <i class="bi bi-bell-fill"></i>
                </div>
                <h2 class="mb-2">Centre de notifications</h2>
                <p class="mb-0 opacity-75">Recevez vos mises à jour de commandes, négociations et alertes en temps réel.</p>
            </div>
            <div class="access-required-content">
                <span class="access-required-badge">Notifications</span>
                <h1 class="h3 fw-bold mt-3 mb-3">Tu dois te connecter pour accéder à tes notifications.</h1>
                <p class="text-muted mb-4">
                    Connecte-toi pour consulter les nouveaux messages, le statut de tes achats et les retours vendeurs.
                </p>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <a href="connexion.php" class="btn btn-primary px-4">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Se connecter
                    </a>
                    <a href="inscription.php" class="btn btn-outline-primary px-4">
                        <i class="bi bi-person-plus me-1"></i> Créer un compte
                    </a>
                </div>
                <a href="../index.php" class="btn btn-link text-decoration-none px-0">
                    <i class="bi bi-arrow-left me-1"></i> Retour à l'accueil
                </a>
            </div>
        </div>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
