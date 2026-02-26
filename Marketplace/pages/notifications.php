<?php
session_start();
$base_url = '../';
$page_title = 'Notifications';
require_once $base_url . 'config/database.php';
include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE utilisateur_id = :uid ORDER BY date_creation DESC");
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $notifications = $stmt->fetchAll();
} catch (PDOException $e) {
    $notifications = [];
}
?>

<main class="py-4">
    <div class="container" style="max-width: 800px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0"><i class="bi bi-bell me-2"></i>Notifications</h1>
            <?php if (!empty($notifications)): ?>
                <span class="badge bg-primary rounded-pill"><?php echo count($notifications); ?></span>
            <?php endif; ?>
        </div>

        <?php if (!empty($notifications)): ?>
            <div class="animate-on-scroll">
                <?php foreach ($notifications as $notif):
                    $iconClass = 'bi-info-circle';
                    $iconBg = 'rgba(var(--omnes-primary-rgb),0.1)';
                    $iconColor = 'var(--omnes-primary)';
                    if (strpos($notif['message'], 'commande') !== false || strpos($notif['message'], 'achat') !== false) {
                        $iconClass = 'bi-bag-check'; $iconBg = 'rgba(25,135,84,0.1)'; $iconColor = 'var(--omnes-success)';
                    } elseif (strpos($notif['message'], 'négociation') !== false) {
                        $iconClass = 'bi-chat-dots'; $iconBg = 'rgba(255,193,7,0.1)'; $iconColor = 'var(--omnes-warning)';
                    }
                ?>
                    <div class="notification-item <?php echo !$notif['lue'] ? 'unread' : ''; ?>">
                        <div class="notif-icon" style="background: <?php echo $iconBg; ?>; color: <?php echo $iconColor; ?>;">
                            <i class="bi <?php echo $iconClass; ?>"></i>
                        </div>
                        <div class="flex-grow-1">
                            <p class="mb-1 <?php echo !$notif['lue'] ? 'fw-semibold' : ''; ?>"><?php echo htmlspecialchars($notif['message']); ?></p>
                            <small class="text-muted"><i class="bi bi-clock"></i> <?php echo date('d/m/Y H:i', strtotime($notif['date_creation'])); ?></small>
                        </div>
                        <?php if (!$notif['lue']): ?>
                            <span class="badge bg-primary rounded-pill">Nouveau</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                     style="width:100px;height:100px;background:rgba(var(--omnes-primary-rgb),0.1);">
                    <i class="bi bi-bell-slash text-primary" style="font-size:3rem;"></i>
                </div>
                <h5 class="mt-2">Aucune notification</h5>
                <p class="text-muted">Vous recevrez ici les mises à jour sur vos négociations et commandes.</p>
            </div>
        <?php endif; ?>

        <!-- Alertes de recherche -->
        <section class="mt-5 animate-on-scroll">
            <div class="card p-4 shadow-sm" style="border-radius: 16px;">
                <h5 class="fw-bold mb-2"><i class="bi bi-megaphone me-2"></i>Alertes de recherche</h5>
                <p class="text-muted small mb-3">Créez une alerte pour être notifié lorsqu'un article correspondant est publié.</p>
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
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-bell-fill me-1"></i> Créer</button>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
