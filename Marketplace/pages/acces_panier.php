<?php
session_start();
$base_url = '../';

if (isset($_SESSION['user_id'])) {
    header('Location: panier.php');
    exit;
}

$page_title = 'Connexion requise - Panier';
include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';
?>

<main class="access-required-page py-5">
    <div class="container">
        <div class="access-required-card access-required-panier">
            <div class="access-required-visual">
                <div class="access-required-icon">
                    <i class="bi bi-cart3"></i>
                </div>
                <h2 class="mb-2">Panier sécurisé</h2>
                <p class="mb-0 opacity-75">Conserve tes articles et passe commande en quelques clics.</p>
            </div>
            <div class="access-required-content">
                <span class="access-required-badge">Panier</span>
                <h1 class="h3 fw-bold mt-3 mb-3">Tu dois te connecter pour accéder à ton panier.</h1>
                <p class="text-muted mb-4">
                    Connecte-toi pour ajouter des produits, suivre ton total et finaliser ton paiement en sécurité.
                </p>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <a href="connexion.php" class="btn btn-primary px-4">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Se connecter
                    </a>
                    <a href="tout_parcourir.php" class="btn btn-outline-primary px-4">
                        <i class="bi bi-search me-1"></i> Parcourir les articles
                    </a>
                </div>
                <a href="inscription.php" class="btn btn-link text-decoration-none px-0">
                    <i class="bi bi-person-plus me-1"></i> Pas encore de compte ? Inscris-toi
                </a>
            </div>
        </div>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
