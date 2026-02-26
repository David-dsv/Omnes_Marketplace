<?php
session_start();
$base_url = '../';
$page_title = 'Panier';
require_once $base_url . 'config/database.php';
include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

// Récupérer les articles du panier
try {
    $stmt = $pdo->prepare("SELECT p.*, a.titre, a.prix, a.image_url, a.vendeur_id,
                                  u.prenom AS vendeur_prenom, u.nom AS vendeur_nom
                           FROM panier p
                           JOIN articles a ON p.article_id = a.id
                           JOIN utilisateurs u ON a.vendeur_id = u.id
                           WHERE p.utilisateur_id = :uid");
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $panier_items = $stmt->fetchAll();
} catch (PDOException $e) {
    $panier_items = [];
}

$total = 0;
foreach ($panier_items as $item) {
    $total += $item['prix'] * $item['quantite'];
}
?>

<main class="py-4">
    <div class="container">
        <h1 class="mb-4"><i class="bi bi-cart3"></i> Mon Panier</h1>

        <?php if (!empty($panier_items)): ?>
            <div class="row">
                <div class="col-lg-8">
                    <?php foreach ($panier_items as $item): ?>
                        <div class="cart-item d-flex align-items-center">
                            <img src="<?php echo $base_url . htmlspecialchars($item['image_url'] ?? 'images/placeholder.png'); ?>"
                                 class="rounded me-3" style="width: 80px; height: 80px; object-fit: cover;"
                                 alt="<?php echo htmlspecialchars($item['titre']); ?>">
                            <div class="flex-grow-1">
                                <h5 class="mb-1">
                                    <a href="article.php?id=<?php echo $item['article_id']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($item['titre']); ?>
                                    </a>
                                </h5>
                                <p class="text-muted mb-0 small">
                                    Vendeur : <?php echo htmlspecialchars($item['vendeur_prenom'] . ' ' . $item['vendeur_nom']); ?>
                                </p>
                            </div>
                            <div class="text-end">
                                <p class="fw-bold text-primary mb-1"><?php echo number_format($item['prix'], 2, ',', ' '); ?> &euro;</p>
                                <button class="btn btn-outline-danger btn-sm btn-remove-cart" data-article-id="<?php echo $item['article_id']; ?>">
                                    <i class="bi bi-trash"></i> Retirer
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="col-lg-4">
                    <div class="card p-4 shadow-sm">
                        <h5>Récapitulatif</h5>
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Articles (<?php echo count($panier_items); ?>)</span>
                            <span><?php echo number_format($total, 2, ',', ' '); ?> &euro;</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Livraison</span>
                            <span class="text-success">Gratuite</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold fs-5">
                            <span>Total</span>
                            <span class="text-primary"><?php echo number_format($total, 2, ',', ' '); ?> &euro;</span>
                        </div>
                        <?php if ($total > 100): ?>
                            <div class="alert alert-success mt-3 small">
                                <i class="bi bi-gift"></i> Vous êtes éligible à une carte de réduction de 10 à 20% !
                            </div>
                        <?php endif; ?>
                        <a href="paiement.php" class="btn btn-primary btn-lg w-100 mt-3">Passer au paiement</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-cart-x display-4"></i>
                <p class="mt-3">Votre panier est vide.</p>
                <a href="tout_parcourir.php" class="btn btn-primary">Parcourir les articles</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
