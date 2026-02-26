<?php
session_start();
$base_url = '';
$page_title = 'Accueil';
require_once 'config/database.php';
include 'includes/header.php';
include 'includes/navbar.php';

// Stats for counters
try {
    $nb_articles = $pdo->query("SELECT COUNT(*) FROM articles WHERE statut = 'disponible'")->fetchColumn();
    $nb_vendeurs = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'vendeur'")->fetchColumn();
    $nb_acheteurs = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'acheteur'")->fetchColumn();
    $nb_commandes = $pdo->query("SELECT COUNT(*) FROM commandes")->fetchColumn();
} catch (PDOException $e) {
    $nb_articles = 150; $nb_vendeurs = 25; $nb_acheteurs = 500; $nb_commandes = 300;
}
?>

<main>
    <!-- Hero Banner with Carousel -->
    <section class="hero-banner text-white">
        <div class="geometric-shapes">
            <span></span><span></span><span></span><span></span><span></span>
        </div>
        <div class="container text-center py-5">
            <div id="heroCarousel" class="carousel slide hero-carousel" data-bs-ride="carousel" data-bs-interval="5000">
                <div class="carousel-indicators">
                    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></button>
                    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
                    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
                </div>
                <div class="carousel-inner">
                    <div class="carousel-item active">
                        <div class="py-5">
                            <h1 class="display-4 fw-bold mb-3">Bienvenue sur Omnes MarketPlace</h1>
                            <p class="lead mb-0">La marketplace de la communauté Omnes Education</p>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <div class="py-5">
                            <h1 class="display-4 fw-bold mb-3">Ventes Flash</h1>
                            <p class="lead mb-0">Découvrez des articles rares et haut de gamme à prix exceptionnels</p>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <div class="py-5">
                            <h1 class="display-4 fw-bold mb-3">Négociations</h1>
                            <p class="lead mb-0">Négociez directement avec les vendeurs pour obtenir le meilleur prix</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hero Search Bar -->
            <div class="hero-search">
                <form action="pages/tout_parcourir.php" method="GET">
                    <div class="input-group">
                        <input type="text" name="q" class="form-control form-control-lg" placeholder="Rechercher un article, une catégorie...">
                        <button class="btn btn-primary btn-lg" type="submit">
                            <i class="bi bi-search me-1"></i> Rechercher
                        </button>
                    </div>
                </form>
            </div>

            <!-- Hero Stats -->
            <div class="hero-stats">
                <div class="stat-item">
                    <div class="stat-number counter-animate" data-target="<?php echo $nb_articles ?: 150; ?>">0</div>
                    <div class="stat-label">Articles en vente</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number counter-animate" data-target="<?php echo $nb_vendeurs ?: 25; ?>">0</div>
                    <div class="stat-label">Vendeurs actifs</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number counter-animate" data-target="<?php echo $nb_acheteurs ?: 500; ?>">0</div>
                    <div class="stat-label">Acheteurs</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Catégories -->
    <section class="py-5">
        <div class="container">
            <div class="section-title animate-on-scroll">
                <h2>Catégories</h2>
                <p>Trouvez ce dont vous avez besoin en un clic</p>
                <div class="title-line"></div>
            </div>
            <div class="row g-3 justify-content-center">
                <?php
                $categories = [
                    ['name' => 'Électronique', 'color' => '#0d6efd', 'desc' => 'PC, smartphones...'],
                    ['name' => 'Vêtements', 'color' => '#6f42c1', 'desc' => 'Mode & accessoires'],
                    ['name' => 'Maison', 'color' => '#198754', 'desc' => 'Déco & meubles'],
                    ['name' => 'Livres', 'color' => '#fd7e14', 'desc' => 'Manuels & romans'],
                    ['name' => 'Sports', 'color' => '#dc3545', 'desc' => 'Équipements sport'],
                    ['name' => 'Divers', 'color' => '#6c757d', 'desc' => 'Tout le reste'],
                ];
                foreach ($categories as $index => $cat): ?>
                    <div class="col-6 col-md-4 col-lg-2 animate-on-scroll animate-delay-<?php echo $index + 1; ?>">
                        <a href="pages/tout_parcourir.php?categorie=<?php echo urlencode($cat['name']); ?>" class="text-decoration-none">
                            <div class="card category-card text-center p-3 h-100">
                                <p class="mt-2 mb-0 fw-semibold text-dark" style="color: <?php echo $cat['color']; ?> !important;"><?php echo $cat['name']; ?></p>
                                <small class="text-muted"><?php echo $cat['desc']; ?></small>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Sélection du jour -->
    <section class="py-5 bg-white">
        <div class="container">
            <div class="section-title animate-on-scroll">
                <h2>Sélection du Jour</h2>
                <p>Les derniers articles ajoutés par nos vendeurs</p>
                <div class="title-line"></div>
            </div>
            <div class="row g-4" id="selection-jour">
                <?php
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
                    foreach ($articles_jour as $index => $article): ?>
                        <div class="col-md-6 col-lg-3 animate-on-scroll animate-delay-<?php echo $index + 1; ?>">
                            <div class="card article-card h-100 shadow-sm">
                                <div class="card-img-wrapper">
                                    <span class="badge badge-<?php echo $article['gamme']; ?> badge-gamme"><?php echo htmlspecialchars($article['gamme']); ?></span>
                                    <img src="<?php echo htmlspecialchars($article['image_url'] ?? 'images/placeholder.png'); ?>"
                                         class="card-img-top" alt="<?php echo htmlspecialchars($article['titre']); ?>">
                                    <div class="card-img-overlay-hover">
                                        <a href="pages/article.php?id=<?php echo $article['id']; ?>" class="btn btn-light btn-sm">
                                            <i class="bi bi-eye"></i> Voir l'article
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($article['titre']); ?></h5>
                                    <p class="text-muted small mb-2">
                                        <i class="bi bi-person"></i> <?php echo htmlspecialchars($article['vendeur_prenom'] . ' ' . $article['vendeur_nom']); ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="price-tag"><?php echo number_format($article['prix'], 2, ',', ' '); ?> &euro;</span>
                                        <span class="badge badge-<?php echo $article['type_vente']; ?>"><?php echo htmlspecialchars($article['type_vente']); ?></span>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <a href="pages/article.php?id=<?php echo $article['id']; ?>" class="btn btn-primary btn-sm w-100">Voir l'article</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach;
                else: ?>
                    <div class="col-12 text-center text-muted py-4">
                        <p class="display-4">Aucun article</p>
                        <p class="mt-3">Aucun article disponible pour le moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Comment ça marche -->
    <section class="py-5" style="background: #f0f4ff;">
        <div class="container">
            <div class="section-title animate-on-scroll">
                <h2>Comment ça marche ?</h2>
                <p>Achetez et vendez en 3 étapes simples</p>
                <div class="title-line"></div>
            </div>
            <div class="row g-4 justify-content-center">
                <div class="col-md-4 animate-on-scroll animate-delay-1">
                    <div class="how-it-works-step">
                        <div class="step-icon" style="background: linear-gradient(135deg, #0d6efd, #0dcaf0);">
                            <i class="bi bi-search"></i>
                            <span class="step-number">1</span>
                        </div>
                        <h5>Trouvez votre article</h5>
                        <p>Parcourez des centaines d'articles ou utilisez la recherche pour trouver exactement ce qu'il vous faut.</p>
                        <span class="how-it-works-connector d-none d-md-block"><i class="bi bi-arrow-right"></i></span>
                    </div>
                </div>
                <div class="col-md-4 animate-on-scroll animate-delay-2">
                    <div class="how-it-works-step">
                        <div class="step-icon" style="background: linear-gradient(135deg, #198754, #20c997);">
                            <i class="bi bi-chat-dots"></i>
                            <span class="step-number">2</span>
                        </div>
                        <h5>Achetez, enchérissez ou négociez</h5>
                        <p>Achat immédiat, enchères ou négociation directe — choisissez le mode qui vous convient.</p>
                        <span class="how-it-works-connector d-none d-md-block"><i class="bi bi-arrow-right"></i></span>
                    </div>
                </div>
                <div class="col-md-4 animate-on-scroll animate-delay-3">
                    <div class="how-it-works-step">
                        <div class="step-icon" style="background: linear-gradient(135deg, #fd7e14, #ffc107);">
                            <i class="bi bi-bag-check"></i>
                            <span class="step-number">3</span>
                        </div>
                        <h5>Recevez votre commande</h5>
                        <p>Payez en toute sécurité et recevez votre article. Profitez de réductions dès 100€ d'achats !</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Ventes Flash / Best-sellers -->
    <section class="py-5 bg-white">
        <div class="container">
            <div class="section-title animate-on-scroll">
                <h2>Ventes Flash &amp; Best-sellers</h2>
                <p>Articles rares et haut de gamme à ne pas manquer</p>
                <div class="title-line"></div>
            </div>
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
                    foreach ($articles_flash as $index => $article): ?>
                        <div class="col-md-6 col-lg-3 animate-on-scroll animate-delay-<?php echo $index + 1; ?>">
                            <div class="card article-card h-100 shadow-sm">
                                <div class="card-img-wrapper">
                                    <span class="badge badge-<?php echo $article['gamme']; ?> badge-gamme"><?php echo htmlspecialchars($article['gamme']); ?></span>
                                    <span class="badge bg-danger badge-flash">Flash</span>
                                    <img src="<?php echo htmlspecialchars($article['image_url'] ?? 'images/placeholder.png'); ?>"
                                         class="card-img-top" alt="<?php echo htmlspecialchars($article['titre']); ?>">
                                    <div class="card-img-overlay-hover">
                                        <a href="pages/article.php?id=<?php echo $article['id']; ?>" class="btn btn-light btn-sm">
                                            <i class="bi bi-eye"></i> Voir l'article
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($article['titre']); ?></h5>
                                    <span class="price-tag text-danger"><?php echo number_format($article['prix'], 2, ',', ' '); ?> &euro;</span>
                                </div>
                                <div class="card-footer">
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

    <!-- Compteurs animés -->
    <section class="counter-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-6 col-md-3">
                    <div class="counter-item animate-on-scroll">
                        <div class="counter-icon"><i class="bi bi-box-seam"></i></div>
                        <div class="counter-number counter-animate" data-target="<?php echo $nb_articles ?: 150; ?>">0</div>
                        <div class="counter-label">Articles en vente</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="counter-item animate-on-scroll animate-delay-1">
                        <div class="counter-icon"><img src="images/Logo_Omnes_Éducation.svg.png" alt="Omnes" class="counter-logo"></div>
                        <div class="counter-number counter-animate" data-target="<?php echo $nb_vendeurs ?: 25; ?>">0</div>
                        <div class="counter-label">Vendeurs vérifiés</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="counter-item animate-on-scroll animate-delay-2">
                        <div class="counter-icon"><i class="bi bi-people"></i></div>
                        <div class="counter-number counter-animate" data-target="<?php echo $nb_acheteurs ?: 500; ?>">0</div>
                        <div class="counter-label">Acheteurs actifs</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="counter-item animate-on-scroll animate-delay-3">
                        <div class="counter-icon"><i class="bi bi-bag-check"></i></div>
                        <div class="counter-number counter-animate" data-target="<?php echo $nb_commandes ?: 300; ?>">0</div>
                        <div class="counter-label">Commandes réalisées</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Témoignages -->
    <section class="py-5">
        <div class="container">
            <div class="section-title animate-on-scroll">
                <h2>Ce qu'en disent nos étudiants</h2>
                <p>Retours d'expérience de la communauté Omnes</p>
                <div class="title-line"></div>
            </div>
            <div class="row g-4">
                <div class="col-md-4 animate-on-scroll animate-delay-1">
                    <div class="testimonial-card h-100">
                        <p class="testimonial-text">"J'ai trouvé mon MacBook à un super prix grâce au système d'enchères. La plateforme est vraiment intuitive !"</p>
                        <div class="testimonial-author">
                            <div class="testimonial-avatar">SL</div>
                            <div>
                                <strong>Sophie L.</strong>
                                <small class="d-block text-muted">Étudiante ECE Paris</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 animate-on-scroll animate-delay-2">
                    <div class="testimonial-card h-100">
                        <p class="testimonial-text">"En tant que vendeur, la gestion de mes articles est très simple. Les négociations rendent les échanges dynamiques."</p>
                        <div class="testimonial-author">
                            <div class="testimonial-avatar">MR</div>
                            <div>
                                <strong>Maxime R.</strong>
                                <small class="d-block text-muted">Étudiant Omnes Education</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 animate-on-scroll animate-delay-3">
                    <div class="testimonial-card h-100">
                        <p class="testimonial-text">"La carte de réduction après 100€ d'achats, c'est vraiment un plus. Je recommande à tous les étudiants !"</p>
                        <div class="testimonial-author">
                            <div class="testimonial-avatar">AC</div>
                            <div>
                                <strong>Amélie C.</strong>
                                <small class="d-block text-muted">Étudiante INSEEC</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
