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
    $stmt = $pdo->prepare("SELECT a.*, u.prenom AS vendeur_prenom, u.nom AS vendeur_nom, u.email AS vendeur_email
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

// Récupérer les avis
try {
    $stmt_avis = $pdo->prepare("SELECT av.*, u.prenom, u.nom FROM avis av JOIN utilisateurs u ON av.auteur_id = u.id WHERE av.article_id = :id ORDER BY av.date_creation DESC");
    $stmt_avis->execute([':id' => $article_id]);
    $avis_list = $stmt_avis->fetchAll();
} catch (PDOException $e) {
    $avis_list = [];
}

include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';
?>

<main class="py-4">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>index.php">Accueil</a></li>
                <li class="breadcrumb-item"><a href="tout_parcourir.php">Tout Parcourir</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($article['titre']); ?></li>
            </ol>
        </nav>

        <div class="row">
            <!-- Image -->
            <div class="col-md-6 mb-4">
                <img src="<?php echo $base_url . htmlspecialchars($article['image_url'] ?? 'images/placeholder.png'); ?>"
                     class="img-fluid rounded shadow" alt="<?php echo htmlspecialchars($article['titre']); ?>">
            </div>

            <!-- Détails -->
            <div class="col-md-6">
                <h1 class="h2"><?php echo htmlspecialchars($article['titre']); ?></h1>
                <p class="text-muted">
                    Publié par <strong><?php echo htmlspecialchars($article['vendeur_prenom'] . ' ' . $article['vendeur_nom']); ?></strong>
                </p>

                <div class="mb-3">
                    <span class="badge bg-info"><?php echo htmlspecialchars($article['type_vente']); ?></span>
                    <span class="badge badge-<?php echo $article['gamme']; ?>"><?php echo htmlspecialchars($article['gamme']); ?></span>
                    <span class="badge bg-secondary"><?php echo htmlspecialchars($article['categorie']); ?></span>
                </div>

                <p class="fs-3 fw-bold text-primary"><?php echo number_format($article['prix'], 2, ',', ' '); ?> &euro;</p>

                <p><?php echo nl2br(htmlspecialchars($article['description'])); ?></p>

                <!-- Actions selon type de vente -->
                <?php if ($article['type_vente'] === 'achat_immediat'): ?>
                    <button class="btn btn-primary btn-lg btn-add-cart" data-article-id="<?php echo $article['id']; ?>">
                        <i class="bi bi-cart-plus"></i> Ajouter au panier
                    </button>
                <?php elseif ($article['type_vente'] === 'negociation'): ?>
                    <a href="negociation.php?article_id=<?php echo $article['id']; ?>" class="btn btn-warning btn-lg">
                        <i class="bi bi-chat-dots"></i> Négocier
                    </a>
                <?php elseif ($article['type_vente'] === 'enchere'): ?>
                    <a href="enchere.php?article_id=<?php echo $article['id']; ?>" class="btn btn-danger btn-lg">
                        <i class="bi bi-hammer"></i> Participer à l'enchère
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Avis -->
        <section class="mt-5">
            <h3><i class="bi bi-chat-square-text"></i> Avis (<?php echo count($avis_list); ?>)</h3>
            <hr>

            <?php if (!empty($avis_list)):
                foreach ($avis_list as $avis): ?>
                    <div class="mb-3 p-3 bg-white rounded shadow-sm">
                        <div class="d-flex justify-content-between">
                            <strong><?php echo htmlspecialchars($avis['prenom'] . ' ' . $avis['nom']); ?></strong>
                            <div class="star-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi <?php echo $i <= $avis['note'] ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <p class="mb-0 mt-2"><?php echo htmlspecialchars($avis['commentaire']); ?></p>
                        <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($avis['date_creation'])); ?></small>
                    </div>
                <?php endforeach;
            else: ?>
                <p class="text-muted">Aucun avis pour cet article.</p>
            <?php endif; ?>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="card mt-3 p-3">
                    <h5>Laisser un avis</h5>
                    <form id="review-form">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                        <input type="hidden" name="rating" id="rating-value" value="5">
                        <div class="mb-3">
                            <label class="form-label">Note</label>
                            <div class="star-rating-input fs-4">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi bi-star-fill" data-value="<?php echo $i; ?>" style="cursor: pointer;"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Commentaire</label>
                            <textarea name="commentaire" class="form-control" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Envoyer</button>
                    </form>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
