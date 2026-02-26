<?php
session_start();
$base_url = '../';
$page_title = 'Connexion';
require_once $base_url . 'config/database.php';
include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';

// Rediriger si déjà connecté
if (isset($_SESSION['user_id'])) {
    header('Location: compte.php');
    exit;
}

$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';
?>

<main class="py-5">
    <div class="auth-container">
        <div class="card">
            <div class="text-center mb-4">
                <i class="bi bi-shop display-4 text-primary"></i>
                <h2 class="mt-2">Connexion</h2>
                <p class="text-muted">Accédez à votre compte Omnes MarketPlace</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" action="<?php echo $base_url; ?>php/auth.php">
                <input type="hidden" name="action" value="login">

                <div class="mb-3">
                    <label for="email" class="form-label">Adresse email</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="votre@email.com" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Votre mot de passe" required>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3">Se connecter</button>
            </form>

            <hr>
            <p class="text-center mb-0">
                Pas encore de compte ?
                <a href="inscription.php">Créer un compte</a>
            </p>
        </div>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
