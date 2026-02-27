<?php
session_start();
$base_url = '../../';
$page_title = 'Éditer un article';
require_once $base_url . 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'vendeur') {
    header('Location: ' . $base_url . 'pages/connexion.php');
    exit;
}

$article_id = (int)($_GET['id'] ?? 0);
if ($article_id <= 0) {
    header('Location: mes_articles.php?error=' . urlencode('Article invalide.'));
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT a.*,
               (SELECT COUNT(*) FROM encheres e WHERE e.article_id = a.id) AS nb_encheres
        FROM articles a
        WHERE a.id = :id AND a.vendeur_id = :uid
        LIMIT 1
    ");
    $stmt->execute([
        ':id' => $article_id,
        ':uid' => $_SESSION['user_id'],
    ]);
    $article = $stmt->fetch();
} catch (PDOException $e) {
    $article = null;
}

if (!$article) {
    header('Location: mes_articles.php?error=' . urlencode('Article introuvable ou accès refusé.'));
    exit;
}

$error = $_GET['error'] ?? '';

$is_sold = $article['statut'] === 'vendu';
$is_auction = $article['type_vente'] === 'meilleure_offre';
$auction_has_bids = $is_auction && (int)$article['nb_encheres'] > 0;
$lock_auction_values = $is_sold || $auction_has_bids;

$type_labels = [
    'achat_immediat' => 'Achat immédiat',
    'negociation' => 'Négociation',
    'meilleure_offre' => 'Meilleure offre',
];

$categories = ['Électronique', 'Vêtements', 'Maison', 'Livres', 'Sports', 'Divers'];
$gammes = ['regulier' => 'Article régulier', 'haut_de_gamme' => 'Haut de gamme', 'rare' => 'Article rare'];

$date_debut_local = $article['date_debut_enchere'] ? date('Y-m-d\TH:i', strtotime($article['date_debut_enchere'])) : '';
$date_fin_local = $article['date_fin_enchere'] ? date('Y-m-d\TH:i', strtotime($article['date_fin_enchere'])) : '';

include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';
?>

