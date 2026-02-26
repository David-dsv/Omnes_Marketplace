<?php
session_start();
$base_url = '../';
$page_title = 'Inscription';
require_once $base_url . 'config/database.php';
include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';

if (isset($_SESSION['user_id'])) {
    header('Location: compte.php');
    exit;
}

$error = $_GET['error'] ?? '';
?>

<main class="py-5">
    <div class="auth-container">
        <div class="card">
            <div class="text-center mb-4">
                <i class="bi bi-person-plus display-4 text-primary"></i>
                <h2 class="mt-2">Inscription</h2>
                <p class="text-muted">Créez votre compte acheteur</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="<?php echo $base_url; ?>php/auth.php">
                <input type="hidden" name="action" value="register">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="prenom" class="form-label">Prénom</label>
                        <input type="text" name="prenom" id="prenom" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="nom" class="form-label">Nom</label>
                        <input type="text" name="nom" id="nom" class="form-control" required>
                    </div>
                </div>

                <div class="mb-3 mt-3">
                    <label for="email" class="form-label">Adresse email</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="votre@email.com" required>
                </div>

                <div class="mb-3">
                    <label for="telephone" class="form-label">Téléphone</label>
                    <input type="tel" name="telephone" id="telephone" class="form-control" placeholder="06 12 34 56 78">
                </div>

                <div class="mb-3">
                    <label for="adresse" class="form-label">Adresse</label>
                    <input type="text" name="adresse" id="adresse" class="form-control">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input type="password" name="password" id="password" class="form-control" minlength="6" required>
                </div>

                <div class="mb-3">
                    <label for="password_confirm" class="form-label">Confirmer le mot de passe</label>
                    <input type="password" name="password_confirm" id="password_confirm" class="form-control" minlength="6" required>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3">Créer mon compte</button>
            </form>

            <hr>
            <p class="text-center mb-0">
                Déjà un compte ?
                <a href="connexion.php">Se connecter</a>
            </p>
        </div>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
