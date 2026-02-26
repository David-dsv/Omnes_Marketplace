<?php
session_start();
$base_url = '../';
$page_title = 'Paiement';
require_once $base_url . 'config/database.php';
include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

// Récupérer le total du panier
try {
    $stmt = $pdo->prepare("SELECT SUM(a.prix * p.quantite) AS total
                           FROM panier p
                           JOIN articles a ON p.article_id = a.id
                           WHERE p.utilisateur_id = :uid");
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $result = $stmt->fetch();
    $total = $result['total'] ?? 0;
} catch (PDOException $e) {
    $total = 0;
}

if ($total <= 0) {
    header('Location: panier.php');
    exit;
}
?>

<main class="py-4">
    <div class="container">
        <h1 class="mb-4"><i class="bi bi-credit-card"></i> Paiement</h1>

        <div class="row">
            <!-- Formulaire de paiement -->
            <div class="col-lg-8">
                <form method="POST" action="<?php echo $base_url; ?>php/paiement_actions.php" id="payment-form">
                    <input type="hidden" name="action" value="process">

                    <!-- Adresse de livraison -->
                    <div class="card p-4 mb-4 shadow-sm">
                        <h5><i class="bi bi-truck"></i> Adresse de livraison</h5>
                        <hr>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Prénom</label>
                                <input type="text" name="prenom" class="form-control" value="<?php echo htmlspecialchars($_SESSION['user_prenom'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nom</label>
                                <input type="text" name="nom" class="form-control" value="<?php echo htmlspecialchars($_SESSION['user_nom'] ?? ''); ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Adresse</label>
                                <input type="text" name="adresse" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Code postal</label>
                                <input type="text" name="code_postal" class="form-control" pattern="[0-9]{5}" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Ville</label>
                                <input type="text" name="ville" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <!-- Informations de paiement -->
                    <div class="card p-4 mb-4 shadow-sm">
                        <h5><i class="bi bi-credit-card-2-front"></i> Informations de paiement</h5>
                        <hr>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Numéro de carte</label>
                                <input type="text" name="numero_carte" class="form-control" placeholder="1234 5678 9012 3456" maxlength="19" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date d'expiration</label>
                                <input type="text" name="expiration" class="form-control" placeholder="MM/AA" maxlength="5" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">CVV</label>
                                <input type="text" name="cvv" class="form-control" placeholder="123" maxlength="3" required>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="bi bi-lock"></i> Payer <?php echo number_format($total, 2, ',', ' '); ?> &euro;
                    </button>
                </form>
            </div>

            <!-- Récapitulatif -->
            <div class="col-lg-4">
                <div class="card p-4 shadow-sm">
                    <h5>Récapitulatif</h5>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold fs-5">
                        <span>Total</span>
                        <span class="text-primary"><?php echo number_format($total, 2, ',', ' '); ?> &euro;</span>
                    </div>
                    <?php if ($total > 100): ?>
                        <div class="alert alert-success mt-3 small">
                            <i class="bi bi-gift"></i> Une carte de réduction vous sera attribuée après cet achat !
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
