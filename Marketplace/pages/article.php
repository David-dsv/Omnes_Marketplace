<?php
session_start();
$base_url = '../';
$page_title = 'Détail article';
require_once $base_url . 'config/database.php';

$article_id = (int)($_GET['id'] ?? 0);
if ($article_id <= 0) {
    header('Location: tout_parcourir.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT a.*, u.prenom AS vendeur_prenom, u.nom AS vendeur_nom, u.email AS vendeur_email, u.date_creation AS vendeur_depuis
                           FROM articles a
                           JOIN utilisateurs u ON a.vendeur_id = u.id
                           WHERE a.id = :id");
    $stmt->execute([':id' => $article_id]);
    $article = $stmt->fetch();
} catch (PDOException $e) {
    $article = null;
}

if (!$article) {
    header('Location: tout_parcourir.php');
    exit;
}

$page_title = $article['titre'];

// Avis
try {
    $stmt_avis = $pdo->prepare("SELECT av.*, u.prenom, u.nom FROM avis av JOIN utilisateurs u ON av.auteur_id = u.id WHERE av.article_id = :id ORDER BY av.date_creation DESC");
    $stmt_avis->execute([':id' => $article_id]);
    $avis_list = $stmt_avis->fetchAll();
    $avg_rating = 0;
    if (count($avis_list) > 0) {
        $avg_rating = array_sum(array_column($avis_list, 'note')) / count($avis_list);
    }
} catch (PDOException $e) {
    $avis_list = [];
    $avg_rating = 0;
}

// Articles similaires
try {
    $stmt_similar = $pdo->prepare("SELECT a.*, u.prenom AS vendeur_prenom FROM articles a JOIN utilisateurs u ON a.vendeur_id = u.id WHERE a.categorie = :cat AND a.id != :id AND a.statut = 'disponible' ORDER BY RAND() LIMIT 4");
    $stmt_similar->execute([':cat' => $article['categorie'], ':id' => $article_id]);
    $similar_articles = $stmt_similar->fetchAll();
} catch (PDOException $e) {
    $similar_articles = [];
}

include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';
?>

<main class="py-4">
    <div class="container">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>index.php"><i class="bi bi-house"></i> Accueil</a></li>
                <li class="breadcrumb-item"><a href="tout_parcourir.php">Tout Parcourir</a></li>
                <li class="breadcrumb-item"><a href="tout_parcourir.php?categorie=<?php echo urlencode($article['categorie']); ?>"><?php echo htmlspecialchars($article['categorie']); ?></a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($article['titre']); ?></li>
            </ol>
        </nav>

        <div class="row g-4">
            <!-- Image -->
            <div class="col-lg-6 mb-4 animate-on-scroll">
                <div class="card overflow-hidden" style="border-radius: 16px;">
                    <div class="position-relative">
                        <img src="<?php echo $base_url . htmlspecialchars($article['image_url'] ?? 'images/articles/placeholder.png'); ?>"
                             class="img-fluid w-100" style="max-height: 500px; object-fit: cover;"
                             alt="<?php echo htmlspecialchars($article['titre']); ?>">
                        <span class="badge badge-<?php echo $article['gamme']; ?> badge-gamme" style="position:absolute;top:16px;left:16px;font-size:0.85rem;padding:0.5em 1em;">
                            <?php echo htmlspecialchars($article['gamme']); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Détails -->
            <div class="col-lg-6 animate-on-scroll animate-delay-1">
                <h1 class="h2 fw-bold mb-2"><?php echo htmlspecialchars($article['titre']); ?></h1>

                <!-- Rating summary -->
                <?php if (!empty($avis_list)): ?>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="star-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi <?php echo $i <= round($avg_rating) ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="text-muted">(<?php echo count($avis_list); ?> avis)</span>
                    </div>
                <?php endif; ?>

                <!-- Badges -->
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge badge-<?php echo $article['type_vente']; ?> px-3 py-2">
                        <?php echo $article['type_vente'] === 'negociation' ? 'Négociation' : 'Achat immédiat'; ?>
                    </span>
                    <span class="badge bg-light text-dark border px-3 py-2">
                        <i class="bi bi-tag"></i> <?php echo htmlspecialchars($article['categorie']); ?>
                    </span>
                </div>

                <!-- Price -->
                <div class="card p-3 mb-3" style="background: linear-gradient(135deg, #f0f4ff, #e8f0fe); border: none; border-radius: 12px;">
                    <p class="fs-2 fw-bold text-primary mb-0"><?php echo number_format($article['prix'], 2, ',', ' '); ?> &euro;</p>
                </div>

                <!-- Description -->
                <div class="mb-4">
                    <h6 class="fw-bold text-muted text-uppercase small">Description</h6>
                    <p class="lh-lg"><?php echo nl2br(htmlspecialchars($article['description'])); ?></p>
                </div>

                <!-- Seller info -->
                <div class="card p-3 mb-4" style="border: 1px solid #e9ecef; border-radius: 12px;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="account-avatar" style="width:50px;height:50px;border-width:2px;">
                            <div class="avatar-initials" style="font-size:1rem;">
                                <?php echo strtoupper(substr($article['vendeur_prenom'], 0, 1) . substr($article['vendeur_nom'], 0, 1)); ?>
                            </div>
                        </div>
                        <div>
                            <strong><?php echo htmlspecialchars($article['vendeur_prenom'] . ' ' . $article['vendeur_nom']); ?></strong>
                            <small class="d-block text-muted">Vendeur depuis <?php echo date('m/Y', strtotime($article['vendeur_depuis'])); ?></small>
                        </div>
                    </div>
                </div>

                <!-- Action buttons -->
                <div class="d-grid gap-2">
                    <?php if ($article['type_vente'] === 'achat_immediat'): ?>
                        <button class="btn btn-primary btn-lg btn-add-cart" data-article-id="<?php echo $article['id']; ?>">
                            Ajouter au panier
                        </button>
                    <?php elseif ($article['type_vente'] === 'negociation'): ?>
                        <a href="negociation.php?article_id=<?php echo $article['id']; ?>" class="btn btn-warning btn-lg">
                            Négocier le prix
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Avis -->
        <section class="mt-5 animate-on-scroll">
            <div class="card p-4 shadow-sm" style="border-radius: 16px;">
                <h3 class="mb-4"><i class="bi bi-chat-square-text me-2"></i>Avis (<?php echo count($avis_list); ?>)</h3>

                <?php if (!empty($avis_list)):
                    foreach ($avis_list as $avis): ?>
                        <div class="review-card mb-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                                         style="width:36px;height:36px;background:linear-gradient(135deg,var(--omnes-primary),var(--omnes-accent));font-size:0.8rem;">
                                        <?php echo strtoupper(substr($avis['prenom'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($avis['prenom'] . ' ' . $avis['nom']); ?></strong>
                                        <small class="d-block text-muted"><?php echo date('d/m/Y', strtotime($avis['date_creation'])); ?></small>
                                    </div>
                                </div>
                                <div class="star-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi <?php echo $i <= $avis['note'] ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p class="mb-0 mt-2"><?php echo htmlspecialchars($avis['commentaire']); ?></p>
                        </div>
                    <?php endforeach;
                else: ?>
                    <p class="text-muted">Aucun avis pour cet article. Soyez le premier à donner votre avis !</p>
                <?php endif; ?>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <hr>
                    <h5>Laisser un avis</h5>
                    <form id="review-form">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                        <input type="hidden" name="rating" id="rating-value" value="5">
                        <div class="mb-3">
                            <label class="form-label">Note</label>
                            <div class="star-rating-input fs-3">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi bi-star-fill" data-value="<?php echo $i; ?>" style="cursor: pointer; color: #ffc107;"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Commentaire</label>
                            <textarea name="commentaire" class="form-control" rows="3" placeholder="Partagez votre expérience..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i> Envoyer</button>
                    </form>
                <?php else: ?>
                    <hr>
                    <p class="text-muted text-center">
                        <a href="connexion.php" class="btn btn-outline-primary btn-sm">Connectez-vous</a> pour laisser un avis.
                    </p>
                <?php endif; ?>
            </div>
        </section>

        <!-- Articles similaires -->
        <?php if (!empty($similar_articles)): ?>
            <section class="mt-5 animate-on-scroll">
                <h3 class="mb-4"><i class="bi bi-grid me-2"></i>Articles similaires</h3>
                <div class="row g-4">
                    <?php foreach ($similar_articles as $sim): ?>
                        <div class="col-md-6 col-lg-3">
                            <div class="card article-card h-100 shadow-sm">
                                <div class="card-img-wrapper">
                                    <img src="<?php echo $base_url . htmlspecialchars($sim['image_url'] ?? 'images/articles/placeholder.png'); ?>"
                                         class="card-img-top" alt="<?php echo htmlspecialchars($sim['titre']); ?>">
                                    <div class="card-img-overlay-hover">
                                        <a href="article.php?id=<?php echo $sim['id']; ?>" class="btn btn-light btn-sm"><i class="bi bi-eye"></i> Voir</a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title"><?php echo htmlspecialchars($sim['titre']); ?></h6>
                                    <span class="price-tag"><?php echo number_format($sim['prix'], 2, ',', ' '); ?> &euro;</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
