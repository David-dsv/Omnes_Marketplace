<?php
session_start();
$base_url = '';
$page_title = 'Accueil';
require_once 'config/database.php';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<main>
    <!-- Hero Banner -->
    <section class="hero-banner text-white text-center py-5">
        <div class="container">
            <h1 class="display-4 fw-bold">Bienvenue sur Omnes MarketPlace</h1>
            <p class="lead">La marketplace de la communauté Omnes Education</p>
            <a href="pages/tout_parcourir.php" class="btn btn-light btn-lg mt-3">
                <i class="bi bi-search"></i> Parcourir les articles
            </a>
        </div>
    </section>

    <!-- Catégories -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-4">Catégories</h2>
            <div class="row g-3 justify-content-center">
                <?php
                $categories = [
                    ['name' => 'Électronique', 'icon' => 'bi-laptop', 'color' => '#0d6efd'],
                    ['name' => 'Vêtements', 'icon' => 'bi-bag', 'color' => '#6f42c1'],
                    ['name' => 'Maison', 'icon' => 'bi-house-heart', 'color' => '#198754'],
                    ['name' => 'Livres', 'icon' => 'bi-book', 'color' => '#fd7e14'],
                    ['name' => 'Sports', 'icon' => 'bi-dribbble', 'color' => '#dc3545'],
                    ['name' => 'Divers', 'icon' => 'bi-three-dots', 'color' => '#6c757d'],
                ];
                foreach ($categories as $cat): ?>
                    <div class="col-6 col-md-4 col-lg-2">
                        <a href="pages/tout_parcourir.php?categorie=<?php echo urlencode($cat['name']); ?>" class="text-decoration-none">
                            <div class="card category-card text-center p-3 h-100">
                                <i class="bi <?php echo $cat['icon']; ?> display-4" style="color: <?php echo $cat['color']; ?>"></i>
                                <p class="mt-2 mb-0 fw-semibold text-dark"><?php echo $cat['name']; ?></p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Sélection du jour -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-4"><i class="bi bi-star-fill text-warning"></i> Sélection du Jour</h2>
            <div class="row g-4" id="selection-jour">
                <?php
                // Récupérer les articles mis en avant
                try {
                    $stmt = $pdo->query("SELECT a.*, u.prenom AS vendeur_prenom, u.nom AS vendeur_nom
                                         FROM articles a
                                         JOIN utilisateurs u ON a.vendeur_id = u.id
                                         WHERE a.statut = 'disponible'
                                         ORDER BY a.date_creation DESC
                                         LIMIT 4");
                    $articles_jour = $stmt->fetchAll();
                } catch (PDOException $e) {
                    $articles_jour = [];
                }

                if (!empty($articles_jour)):
                    foreach ($articles_jour as $article): ?>
                        <div class="col-md-6 col-lg-3">
                            <div class="card article-card h-100 shadow-sm">
                                <img src="<?php echo htmlspecialchars($article['image_url'] ?? 'images/placeholder.png'); ?>"
                                     class="card-img-top" alt="<?php echo htmlspecialchars($article['titre']); ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($article['titre']); ?></h5>
                                    <p class="card-text text-muted small"><?php echo htmlspecialchars($article['categorie']); ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold text-primary fs-5"><?php echo number_format($article['prix'], 2, ',', ' '); ?> &euro;</span>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($article['type_vente']); ?></span>
                                    </div>
                                </div>
                                <div class="card-footer bg-white">
                                    <a href="pages/article.php?id=<?php echo $article['id']; ?>" class="btn btn-primary btn-sm w-100">Voir l'article</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach;
                else: ?>
                    <div class="col-12 text-center text-muted">
                        <p><i class="bi bi-inbox display-4"></i></p>
                        <p>Aucun article disponible pour le moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Ventes Flash / Best-sellers -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-4"><i class="bi bi-lightning-fill text-danger"></i> Ventes Flash &amp; Best-sellers</h2>
            <div class="row g-4" id="ventes-flash">
                <?php
                try {
                    $stmt = $pdo->query("SELECT a.*, u.prenom AS vendeur_prenom, u.nom AS vendeur_nom
                                         FROM articles a
                                         JOIN utilisateurs u ON a.vendeur_id = u.id
                                         WHERE a.statut = 'disponible' AND a.gamme IN ('rare', 'haut_de_gamme')
                                         ORDER BY RAND()
                                         LIMIT 4");
                    $articles_flash = $stmt->fetchAll();
                } catch (PDOException $e) {
                    $articles_flash = [];
                }

                if (!empty($articles_flash)):
                    foreach ($articles_flash as $article): ?>
                        <div class="col-md-6 col-lg-3">
                            <div class="card article-card h-100 shadow-sm border-danger">
                                <div class="position-relative">
                                    <img src="<?php echo htmlspecialchars($article['image_url'] ?? 'images/placeholder.png'); ?>"
                                         class="card-img-top" alt="<?php echo htmlspecialchars($article['titre']); ?>">
                                    <span class="badge bg-danger position-absolute top-0 end-0 m-2">Flash</span>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($article['titre']); ?></h5>
                                    <span class="fw-bold text-danger fs-5"><?php echo number_format($article['prix'], 2, ',', ' '); ?> &euro;</span>
                                </div>
                                <div class="card-footer bg-white">
                                    <a href="pages/article.php?id=<?php echo $article['id']; ?>" class="btn btn-outline-danger btn-sm w-100">Voir l'article</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach;
                else: ?>
                    <div class="col-12 text-center text-muted">
                        <p>Pas de ventes flash en ce moment. Revenez bientôt !</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
