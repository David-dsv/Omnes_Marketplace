<?php
session_start();
$base_url = '../';
$page_title = 'Tout Parcourir';
require_once $base_url . 'config/database.php';
include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';

// Filtres
$categorie = $_GET['categorie'] ?? '';
$type_vente = $_GET['type_vente'] ?? '';
$gamme = $_GET['gamme'] ?? '';
$recherche = $_GET['q'] ?? '';
$tri = $_GET['tri'] ?? 'recent';
$page_num = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page_num - 1) * $per_page;

// Construction de la requête
$where = ["a.statut = 'disponible'"];
$params = [];

if ($categorie) {
    $where[] = "a.categorie = :categorie";
    $params[':categorie'] = $categorie;
}
if ($type_vente) {
    $where[] = "a.type_vente = :type_vente";
    $params[':type_vente'] = $type_vente;
}
if ($gamme) {
    $where[] = "a.gamme = :gamme";
    $params[':gamme'] = $gamme;
}
if ($recherche) {
    $where[] = "(a.titre LIKE :recherche OR a.description LIKE :recherche)";
    $params[':recherche'] = '%' . $recherche . '%';
}

$orderBy = match ($tri) {
    'prix_asc'  => 'a.prix ASC',
    'prix_desc' => 'a.prix DESC',
    default     => 'a.date_creation DESC',
};

$whereClause = implode(' AND ', $where);

// Count total for pagination
try {
    $countSql = "SELECT COUNT(*) FROM articles a WHERE " . $whereClause;
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total_articles = $countStmt->fetchColumn();
    $total_pages = ceil($total_articles / $per_page);
} catch (PDOException $e) {
    $total_articles = 0;
    $total_pages = 1;
}

$sql = "SELECT a.*, u.prenom AS vendeur_prenom, u.nom AS vendeur_nom
        FROM articles a
        JOIN utilisateurs u ON a.vendeur_id = u.id
        WHERE " . $whereClause . "
        ORDER BY " . $orderBy . "
        LIMIT " . $per_page . " OFFSET " . $offset;

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $articles = $stmt->fetchAll();
} catch (PDOException $e) {
    $articles = [];
}

// Labels lisibles
$type_vente_labels = [
    'achat_immediat' => 'Achat immédiat',
    'negociation' => 'Négociation',
    'meilleure_offre' => 'Meilleure offre',
];

// Active filters for chips
$active_filters = [];
if ($categorie) $active_filters[] = ['label' => $categorie, 'param' => 'categorie'];
if ($type_vente) $active_filters[] = ['label' => $type_vente_labels[$type_vente] ?? $type_vente, 'param' => 'type_vente'];
if ($gamme) $active_filters[] = ['label' => $gamme, 'param' => 'gamme'];
if ($recherche) $active_filters[] = ['label' => '"' . $recherche . '"', 'param' => 'q'];
?>

