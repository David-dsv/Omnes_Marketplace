<?php
session_start();
$base_url = '../';

if (!isset($_SESSION['user_id'])) {
    header('Location: acces_panier.php');
    exit;
}

$page_title = 'Panier';
require_once $base_url . 'config/database.php';
include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';

try {
    $stmt = $pdo->prepare("SELECT p.*, a.titre, a.prix, a.type_vente, a.image_url, a.vendeur_id,
                                  COALESCE(p.prix_negocie, a.prix) AS prix_final,
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
    $total += (float)$item['prix_final'] * (int)$item['quantite'];
}
$error_message = trim((string)($_GET['error'] ?? ''));
?>

<main class="py-4">
    <div class="container">
        <h1 class="h3 mb-4"><i class="bi bi-cart3 me-2"></i>Mon Panier</h1>
        <?php if ($error_message !== ''): ?>
            <div class="alert alert-warning d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($panier_items)): ?>
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card p-3 shadow-sm" style="border-radius: 16px;">
                        <div class="d-flex justify-content-between align-items-center px-3 pb-3 border-bottom">
                            <h6 class="mb-0 fw-bold"><?php echo count($panier_items); ?> article<?php echo count($panier_items) > 1 ? 's' : ''; ?></h6>
                            <span class="text-muted small">Prix</span>
                        </div>
                        <?php foreach ($panier_items as $item): ?>
                            <div class="cart-item d-flex align-items-center px-3">
                                <img src="<?php echo $base_url . htmlspecialchars($item['image_url'] ?? 'images/articles/placeholder.png'); ?>"
                                     class="rounded me-3" style="width: 90px; height: 90px; object-fit: cover;"
                                     alt="<?php echo htmlspecialchars($item['titre']); ?>">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">
                                        <a href="article.php?id=<?php echo $item['article_id']; ?>" class="text-decoration-none text-dark">
                                            <?php echo htmlspecialchars($item['titre']); ?>
                                        </a>
                                    </h6>
                                    <p class="text-muted mb-1 small">
                                        <i class="bi bi-person"></i> <?php echo htmlspecialchars($item['vendeur_prenom'] . ' ' . $item['vendeur_nom']); ?>
                                    </p>
                                    <?php
                                        $type_label = match ($item['type_vente']) {
                                            'achat_immediat' => 'Achat immédiat',
                                            'negociation' => 'Négociation',
                                            'meilleure_offre' => 'Enchère gagnée',
                                            default => ucfirst((string)$item['type_vente']),
                                        };
                                        $type_badge_class = match ($item['type_vente']) {
                                            'achat_immediat' => 'bg-primary-subtle text-primary',
                                            'negociation' => 'bg-warning-subtle text-warning-emphasis',
                                            'meilleure_offre' => 'bg-info-subtle text-info-emphasis',
                                            default => 'bg-light text-dark',
                                        };
                                    ?>
                                    <span class="badge <?php echo $type_badge_class; ?> mb-2"><?php echo htmlspecialchars($type_label); ?></span>
                                    <?php if (!empty($item['enchere_id'])): ?>
                                        <span class="text-muted small d-block">Retrait désactivé pour un article remporté aux enchères.</span>
                                    <?php elseif (!empty($item['negociation_id'])): ?>
                                        <span class="text-muted small d-block">Retirer cet article annulera l'accord et le remettra en vente.</span>
                                        <button class="btn btn-sm btn-outline-danger btn-remove-cart mt-1"
                                                data-article-id="<?php echo $item['article_id']; ?>"
                                                data-relist-on-remove="1">
                                            <i class="bi bi-trash3"></i> Retirer (remise en vente)
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-outline-danger btn-remove-cart" data-article-id="<?php echo $item['article_id']; ?>">
                                            <i class="bi bi-trash3"></i> Retirer
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <div class="text-end">
                                    <?php $prix_affiche = (float)$item['prix_final']; ?>
                                    <p class="fw-bold text-primary fs-5 mb-0"><?php echo number_format($prix_affiche, 2, ',', ' '); ?> &euro;</p>
                                    <?php if ($item['prix_negocie'] !== null): ?>
                                        <small class="text-muted">Prix validé</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card p-4 shadow-sm cart-summary" style="border-radius: 16px;">
                        <h5 class="fw-bold mb-3">Récapitulatif</h5>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Sous-total (<?php echo count($panier_items); ?> article<?php echo count($panier_items) > 1 ? 's' : ''; ?>)</span>
                            <span class="fw-semibold"><?php echo number_format($total, 2, ',', ' '); ?> &euro;</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Livraison</span>
                            <span class="text-success fw-semibold"><i class="bi bi-check-circle"></i> Gratuite</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold fs-5 mb-3">
                            <span>Total</span>
                            <span class="text-primary"><?php echo number_format($total, 2, ',', ' '); ?> &euro;</span>
                        </div>
                        <?php if ($total > 100): ?>
                            <div class="alert alert-success d-flex align-items-start gap-2 mb-3" style="border-radius: 10px;">
                                <i class="bi bi-gift-fill fs-5 mt-1"></i>
                                <div>
                                    <strong>Vous êtes éligible !</strong>
                                    <p class="mb-0 small">Carte de réduction de 10 à 20% après cet achat.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                        <a href="paiement.php" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-lock me-1"></i> Passer au paiement
                        </a>
                        <a href="tout_parcourir.php" class="btn btn-outline-secondary w-100 mt-2">
                            <i class="bi bi-arrow-left me-1"></i> Continuer mes achats
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                     style="width:100px;height:100px;background:rgba(var(--omnes-primary-rgb),0.1);">
                    <i class="bi bi-cart-x text-primary" style="font-size:3rem;"></i>
                </div>
                <h4 class="mt-2">Votre panier est vide</h4>
                <p class="text-muted mb-4">Parcourez notre marketplace pour trouver des articles intéressants !</p>
                <a href="tout_parcourir.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-search me-2"></i> Parcourir les articles
                </a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
