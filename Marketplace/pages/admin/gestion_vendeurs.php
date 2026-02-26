<?php
session_start();
$base_url = '../../';
$page_title = 'Gestion des vendeurs';
require_once $base_url . 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrateur') {
    header('Location: ' . $base_url . 'pages/connexion.php');
    exit;
}

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
            <div>
                <h1 class="h3 mb-1"><i class="bi bi-people me-2"></i>Gestion des vendeurs</h1>
                <p class="text-muted mb-0"><?php echo count($vendeurs); ?> vendeur<?php echo count($vendeurs) > 1 ? 's' : ''; ?></p>
            </div>
            <a href="dashboard.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Retour</a>
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

        <!-- Add vendor form -->
        <div class="card p-4 shadow-sm mb-4 animate-on-scroll" style="border-radius: 16px; border: 2px solid rgba(var(--omnes-primary-rgb), 0.1);">
            <h5 class="fw-bold mb-3"><i class="bi bi-person-plus me-2 text-primary"></i>Ajouter un vendeur</h5>
            <form method="POST" action="<?php echo $base_url; ?>php/admin_actions.php">
                <input type="hidden" name="action" value="create_vendor">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small">Prénom</label>
                        <input type="text" name="prenom" class="form-control" placeholder="Prénom" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Nom</label>
                        <input type="text" name="nom" class="form-control" placeholder="Nom" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="email@exemple.com" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Mot de passe</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••" required>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-success w-100" title="Ajouter"><i class="bi bi-plus-lg"></i></button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Vendors list -->
        <div class="card shadow-sm table-enhanced animate-on-scroll" style="border-radius: 16px;">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Vendeur</th>
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
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
                                             style="width:36px;height:36px;background:linear-gradient(135deg,var(--omnes-primary),var(--omnes-accent));font-size:0.8rem;">
                                            <?php echo strtoupper(substr($v['prenom'], 0, 1) . substr($v['nom'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($v['prenom'] . ' ' . $v['nom']); ?></strong>
                                            <small class="d-block text-muted">#<?php echo $v['id']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($v['email']); ?></td>
                                <td>
                                    <span class="badge bg-light text-dark"><?php echo $v['nb_articles']; ?> articles</span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $v['actif'] ? 'bg-success' : 'bg-secondary'; ?> rounded-pill">
                                        <i class="bi <?php echo $v['actif'] ? 'bi-check-circle' : 'bi-x-circle'; ?> me-1"></i>
                                        <?php echo $v['actif'] ? 'Actif' : 'Inactif'; ?>
                                    </span>
                                </td>
                                <td class="text-muted"><?php echo date('d/m/Y', strtotime($v['date_creation'])); ?></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <form method="POST" action="<?php echo $base_url; ?>php/admin_actions.php" class="d-inline">
                                            <input type="hidden" name="action" value="toggle_vendor">
                                            <input type="hidden" name="vendor_id" value="<?php echo $v['id']; ?>">
                                            <button type="submit" class="btn btn-sm <?php echo $v['actif'] ? 'btn-outline-warning' : 'btn-outline-success'; ?>" title="<?php echo $v['actif'] ? 'Désactiver' : 'Activer'; ?>">
                                                <i class="bi <?php echo $v['actif'] ? 'bi-pause-fill' : 'bi-play-fill'; ?>"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="<?php echo $base_url; ?>php/admin_actions.php" class="d-inline"
                                              data-confirm="Supprimer le vendeur '<?php echo htmlspecialchars($v['prenom'] . ' ' . $v['nom']); ?>' ?">
                                            <input type="hidden" name="action" value="delete_vendor">
                                            <input type="hidden" name="vendor_id" value="<?php echo $v['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($vendeurs)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Aucun vendeur enregistré.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
