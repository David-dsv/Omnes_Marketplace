<?php
session_start();
$base_url = '../../';
$page_title = 'Tableau de bord vendeur';
require_once $base_url . 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'vendeur') {
    header('Location: ' . $base_url . 'pages/connexion.php');
    exit;
}

// Statistiques vendeur
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
} catch (PDOException $e) {
    $nb_articles = $nb_vendus = $chiffre_affaires = 0;
}

include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';
?>

<main class="py-4">
    <div class="container">
        <h1 class="mb-4"><i class="bi bi-speedometer2"></i> Tableau de bord vendeur</h1>

        <!-- Statistiques -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="dashboard-stat">
                    <i class="bi bi-box-seam display-5 text-primary"></i>
                    <div class="stat-number"><?php echo $nb_articles; ?></div>
                    <p class="text-muted mb-0">Articles en ligne</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dashboard-stat">
                    <i class="bi bi-bag-check display-5 text-success"></i>
                    <div class="stat-number"><?php echo $nb_vendus; ?></div>
                    <p class="text-muted mb-0">Articles vendus</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dashboard-stat">
                    <i class="bi bi-currency-euro display-5 text-warning"></i>
                    <div class="stat-number"><?php echo number_format($chiffre_affaires, 2, ',', ' '); ?> &euro;</div>
                    <p class="text-muted mb-0">Chiffre d'affaires</p>
                </div>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card p-4 shadow-sm h-100">
                    <h5><i class="bi bi-plus-circle"></i> Ajouter un article</h5>
                    <p class="text-muted">Mettez un nouvel article en vente sur la marketplace.</p>
                    <a href="ajouter_article.php" class="btn btn-primary">Ajouter un article</a>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card p-4 shadow-sm h-100">
                    <h5><i class="bi bi-list-ul"></i> Mes articles</h5>
                    <p class="text-muted">Gérez vos articles en vente, modifiez ou supprimez-les.</p>
                    <a href="mes_articles.php" class="btn btn-outline-primary">Voir mes articles</a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
