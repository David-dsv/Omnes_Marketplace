<?php
session_start();
$base_url = '../';
$page_title = 'Détail article';
require_once $base_url . 'config/database.php';

$article_id = (int)($_GET['id'] ?? 0);
if ($article_id <= 0) {
    header('Location: tout_parcourir.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT a.*, u.prenom AS vendeur_prenom, u.nom AS vendeur_nom, u.email AS vendeur_email, u.date_creation AS vendeur_depuis
                           FROM articles a
                           JOIN utilisateurs u ON a.vendeur_id = u.id
                           WHERE a.id = :id");
    $stmt->execute([':id' => $article_id]);
    $article = $stmt->fetch();
} catch (PDOException $e) {
    $article = null;
}

if (!$article) {
    header('Location: tout_parcourir.php');
    exit;
}

$page_title = $article['titre'];

// Avis
try {
    $stmt_avis = $pdo->prepare("SELECT av.*, u.prenom, u.nom FROM avis av JOIN utilisateurs u ON av.auteur_id = u.id WHERE av.article_id = :id ORDER BY av.date_creation DESC");
    $stmt_avis->execute([':id' => $article_id]);
    $avis_list = $stmt_avis->fetchAll();
    $avg_rating = 0;
    if (count($avis_list) > 0) {
        $avg_rating = array_sum(array_column($avis_list, 'note')) / count($avis_list);
    }
} catch (PDOException $e) {
    $avis_list = [];
    $avg_rating = 0;
}

// Articles similaires
try {
    $stmt_similar = $pdo->prepare("SELECT a.*, u.prenom AS vendeur_prenom FROM articles a JOIN utilisateurs u ON a.vendeur_id = u.id WHERE a.categorie = :cat AND a.id != :id AND a.statut = 'disponible' ORDER BY RAND() LIMIT 4");
    $stmt_similar->execute([':cat' => $article['categorie'], ':id' => $article_id]);
    $similar_articles = $stmt_similar->fetchAll();
} catch (PDOException $e) {
    $similar_articles = [];
}

include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';
?>

<main class="py-4">
    <div class="container">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>index.php"><i class="bi bi-house"></i> Accueil</a></li>
                <li class="breadcrumb-item"><a href="tout_parcourir.php">Tout Parcourir</a></li>
                <li class="breadcrumb-item"><a href="tout_parcourir.php?categorie=<?php echo urlencode($article['categorie']); ?>"><?php echo htmlspecialchars($article['categorie']); ?></a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($article['titre']); ?></li>
            </ol>
        </nav>

        <div class="row g-4">
            <!-- Photos -->
            <div class="col-lg-6 mb-4 animate-on-scroll">
                <?php
                // Récupérer les photos supplémentaires
                try {
                    $stmt_photos = $pdo->prepare("SELECT image_url FROM article_images WHERE article_id = :aid ORDER BY position");
                    $stmt_photos->execute([':aid' => $article_id]);
                    $extra_photos = $stmt_photos->fetchAll(PDO::FETCH_COLUMN);
                } catch (PDOException $e) {
                    $extra_photos = [];
                }
                $all_photos = array_merge(
                    [$article['image_url'] ?? 'images/articles/placeholder.png'],
                    $extra_photos
                );
                ?>
                <?php if (count($all_photos) > 1): ?>
                    <!-- Carrousel multi-photos -->
                    <div id="articlePhotosCarousel" class="carousel slide" data-bs-ride="carousel" style="border-radius: 16px; overflow: hidden;">
                        <div class="carousel-indicators">
                            <?php foreach ($all_photos as $i => $photo): ?>
                                <button type="button" data-bs-target="#articlePhotosCarousel" data-bs-slide-to="<?php echo $i; ?>" <?php echo $i === 0 ? 'class="active"' : ''; ?>></button>
                            <?php endforeach; ?>
                        </div>
                        <div class="carousel-inner">
                            <?php foreach ($all_photos as $i => $photo): ?>
                                <div class="carousel-item <?php echo $i === 0 ? 'active' : ''; ?>">
                                    <div class="position-relative">
                                        <img src="<?php echo $base_url . htmlspecialchars($photo); ?>"
                                             class="d-block w-100" style="max-height: 500px; object-fit: cover;"
                                             alt="<?php echo htmlspecialchars($article['titre']); ?>">
                                        <?php if ($i === 0): ?>
                                            <span class="badge badge-<?php echo $article['gamme']; ?> badge-gamme" style="position:absolute;top:16px;left:16px;font-size:0.85rem;padding:0.5em 1em;">
                                                <?php echo htmlspecialchars($article['gamme']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#articlePhotosCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#articlePhotosCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon"></span>
                        </button>
                    </div>
                    <!-- Thumbnails -->
                    <div class="d-flex gap-2 mt-2">
                        <?php foreach ($all_photos as $i => $photo): ?>
                            <img src="<?php echo $base_url . htmlspecialchars($photo); ?>"
                                 class="rounded" style="width:60px;height:60px;object-fit:cover;cursor:pointer;border:2px solid <?php echo $i === 0 ? 'var(--omnes-primary)' : '#dee2e6'; ?>;"
                                 onclick="document.querySelector('#articlePhotosCarousel').querySelector('[data-bs-slide-to=\'<?php echo $i; ?>\']').click()"
                                 alt="Photo <?php echo $i + 1; ?>">
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="card overflow-hidden" style="border-radius: 16px;">
                        <div class="position-relative">
                            <img src="<?php echo $base_url . htmlspecialchars($article['image_url'] ?? 'images/articles/placeholder.png'); ?>"
                                 class="img-fluid w-100" style="max-height: 500px; object-fit: cover;"
                                 alt="<?php echo htmlspecialchars($article['titre']); ?>">
                            <span class="badge badge-<?php echo $article['gamme']; ?> badge-gamme" style="position:absolute;top:16px;left:16px;font-size:0.85rem;padding:0.5em 1em;">
                                <?php echo htmlspecialchars($article['gamme']); ?>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($article['video_url'])): ?>
                    <div class="mt-3">
                        <?php
                        $video = $article['video_url'];
                        // Convertir URL YouTube en embed
                        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $video, $yt_match)):
                        ?>
                            <div class="ratio ratio-16x9" style="border-radius: 12px; overflow: hidden;">
                                <iframe src="https://www.youtube.com/embed/<?php echo htmlspecialchars($yt_match[1]); ?>" allowfullscreen></iframe>
                            </div>
                        <?php else: ?>
                            <video controls class="w-100 rounded" style="max-height:300px;">
                                <source src="<?php echo htmlspecialchars($video); ?>">
                            </video>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Détails -->
            <div class="col-lg-6 animate-on-scroll animate-delay-1">
                <h1 class="h2 fw-bold mb-2"><?php echo htmlspecialchars($article['titre']); ?></h1>

                <!-- Rating summary -->
                <?php if (!empty($avis_list)): ?>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="star-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi <?php echo $i <= round($avg_rating) ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="text-muted">(<?php echo count($avis_list); ?> avis)</span>
                    </div>
                <?php endif; ?>

                <!-- Badges -->
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge badge-<?php echo $article['type_vente']; ?> px-3 py-2">
                        <?php
                            echo match ($article['type_vente']) {
                                'achat_immediat' => 'Achat immédiat',
                                'negociation' => 'Négociation',
                                'meilleure_offre' => 'Meilleure offre',
                                default => htmlspecialchars((string)$article['type_vente']),
                            };
                        ?>
                    </span>
                    <span class="badge bg-light text-dark border px-3 py-2">
                        <i class="bi bi-tag"></i> <?php echo htmlspecialchars($article['categorie']); ?>
                    </span>
                </div>

                <!-- Price -->
                <div class="card p-3 mb-3" style="background: linear-gradient(135deg, #f0f4ff, #e8f0fe); border: none; border-radius: 12px;">
                    <p class="fs-2 fw-bold text-primary mb-0"><?php echo number_format($article['prix'], 2, ',', ' '); ?> &euro;</p>
                </div>

                <!-- Description -->
                <div class="mb-4">
                    <?php if (!empty($article['description_qualite'])): ?>
                        <h6 class="fw-bold text-muted text-uppercase small">Qualités</h6>
                        <p class="lh-lg"><?php echo nl2br(htmlspecialchars($article['description_qualite'])); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($article['description_defaut'])): ?>
                        <h6 class="fw-bold text-muted text-uppercase small mt-3">Défauts</h6>
                        <p class="lh-lg text-muted"><?php echo nl2br(htmlspecialchars($article['description_defaut'])); ?></p>
                    <?php endif; ?>
                    <?php if (empty($article['description_qualite']) && !empty($article['description'])): ?>
                        <h6 class="fw-bold text-muted text-uppercase small">Description</h6>
                        <p class="lh-lg"><?php echo nl2br(htmlspecialchars($article['description'])); ?></p>
                    <?php endif; ?>
                </div>

                <!-- Identifiant article -->
                <div class="mb-3">
                    <small class="text-muted">N° d'identification : <strong>#<?php echo $article['id']; ?></strong></small>
                </div>

                <!-- Seller info -->
                <div class="card p-3 mb-4" style="border: 1px solid #e9ecef; border-radius: 12px;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="account-avatar" style="width:50px;height:50px;border-width:2px;">
                            <div class="avatar-initials" style="font-size:1rem;">
                                <?php echo strtoupper(substr($article['vendeur_prenom'], 0, 1) . substr($article['vendeur_nom'], 0, 1)); ?>
                            </div>
                        </div>
                        <div>
                            <strong><?php echo htmlspecialchars($article['vendeur_prenom'] . ' ' . $article['vendeur_nom']); ?></strong>
                            <small class="d-block text-muted">Vendeur depuis <?php echo date('m/Y', strtotime($article['vendeur_depuis'])); ?></small>
                        </div>
                    </div>
                </div>

                <!-- Action buttons -->
                <div class="d-grid gap-2">
                    <?php if ($article['type_vente'] === 'achat_immediat'): ?>
                        <?php if ($article['statut'] === 'disponible'): ?>
                            <button class="btn btn-primary btn-lg btn-add-cart" data-article-id="<?php echo $article['id']; ?>">
                                Ajouter au panier
                            </button>
                        <?php else: ?>
                            <button class="btn btn-secondary btn-lg" disabled>Article vendu</button>
                        <?php endif; ?>

                    <?php elseif ($article['type_vente'] === 'negociation'): ?>
                        <?php if ($article['statut'] === 'disponible'): ?>
                            <a href="negociation.php?article_id=<?php echo $article['id']; ?>" class="btn btn-warning btn-lg">
                                Négocier le prix
                            </a>
                        <?php else: ?>
                            <button class="btn btn-secondary btn-lg" disabled>Négociation terminée - article vendu</button>
                        <?php endif; ?>

                    <?php elseif ($article['type_vente'] === 'meilleure_offre'): ?>
                        <?php
                            $now = new DateTime();
                            $date_debut = $article['date_debut_enchere'] ? new DateTime($article['date_debut_enchere']) : $now;
                            $date_fin = $article['date_fin_enchere'] ? new DateTime($article['date_fin_enchere']) : null;
                            $auction_started = $date_fin && $now >= $date_debut;
                            $auction_active = $date_fin && $now >= $date_debut && $now < $date_fin;
                            $auction_ended = $date_fin && $now >= $date_fin;

                            // Nombre d'enchérisseurs (sans révéler les montants — enchères scellées)
                            $stmt_bids = $pdo->prepare("SELECT COUNT(*) FROM encheres WHERE article_id = :id");
                            $stmt_bids->execute([':id' => $article_id]);
                            $nb_bidders = (int)$stmt_bids->fetchColumn();

                            // Vérifier si l'utilisateur courant a déjà enchéri
                            $user_bid = null;
                            if (isset($_SESSION['user_id'])) {
                                $stmt_ubid = $pdo->prepare("SELECT * FROM encheres WHERE article_id = :aid AND acheteur_id = :uid");
                                $stmt_ubid->execute([':aid' => $article_id, ':uid' => $_SESSION['user_id']]);
                                $user_bid = $stmt_ubid->fetch();
                            }
                        ?>

                        <!-- Bloc Enchères -->
                        <div class="card p-3 mb-3" style="background: linear-gradient(135deg, #fff8e1, #fffbf0); border: 1px solid #ffc107; border-radius: 12px;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong class="text-dark">Meilleure Offre</strong>
                                <span class="badge bg-warning text-dark"><?php echo $nb_bidders; ?> enchère<?php echo $nb_bidders > 1 ? 's' : ''; ?></span>
                            </div>

                            <?php if ($date_fin): ?>
                                <div class="mb-2">
                                    <small class="text-muted d-block">Du <?php echo $date_debut->format('d/m/Y H:i'); ?> au <?php echo $date_fin->format('d/m/Y H:i'); ?></small>
                                </div>

                                <?php if ($auction_active): ?>
                                    <div class="countdown-timer mb-2" id="countdown-timer" data-end-time="<?php echo $article['date_fin_enchere']; ?>">
                                        <small class="text-muted">Temps restant : </small>
                                        <strong id="countdown-display" class="text-primary" style="font-family: 'Courier New', monospace; font-size: 1.1rem;">--:--:--</strong>
                                    </div>
                                <?php elseif ($auction_ended): ?>
                                    <div class="mb-2">
                                        <span class="badge bg-secondary">Enchères terminées</span>
                                    </div>
                                <?php else: ?>
                                    <div class="mb-2">
                                        <span class="badge bg-info text-dark">Enchères pas encore commencées</span>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if ($user_bid): ?>
                                <div class="alert alert-info small mb-0 mt-2" style="border-radius: 10px;">
                                    <i class="bi bi-check-circle me-1"></i>
                                    Vous avez déjà enchéri. Votre enchère max : <strong><?php echo number_format($user_bid['montant_max'], 2, ',', ' '); ?> €</strong>
                                    <?php if ($user_bid['statut'] === 'gagnant'): ?>
                                        <br><span class="badge bg-success mt-1">Vous avez gagné !</span>
                                    <?php elseif ($user_bid['statut'] === 'perdant'): ?>
                                        <br><span class="badge bg-danger mt-1">Enchère perdue</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="alert alert-info d-flex align-items-start gap-2 small mb-3" style="border-radius: 10px;">
                            <i class="bi bi-info-circle-fill mt-1"></i>
                            <span>Enchère scellée : indiquez le prix max que vous êtes prêt à payer. Le système enchérira pour vous. Vous ne payerez que le minimum nécessaire (2ème enchère + 1 €).</span>
                        </div>

                        <?php if ($auction_active && $article['statut'] === 'disponible'): ?>
                            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $article['vendeur_id']): ?>
                                <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#bidModal">
                                    <?php echo $user_bid ? 'Modifier mon enchère' : 'Enchérir'; ?>
                                </button>
                            <?php elseif (!isset($_SESSION['user_id'])): ?>
                                <a href="connexion.php" class="btn btn-outline-primary btn-lg">Se connecter pour enchérir</a>
                            <?php endif; ?>
                        <?php elseif ($auction_ended && $article['statut'] === 'disponible'): ?>
                            <button class="btn btn-secondary btn-lg" disabled>Enchères terminées — En attente de clôture</button>
                        <?php elseif ($article['statut'] === 'vendu'): ?>
                            <button class="btn btn-secondary btn-lg" disabled>Article vendu</button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <?php if (isset($auction_active) && $auction_active && $article['statut'] === 'disponible' && isset($_SESSION['user_id']) && $_SESSION['user_id'] != $article['vendeur_id']): ?>
                <!-- Modal d'enchère -->
                <div class="modal fade" id="bidModal" tabindex="-1" aria-labelledby="bidModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content" style="border-radius: 16px; overflow: hidden;">
                            <div class="modal-header" style="background: linear-gradient(135deg, var(--omnes-primary), var(--omnes-primary-dark)); color: white;">
                                <h5 class="modal-title" id="bidModalLabel"><i class="bi bi-hammer me-2"></i>Placer une enchère</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body p-4">
                                <p class="text-muted mb-3">Indiquez le prix maximum que vous êtes prêt à payer. Le système enchérira automatiquement pour vous jusqu'à ce montant.</p>
                                <form id="bid-form">
                                    <input type="hidden" name="action" value="place_bid">
                                    <input type="hidden" name="article_id" value="<?php echo $article_id; ?>">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Votre prix maximum</label>
                                        <div class="input-group input-group-lg">
                                            <input type="number" name="montant_max" id="bid-montant-max" class="form-control"
                                                   placeholder="0.00" step="0.01"
                                                   min="<?php echo $article['prix']; ?>"
                                                   <?php if ($user_bid): ?>value="<?php echo $user_bid['montant_max']; ?>"<?php endif; ?>
                                                   required>
                                            <span class="input-group-text">&euro;</span>
                                        </div>
                                        <small class="text-muted d-block mt-1">Prix de réserve : <?php echo number_format($article['prix'], 2, ',', ' '); ?> €</small>
                                        <?php if ($user_bid): ?>
                                            <small class="text-info d-block mt-1">Votre enchère actuelle : <?php echo number_format($user_bid['montant_max'], 2, ',', ' '); ?> €</small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="alert alert-info small" style="border-radius: 10px;">
                                        <i class="bi bi-shield-lock me-1"></i> Votre enchère reste confidentielle. Vous ne payerez que le montant nécessaire pour remporter l'article.
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="button" class="btn btn-primary" id="submit-bid-btn">
                                    <i class="bi bi-hammer me-1"></i> <?php echo $user_bid ? 'Mettre à jour' : 'Placer l\'enchère'; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Avis -->
        <section class="mt-5 animate-on-scroll">
            <div class="card p-4 shadow-sm" style="border-radius: 16px;">
                <h3 class="mb-4"><i class="bi bi-chat-square-text me-2"></i>Avis (<?php echo count($avis_list); ?>)</h3>

                <?php if (!empty($avis_list)):
                    foreach ($avis_list as $avis): ?>
                        <div class="review-card mb-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                                         style="width:36px;height:36px;background:linear-gradient(135deg,var(--omnes-primary),var(--omnes-accent));font-size:0.8rem;">
                                        <?php echo strtoupper(substr($avis['prenom'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($avis['prenom'] . ' ' . $avis['nom']); ?></strong>
                                        <small class="d-block text-muted"><?php echo date('d/m/Y', strtotime($avis['date_creation'])); ?></small>
                                    </div>
                                </div>
                                <div class="star-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi <?php echo $i <= $avis['note'] ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p class="mb-0 mt-2"><?php echo htmlspecialchars($avis['commentaire']); ?></p>
                        </div>
                    <?php endforeach;
                else: ?>
                    <p class="text-muted">Aucun avis pour cet article. Soyez le premier à donner votre avis !</p>
                <?php endif; ?>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <hr>
                    <h5>Laisser un avis</h5>
                    <form id="review-form">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                        <input type="hidden" name="rating" id="rating-value" value="5">
                        <div class="mb-3">
                            <label class="form-label">Note</label>
                            <div class="star-rating-input fs-3">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi bi-star-fill" data-value="<?php echo $i; ?>" style="cursor: pointer; color: #ffc107;"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Commentaire</label>
                            <textarea name="commentaire" class="form-control" rows="3" placeholder="Partagez votre expérience..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i> Envoyer</button>
                    </form>
                <?php else: ?>
                    <hr>
                    <p class="text-muted text-center">
                        <a href="connexion.php" class="btn btn-outline-primary btn-sm">Connectez-vous</a> pour laisser un avis.
                    </p>
                <?php endif; ?>
            </div>
        </section>

        <!-- Articles similaires -->
        <?php if (!empty($similar_articles)): ?>
            <section class="mt-5 animate-on-scroll">
                <h3 class="mb-4"><i class="bi bi-grid me-2"></i>Articles similaires</h3>
                <div class="row g-4">
                    <?php foreach ($similar_articles as $sim): ?>
                        <div class="col-md-6 col-lg-3">
                            <div class="card article-card h-100 shadow-sm">
                                <div class="card-img-wrapper">
                                    <img src="<?php echo $base_url . htmlspecialchars($sim['image_url'] ?? 'images/articles/placeholder.png'); ?>"
                                         class="card-img-top" alt="<?php echo htmlspecialchars($sim['titre']); ?>">
                                    <div class="card-img-overlay-hover">
                                        <a href="article.php?id=<?php echo $sim['id']; ?>" class="btn btn-light btn-sm"><i class="bi bi-eye"></i> Voir</a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title"><?php echo htmlspecialchars($sim['titre']); ?></h6>
                                    <span class="price-tag"><?php echo number_format($sim['prix'], 2, ',', ' '); ?> &euro;</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
