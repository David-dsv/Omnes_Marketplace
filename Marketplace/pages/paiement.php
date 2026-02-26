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

try {
    $stmt = $pdo->prepare("SELECT SUM(a.prix * p.quantite) AS total, COUNT(*) AS nb_items
                           FROM panier p
                           JOIN articles a ON p.article_id = a.id
                           WHERE p.utilisateur_id = :uid");
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $result = $stmt->fetch();
    $total = $result['total'] ?? 0;
    $nb_items = $result['nb_items'] ?? 0;
} catch (PDOException $e) {
    $total = 0;
    $nb_items = 0;
}

if ($total <= 0) {
    header('Location: panier.php');
    exit;
}
?>

<main class="py-4">
    <div class="container">
        <h1 class="h3 mb-4"><i class="bi bi-credit-card me-2"></i>Paiement</h1>

        <!-- Stepper -->
        <div class="payment-stepper mb-4">
            <div class="step completed">
                <div>
                    <div class="step-circle"><i class="bi bi-check"></i></div>
                    <div class="step-label">Panier</div>
                </div>
            </div>
            <div class="step-connector completed"></div>
            <div class="step active">
                <div>
                    <div class="step-circle">2</div>
                    <div class="step-label">Paiement</div>
                </div>
            </div>
            <div class="step-connector"></div>
            <div class="step">
                <div>
                    <div class="step-circle">3</div>
                    <div class="step-label">Confirmation</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Formulaire -->
            <div class="col-lg-8">
                <form method="POST" action="<?php echo $base_url; ?>php/paiement_actions.php" id="payment-form">
                    <input type="hidden" name="action" value="process">

                    <!-- Adresse de livraison -->
                    <div class="card p-4 mb-4 shadow-sm" style="border-radius: 16px;">
                        <h5 class="fw-bold mb-3"><i class="bi bi-truck me-2 text-primary"></i>Adresse de livraison</h5>
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
                                <input type="text" name="adresse" class="form-control" placeholder="Numéro et rue" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Code postal</label>
                                <input type="text" name="code_postal" class="form-control" pattern="[0-9]{5}" placeholder="75015" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Ville</label>
                                <input type="text" name="ville" class="form-control" placeholder="Paris" required>
                            </div>
                        </div>
                    </div>

                    <!-- Paiement -->
                    <div class="card p-4 mb-4 shadow-sm" style="border-radius: 16px;">
                        <h5 class="fw-bold mb-3"><i class="bi bi-credit-card-2-front me-2 text-primary"></i>Informations de paiement</h5>
                        <div class="card-type-icons mb-3">
                            <i class="fa-brands fa-cc-visa text-primary"></i>
                            <i class="fa-brands fa-cc-mastercard text-danger"></i>
                            <i class="fa-brands fa-cc-amex text-info"></i>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Numéro de carte</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-credit-card"></i></span>
                                    <input type="text" name="numero_carte" class="form-control" placeholder="1234 5678 9012 3456" maxlength="19" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date d'expiration</label>
                                <input type="text" name="expiration" class="form-control" placeholder="MM/AA" maxlength="5" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">CVV</label>
                                <div class="input-group">
                                    <input type="text" name="cvv" class="form-control" placeholder="123" maxlength="3" required>
                                    <span class="input-group-text bg-light" title="3 chiffres au dos de la carte"><i class="bi bi-question-circle"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100" style="border-radius: 12px;">
                        <i class="bi bi-lock me-2"></i>Payer <?php echo number_format($total, 2, ',', ' '); ?> &euro;
                    </button>
                    <p class="text-center text-muted small mt-2">
                        <i class="bi bi-shield-lock"></i> Paiement 100% sécurisé — Vos données sont protégées
                    </p>
                </form>
            </div>

            <!-- Récapitulatif -->
            <div class="col-lg-4">
                <div class="card p-4 shadow-sm cart-summary" style="border-radius: 16px;">
                    <h5 class="fw-bold mb-3">Récapitulatif</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted"><?php echo $nb_items; ?> article<?php echo $nb_items > 1 ? 's' : ''; ?></span>
                        <span><?php echo number_format($total, 2, ',', ' '); ?> &euro;</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Livraison</span>
                        <span class="text-success">Gratuite</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold fs-5">
                        <span>Total</span>
                        <span class="text-primary"><?php echo number_format($total, 2, ',', ' '); ?> &euro;</span>
                    </div>
                    <?php if ($total > 100): ?>
                        <div class="alert alert-success mt-3 d-flex align-items-center gap-2" style="border-radius: 10px;">
                            <i class="bi bi-gift-fill"></i>
                            <small>Carte de réduction attribuée après cet achat !</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
