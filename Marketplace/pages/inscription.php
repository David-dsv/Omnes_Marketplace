<?php
session_start();
$base_url = '../';

if (isset($_SESSION['user_id'])) {
    header('Location: compte.php');
    exit;
}

$page_title = 'Inscription';
require_once $base_url . 'config/database.php';
include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';

$error = $_GET['error'] ?? '';
?>

<main class="auth-page">
    <div class="container-fluid p-0">
        <div class="auth-split">
            <!-- Illustration side -->
            <div class="auth-illustration">
                <i class="bi bi-person-plus display-1"></i>
                <h2>Rejoignez la communauté !</h2>
                <p>Créez votre compte pour acheter, vendre, enchérir et négocier avec les étudiants Omnes Education.</p>
                <div class="mt-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Inscription gratuite et rapide</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Carte de réduction dès 100€ d'achats</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Enchères et négociations exclusives</span>
                    </div>
                </div>
            </div>

            <!-- Form side -->
            <div class="auth-form-side">
                <div class="auth-form-container">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                             style="width:70px;height:70px;background:linear-gradient(135deg,var(--omnes-primary),var(--omnes-accent));">
                            <i class="bi bi-person-plus-fill text-white fs-2"></i>
                        </div>
                        <h2 class="fw-bold">Inscription</h2>
                        <p class="text-muted">Créez votre compte acheteur</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo $base_url; ?>php/auth.php">
                        <input type="hidden" name="action" value="register">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="prenom" class="form-label">Prénom</label>
                                <input type="text" name="prenom" id="prenom" class="form-control" placeholder="Jean" required>
                            </div>
                            <div class="col-md-6">
                                <label for="nom" class="form-label">Nom</label>
                                <input type="text" name="nom" id="nom" class="form-control" placeholder="Dupont" required>
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label for="email" class="form-label">Adresse email</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" id="email" class="form-control border-start-0" placeholder="votre@email.com" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="telephone" class="form-label">Téléphone</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-telephone"></i></span>
                                <input type="tel" name="telephone" id="telephone" class="form-control border-start-0" placeholder="06 12 34 56 78">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="adresse" class="form-label">Adresse</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-geo-alt"></i></span>
                                <input type="text" name="adresse" id="adresse" class="form-control border-start-0" placeholder="Votre adresse">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <div class="input-group password-toggle">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" id="password" class="form-control border-start-0 border-end-0" minlength="6" placeholder="Minimum 6 caractères" required>
                                <span class="input-group-text bg-light border-start-0 toggle-password" style="cursor:pointer;">
                                    <i class="bi bi-eye"></i>
                                </span>
                            </div>
                            <div class="password-strength mt-2">
                                <div class="password-strength-bar"></div>
                            </div>
                            <small class="text-muted">Utilisez majuscules, chiffres et caractères spéciaux</small>
                        </div>

                        <div class="mb-4">
                            <label for="password_confirm" class="form-label">Confirmer le mot de passe</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" name="password_confirm" id="password_confirm" class="form-control border-start-0" minlength="6" placeholder="Répétez votre mot de passe" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                            <i class="bi bi-person-plus me-2"></i> Créer mon compte
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <span class="text-muted">Déjà un compte ?</span>
                        <a href="connexion.php" class="fw-semibold ms-1">Se connecter</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
