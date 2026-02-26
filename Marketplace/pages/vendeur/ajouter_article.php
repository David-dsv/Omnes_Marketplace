<?php
session_start();
$base_url = '../../';
$page_title = 'Ajouter un article';
require_once $base_url . 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'vendeur') {
    header('Location: ' . $base_url . 'pages/connexion.php');
    exit;
}

$error = $_GET['error'] ?? '';

include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';
?>

<main class="py-4">
    <div class="container" style="max-width: 800px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1"><i class="bi bi-plus-circle me-2"></i>Ajouter un article</h1>
                <p class="text-muted mb-0">Remplissez les informations pour publier votre article</p>
            </div>
            <a href="mes_articles.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Retour</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="card p-4 shadow-sm animate-on-scroll" style="border-radius: 16px;">
            <form method="POST" action="<?php echo $base_url; ?>php/article_actions.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Titre de l'article</label>
                        <input type="text" name="titre" class="form-control" placeholder="Ex: MacBook Pro 2024" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Prix (&euro;)</label>
                        <div class="input-group">
                            <input type="number" name="prix" class="form-control" step="0.01" min="0.01" placeholder="99.99" required>
                            <span class="input-group-text">&euro;</span>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="4" placeholder="Décrivez votre article en détail (état, caractéristiques, raison de la vente...)" required></textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Catégorie</label>
                        <select name="categorie" class="form-select" required>
                            <option value="">Choisir...</option>
                            <option value="Électronique">Électronique</option>
                            <option value="Vêtements">Vêtements</option>
                            <option value="Maison">Maison</option>
                            <option value="Livres">Livres</option>
                            <option value="Sports">Sports</option>
                            <option value="Divers">Divers</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Type de vente</label>
                        <select name="type_vente" class="form-select" required>
                            <option value="">Choisir...</option>
                            <option value="achat_immediat">Achat immédiat</option>
                            <option value="negociation">Négociation</option>
                            <option value="enchere">Enchère (meilleure offre)</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Gamme</label>
                        <select name="gamme" class="form-select" required>
                            <option value="">Choisir...</option>
                            <option value="regulier">Article régulier</option>
                            <option value="haut_de_gamme">Haut de gamme</option>
                            <option value="rare">Article rare</option>
                        </select>
                    </div>

                    <!-- Auction fields -->
                    <div class="col-md-6" id="enchere-fields" style="display:none;">
                        <label class="form-label fw-semibold">Date de fin d'enchère</label>
                        <input type="datetime-local" name="date_fin_enchere" class="form-control">
                        <small class="text-muted">L'enchère se termine automatiquement à cette date</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Image de l'article</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <small class="text-muted">Formats acceptés : JPG, PNG, WEBP (max 5 Mo)</small>
                    </div>
                </div>

                <hr class="my-4">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-check-lg me-2"></i>Publier l'article
                    </button>
                    <a href="mes_articles.php" class="btn btn-outline-secondary btn-lg">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
    document.querySelector('[name="type_vente"]').addEventListener('change', function() {
        document.getElementById('enchere-fields').style.display =
            this.value === 'enchere' ? 'block' : 'none';
    });
</script>

<?php include $base_url . 'includes/footer.php'; ?>
