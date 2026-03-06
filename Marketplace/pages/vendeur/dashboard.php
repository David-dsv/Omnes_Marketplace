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
    
    // Récupérer les informations du vendeur (photo et background)
    $vendor = $pdo->prepare("SELECT prenom, nom, photo_url, background_url FROM utilisateurs WHERE id = :uid");
    $vendor->execute([':uid' => $uid]);
    $vendor_data = $vendor->fetch();
    
    // Si pas de données en BDD, créer une structure par défaut
    if (!$vendor_data) {
        $vendor_data = [
            'prenom' => $_SESSION['user_prenom'] ?? 'Vendeur',
            'nom' => $_SESSION['user_nom'] ?? '',
            'photo_url' => null,
            'background_url' => null
        ];
    }
    
    $stats_stmt = $pdo->prepare("SELECT COUNT(*) AS nb_articles,
                                        SUM(CASE WHEN statut = 'vendu' THEN 1 ELSE 0 END) AS nb_vendus,
                                        COALESCE(SUM(CASE WHEN statut = 'vendu' THEN prix ELSE 0 END), 0) AS chiffre_affaires
                                 FROM articles WHERE vendeur_id = :uid");
    $stats_stmt->execute([':uid' => $uid]);
    $stats = $stats_stmt->fetch();
    $nb_articles = (int)$stats['nb_articles'];
    $nb_vendus = (int)$stats['nb_vendus'];
    $chiffre_affaires = (float)$stats['chiffre_affaires'];

} catch (PDOException $e) {
    $nb_articles = $nb_vendus = $chiffre_affaires = 0;
    $vendor_data = [
        'prenom' => $_SESSION['user_prenom'] ?? 'Vendeur',
        'nom' => $_SESSION['user_nom'] ?? '',
        'photo_url' => null,
        'background_url' => null
    ];
}

include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';
?>

<main class="py-4">
    <div class="container">
        <!-- Profil Vendeur - Mur du vendeur -->
        <div class="vendor-wall mb-5 animate-on-scroll">
            <div class="vendor-background">
                <?php if ($vendor_data['background_url']): ?>
                    <img src="<?php echo htmlspecialchars($vendor_data['background_url']); ?>" alt="Background">
                <?php endif; ?>
            </div>
            <div class="vendor-info">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="vendor-avatar">
                            <?php if ($vendor_data['photo_url']): ?>
                                <img src="<?php echo htmlspecialchars($vendor_data['photo_url']); ?>" alt="Photo profil">
                            <?php else: ?>
                                <div class="vendor-avatar-placeholder">
                                    <i class="bi bi-person"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col ms-3">
                        <h2 class="fw-bold mb-0">
                            <?php echo htmlspecialchars($vendor_data['prenom'] . ' ' . $vendor_data['nom']); ?>
                        </h2>
                        <p class="text-muted mb-3"><i class="bi bi-shop me-1"></i>Vendeur</p>
                        <a href="editer_profil.php" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil me-1"></i>Éditer mon profil
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="h5 mb-1"><i class="bi bi-speedometer2 me-2"></i>Tableau de bord</h3>
                <p class="text-muted mb-0">Suivez vos ventes et gérez vos articles</p>
            </div>
            <a href="ajouter_article.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nouvel article</a>
        </div>

        <!-- Stats -->
        <div class="row g-4 mb-5">
            <div class="col-md-3 col-6 animate-on-scroll">
                <div class="dashboard-stat stat-primary">
                    <div class="stat-icon" style="background: rgba(var(--omnes-primary-rgb),0.1); color: var(--omnes-primary);">
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
        </div>

        <!-- Quick actions -->
        <h5 class="fw-bold mb-3">Actions rapides</h5>
        <div class="row g-4 animate-on-scroll">
            <div class="col-md-4">
                <div class="action-card h-100">
                    <div class="action-icon" style="background: rgba(var(--omnes-primary-rgb),0.1); color: var(--omnes-primary);">
                        <i class="bi bi-plus-circle-fill"></i>
                    </div>
                    <h5 class="fw-bold">Ajouter un article</h5>
                    <p class="text-muted">Mettez un nouvel article en vente sur la marketplace.</p>
                    <a href="ajouter_article.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Ajouter</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="action-card h-100">
                    <div class="action-icon" style="background: rgba(253,126,20,0.1); color: var(--omnes-orange);">
                        <i class="bi bi-list-ul"></i>
                    </div>
                    <h5 class="fw-bold">Mes articles</h5>
                    <p class="text-muted">Gérez vos articles en vente, modifiez ou supprimez-les.</p>
                    <a href="mes_articles.php" class="btn btn-outline-primary"><i class="bi bi-arrow-right me-1"></i>Voir</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="action-card h-100">
                    <div class="action-icon" style="background: rgba(255,193,7,0.16); color: #b78300;">
                        <i class="bi bi-chat-dots-fill"></i>
                    </div>
                    <h5 class="fw-bold">Mes négociations</h5>
                    <p class="text-muted">Répondez aux offres et suivez vos transactions en cours.</p>
                    <a href="negociations.php" class="btn btn-outline-primary"><i class="bi bi-arrow-right me-1"></i>Voir</a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
