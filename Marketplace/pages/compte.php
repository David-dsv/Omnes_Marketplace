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

// Récupérer les infos utilisateur
try {
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    $user = null;
}

// Récupérer les commandes récentes
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
?>

<main class="py-4">
    <div class="container">
        <h1 class="mb-4"><i class="bi bi-person-circle"></i> Mon Compte</h1>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="row">
            <!-- Profil -->
            <div class="col-lg-4 mb-4">
                <div class="card p-4 shadow-sm text-center">
                    <i class="bi bi-person-circle display-3 text-primary"></i>
                    <h4 class="mt-3"><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></h4>
                    <span class="badge bg-primary mb-3"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span>
                    <ul class="list-unstyled text-start">
                        <li class="mb-2"><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></li>
                        <li class="mb-2"><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($user['telephone'] ?? 'Non renseigné'); ?></li>
                        <li class="mb-2"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($user['adresse'] ?? 'Non renseignée'); ?></li>
                        <li class="mb-2"><i class="bi bi-calendar"></i> Membre depuis <?php echo date('d/m/Y', strtotime($user['date_creation'])); ?></li>
                    </ul>
                </div>
            </div>

            <!-- Actions et historique -->
            <div class="col-lg-8">
                <!-- Modifier profil -->
                <div class="card p-4 shadow-sm mb-4">
                    <h5>Modifier mes informations</h5>
                    <hr>
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
                                <input type="tel" name="telephone" class="form-control" value="<?php echo htmlspecialchars($user['telephone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Adresse</label>
                                <input type="text" name="adresse" class="form-control" value="<?php echo htmlspecialchars($user['adresse'] ?? ''); ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">Enregistrer</button>
                    </form>
                </div>

                <!-- Historique commandes -->
                <div class="card p-4 shadow-sm">
                    <h5>Mes commandes récentes</h5>
                    <hr>
                    <?php if (!empty($commandes)): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>N°</th>
                                        <th>Articles</th>
                                        <th>Total</th>
                                        <th>Statut</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($commandes as $commande): ?>
                                        <tr>
                                            <td>#<?php echo $commande['id']; ?></td>
                                            <td><?php echo htmlspecialchars($commande['articles'] ?? ''); ?></td>
                                            <td class="fw-bold"><?php echo number_format($commande['total'], 2, ',', ' '); ?> &euro;</td>
                                            <td><span class="badge bg-info"><?php echo htmlspecialchars($commande['statut']); ?></span></td>
                                            <td><?php echo date('d/m/Y', strtotime($commande['date_creation'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Aucune commande pour le moment.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
