<?php
session_start();
$base_url = '../';

if (isset($_SESSION['user_id'])) {
    header('Location: compte.php');
    exit;
}

$page_title = 'Connexion';
require_once $base_url . 'config/database.php';
include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';

$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';
?>

<main class="auth-page">
    <div class="container-fluid p-0">
        <div class="auth-split">
            <!-- Illustration side -->
            <div class="auth-illustration">
                <img src="<?php echo $base_url; ?>images/Logo_Omnes_Éducation.svg.png" alt="Omnes Education" class="auth-logo">
                <h2>Bon retour parmi nous !</h2>
                <p>Connectez-vous pour accéder à vos articles favoris, votre panier et suivre vos commandes.</p>
                <div class="mt-4 d-flex gap-3">
                    <div class="text-center">
                        <i class="bi bi-shield-check fs-4"></i>
                        <small class="d-block mt-1">Paiement sécurisé</small>
                    </div>
                    <div class="text-center">
                        <i class="bi bi-chat-dots fs-4"></i>
                        <small class="d-block mt-1">Négociations</small>
                    </div>
                    <div class="text-center">
                        <i class="bi bi-hammer fs-4"></i>
                        <small class="d-block mt-1">Enchères live</small>
                    </div>
                </div>
            </div>

            <!-- Form side -->
            <div class="auth-form-side">
                <div class="auth-form-container">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                             style="width:70px;height:70px;background:linear-gradient(135deg,var(--omnes-primary),var(--omnes-accent));">
                            <i class="bi bi-person-fill text-white fs-2"></i>
                        </div>
                        <h2 class="fw-bold">Connexion</h2>
                        <p class="text-muted">Accédez à votre compte Omnes MarketPlace</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success d-flex align-items-center">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo $base_url; ?>php/auth.php">
                        <input type="hidden" name="action" value="login">

                        <div class="mb-3">
                            <label for="email" class="form-label">Adresse email</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" id="email" class="form-control border-start-0" placeholder="votre@email.com" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">Mot de passe</label>
                            <div class="input-group password-toggle">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" id="password" class="form-control border-start-0 border-end-0" placeholder="Votre mot de passe" required>
                                <span class="input-group-text bg-light border-start-0 toggle-password" style="cursor:pointer;">
                                    <i class="bi bi-eye"></i>
                                </span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                            <i class="bi bi-box-arrow-in-right me-2"></i> Se connecter
                        </button>
                    </form>

                    <div class="text-center mt-4">
                        <span class="text-muted">Pas encore de compte ?</span>
                        <a href="inscription.php" class="fw-semibold ms-1">Créer un compte</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
