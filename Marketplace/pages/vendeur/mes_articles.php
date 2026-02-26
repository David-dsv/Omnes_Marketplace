<?php
session_start();
$base_url = '../../';
$page_title = 'Mes articles';
require_once $base_url . 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'vendeur') {
    header('Location: ' . $base_url . 'pages/connexion.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE vendeur_id = :uid ORDER BY date_creation DESC");
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $articles = $stmt->fetchAll();
} catch (PDOException $e) {
    $articles = [];
}

$success = $_GET['success'] ?? '';

include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';
?>

<main class="py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-list-ul"></i> Mes articles</h1>
            <a href="ajouter_article.php" class="btn btn-primary"><i class="bi bi-plus"></i> Ajouter un article</a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (!empty($articles)): ?>
            <div class="row g-4">
                <?php foreach ($articles as $article): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card article-card h-100 shadow-sm">
                            <img src="<?php echo $base_url . htmlspecialchars($article['image_url'] ?? 'images/placeholder.png'); ?>"
                                 class="card-img-top" alt="<?php echo htmlspecialchars($article['titre']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($article['titre']); ?></h5>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-primary"><?php echo number_format($article['prix'], 2, ',', ' '); ?> &euro;</span>
                                    <span class="badge <?php echo $article['statut'] === 'disponible' ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo htmlspecialchars($article['statut']); ?>
                                    </span>
                                </div>
                                <p class="text-muted small mt-2 mb-0">
                                    <?php echo htmlspecialchars($article['type_vente']); ?> | <?php echo htmlspecialchars($article['gamme']); ?>
                                </p>
                            </div>
                            <div class="card-footer bg-white d-flex gap-2">
                                <a href="<?php echo $base_url; ?>pages/article.php?id=<?php echo $article['id']; ?>" class="btn btn-outline-primary btn-sm flex-grow-1">Voir</a>
                                <form method="POST" action="<?php echo $base_url; ?>php/article_actions.php" class="flex-grow-1"
                                      onsubmit="return confirm('Supprimer cet article ?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm w-100"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox display-4"></i>
                <p class="mt-3">Vous n'avez aucun article en vente.</p>
                <a href="ajouter_article.php" class="btn btn-primary">Ajouter votre premier article</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
