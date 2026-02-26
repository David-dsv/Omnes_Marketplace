<?php
session_start();
$base_url = '../';
$page_title = 'Enchère';
require_once $base_url . 'config/database.php';

$article_id = (int)($_GET['article_id'] ?? 0);
if ($article_id <= 0) {
    header('Location: tout_parcourir.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT a.*, u.prenom AS vendeur_prenom, u.nom AS vendeur_nom
                           FROM articles a
                           JOIN utilisateurs u ON a.vendeur_id = u.id
                           WHERE a.id = :id AND a.type_vente = 'enchere'");
    $stmt->execute([':id' => $article_id]);
    $article = $stmt->fetch();
} catch (PDOException $e) {
    $article = null;
}

if (!$article) {
    header('Location: tout_parcourir.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT e.*, u.prenom, u.nom
                           FROM encheres e
                           JOIN utilisateurs u ON e.acheteur_id = u.id
                           WHERE e.article_id = :id
                           ORDER BY e.montant DESC");
    $stmt->execute([':id' => $article_id]);
    $encheres = $stmt->fetchAll();
    $meilleure_offre = $encheres[0] ?? null;
} catch (PDOException $e) {
    $encheres = [];
    $meilleure_offre = null;
}

$prix_actuel = $meilleure_offre ? $meilleure_offre['montant'] : $article['prix'];
$nb_encheres = count($encheres);

// Calculate progress (percentage from start price toward a theoretical max)
$progress = min(100, (($prix_actuel - $article['prix']) / max(1, $article['prix'])) * 100);

$page_title = 'Enchère - ' . $article['titre'];
include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';
?>

<main class="py-4">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>index.php"><i class="bi bi-house"></i> Accueil</a></li>
                <li class="breadcrumb-item"><a href="article.php?id=<?php echo $article_id; ?>"><?php echo htmlspecialchars($article['titre']); ?></a></li>
                <li class="breadcrumb-item active">Enchère</li>
            </ol>
        </nav>

        <div class="row g-4">
            <!-- Image -->
            <div class="col-lg-5 animate-on-scroll">
                <div class="card overflow-hidden" style="border-radius: 16px;">
                    <img src="<?php echo $base_url . htmlspecialchars($article['image_url'] ?? 'images/placeholder.png'); ?>"
                         class="img-fluid w-100" style="max-height: 400px; object-fit: cover;"
                         alt="<?php echo htmlspecialchars($article['titre']); ?>">
                </div>
            </div>

            <!-- Auction details -->
            <div class="col-lg-7 animate-on-scroll animate-delay-1">
                <h1 class="h2 fw-bold mb-2"><?php echo htmlspecialchars($article['titre']); ?></h1>
                <p class="text-muted mb-3">
                    <i class="bi bi-person"></i> Vendeur : <?php echo htmlspecialchars($article['vendeur_prenom'] . ' ' . $article['vendeur_nom']); ?>
                    <span class="mx-2">|</span>
                    <i class="bi bi-hammer"></i> <?php echo $nb_encheres; ?> enchère<?php echo $nb_encheres > 1 ? 's' : ''; ?>
                </p>

                <!-- Timer -->
                <div class="auction-timer-card mb-3">
                    <p class="mb-1 text-muted small text-uppercase fw-bold"><i class="bi bi-clock"></i> Temps restant</p>
                    <div id="auction-timer" class="auction-timer" data-end-time="<?php echo $article['date_fin_enchere'] ?? ''; ?>">
                        --:--:--
                    </div>
                </div>

                <!-- Prices -->
                <div class="card p-4 mb-3" style="border: 2px solid #e9ecef; border-radius: 16px;">
                    <div class="row text-center">
                        <div class="col-6">
                            <p class="mb-1 text-muted small text-uppercase">Prix de départ</p>
                            <p class="mb-0 fs-5"><?php echo number_format($article['prix'], 2, ',', ' '); ?> &euro;</p>
                        </div>
                        <div class="col-6" style="border-left: 2px solid #e9ecef;">
                            <p class="mb-1 text-muted small text-uppercase">Enchère actuelle</p>
                            <p class="mb-0 fs-3 fw-bold text-danger"><?php echo number_format($prix_actuel, 2, ',', ' '); ?> &euro;</p>
                        </div>
                    </div>
                    <!-- Progress bar -->
                    <div class="auction-progress mt-3">
                        <div class="auction-progress-bar" style="width: <?php echo $progress; ?>%;"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                        <small class="text-muted"><?php echo number_format($article['prix'], 0, ',', ' '); ?> €</small>
                        <small class="text-muted fw-bold"><?php echo number_format($prix_actuel, 0, ',', ' '); ?> €</small>
                    </div>
                </div>

                <!-- Bidding form -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="card p-4 shadow-sm" style="border-radius: 16px; border: 2px solid rgba(var(--omnes-primary-rgb), 0.15);">
                        <h5 class="fw-bold mb-3"><i class="bi bi-hammer me-2"></i>Placer une enchère</h5>
                        <form method="POST" action="<?php echo $base_url; ?>php/enchere_actions.php">
                            <input type="hidden" name="action" value="bid">
                            <input type="hidden" name="article_id" value="<?php echo $article_id; ?>">
                            <div class="mb-3">
                                <label class="form-label">Votre enchère maximale</label>
                                <div class="input-group input-group-lg">
                                    <input type="number" name="montant_max" class="form-control"
                                           min="<?php echo $prix_actuel + 1; ?>" step="1"
                                           placeholder="<?php echo $prix_actuel + 1; ?>" required>
                                    <span class="input-group-text">&euro;</span>
                                </div>
                                <small class="text-muted mt-1 d-block">
                                    <i class="bi bi-info-circle"></i> Le système enchérira automatiquement pour vous (+1€ par enchère jusqu'à votre max).
                                </small>
                            </div>
                            <button type="submit" class="btn btn-danger btn-lg w-100">
                                <i class="bi bi-hammer me-2"></i>Placer mon enchère
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="card p-4 text-center" style="border-radius: 16px;">
                        <p class="mb-2"><i class="bi bi-lock fs-3 text-muted"></i></p>
                        <a href="connexion.php" class="btn btn-primary btn-lg">Connectez-vous pour enchérir</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Historique -->
        <section class="mt-5 animate-on-scroll">
            <div class="card shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-white py-3 px-4" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2"></i>Historique des enchères</h5>
                </div>
                <?php if (!empty($encheres)): ?>
                    <div class="bid-history">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Enchérisseur</th>
                                    <th>Montant</th>
                                    <th>Date</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($encheres as $index => $e): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center bg-light fw-bold"
                                                     style="width:32px;height:32px;font-size:0.75rem;">
                                                    <?php echo strtoupper(substr($e['prenom'], 0, 1)); ?>
                                                </div>
                                                <?php echo htmlspecialchars($e['prenom'] . ' ' . substr($e['nom'], 0, 1) . '.'); ?>
                                            </div>
                                        </td>
                                        <td class="fw-bold <?php echo $index === 0 ? 'text-danger' : ''; ?>">
                                            <?php echo number_format($e['montant'], 2, ',', ' '); ?> &euro;
                                            <?php if ($index === 0): ?>
                                                <span class="badge bg-danger ms-1">Meilleure</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted"><?php echo date('d/m/Y H:i', strtotime($e['date_creation'])); ?></td>
                                        <td>
                                            <?php if ($index === 0): ?>
                                                <i class="bi bi-trophy-fill text-warning"></i>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-hammer display-4"></i>
                        <p class="mt-2">Aucune enchère pour le moment. Soyez le premier !</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
