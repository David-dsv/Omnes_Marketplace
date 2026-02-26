<?php
session_start();
$base_url = '../../';
$page_title = 'Gestion des articles';
require_once $base_url . 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrateur') {
    header('Location: ' . $base_url . 'pages/connexion.php');
    exit;
}

try {
    $stmt = $pdo->query("SELECT a.*, u.prenom AS vendeur_prenom, u.nom AS vendeur_nom
                         FROM articles a
                         JOIN utilisateurs u ON a.vendeur_id = u.id
                         ORDER BY a.date_creation DESC");
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
            <div>
                <h1 class="h3 mb-1"><i class="bi bi-box-seam me-2"></i>Gestion des articles</h1>
                <p class="text-muted mb-0"><?php echo count($articles); ?> article<?php echo count($articles) > 1 ? 's' : ''; ?> au total</p>
            </div>
            <a href="dashboard.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Retour</a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success d-flex align-items-center">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Search -->
        <div class="mb-3">
            <input type="text" class="form-control search-dynamic" placeholder="Rechercher un article..." style="max-width: 400px;">
        </div>

        <div class="card shadow-sm table-enhanced" style="border-radius: 16px;">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Titre</th>
                            <th>Vendeur</th>
                            <th>Catégorie</th>
                            <th>Prix</th>
                            <th>Type</th>
                            <th>Gamme</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($articles as $a): ?>
                            <tr class="searchable-item">
                                <td class="ps-4 fw-semibold">#<?php echo $a['id']; ?></td>
                                <td>
                                    <a href="<?php echo $base_url; ?>pages/article.php?id=<?php echo $a['id']; ?>" class="text-decoration-none fw-semibold">
                                        <?php echo htmlspecialchars($a['titre']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($a['vendeur_prenom'] . ' ' . $a['vendeur_nom']); ?></td>
                                <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($a['categorie']); ?></span></td>
                                <td class="fw-semibold"><?php echo number_format($a['prix'], 2, ',', ' '); ?> &euro;</td>
                                <td><span class="badge badge-<?php echo $a['type_vente']; ?>"><?php echo htmlspecialchars($a['type_vente']); ?></span></td>
                                <td><span class="badge badge-<?php echo $a['gamme']; ?>"><?php echo htmlspecialchars($a['gamme']); ?></span></td>
                                <td>
                                    <span class="badge <?php echo $a['statut'] === 'disponible' ? 'bg-success' : 'bg-secondary'; ?> rounded-pill">
                                        <?php echo htmlspecialchars($a['statut']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" action="<?php echo $base_url; ?>php/admin_actions.php" class="d-inline"
                                          data-confirm="Supprimer l'article '<?php echo htmlspecialchars($a['titre']); ?>' ?">
                                        <input type="hidden" name="action" value="delete_article">
                                        <input type="hidden" name="article_id" value="<?php echo $a['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
