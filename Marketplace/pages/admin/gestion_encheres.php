<?php
session_start();
$base_url = '../../';
$page_title = 'Gestion des enchères';
require_once $base_url . 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrateur') {
    header('Location: ' . $base_url . 'pages/connexion.php');
    exit;
}

$articles = [];
$articles_to_close = [];
$articles_ongoing = [];
$articles_completed = [];
$articles_no_bids = [];

try {
    $stmt = $pdo->prepare("
        SELECT a.*, u.prenom AS vendeur_prenom, u.nom AS vendeur_nom,
               (SELECT COUNT(*) FROM encheres e WHERE e.article_id = a.id) AS nb_encheres,
               (SELECT e2.montant_max
                FROM encheres e2
                WHERE e2.article_id = a.id
                ORDER BY e2.montant_max DESC, e2.date_creation ASC, e2.id ASC
                LIMIT 1) AS meilleure_enchere,
               (SELECT e5.prix_paye
                FROM encheres e5
                WHERE e5.article_id = a.id AND e5.statut = 'gagnant'
                LIMIT 1) AS prix_final,
               (SELECT u2.prenom FROM encheres e3
                JOIN utilisateurs u2 ON e3.acheteur_id = u2.id
                WHERE e3.article_id = a.id
                ORDER BY e3.montant_max DESC, e3.date_creation ASC, e3.id ASC
                LIMIT 1) AS gagnant_prenom,
               (SELECT u2.nom FROM encheres e3
                JOIN utilisateurs u2 ON e3.acheteur_id = u2.id
                WHERE e3.article_id = a.id
                ORDER BY e3.montant_max DESC, e3.date_creation ASC, e3.id ASC
                LIMIT 1) AS gagnant_nom
        FROM articles a
        JOIN utilisateurs u ON a.vendeur_id = u.id
        WHERE a.type_vente = 'meilleure_offre'
        ORDER BY CASE
            WHEN a.statut = 'disponible' AND a.date_fin_enchere <= NOW() THEN 0
            WHEN a.statut = 'disponible' AND a.date_fin_enchere > NOW() THEN 1
            ELSE 2
        END, a.date_fin_enchere ASC
    ");
    $stmt->execute();
    $articles = $stmt->fetchAll();

    // Categorize articles
    foreach ($articles as $a) {
        if ($a['statut'] === 'disponible' && strtotime($a['date_fin_enchere']) <= time()) {
            if ($a['nb_encheres'] > 0) {
                $articles_to_close[] = $a;
            } else {
                $articles_no_bids[] = $a;
            }
        } elseif ($a['statut'] === 'disponible' && strtotime($a['date_fin_enchere']) > time()) {
            $articles_ongoing[] = $a;
        } elseif ($a['statut'] === 'vendu') {
            $articles_completed[] = $a;
        }
    }
} catch (PDOException $e) {
    $articles = [];
    $articles_to_close = [];
    $articles_ongoing = [];
    $articles_completed = [];
    $articles_no_bids = [];
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';
?>

<main class="py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">Gestion des enchères</h1>
                <p class="text-muted mb-0"><?php echo count($articles); ?> enchère<?php echo count($articles) > 1 ? 's' : ''; ?> au total</p>
            </div>
            <a href="dashboard.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Retour</a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success d-flex align-items-center mb-4">
                <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center mb-4">
                <i class="bi bi-exclamation-circle-fill me-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Enchères à clôturer -->
        <?php if (!empty($articles_to_close)): ?>
            <div class="mb-5">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <h5 class="fw-bold mb-0">Enchères à clôturer</h5>
                    <span class="badge bg-danger rounded-pill"><?php echo count($articles_to_close); ?></span>
                </div>
                <div class="row g-4">
                    <?php foreach ($articles_to_close as $index => $a): ?>
                        <div class="col-md-6 col-lg-4 animate-on-scroll animate-delay-<?php echo ($index % 3) + 1; ?>">
                            <div class="card shadow-sm h-100 border-start border-danger border-5" style="border-radius: 12px;">
                                <div class="card-body">
                                    <!-- Article image and title -->
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <img src="<?php echo $base_url . htmlspecialchars($a['image_url'] ?? 'images/articles/placeholder.png'); ?>"
                                                 alt="<?php echo htmlspecialchars($a['titre']); ?>"
                                                 style="width: 90px; height: 90px; object-fit: cover; border-radius: 8px;">
                                            <div class="flex-grow-1">
                                                <a href="detail_enchere.php?id=<?php echo $a['id']; ?>" class="text-decoration-none fw-semibold">
                                                    <?php echo htmlspecialchars(substr($a['titre'], 0, 35)); ?><?php echo strlen($a['titre']) > 35 ? '...' : ''; ?>
                                                </a>
                                                <div class="text-muted small">Par <?php echo htmlspecialchars($a['vendeur_prenom'] . ' ' . $a['vendeur_nom']); ?></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Price info -->
                                    <div class="mb-3 pb-3 border-bottom">
                                        <small class="text-muted">Prix de réserve</small>
                                        <div class="fw-bold"><?php echo number_format($a['prix'], 2, ',', ' '); ?> &euro;</div>
                                    </div>

                                    <!-- Highest bid -->
                                    <div class="mb-3 pb-3 border-bottom">
                                        <small class="text-muted">Meilleure enchère</small>
                                        <div class="fw-bold" style="color: var(--omnes-primary);">
                                            <?php echo $a['meilleure_enchere'] ? number_format($a['meilleure_enchere'], 2, ',', ' ') . ' &euro;' : 'N/A'; ?>
                                        </div>
                                    </div>

                                    <!-- Number of bids -->
                                    <div class="mb-3 pb-3 border-bottom">
                                        <small class="text-muted">Nombre d'enchères</small>
                                        <div class="fw-semibold"><?php echo $a['nb_encheres']; ?> enchère<?php echo $a['nb_encheres'] > 1 ? 's' : ''; ?></div>
                                    </div>

                                    <!-- End date (overdue) -->
                                    <div class="mb-3">
                                        <small class="text-danger">Enchère terminée depuis</small>
                                        <div class="fw-semibold text-danger"><?php echo date('d/m/Y H:i', strtotime($a['date_fin_enchere'])); ?></div>
                                    </div>

                                    <!-- Action button -->
                                    <button class="btn btn-danger w-100 btn-resolve-auction" data-article-id="<?php echo $a['id']; ?>">
                                        <i class="bi bi-check-circle me-1"></i>Clôturer et déterminer le gagnant
                                    </button>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div id="result-<?php echo $a['id']; ?>" class="result-area"></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Enchères en cours -->
        <?php if (!empty($articles_ongoing)): ?>
            <div class="mb-5">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <h5 class="fw-bold mb-0">Enchères en cours</h5>
                    <span class="badge bg-info rounded-pill"><?php echo count($articles_ongoing); ?></span>
                </div>
                <div class="row g-4">
                    <?php foreach ($articles_ongoing as $index => $a): ?>
                        <div class="col-md-6 col-lg-4 animate-on-scroll animate-delay-<?php echo ($index % 3) + 1; ?>">
                            <div class="card shadow-sm h-100" style="border-radius: 12px;">
                                <div class="card-body">
                                    <!-- Article image and title -->
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <img src="<?php echo $base_url . htmlspecialchars($a['image_url'] ?? 'images/articles/placeholder.png'); ?>"
                                                 alt="<?php echo htmlspecialchars($a['titre']); ?>"
                                                 style="width: 90px; height: 90px; object-fit: cover; border-radius: 8px;">
                                            <div class="flex-grow-1">
                                                <a href="detail_enchere.php?id=<?php echo $a['id']; ?>" class="text-decoration-none fw-semibold">
                                                    <?php echo htmlspecialchars(substr($a['titre'], 0, 35)); ?><?php echo strlen($a['titre']) > 35 ? '...' : ''; ?>
                                                </a>
                                                <div class="text-muted small">Par <?php echo htmlspecialchars($a['vendeur_prenom'] . ' ' . $a['vendeur_nom']); ?></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Price info -->
                                    <div class="mb-3 pb-3 border-bottom">
                                        <small class="text-muted">Prix de réserve</small>
                                        <div class="fw-bold"><?php echo number_format($a['prix'], 2, ',', ' '); ?> &euro;</div>
                                    </div>

                                    <!-- Highest bid -->
                                    <div class="mb-3 pb-3 border-bottom">
                                        <small class="text-muted">Meilleure enchère</small>
                                        <div class="fw-bold" style="color: var(--omnes-primary);">
                                            <?php echo $a['meilleure_enchere'] ? number_format($a['meilleure_enchere'], 2, ',', ' ') . ' &euro;' : 'Aucune enchère'; ?>
                                        </div>
                                    </div>

                                    <!-- Number of bids -->
                                    <div class="mb-3 pb-3 border-bottom">
                                        <small class="text-muted">Nombre d'enchères</small>
                                        <div class="fw-semibold"><?php echo $a['nb_encheres']; ?> enchère<?php echo $a['nb_encheres'] > 1 ? 's' : ''; ?></div>
                                    </div>

                                    <!-- End date (countdown) -->
                                    <div class="mb-3">
                                        <small class="text-muted">Se termine le</small>
                                        <div class="fw-semibold text-success"><?php echo date('d/m/Y H:i', strtotime($a['date_fin_enchere'])); ?></div>
                                    </div>

                                    <div class="alert alert-info mb-0" style="border-radius: 8px; font-size: 0.875rem;">
                                        <i class="bi bi-info-circle me-1"></i>Enchère en cours - Clôture manuelle par un administrateur
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Enchères terminées -->
        <?php if (!empty($articles_completed)): ?>
            <div class="mb-5">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <h5 class="fw-bold mb-0">Enchères terminées</h5>
                    <span class="badge bg-success rounded-pill"><?php echo count($articles_completed); ?></span>
                </div>
                <div class="row g-4">
                    <?php foreach ($articles_completed as $index => $a): ?>
                        <div class="col-md-6 col-lg-4 animate-on-scroll animate-delay-<?php echo ($index % 3) + 1; ?>">
                            <div class="card shadow-sm h-100" style="border-radius: 12px;">
                                <div class="card-body">
                                    <!-- Article image and title -->
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <img src="<?php echo $base_url . htmlspecialchars($a['image_url'] ?? 'images/articles/placeholder.png'); ?>"
                                                 alt="<?php echo htmlspecialchars($a['titre']); ?>"
                                                 style="width: 90px; height: 90px; object-fit: cover; border-radius: 8px;">
                                            <div class="flex-grow-1">
                                                <a href="detail_enchere.php?id=<?php echo $a['id']; ?>" class="text-decoration-none fw-semibold">
                                                    <?php echo htmlspecialchars(substr($a['titre'], 0, 35)); ?><?php echo strlen($a['titre']) > 35 ? '...' : ''; ?>
                                                </a>
                                                <div class="text-muted small">Par <?php echo htmlspecialchars($a['vendeur_prenom'] . ' ' . $a['vendeur_nom']); ?></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Price info -->
                                    <div class="mb-3 pb-3 border-bottom">
                                        <small class="text-muted">Prix de réserve</small>
                                        <div class="fw-bold"><?php echo number_format($a['prix'], 2, ',', ' '); ?> &euro;</div>
                                    </div>

                                    <!-- Final price -->
                                    <div class="mb-3 pb-3 border-bottom">
                                        <small class="text-muted">Prix final de vente</small>
                                        <div class="fw-bold" style="color: var(--omnes-primary);">
                                            <?php
                                                $prix_final = $a['prix_final'] ?? null;
                                                echo $prix_final ? number_format($prix_final, 2, ',', ' ') . ' &euro;' : 'N/A';
                                            ?>
                                        </div>
                                    </div>

                                    <!-- Winner info -->
                                    <?php if ($a['gagnant_prenom'] && $a['gagnant_nom']): ?>
                                        <div class="mb-3 pb-3 border-bottom">
                                            <small class="text-muted">Gagnant</small>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($a['gagnant_prenom'] . ' ' . $a['gagnant_nom']); ?></div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Status -->
                                    <div>
                                        <span class="badge bg-success rounded-pill">Vendu</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Enchères sans offre -->
        <?php if (!empty($articles_no_bids)): ?>
            <div class="mb-5">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <h5 class="fw-bold mb-0">Enchères sans offre</h5>
                    <span class="badge bg-secondary rounded-pill"><?php echo count($articles_no_bids); ?></span>
                </div>
                <div class="row g-4">
                    <?php foreach ($articles_no_bids as $index => $a): ?>
                        <div class="col-md-6 col-lg-4 animate-on-scroll animate-delay-<?php echo ($index % 3) + 1; ?>">
                            <div class="card shadow-sm h-100 opacity-75" style="border-radius: 12px;">
                                <div class="card-body">
                                    <!-- Article image and title -->
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <img src="<?php echo $base_url . htmlspecialchars($a['image_url'] ?? 'images/articles/placeholder.png'); ?>"
                                                 alt="<?php echo htmlspecialchars($a['titre']); ?>"
                                                 style="width: 90px; height: 90px; object-fit: cover; border-radius: 8px;">
                                            <div class="flex-grow-1">
                                                <a href="detail_enchere.php?id=<?php echo $a['id']; ?>" class="text-decoration-none fw-semibold">
                                                    <?php echo htmlspecialchars(substr($a['titre'], 0, 35)); ?><?php echo strlen($a['titre']) > 35 ? '...' : ''; ?>
                                                </a>
                                                <div class="text-muted small">Par <?php echo htmlspecialchars($a['vendeur_prenom'] . ' ' . $a['vendeur_nom']); ?></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Price info -->
                                    <div class="mb-3 pb-3 border-bottom">
                                        <small class="text-muted">Prix de réserve</small>
                                        <div class="fw-bold"><?php echo number_format($a['prix'], 2, ',', ' '); ?> &euro;</div>
                                    </div>

                                    <!-- End date -->
                                    <div class="mb-3">
                                        <small class="text-muted">Enchère terminée le</small>
                                        <div class="fw-semibold text-secondary"><?php echo date('d/m/Y H:i', strtotime($a['date_fin_enchere'])); ?></div>
                                    </div>

                                    <div class="alert alert-secondary mb-0" style="border-radius: 8px; font-size: 0.875rem;">
                                        <i class="bi bi-exclamation-triangle me-1"></i>Aucune enchère reçue
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Empty state -->
        <?php if (empty($articles)): ?>
            <div class="text-center py-5">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                     style="width:100px;height:100px;background:rgba(var(--omnes-primary-rgb),0.1);">
                    <i class="bi bi-inbox text-primary" style="font-size:3rem;"></i>
                </div>
                <h5 class="mt-2">Aucune enchère</h5>
                <p class="text-muted mb-4">Il n'y a pas d'articles avec enchères (meilleure offre) pour le moment.</p>
                <a href="dashboard.php" class="btn btn-primary"><i class="bi bi-arrow-left me-2"></i>Retour au tableau de bord</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-resolve-auction').forEach(function(button) {
        button.addEventListener('click', function() {
            var articleId = this.getAttribute('data-article-id');
            if (window.resolveAuction) {
                window.resolveAuction(articleId);
            }
        });
    });
});
</script>

<?php include $base_url . 'includes/footer.php'; ?>
