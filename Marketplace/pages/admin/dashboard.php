<?php
session_start();
$base_url = '../../';
$page_title = 'Administration';
require_once $base_url . 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrateur') {
    header('Location: ' . $base_url . 'pages/connexion.php');
    exit;
}

// Statistiques
try {
    $nb_users = $pdo->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn();
    $nb_vendeurs = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'vendeur'")->fetchColumn();
    $nb_articles = $pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn();
    $nb_commandes = $pdo->query("SELECT COUNT(*) FROM commandes")->fetchColumn();
} catch (PDOException $e) {
    $nb_users = $nb_vendeurs = $nb_articles = $nb_commandes = 0;
}

include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';
?>

<main class="py-4">
    <div class="container">
        <h1 class="mb-4"><i class="bi bi-gear"></i> Tableau de bord Admin</h1>

        <!-- Statistiques -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="dashboard-stat">
                    <i class="bi bi-people display-5 text-primary"></i>
                    <div class="stat-number"><?php echo $nb_users; ?></div>
                    <p class="text-muted mb-0">Utilisateurs</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-stat">
                    <i class="bi bi-shop display-5 text-success"></i>
                    <div class="stat-number"><?php echo $nb_vendeurs; ?></div>
                    <p class="text-muted mb-0">Vendeurs</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-stat">
                    <i class="bi bi-box-seam display-5 text-warning"></i>
                    <div class="stat-number"><?php echo $nb_articles; ?></div>
                    <p class="text-muted mb-0">Articles</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-stat">
                    <i class="bi bi-receipt display-5 text-danger"></i>
                    <div class="stat-number"><?php echo $nb_commandes; ?></div>
                    <p class="text-muted mb-0">Commandes</p>
                </div>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card p-4 shadow-sm h-100">
                    <h5><i class="bi bi-people"></i> Gestion des vendeurs</h5>
                    <p class="text-muted">Créer, activer ou désactiver des comptes vendeurs.</p>
                    <a href="gestion_vendeurs.php" class="btn btn-primary">Gérer les vendeurs</a>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card p-4 shadow-sm h-100">
                    <h5><i class="bi bi-box-seam"></i> Gestion des articles</h5>
                    <p class="text-muted">Modérer et gérer les articles du site.</p>
                    <a href="gestion_articles.php" class="btn btn-primary">Gérer les articles</a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
