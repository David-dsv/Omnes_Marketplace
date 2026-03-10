<?php
session_start();
$base_url = '../../';
$page_title = 'Mes articles';
require_once $base_url . 'config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'vendeur') {
    header('Location: ' . $base_url . 'pages/connexion.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, titre, prix, type_vente, gamme, statut, image_url FROM articles WHERE vendeur_id = :uid ORDER BY date_creation DESC");
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $articles = $stmt->fetchAll();
} catch (PDOException $e) {
    $articles = [];
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
                <h1 class="h3 mb-1"><i class="bi bi-list-ul me-2"></i>Mes articles</h1>
                <p class="text-muted mb-0"><?php echo count($articles); ?> article<?php echo count($articles) > 1 ? 's' : ''; ?></p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="dashboard.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Retour</a>
                <a href="ajouter_article.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Ajouter un article</a>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success d-flex align-items-center">
                <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($articles)): ?>
            <div class="row g-4">
                <?php foreach ($articles as $index => $article): ?>
                    <div class="col-md-6 col-lg-4 animate-on-scroll animate-delay-<?php echo ($index % 3) + 1; ?>">
                        <div class="card article-card h-100 shadow-sm">
                            <div class="card-img-wrapper">
                                <span class="badge badge-<?php echo $article['gamme']; ?> badge-gamme"><?php echo htmlspecialchars($article['gamme']); ?></span>
                                <img src="<?php echo $base_url . htmlspecialchars($article['image_url'] ?? 'images/articles/placeholder.png'); ?>"
                                     class="card-img-top" alt="<?php echo htmlspecialchars($article['titre']); ?>">
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($article['titre']); ?></h5>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="price-tag"><?php echo number_format($article['prix'], 2, ',', ' '); ?> &euro;</span>
                                    <span class="badge <?php echo $article['statut'] === 'disponible' ? 'bg-success' : 'bg-secondary'; ?> rounded-pill">
                                        <?php echo htmlspecialchars($article['statut']); ?>
                                    </span>
                                </div>
                                <div class="d-flex gap-1">
                                    <span class="badge badge-<?php echo $article['type_vente']; ?> small"><?php echo htmlspecialchars($article['type_vente']); ?></span>
                                </div>
                            </div>
                            <div class="card-footer d-flex gap-2">
                                <a href="editer_article.php?id=<?php echo $article['id']; ?>" class="btn btn-outline-primary btn-sm flex-grow-1">
                                    <i class="bi bi-pencil-square"></i> Éditer
                                </a>
                                <form method="POST" action="<?php echo $base_url; ?>php/article_actions.php" class="flex-grow-1"
                                      data-confirm="Supprimer l'article '<?php echo htmlspecialchars($article['titre']); ?>' ?">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm w-100"><i class="bi bi-trash3"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                     style="width:100px;height:100px;background:rgba(var(--omnes-primary-rgb),0.1);">
                    <i class="bi bi-inbox text-primary" style="font-size:3rem;"></i>
                </div>
                <h5 class="mt-2">Aucun article en vente</h5>
                <p class="text-muted mb-4">Commencez par ajouter votre premier article !</p>
                <a href="ajouter_article.php" class="btn btn-primary btn-lg"><i class="bi bi-plus-lg me-2"></i>Ajouter un article</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
