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

// Récupérer l'article
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

// Récupérer l'enchère en cours
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

$page_title = 'Enchère - ' . $article['titre'];
include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';
?>

<main class="py-4">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>index.php">Accueil</a></li>
                <li class="breadcrumb-item"><a href="article.php?id=<?php echo $article_id; ?>"><?php echo htmlspecialchars($article['titre']); ?></a></li>
                <li class="breadcrumb-item active">Enchère</li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-md-6">
                <img src="<?php echo $base_url . htmlspecialchars($article['image_url'] ?? 'images/placeholder.png'); ?>"
                     class="img-fluid rounded shadow" alt="<?php echo htmlspecialchars($article['titre']); ?>">
            </div>

            <div class="col-md-6">
                <h1 class="h2"><?php echo htmlspecialchars($article['titre']); ?></h1>
                <p class="text-muted">Vendeur : <?php echo htmlspecialchars($article['vendeur_prenom'] . ' ' . $article['vendeur_nom']); ?></p>

                <!-- Timer -->
                <div class="card p-3 mb-3 text-center bg-light">
                    <p class="mb-1 text-muted">Temps restant</p>
                    <div id="auction-timer" class="auction-timer" data-end-time="<?php echo $article['date_fin_enchere'] ?? ''; ?>">
                        --:--:--
                    </div>
                </div>

                <!-- Prix actuel -->
                <div class="card p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-0 text-muted">Prix de départ</p>
                            <p class="mb-0"><?php echo number_format($article['prix'], 2, ',', ' '); ?> &euro;</p>
                        </div>
                        <div class="text-end">
                            <p class="mb-0 text-muted">Enchère actuelle</p>
                            <p class="mb-0 fs-3 fw-bold text-danger"><?php echo number_format($prix_actuel, 2, ',', ' '); ?> &euro;</p>
                        </div>
                    </div>
                </div>

                <!-- Formulaire d'enchère -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <form method="POST" action="<?php echo $base_url; ?>php/enchere_actions.php">
                        <input type="hidden" name="action" value="bid">
                        <input type="hidden" name="article_id" value="<?php echo $article_id; ?>">
                        <div class="mb-3">
                            <label class="form-label">Votre enchère maximale (&euro;)</label>
                            <input type="number" name="montant_max" class="form-control form-control-lg"
                                   min="<?php echo $prix_actuel + 1; ?>" step="1"
                                   placeholder="<?php echo $prix_actuel + 1; ?>" required>
                            <div class="form-text">Le système enchérira automatiquement pour vous jusqu'à ce montant (+1&euro; par enchère).</div>
                        </div>
                        <button type="submit" class="btn btn-danger btn-lg w-100">
                            <i class="bi bi-hammer"></i> Placer mon enchère
                        </button>
                    </form>
                <?php else: ?>
                    <a href="connexion.php" class="btn btn-primary btn-lg w-100">Connectez-vous pour enchérir</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Historique des enchères -->
        <section class="mt-5">
            <h3>Historique des enchères</h3>
            <hr>
            <?php if (!empty($encheres)): ?>
                <div class="bid-history">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Enchérisseur</th>
                                <th>Montant</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($encheres as $e): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($e['prenom'] . ' ' . substr($e['nom'], 0, 1) . '.'); ?></td>
                                    <td class="fw-bold"><?php echo number_format($e['montant'], 2, ',', ' '); ?> &euro;</td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($e['date_creation'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">Aucune enchère pour le moment. Soyez le premier !</p>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
