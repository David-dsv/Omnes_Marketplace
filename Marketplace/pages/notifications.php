<?php
session_start();
$base_url = '../';
$page_title = 'Notifications';
require_once $base_url . 'config/database.php';
include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

// Récupérer les notifications
try {
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE utilisateur_id = :uid ORDER BY date_creation DESC");
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $notifications = $stmt->fetchAll();
} catch (PDOException $e) {
    $notifications = [];
}
?>

<main class="py-4">
    <div class="container">
        <h1 class="mb-4"><i class="bi bi-bell"></i> Notifications</h1>

        <?php if (!empty($notifications)): ?>
            <div class="list-group">
                <?php foreach ($notifications as $notif): ?>
                    <div class="list-group-item list-group-item-action <?php echo $notif['lue'] ? '' : 'list-group-item-light fw-semibold'; ?>">
                        <div class="d-flex justify-content-between">
                            <p class="mb-1"><?php echo htmlspecialchars($notif['message']); ?></p>
                            <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($notif['date_creation'])); ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-bell-slash display-4"></i>
                <p class="mt-3">Aucune notification pour le moment.</p>
            </div>
        <?php endif; ?>

        <!-- Alertes de recherche -->
        <section class="mt-5">
            <h3>Alertes de recherche</h3>
            <p class="text-muted">Créez une alerte pour être notifié lorsqu'un article correspondant est publié.</p>
            <form method="POST" action="<?php echo $base_url; ?>php/article_actions.php">
                <input type="hidden" name="action" value="create_alert">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="mot_cle" class="form-control" placeholder="Mot-clé" required>
                    </div>
                    <div class="col-md-3">
                        <select name="categorie" class="form-select">
                            <option value="">Toute catégorie</option>
                            <option value="Électronique">Électronique</option>
                            <option value="Vêtements">Vêtements</option>
                            <option value="Maison">Maison</option>
                            <option value="Livres">Livres</option>
                            <option value="Sports">Sports</option>
                            <option value="Divers">Divers</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="number" name="prix_max" class="form-control" placeholder="Prix max (€)" step="0.01">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Créer l'alerte</button>
                    </div>
                </div>
            </form>
        </section>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
