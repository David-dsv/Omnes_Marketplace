<?php
session_start();
$base_url = '../../';
$page_title = 'Administration';
require_once $base_url . 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrateur') {
    header('Location: ' . $base_url . 'pages/connexion.php');
    exit;
}

try {
    $stats = $pdo->query("SELECT
        (SELECT COUNT(*) FROM utilisateurs) AS nb_users,
        (SELECT COUNT(*) FROM utilisateurs WHERE role = 'vendeur') AS nb_vendeurs,
        (SELECT COUNT(*) FROM articles) AS nb_articles,
        (SELECT COUNT(*) FROM commandes) AS nb_commandes,
        (SELECT COALESCE(SUM(total), 0) FROM commandes) AS chiffre_affaires")->fetch();
    $nb_users = (int)$stats['nb_users'];
    $nb_vendeurs = (int)$stats['nb_vendeurs'];
    $nb_articles = (int)$stats['nb_articles'];
    $nb_commandes = (int)$stats['nb_commandes'];
    $chiffre_affaires = (float)$stats['chiffre_affaires'];
} catch (PDOException $e) {
    $nb_users = $nb_vendeurs = $nb_articles = $nb_commandes = $chiffre_affaires = 0;
}

include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';
?>

<main class="py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1"><i class="bi bi-gear me-2"></i>Tableau de bord Admin</h1>
                <p class="text-muted mb-0">Bienvenue, <?php echo htmlspecialchars($_SESSION['user_prenom']); ?></p>
            </div>
        </div>

        <!-- Stats -->
        <div class="row g-4 mb-5">
            <div class="col-md-3 col-6 animate-on-scroll">
                <div class="dashboard-stat stat-primary">
                    <div class="stat-icon" style="background: rgba(var(--omnes-primary-rgb),0.1); color: var(--omnes-primary);">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="stat-number"><?php echo $nb_users; ?></div>
                    <div class="stat-label">Utilisateurs</div>
                </div>
            </div>
            <div class="col-md-3 col-6 animate-on-scroll animate-delay-1">
                <div class="dashboard-stat stat-success">
                    <div class="stat-icon" style="background: rgba(25,135,84,0.1); color: var(--omnes-success);">
                        <i class="bi bi-shop"></i>
                    </div>
                    <div class="stat-number"><?php echo $nb_vendeurs; ?></div>
                    <div class="stat-label">Vendeurs</div>
                </div>
            </div>
            <div class="col-md-3 col-6 animate-on-scroll animate-delay-2">
                <div class="dashboard-stat stat-warning">
                    <div class="stat-icon" style="background: rgba(255,193,7,0.1); color: var(--omnes-warning);">
                        <i class="bi bi-box-seam-fill"></i>
                    </div>
                    <div class="stat-number"><?php echo $nb_articles; ?></div>
                    <div class="stat-label">Articles</div>
                </div>
            </div>
            <div class="col-md-3 col-6 animate-on-scroll animate-delay-3">
                <div class="dashboard-stat stat-danger">
                    <div class="stat-icon" style="background: rgba(220,53,69,0.1); color: var(--omnes-danger);">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div class="stat-number"><?php echo $nb_commandes; ?></div>
                    <div class="stat-label">Commandes</div>
                </div>
            </div>
        </div>

        <!-- Extra stats row -->
        <div class="row g-4 mb-5">
            <div class="col-md-6 animate-on-scroll">
                <div class="card p-4 shadow-sm" style="border-radius: 16px;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:56px;height:56px;background:rgba(25,135,84,0.1);">
                            <i class="bi bi-currency-euro text-success fs-4"></i>
                        </div>
                        <div>
                            <p class="text-muted small mb-0">Chiffre d'affaires total</p>
                            <h4 class="fw-bold mb-0"><?php echo number_format($chiffre_affaires, 2, ',', ' '); ?> &euro;</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick actions -->
        <h5 class="fw-bold mb-3">Actions rapides</h5>
        <div class="row g-4 animate-on-scroll">
            <div class="col-md-4">
                <div class="action-card h-100">
                    <div class="action-icon" style="background: rgba(var(--omnes-primary-rgb),0.1); color: var(--omnes-primary);">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <h5 class="fw-bold">Gestion des vendeurs</h5>
                    <p class="text-muted">Créer, activer ou désactiver des comptes vendeurs.</p>
                    <a href="gestion_vendeurs.php" class="btn btn-primary"><i class="bi bi-arrow-right me-1"></i>Gérer</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="action-card h-100">
                    <div class="action-icon" style="background: rgba(253,126,20,0.1); color: var(--omnes-orange);">
                        <i class="bi bi-box-seam-fill"></i>
                    </div>
                    <h5 class="fw-bold">Gestion des articles</h5>
                    <p class="text-muted">Modérer et gérer les articles du site.</p>
                    <a href="gestion_articles.php" class="btn btn-primary"><i class="bi bi-arrow-right me-1"></i>Gérer</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="action-card h-100">
                    <div class="action-icon" style="background: rgba(13,110,253,0.1); color: #0d6efd;">
                        <i class="bi bi-hammer"></i>
                    </div>
                    <h5 class="fw-bold">Gestion des enchères</h5>
                    <p class="text-muted">Clôturez les enchères et attribuez les gagnants.</p>
                    <a href="gestion_encheres.php" class="btn btn-primary"><i class="bi bi-arrow-right me-1"></i>Gérer</a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
