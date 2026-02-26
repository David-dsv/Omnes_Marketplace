<?php
session_start();
$base_url = '../../';
$page_title = 'Gestion des articles';
require_once $base_url . 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrateur') {
    header('Location: ' . $base_url . 'pages/connexion.php');
    exit;
}

// Récupérer tous les articles
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
            <h1><i class="bi bi-box-seam"></i> Gestion des articles</h1>
            <a href="dashboard.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Retour</a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
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
                            <tr>
                                <td><?php echo $a['id']; ?></td>
                                <td><?php echo htmlspecialchars($a['titre']); ?></td>
                                <td><?php echo htmlspecialchars($a['vendeur_prenom'] . ' ' . $a['vendeur_nom']); ?></td>
                                <td><?php echo htmlspecialchars($a['categorie']); ?></td>
                                <td><?php echo number_format($a['prix'], 2, ',', ' '); ?> &euro;</td>
                                <td><span class="badge bg-info"><?php echo htmlspecialchars($a['type_vente']); ?></span></td>
                                <td><span class="badge badge-<?php echo $a['gamme']; ?>"><?php echo htmlspecialchars($a['gamme']); ?></span></td>
                                <td><span class="badge <?php echo $a['statut'] === 'disponible' ? 'bg-success' : 'bg-secondary'; ?>"><?php echo htmlspecialchars($a['statut']); ?></span></td>
                                <td>
                                    <form method="POST" action="<?php echo $base_url; ?>php/admin_actions.php" class="d-inline"
                                          onsubmit="return confirm('Supprimer cet article ?');">
                                        <input type="hidden" name="action" value="delete_article">
                                        <input type="hidden" name="article_id" value="<?php echo $a['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
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
