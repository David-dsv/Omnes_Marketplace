<?php
session_start();
$base_url = '../../';
$page_title = 'Gestion des vendeurs';
require_once $base_url . 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrateur') {
    header('Location: ' . $base_url . 'pages/connexion.php');
    exit;
}

// Récupérer les vendeurs
try {
    $stmt = $pdo->query("SELECT u.*, (SELECT COUNT(*) FROM articles a WHERE a.vendeur_id = u.id) AS nb_articles
                         FROM utilisateurs u
                         WHERE u.role = 'vendeur'
                         ORDER BY u.date_creation DESC");
    $vendeurs = $stmt->fetchAll();
} catch (PDOException $e) {
    $vendeurs = [];
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';
?>

<main class="py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-people"></i> Gestion des vendeurs</h1>
            <a href="dashboard.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Retour</a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Formulaire d'ajout -->
        <div class="card p-4 shadow-sm mb-4">
            <h5>Ajouter un vendeur</h5>
            <form method="POST" action="<?php echo $base_url; ?>php/admin_actions.php">
                <input type="hidden" name="action" value="create_vendor">
                <div class="row g-3">
                    <div class="col-md-3">
                        <input type="text" name="prenom" class="form-control" placeholder="Prénom" required>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="nom" class="form-control" placeholder="Nom" required>
                    </div>
                    <div class="col-md-3">
                        <input type="email" name="email" class="form-control" placeholder="Email" required>
                    </div>
                    <div class="col-md-2">
                        <input type="password" name="password" class="form-control" placeholder="Mot de passe" required>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-success w-100"><i class="bi bi-plus"></i></button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Liste des vendeurs -->
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Articles</th>
                            <th>Statut</th>
                            <th>Inscrit le</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vendeurs as $v): ?>
                            <tr>
                                <td><?php echo $v['id']; ?></td>
                                <td><?php echo htmlspecialchars($v['prenom'] . ' ' . $v['nom']); ?></td>
                                <td><?php echo htmlspecialchars($v['email']); ?></td>
                                <td><?php echo $v['nb_articles']; ?></td>
                                <td>
                                    <span class="badge <?php echo $v['actif'] ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo $v['actif'] ? 'Actif' : 'Inactif'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($v['date_creation'])); ?></td>
                                <td>
                                    <form method="POST" action="<?php echo $base_url; ?>php/admin_actions.php" class="d-inline">
                                        <input type="hidden" name="action" value="toggle_vendor">
                                        <input type="hidden" name="vendor_id" value="<?php echo $v['id']; ?>">
                                        <button type="submit" class="btn btn-sm <?php echo $v['actif'] ? 'btn-warning' : 'btn-success'; ?>">
                                            <?php echo $v['actif'] ? 'Désactiver' : 'Activer'; ?>
                                        </button>
                                    </form>
                                    <form method="POST" action="<?php echo $base_url; ?>php/admin_actions.php" class="d-inline"
                                          onsubmit="return confirm('Supprimer ce vendeur ?');">
                                        <input type="hidden" name="action" value="delete_vendor">
                                        <input type="hidden" name="vendor_id" value="<?php echo $v['id']; ?>">
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
