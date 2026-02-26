<?php
session_start();
$base_url = '../';
$page_title = 'Mon Compte';
require_once $base_url . 'config/database.php';
include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    $user = null;
}

try {
    $stmt = $pdo->prepare("SELECT c.*, GROUP_CONCAT(a.titre SEPARATOR ', ') AS articles
                           FROM commandes c
                           LEFT JOIN commande_articles ca ON c.id = ca.commande_id
                           LEFT JOIN articles a ON ca.article_id = a.id
                           WHERE c.acheteur_id = :uid
                           GROUP BY c.id
                           ORDER BY c.date_creation DESC
                           LIMIT 10");
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $commandes = $stmt->fetchAll();
} catch (PDOException $e) {
    $commandes = [];
}

$success = $_GET['success'] ?? '';
$initials = strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1));
?>

<main class="py-4">
    <div class="container">
        <h1 class="h3 mb-4"><i class="bi bi-person-circle me-2"></i>Mon Compte</h1>

        <?php if ($success): ?>
            <div class="alert alert-success d-flex align-items-center">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Profile card -->
            <div class="col-lg-4 mb-4 animate-on-scroll">
                <div class="card p-4 shadow-sm text-center" style="border-radius: 16px;">
                    <div class="account-avatar mx-auto">
                        <div class="avatar-initials"><?php echo $initials; ?></div>
                        <div class="avatar-upload"><i class="bi bi-camera"></i></div>
                    </div>
                    <h4 class="fw-bold mt-3"><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></h4>
                    <span class="badge bg-primary mb-3 px-3 py-2"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span>
                    <ul class="list-unstyled text-start">
                        <li class="mb-3 d-flex align-items-center gap-2">
                            <div class="rounded d-flex align-items-center justify-content-center"
                                 style="width:36px;height:36px;background:rgba(var(--omnes-primary-rgb),0.1);">
                                <i class="bi bi-envelope text-primary"></i>
                            </div>
                            <span><?php echo htmlspecialchars($user['email']); ?></span>
                        </li>
                        <li class="mb-3 d-flex align-items-center gap-2">
                            <div class="rounded d-flex align-items-center justify-content-center"
                                 style="width:36px;height:36px;background:rgba(25,135,84,0.1);">
                                <i class="bi bi-telephone text-success"></i>
                            </div>
                            <span><?php echo htmlspecialchars($user['telephone'] ?? 'Non renseigné'); ?></span>
                        </li>
                        <li class="mb-3 d-flex align-items-center gap-2">
                            <div class="rounded d-flex align-items-center justify-content-center"
                                 style="width:36px;height:36px;background:rgba(253,126,20,0.1);">
                                <i class="bi bi-geo-alt text-warning"></i>
                            </div>
                            <span><?php echo htmlspecialchars($user['adresse'] ?? 'Non renseignée'); ?></span>
                        </li>
                        <li class="d-flex align-items-center gap-2">
                            <div class="rounded d-flex align-items-center justify-content-center"
                                 style="width:36px;height:36px;background:rgba(108,117,125,0.1);">
                                <i class="bi bi-calendar text-secondary"></i>
                            </div>
                            <span>Membre depuis <?php echo date('d/m/Y', strtotime($user['date_creation'])); ?></span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Tabs: Profile edit + Orders -->
            <div class="col-lg-8 animate-on-scroll animate-delay-1">
                <ul class="nav account-tabs mb-4" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-profile">
                            <i class="bi bi-person me-1"></i> Profil
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-orders">
                            <i class="bi bi-bag me-1"></i> Commandes
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Profile tab -->
                    <div class="tab-pane fade show active" id="tab-profile">
                        <div class="card p-4 shadow-sm" style="border-radius: 16px;">
                            <h5 class="fw-bold mb-3"><i class="bi bi-pencil-square me-2"></i>Modifier mes informations</h5>
                            <form method="POST" action="<?php echo $base_url; ?>php/auth.php">
                                <input type="hidden" name="action" value="update_profile">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Prénom</label>
                                        <input type="text" name="prenom" class="form-control" value="<?php echo htmlspecialchars($user['prenom']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Nom</label>
                                        <input type="text" name="nom" class="form-control" value="<?php echo htmlspecialchars($user['nom']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Téléphone</label>
                                        <input type="tel" name="telephone" class="form-control" value="<?php echo htmlspecialchars($user['telephone'] ?? ''); ?>" placeholder="06 12 34 56 78">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Adresse</label>
                                        <input type="text" name="adresse" class="form-control" value="<?php echo htmlspecialchars($user['adresse'] ?? ''); ?>" placeholder="Votre adresse">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary mt-3">
                                    <i class="bi bi-check-lg me-1"></i> Enregistrer
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Orders tab -->
                    <div class="tab-pane fade" id="tab-orders">
                        <?php if (!empty($commandes)):
                            foreach ($commandes as $commande): ?>
                                <div class="order-card">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                        <div>
                                            <h6 class="fw-bold mb-1">Commande #<?php echo $commande['id']; ?></h6>
                                            <p class="text-muted small mb-1"><?php echo htmlspecialchars($commande['articles'] ?? 'N/A'); ?></p>
                                            <small class="text-muted"><i class="bi bi-calendar"></i> <?php echo date('d/m/Y', strtotime($commande['date_creation'])); ?></small>
                                        </div>
                                        <div class="text-end">
                                            <p class="fw-bold text-primary fs-5 mb-1"><?php echo number_format($commande['total'], 2, ',', ' '); ?> &euro;</p>
                                            <span class="order-status badge <?php
                                                echo match($commande['statut']) {
                                                    'confirmee' => 'bg-success',
                                                    'en_cours' => 'bg-warning text-dark',
                                                    'annulee' => 'bg-danger',
                                                    default => 'bg-info'
                                                };
                                            ?>">
                                                <?php echo htmlspecialchars(ucfirst($commande['statut'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach;
                        else: ?>
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-bag display-4"></i>
                                <p class="mt-3">Aucune commande pour le moment.</p>
                                <a href="tout_parcourir.php" class="btn btn-outline-primary">Découvrir les articles</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
