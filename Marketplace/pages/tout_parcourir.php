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

$sql = "SELECT a.*, u.prenom AS vendeur_prenom, u.nom AS vendeur_nom
        FROM articles a
        JOIN utilisateurs u ON a.vendeur_id = u.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY " . $orderBy;

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $articles = $stmt->fetchAll();
} catch (PDOException $e) {
    $articles = [];
}
?>

<main class="py-4">
    <div class="container">
        <h1 class="mb-4">Tout Parcourir</h1>

        <div class="row">
            <!-- Filtres latéraux -->
            <div class="col-lg-3 mb-4">
                <div class="card filter-sidebar p-3">
                    <h5>Filtres</h5>
                    <form method="GET" action="">
                        <!-- Recherche -->
                        <div class="mb-3">
                            <label class="form-label">Recherche</label>
                            <input type="text" name="q" class="form-control" value="<?php echo htmlspecialchars($recherche); ?>" placeholder="Mot-clé...">
                        </div>
                        <!-- Catégorie -->
                        <div class="mb-3">
                            <label class="form-label">Catégorie</label>
                            <select name="categorie" class="form-select">
                                <option value="">Toutes</option>
                                <?php foreach (['Électronique', 'Vêtements', 'Maison', 'Livres', 'Sports', 'Divers'] as $cat): ?>
                                    <option value="<?php echo $cat; ?>" <?php echo $categorie === $cat ? 'selected' : ''; ?>><?php echo $cat; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- Type de vente -->
                        <div class="mb-3">
                            <label class="form-label">Type de vente</label>
                            <select name="type_vente" class="form-select">
                                <option value="">Tous</option>
                                <option value="achat_immediat" <?php echo $type_vente === 'achat_immediat' ? 'selected' : ''; ?>>Achat immédiat</option>
                                <option value="negociation" <?php echo $type_vente === 'negociation' ? 'selected' : ''; ?>>Négociation</option>
                                <option value="enchere" <?php echo $type_vente === 'enchere' ? 'selected' : ''; ?>>Enchère</option>
                            </select>
                        </div>
                        <!-- Gamme -->
                        <div class="mb-3">
                            <label class="form-label">Gamme</label>
                            <select name="gamme" class="form-select">
                                <option value="">Toutes</option>
                                <option value="rare" <?php echo $gamme === 'rare' ? 'selected' : ''; ?>>Articles rares</option>
                                <option value="haut_de_gamme" <?php echo $gamme === 'haut_de_gamme' ? 'selected' : ''; ?>>Haut de gamme</option>
                                <option value="regulier" <?php echo $gamme === 'regulier' ? 'selected' : ''; ?>>Régulier</option>
                            </select>
                        </div>
                        <!-- Tri -->
                        <div class="mb-3">
                            <label class="form-label">Trier par</label>
                            <select name="tri" class="form-select">
                                <option value="recent" <?php echo $tri === 'recent' ? 'selected' : ''; ?>>Plus récent</option>
                                <option value="prix_asc" <?php echo $tri === 'prix_asc' ? 'selected' : ''; ?>>Prix croissant</option>
                                <option value="prix_desc" <?php echo $tri === 'prix_desc' ? 'selected' : ''; ?>>Prix décroissant</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Appliquer</button>
                    </form>
                </div>
            </div>

            <!-- Grille d'articles -->
            <div class="col-lg-9">
                <div class="row g-4">
                    <?php if (!empty($articles)):
                        foreach ($articles as $article): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card article-card h-100 shadow-sm">
                                    <img src="<?php echo $base_url . htmlspecialchars($article['image_url'] ?? 'images/placeholder.png'); ?>"
                                         class="card-img-top" alt="<?php echo htmlspecialchars($article['titre']); ?>">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($article['titre']); ?></h5>
                                        <p class="text-muted small mb-1">
                                            <i class="bi bi-person"></i> <?php echo htmlspecialchars($article['vendeur_prenom'] . ' ' . $article['vendeur_nom']); ?>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <span class="fw-bold text-primary"><?php echo number_format($article['prix'], 2, ',', ' '); ?> &euro;</span>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($article['type_vente']); ?></span>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-white">
                                        <a href="article.php?id=<?php echo $article['id']; ?>" class="btn btn-primary btn-sm w-100">Voir</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach;
                    else: ?>
                        <div class="col-12 text-center text-muted py-5">
                            <i class="bi bi-search display-4"></i>
                            <p class="mt-3">Aucun article trouvé.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
