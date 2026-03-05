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

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Description — Qualités</label>
                        <textarea name="description_qualite" class="form-control" rows="3" placeholder="Points forts, caractéristiques, état général..." required></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Description — Défauts</label>
                        <textarea name="description_defaut" class="form-control" rows="3" placeholder="Défauts, usures, rayures, pièces manquantes..."></textarea>
                        <small class="text-muted">Laisser vide si aucun défaut</small>
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
                        <select name="type_vente" id="type-vente-select" class="form-select" required>
                            <option value="">Choisir...</option>
                            <option value="achat_immediat">Achat immédiat</option>
                            <option value="negociation">Négociation</option>
                            <option value="meilleure_offre">Meilleure Offre</option>
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

                    <!-- Champs spécifiques Meilleure Offre -->
                    <div id="auction-fields" class="col-12" style="display: none;">
                        <div class="card p-3 mb-2" style="background: linear-gradient(135deg, #fff8e1, #fffbf0); border: 1px dashed #ffc107; border-radius: 12px;">
                            <h6 class="fw-semibold mb-3"><i class="bi bi-clock-history me-1"></i>Période d'enchères</h6>
                            <p class="text-muted small mb-3">Définissez la période pendant laquelle les acheteurs pourront soumettre leurs enchères. Le prix ci-dessus sera utilisé comme prix de réserve minimum.</p>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Début des enchères</label>
                                    <input type="datetime-local" name="date_debut_enchere" id="date-debut-enchere" class="form-control">
                                    <small class="text-muted">Laisser vide = commence immédiatement</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Fin des enchères</label>
                                    <input type="datetime-local" name="date_fin_enchere" id="date-fin-enchere" class="form-control">
                                    <small class="text-muted">Date/heure de clôture des enchères</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Photo principale</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <small class="text-muted">Formats : JPG, PNG, WEBP (max 5 Mo)</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Photos supplémentaires</label>
                        <input type="file" name="images_supplementaires[]" class="form-control" accept="image/*" multiple>
                        <small class="text-muted">Jusqu'à 4 photos supplémentaires</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Vidéo (optionnel)</label>
                        <input type="url" name="video_url" class="form-control" placeholder="https://www.youtube.com/watch?v=... ou lien direct vers la vidéo">
                        <small class="text-muted">Lien YouTube ou URL directe de la vidéo</small>
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
document.addEventListener('DOMContentLoaded', function() {
    var typeSelect = document.getElementById('type-vente-select');
    var auctionFields = document.getElementById('auction-fields');
    var dateFinInput = document.getElementById('date-fin-enchere');

    function toggleAuctionFields() {
        if (typeSelect.value === 'meilleure_offre') {
            auctionFields.style.display = 'block';
            dateFinInput.required = true;
        } else {
            auctionFields.style.display = 'none';
            dateFinInput.required = false;
        }
    }

    typeSelect.addEventListener('change', toggleAuctionFields);
    toggleAuctionFields();
});
</script>

<?php include $base_url . 'includes/footer.php'; ?>