<main class="py-4">
    <div class="container" style="max-width: 920px;">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <div>
                <h1 class="h3 mb-1"><i class="bi bi-pencil-square me-2"></i>Éditer l'article</h1>
                <p class="text-muted mb-0"><?php echo htmlspecialchars($article['titre']); ?></p>
            </div>
            <div class="d-flex flex-wrap gap-2 article-edit-header-actions">
                <a href="mes_articles.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Retour</a>
                <a href="<?php echo $base_url; ?>pages/article.php?id=<?php echo (int)$article['id']; ?>" class="btn btn-outline-primary">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Voir en tant que client
                </a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center mb-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($is_sold): ?>
            <div class="alert alert-warning article-edit-lock-banner d-flex align-items-start gap-2 mb-4">
                <i class="bi bi-lock-fill mt-1"></i>
                <div>
                    <strong>Article vendu</strong><br>
                    Cet article est verrouillé et ne peut plus être modifié.
                </div>
            </div>
        <?php endif; ?>

        <?php if ($auction_has_bids): ?>
            <div class="alert alert-info article-edit-lock-banner d-flex align-items-start gap-2 mb-4">
                <i class="bi bi-info-circle-fill mt-1"></i>
                <div>
                    <strong>Valeurs d'enchère verrouillées</strong><br>
                    Cet article a déjà reçu <?php echo (int)$article['nb_encheres']; ?> enchère<?php echo (int)$article['nb_encheres'] > 1 ? 's' : ''; ?>.
                    Le prix et les dates d'enchère ne peuvent plus être modifiés.
                </div>
            </div>
        <?php endif; ?>

        <div class="card p-4 shadow-sm article-edit-card">
            <form method="POST" action="<?php echo $base_url; ?>php/article_actions.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="article_id" value="<?php echo (int)$article['id']; ?>">

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Titre de l'article</label>
                        <input type="text"
                               name="titre"
                               class="form-control <?php echo $is_sold ? 'article-edit-field-locked' : ''; ?>"
                               value="<?php echo htmlspecialchars($article['titre']); ?>"
                               required
                               <?php echo $is_sold ? 'disabled' : ''; ?>>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Prix (&euro;)</label>
                        <div class="input-group">
                            <input type="number"
                                   name="prix"
                                   class="form-control <?php echo $lock_auction_values ? 'article-edit-field-locked' : ''; ?>"
                                   step="0.01"
                                   min="0.01"
                                   value="<?php echo htmlspecialchars((string)$article['prix']); ?>"
                                   required
                                   <?php echo $lock_auction_values ? 'disabled' : ''; ?>>
                            <span class="input-group-text">&euro;</span>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description"
                                  class="form-control <?php echo $is_sold ? 'article-edit-field-locked' : ''; ?>"
                                  rows="4"
                                  required
                                  <?php echo $is_sold ? 'disabled' : ''; ?>><?php echo htmlspecialchars($article['description']); ?></textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Catégorie</label>
                        <select name="categorie" class="form-select <?php echo $is_sold ? 'article-edit-field-locked' : ''; ?>" required <?php echo $is_sold ? 'disabled' : ''; ?>>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>" <?php echo $article['categorie'] === $category ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Type de vente</label>
                        <div class="article-edit-readonly">
                            <span class="badge badge-<?php echo htmlspecialchars($article['type_vente']); ?> px-3 py-2">
                                <?php echo htmlspecialchars($type_labels[$article['type_vente']] ?? $article['type_vente']); ?>
                            </span>
                            <small class="d-block text-muted mt-2">Le type de vente est figé après création.</small>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Gamme</label>
                        <select name="gamme" class="form-select <?php echo $is_sold ? 'article-edit-field-locked' : ''; ?>" required <?php echo $is_sold ? 'disabled' : ''; ?>>
                            <?php foreach ($gammes as $value => $label): ?>
                                <option value="<?php echo htmlspecialchars($value); ?>" <?php echo $article['gamme'] === $value ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Statut vendeur</label>
                        <?php if ($is_sold): ?>
                            <div class="article-edit-readonly">
                                <span class="badge bg-secondary px-3 py-2">vendu</span>
                                <small class="d-block text-muted mt-2">Statut verrouillé car l'article est vendu.</small>
                            </div>
                        <?php else: ?>
                            <select name="statut" class="form-select" required>
                                <option value="disponible" <?php echo $article['statut'] === 'disponible' ? 'selected' : ''; ?>>disponible</option>
                                <option value="retire" <?php echo $article['statut'] === 'retire' ? 'selected' : ''; ?>>retire</option>
                            </select>
                        <?php endif; ?>
                    </div>

                    <?php if ($is_auction): ?>
                        <div class="col-12 mt-2">
                            <div class="card p-3 article-edit-auction-block">
                                <h6 class="fw-semibold mb-3"><i class="bi bi-clock-history me-1"></i>Période d'enchères</h6>
                                <p class="text-muted small mb-3">
                                    Le prix de l'article est utilisé comme prix de réserve.
                                    <?php if ($auction_has_bids): ?>
                                        Les dates et le prix sont verrouillés car des enchères existent déjà.
                                    <?php endif; ?>
                                </p>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Début des enchères</label>
                                        <input type="datetime-local"
                                               name="date_debut_enchere"
                                               class="form-control <?php echo $lock_auction_values ? 'article-edit-field-locked' : ''; ?>"
                                               value="<?php echo htmlspecialchars($date_debut_local); ?>"
                                               <?php echo $lock_auction_values ? 'disabled' : ''; ?>>
                                        <small class="text-muted">Laisser vide = commence immédiatement</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Fin des enchères</label>
                                        <input type="datetime-local"
                                               name="date_fin_enchere"
                                               class="form-control <?php echo $lock_auction_values ? 'article-edit-field-locked' : ''; ?>"
                                               value="<?php echo htmlspecialchars($date_fin_local); ?>"
                                               <?php echo $lock_auction_values ? 'disabled' : ''; ?>
                                               required>
                                        <small class="text-muted">Obligatoire pour les articles en meilleure offre</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="col-12 mt-2">
                        <div class="article-edit-image-wrap">
                            <h6 class="fw-semibold mb-3"><i class="bi bi-image me-1"></i>Image de l'article</h6>
                            <div class="row g-3 align-items-center">
                                <div class="col-md-4">
                                    <img src="<?php echo $base_url . htmlspecialchars($article['image_url'] ?? 'images/articles/placeholder.png'); ?>"
                                         class="article-edit-image-preview"
                                         alt="<?php echo htmlspecialchars($article['titre']); ?>">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label fw-semibold">Remplacer l'image</label>
                                    <input type="file" name="image" class="form-control <?php echo $is_sold ? 'article-edit-field-locked' : ''; ?>" accept="image/*" <?php echo $is_sold ? 'disabled' : ''; ?>>
                                    <small class="text-muted d-block mt-2">Laisser vide pour conserver l'image actuelle.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-4">
                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary btn-lg" <?php echo $is_sold ? 'disabled' : ''; ?>>
                        <i class="bi bi-check-lg me-2"></i>Enregistrer les modifications
                    </button>
                    <a href="mes_articles.php" class="btn btn-outline-secondary btn-lg">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
