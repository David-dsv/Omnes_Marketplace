<?php
session_start();
$base_url = '../';

if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

require_once $base_url . 'config/database.php';
$user_prenom = $_SESSION['user_prenom'] ?? '';
$user_nom = $_SESSION['user_nom'] ?? '';
$adresse_compte = '';
$panier_items = [];

try {
    $stmt = $pdo->prepare("SELECT SUM(COALESCE(p.prix_negocie, a.prix) * p.quantite) AS total, COUNT(*) AS nb_items
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

try {
    $stmt_items = $pdo->prepare("SELECT a.titre, a.type_vente, COALESCE(p.prix_negocie, a.prix) AS prix_final,
                                        p.quantite, (COALESCE(p.prix_negocie, a.prix) * p.quantite) AS total_ligne
                                 FROM panier p
                                 JOIN articles a ON p.article_id = a.id
                                 WHERE p.utilisateur_id = :uid
                                 ORDER BY p.date_ajout DESC");
    $stmt_items->execute([':uid' => $_SESSION['user_id']]);
    $panier_items = $stmt_items->fetchAll();
} catch (PDOException $e) {
    $panier_items = [];
}

try {
    $stmt_user = $pdo->prepare("SELECT prenom, nom, adresse FROM utilisateurs WHERE id = :uid");
    $stmt_user->execute([':uid' => $_SESSION['user_id']]);
    $user = $stmt_user->fetch();

    if ($user) {
        $user_prenom = $user['prenom'] ?: $user_prenom;
        $user_nom = $user['nom'] ?: $user_nom;
        $adresse_compte = trim((string)($user['adresse'] ?? ''));
    }
} catch (PDOException $e) {
    $adresse_compte = '';
}

if ($total <= 0) {
    header('Location: panier.php');
    exit;
}

$adresse_ligne = '';
$code_postal = '';
$ville = '';

if ($adresse_compte !== '') {
    if (preg_match('/^(.*?)(?:,\s*)?([0-9]{5})\s+(.+)$/u', $adresse_compte, $matches)) {
        $adresse_ligne = trim($matches[1]);
        $code_postal = trim($matches[2]);
        $ville = trim($matches[3]);
    } else {
        $adresse_ligne = $adresse_compte;
    }
}

$has_saved_address = ($adresse_ligne !== '' || $code_postal !== '' || $ville !== '');
$custom_address_default = !$has_saved_address;
$error_message = trim((string)($_GET['error'] ?? ''));

$page_title = 'Paiement';
include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';
?>

<main class="py-4">
    <div class="container">
        <h1 class="h3 mb-4"><i class="bi bi-credit-card me-2"></i>Paiement</h1>
        <?php if ($error_message !== ''): ?>
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i>
            Paiement simulé (projet étudiant) : aucun débit réel ne sera effectué.
        </div>

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
                        <?php if ($has_saved_address): ?>
                            <p class="text-muted small mb-3">
                                Adresse enregistrée détectée sur ton compte.
                            </p>
                        <?php else: ?>
                            <p class="text-muted small mb-3">
                                Aucune adresse enregistrée sur ton compte. Merci de renseigner une adresse de livraison.
                            </p>
                        <?php endif; ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Prénom</label>
                                <input type="text" name="prenom" class="form-control" value="<?php echo htmlspecialchars($user_prenom); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nom</label>
                                <input type="text" name="nom" class="form-control" value="<?php echo htmlspecialchars($user_nom); ?>" required>
                            </div>
                        </div>

                        <?php if ($has_saved_address): ?>
                        <div class="row g-3 mt-1">
                            <div class="col-12">
                                <label class="form-label">Adresse enregistrée</label>
                                <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($adresse_ligne); ?>" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Code postal</label>
                                <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($code_postal); ?>" readonly>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Ville</label>
                                <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($ville); ?>" readonly>
                            </div>
                            <div class="col-12">
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="checkbox" id="use-custom-address" <?php echo $custom_address_default ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="use-custom-address">
                                        Définir une autre adresse de livraison
                                    </label>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div
                            id="custom-delivery-address-fields"
                            class="row g-3 mt-2 <?php echo $custom_address_default ? '' : 'd-none'; ?>"
                        >
                            <div class="col-12">
                                <label class="form-label"><?php echo $has_saved_address ? 'Autre adresse' : 'Adresse'; ?></label>
                                <input type="text" id="custom-delivery-address" class="form-control" placeholder="Numéro et rue" <?php echo !$has_saved_address ? 'required' : ''; ?>>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label"><?php echo $has_saved_address ? 'Autre code postal' : 'Code postal'; ?></label>
                                <input type="text" id="custom-delivery-postal-code" class="form-control" pattern="[0-9]{5}" placeholder="75015" <?php echo !$has_saved_address ? 'required' : ''; ?>>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label"><?php echo $has_saved_address ? 'Autre ville' : 'Ville'; ?></label>
                                <input type="text" id="custom-delivery-city" class="form-control" placeholder="Paris" <?php echo !$has_saved_address ? 'required' : ''; ?>>
                            </div>
                        </div>

                        <input type="hidden" name="adresse" id="delivery-address-hidden" value="<?php echo htmlspecialchars($adresse_ligne); ?>">
                        <input type="hidden" name="code_postal" id="delivery-postal-code-hidden" value="<?php echo htmlspecialchars($code_postal); ?>">
                        <input type="hidden" name="ville" id="delivery-city-hidden" value="<?php echo htmlspecialchars($ville); ?>">
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
                                    <input
                                        type="text"
                                        name="numero_carte"
                                        id="card-number-input"
                                        class="form-control"
                                        placeholder="1234567890123456"
                                        inputmode="numeric"
                                        autocomplete="cc-number"
                                        minlength="16"
                                        maxlength="16"
                                        pattern="[0-9]{16}"
                                        title="Le numéro de carte doit contenir exactement 16 chiffres."
                                        required
                                    >
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date d'expiration</label>
                                <input type="text" name="expiration" class="form-control" placeholder="MM/AA" maxlength="5" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">CVV</label>
                                <div class="input-group">
                                    <input
                                        type="text"
                                        name="cvv"
                                        id="card-cvv-input"
                                        class="form-control"
                                        placeholder="123"
                                        inputmode="numeric"
                                        autocomplete="cc-csc"
                                        minlength="3"
                                        maxlength="3"
                                        pattern="[0-9]{3}"
                                        title="Le CVV doit contenir exactement 3 chiffres."
                                        required
                                    >
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
                    <?php if (!empty($panier_items)): ?>
                        <div class="mb-3">
                            <?php foreach ($panier_items as $item): ?>
                                <div class="d-flex justify-content-between align-items-start small mb-1">
                                    <span class="text-muted text-truncate me-2" style="max-width: 72%;" title="<?php echo htmlspecialchars($item['titre']); ?>">
                                        <?php echo htmlspecialchars($item['titre']); ?><?php echo (int)$item['quantite'] > 1 ? ' x' . (int)$item['quantite'] : ''; ?>
                                    </span>
                                    <span class="text-nowrap"><?php echo number_format((float)$item['total_ligne'], 2, ',', ' '); ?> &euro;</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Sous-total (<?php echo $nb_items; ?> article<?php echo $nb_items > 1 ? 's' : ''; ?>)</span>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    var checkbox = document.getElementById('use-custom-address');
    var customAddressFields = document.getElementById('custom-delivery-address-fields');
    var customAddress = document.getElementById('custom-delivery-address');
    var customPostalCode = document.getElementById('custom-delivery-postal-code');
    var customCity = document.getElementById('custom-delivery-city');
    var hiddenAddress = document.getElementById('delivery-address-hidden');
    var hiddenPostalCode = document.getElementById('delivery-postal-code-hidden');
    var hiddenCity = document.getElementById('delivery-city-hidden');
    var form = document.getElementById('payment-form');
    var cardNumberInput = document.getElementById('card-number-input');
    var cardCvvInput = document.getElementById('card-cvv-input');

    function forceDigits(input, maxLength) {
        if (!input) return;
        input.addEventListener('input', function () {
            input.value = input.value.replace(/\D/g, '').slice(0, maxLength);
        });
    }

    forceDigits(cardNumberInput, 16);
    forceDigits(cardCvvInput, 3);

    if (!customAddress || !customPostalCode || !customCity || !hiddenAddress || !hiddenPostalCode || !hiddenCity || !form) {
        return;
    }

    var savedAddress = <?php echo json_encode($adresse_ligne); ?>;
    var savedPostalCode = <?php echo json_encode($code_postal); ?>;
    var savedCity = <?php echo json_encode($ville); ?>;
    var hasSavedAddress = <?php echo $has_saved_address ? 'true' : 'false'; ?>;

    function useCustomAddressMode() {
        return !hasSavedAddress || (checkbox && checkbox.checked);
    }

    function syncCustomAddressUI(clearFields) {
        if (!customAddressFields) return;

        if (useCustomAddressMode()) {
            customAddressFields.classList.remove('d-none');
            customAddress.required = true;
            customPostalCode.required = true;
            customCity.required = true;
            if (clearFields) {
                customAddress.value = '';
                customPostalCode.value = '';
                customCity.value = '';
            }
        } else {
            customAddressFields.classList.add('d-none');
            customAddress.required = false;
            customPostalCode.required = false;
            customCity.required = false;
        }
    }

    if (checkbox) {
        checkbox.addEventListener('change', function () {
            syncCustomAddressUI(true);
        });
    }

    form.addEventListener('submit', function () {
        if (useCustomAddressMode()) {
            hiddenAddress.value = customAddress.value.trim();
            hiddenPostalCode.value = customPostalCode.value.trim();
            hiddenCity.value = customCity.value.trim();
        } else {
            hiddenAddress.value = savedAddress || '';
            hiddenPostalCode.value = savedPostalCode || '';
            hiddenCity.value = savedCity || '';
        }
    });

    syncCustomAddressUI(false);
});
</script>

<?php include $base_url . 'includes/footer.php'; ?>