<main class="py-4">
    <div class="container">
        <!-- Header with results count -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h3 mb-1">Tout Parcourir</h1>
                <p class="text-muted mb-0"><?php echo $total_articles; ?> article<?php echo $total_articles > 1 ? 's' : ''; ?> trouvé<?php echo $total_articles > 1 ? 's' : ''; ?></p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="view-toggle d-none d-md-flex">
                    <button data-view="grid" class="active" title="Vue grille"><i class="bi bi-grid-3x3-gap"></i></button>
                    <button data-view="list" title="Vue liste"><i class="bi bi-list-ul"></i></button>
                </div>
            </div>
        </div>

        <!-- Active filter chips -->
        <?php if (!empty($active_filters)): ?>
            <div class="filter-chips mb-3">
                <?php foreach ($active_filters as $filter):
                    $removeParams = $_GET;
                    unset($removeParams[$filter['param']]);
                    $removeUrl = '?' . http_build_query($removeParams);
                ?>
                    <span class="filter-chip">
                        <?php echo htmlspecialchars($filter['label']); ?>
                        <a href="<?php echo $removeUrl; ?>" class="remove-filter"><i class="bi bi-x"></i></a>
                    </span>
                <?php endforeach; ?>
                <a href="tout_parcourir.php" class="text-muted small ms-2">Effacer tout</a>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Filtres latéraux -->
            <div class="col-lg-3 mb-4">
                <div class="card filter-sidebar p-4">
                    <h5><i class="bi bi-funnel"></i> Filtres</h5>
                    <hr>
                    <form method="GET" action="">
                        <!-- Recherche -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Recherche</label>
                            <div class="position-relative">
                                <input type="text" name="q" class="form-control" value="<?php echo htmlspecialchars($recherche); ?>" placeholder="Mot-clé...">
                            </div>
                        </div>
                        <!-- Catégorie -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Catégorie</label>
                            <select name="categorie" class="form-select">
                                <option value="">Toutes</option>
                                <?php foreach (['Électronique', 'Vêtements', 'Maison', 'Livres', 'Sports', 'Divers'] as $cat): ?>
                                    <option value="<?php echo $cat; ?>" <?php echo $categorie === $cat ? 'selected' : ''; ?>><?php echo $cat; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- Type de vente -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Type de vente</label>
                            <select name="type_vente" class="form-select">
                                <option value="">Tous</option>
                                <option value="achat_immediat" <?php echo $type_vente === 'achat_immediat' ? 'selected' : ''; ?>>Achat immédiat</option>
                                <option value="negociation" <?php echo $type_vente === 'negociation' ? 'selected' : ''; ?>>Négociation</option>
                                <option value="meilleure_offre" <?php echo $type_vente === 'meilleure_offre' ? 'selected' : ''; ?>>Meilleure offre</option>
                            </select>
                        </div>
                        <!-- Gamme -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Gamme</label>
                            <select name="gamme" class="form-select">
                                <option value="">Toutes</option>
                                <option value="rare" <?php echo $gamme === 'rare' ? 'selected' : ''; ?>>Articles rares</option>
                                <option value="haut_de_gamme" <?php echo $gamme === 'haut_de_gamme' ? 'selected' : ''; ?>>Haut de gamme</option>
                                <option value="regulier" <?php echo $gamme === 'regulier' ? 'selected' : ''; ?>>Régulier</option>
                            </select>
                        </div>
                        <!-- Tri -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Trier par</label>
                            <select name="tri" class="form-select">
                                <option value="recent" <?php echo $tri === 'recent' ? 'selected' : ''; ?>>Plus récent</option>
                                <option value="prix_asc" <?php echo $tri === 'prix_asc' ? 'selected' : ''; ?>>Prix croissant</option>
                                <option value="prix_desc" <?php echo $tri === 'prix_desc' ? 'selected' : ''; ?>>Prix décroissant</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-1"></i> Appliquer
                        </button>
                    </form>
                </div>
            </div>

            <!-- Grille d'articles -->
            <div class="col-lg-9">
                <div class="row g-4" id="articles-container">
                    <?php if (!empty($articles)):
                        foreach ($articles as $index => $article): ?>
                            <div class="col-md-6 col-lg-4 animate-on-scroll animate-delay-<?php echo ($index % 3) + 1; ?> searchable-item">
                                <div class="card article-card h-100 shadow-sm">
                                    <div class="card-img-wrapper">
                                        <span class="badge badge-<?php echo $article['gamme']; ?> badge-gamme"><?php echo htmlspecialchars($article['gamme']); ?></span>
                                        <img src="<?php echo $base_url . htmlspecialchars($article['image_url'] ?? 'images/articles/placeholder.png'); ?>"
                                             class="card-img-top" alt="<?php echo htmlspecialchars($article['titre']); ?>">
                                        <div class="card-img-overlay-hover">
                                            <a href="article.php?id=<?php echo $article['id']; ?>" class="btn btn-light btn-sm">
                                                <i class="bi bi-eye"></i> Voir
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
                                            <span class="badge badge-<?php echo $article['type_vente']; ?>">
                                                <?php echo htmlspecialchars($type_vente_labels[$article['type_vente']] ?? $article['type_vente']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <a href="article.php?id=<?php echo $article['id']; ?>" class="btn btn-primary btn-sm w-100">Voir l'article</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach;
                    else: ?>
                        <div class="col-12 text-center text-muted py-5">
                            <i class="bi bi-search display-3"></i>
                            <h5 class="mt-3">Aucun article trouvé</h5>
                            <p>Essayez de modifier vos filtres ou votre recherche.</p>
                            <a href="tout_parcourir.php" class="btn btn-outline-primary">Réinitialiser les filtres</a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Pagination" class="mt-4">
                        <ul class="pagination justify-content-center browse-pagination">
                            <li class="page-item <?php echo $page_num <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page_num - 1])); ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            <?php for ($i = max(1, $page_num - 2); $i <= min($total_pages, $page_num + 2); $i++): ?>
                                <li class="page-item <?php echo $i === $page_num ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page_num >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page_num + 1])); ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
