<?php
session_start();
$base_url = '../../';
$page_title = 'Tableau de bord vendeur';
require_once $base_url . 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'vendeur') {
    header('Location: ' . $base_url . 'pages/connexion.php');
    exit;
}

try {
    $uid = $_SESSION['user_id'];
    $nb_articles = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE vendeur_id = :uid");
    $nb_articles->execute([':uid' => $uid]);
    $nb_articles = $nb_articles->fetchColumn();

    $nb_vendus = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE vendeur_id = :uid AND statut = 'vendu'");
    $nb_vendus->execute([':uid' => $uid]);
    $nb_vendus = $nb_vendus->fetchColumn();

    $chiffre_affaires = $pdo->prepare("SELECT COALESCE(SUM(prix), 0) FROM articles WHERE vendeur_id = :uid AND statut = 'vendu'");
    $chiffre_affaires->execute([':uid' => $uid]);
    $chiffre_affaires = $chiffre_affaires->fetchColumn();

    $nb_encheres = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE vendeur_id = :uid AND type_vente = 'enchere' AND statut = 'disponible'");
    $nb_encheres->execute([':uid' => $uid]);
    $nb_encheres = $nb_encheres->fetchColumn();
} catch (PDOException $e) {
    $nb_articles = $nb_vendus = $chiffre_affaires = $nb_encheres = 0;
}

include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';
?>

<main class="py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1"><i class="bi bi-speedometer2 me-2"></i>Tableau de bord vendeur</h1>
                <p class="text-muted mb-0">Bienvenue, <?php echo htmlspecialchars($_SESSION['user_prenom']); ?> !</p>
            </div>
            <a href="ajouter_article.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nouvel article</a>
        </div>

        <!-- Stats -->
        <div class="row g-4 mb-5">
            <div class="col-md-3 col-6 animate-on-scroll">
                <div class="dashboard-stat stat-primary">
                    <div class="stat-icon" style="background: rgba(13,110,253,0.1); color: var(--omnes-primary);">
                        <i class="bi bi-box-seam-fill"></i>
                    </div>
                    <div class="stat-number"><?php echo $nb_articles; ?></div>
                    <div class="stat-label">Articles en ligne</div>
                </div>
            </div>
            <div class="col-md-3 col-6 animate-on-scroll animate-delay-1">
                <div class="dashboard-stat stat-success">
                    <div class="stat-icon" style="background: rgba(25,135,84,0.1); color: var(--omnes-success);">
                        <i class="bi bi-bag-check-fill"></i>
                    </div>
                    <div class="stat-number"><?php echo $nb_vendus; ?></div>
                    <div class="stat-label">Articles vendus</div>
                </div>
            </div>
            <div class="col-md-3 col-6 animate-on-scroll animate-delay-2">
                <div class="dashboard-stat stat-warning">
                    <div class="stat-icon" style="background: rgba(255,193,7,0.1); color: var(--omnes-warning);">
                        <i class="bi bi-currency-euro"></i>
                    </div>
                    <div class="stat-number" style="font-size: 1.5rem;"><?php echo number_format($chiffre_affaires, 0, ',', ' '); ?> &euro;</div>
                    <div class="stat-label">Chiffre d'affaires</div>
                </div>
            </div>
            <div class="col-md-3 col-6 animate-on-scroll animate-delay-3">
                <div class="dashboard-stat stat-danger">
                    <div class="stat-icon" style="background: rgba(220,53,69,0.1); color: var(--omnes-danger);">
                        <i class="bi bi-hammer"></i>
                    </div>
                    <div class="stat-number"><?php echo $nb_encheres; ?></div>
                    <div class="stat-label">Enchères actives</div>
                </div>
            </div>
        </div>

        <!-- Quick actions -->
        <h5 class="fw-bold mb-3">Actions rapides</h5>
        <div class="row g-4 animate-on-scroll">
            <div class="col-md-6">
                <div class="action-card h-100">
                    <div class="action-icon" style="background: rgba(13,110,253,0.1); color: var(--omnes-primary);">
                        <i class="bi bi-plus-circle-fill"></i>
                    </div>
                    <h5 class="fw-bold">Ajouter un article</h5>
                    <p class="text-muted">Mettez un nouvel article en vente sur la marketplace.</p>
                    <a href="ajouter_article.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Ajouter</a>
                </div>
            </div>
            <div class="col-md-6">
                <div class="action-card h-100">
                    <div class="action-icon" style="background: rgba(253,126,20,0.1); color: var(--omnes-orange);">
                        <i class="bi bi-list-ul"></i>
                    </div>
                    <h5 class="fw-bold">Mes articles</h5>
                    <p class="text-muted">Gérez vos articles en vente, modifiez ou supprimez-les.</p>
                    <a href="mes_articles.php" class="btn btn-outline-primary"><i class="bi bi-arrow-right me-1"></i>Voir</a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
