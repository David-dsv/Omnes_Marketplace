<?php
session_start();
$base_url = '../../';
$page_title = 'Mes négociations';
require_once $base_url . 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'vendeur') {
    header('Location: ' . $base_url . 'pages/connexion.php');
    exit;
}

$uid = $_SESSION['user_id'];
$negotiations = [];
$negotiations_pending = [];

try {
    $stmt = $pdo->prepare("
        SELECT n.id, n.statut, n.date_creation,
               a.id AS article_id, a.titre, a.prix, a.image_url,
               u.prenom AS acheteur_prenom, u.nom AS acheteur_nom, u.id AS acheteur_id,
               (SELECT COUNT(*) FROM negociation_messages nm WHERE nm.negociation_id = n.id) AS nb_messages,
               (SELECT nm2.montant_propose FROM negociation_messages nm2 WHERE nm2.negociation_id = n.id ORDER BY nm2.date_creation DESC LIMIT 1) AS derniere_offre,
               (SELECT nm3.auteur_id FROM negociation_messages nm3 WHERE nm3.negociation_id = n.id ORDER BY nm3.date_creation DESC LIMIT 1) AS dernier_auteur_id
        FROM negociations n
        JOIN articles a ON n.article_id = a.id
        JOIN utilisateurs u ON n.acheteur_id = u.id
        WHERE a.vendeur_id = :uid
        ORDER BY FIELD(n.statut, 'en_cours', 'accepte', 'refuse', 'expire'), n.date_creation DESC
    ");
    $stmt->execute([':uid' => $uid]);
    $negotiations = $stmt->fetchAll();

    // Filter pending negotiations (awaiting vendor response)
    foreach ($negotiations as $neg) {
        if ($neg['statut'] === 'en_cours' && $neg['dernier_auteur_id'] != $uid) {
            $negotiations_pending[] = $neg;
        }
    }
} catch (PDOException $e) {
    $negotiations = [];
    $negotiations_pending = [];
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';
?>

<main class="py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">Mes négociations</h1>
                <p class="text-muted mb-0"><?php echo count($negotiations); ?> négociation<?php echo count($negotiations) > 1 ? 's' : ''; ?> au total</p>
            </div>
            <a href="dashboard.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Retour au dashboard</a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success d-flex align-items-center mb-4">
                <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center mb-4">
                <i class="bi bi-exclamation-circle-fill me-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Pending negotiations section -->
        <?php if (!empty($negotiations_pending)): ?>
            <div class="mb-5">
                <h5 class="fw-bold mb-3">En attente de votre réponse</h5>
                <div class="row g-4">
                    <?php foreach ($negotiations_pending as $index => $neg): ?>
                        <div class="col-md-6 col-lg-4 animate-on-scroll animate-delay-<?php echo ($index % 3) + 1; ?>">
                            <div class="card shadow-sm h-100 border-start border-warning border-5" style="border-radius: 12px;">
                                <div class="card-body">
                                    <!-- Article image and title -->
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <img src="<?php echo $base_url . htmlspecialchars($neg['image_url'] ?? 'images/articles/placeholder.png'); ?>"
                                                 alt="<?php echo htmlspecialchars($neg['titre']); ?>"
                                                 style="width: 90px; height: 90px; object-fit: cover; border-radius: 8px;">
                                            <div class="flex-grow-1">
                                                <a href="<?php echo $base_url; ?>pages/article.php?id=<?php echo $neg['article_id']; ?>" class="text-decoration-none fw-semibold">
                                                    <?php echo htmlspecialchars(substr($neg['titre'], 0, 40)); ?><?php echo strlen($neg['titre']) > 40 ? '...' : ''; ?>
                                                </a>
                                                <div class="text-muted small"><?php echo number_format($neg['prix'], 2, ',', ' '); ?> &euro;</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Buyer name -->
                                    <div class="mb-3 pb-3 border-bottom">
                                        <small class="text-muted">Acheteur</small>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($neg['acheteur_prenom'] . ' ' . $neg['acheteur_nom']); ?></div>
                                    </div>

                                    <!-- Last offer -->
                                    <div class="mb-3 pb-3 border-bottom">
                                        <small class="text-muted">Dernière offre</small>
                                        <div class="fw-bold" style="color: var(--omnes-primary);">
                                            <?php echo $neg['derniere_offre'] ? number_format($neg['derniere_offre'], 2, ',', ' ') . ' &euro;' : 'N/A'; ?>
                                        </div>
                                    </div>

                                    <!-- Rounds used -->
                                    <div class="mb-3 pb-3 border-bottom">
                                        <small class="text-muted">Rounds utilisés</small>
                                        <div class="fw-semibold"><?php echo ceil($neg['nb_messages'] / 2); ?> / 5</div>
                                    </div>

                                    <!-- Status badge -->
                                    <div class="mb-3">
                                        <?php
                                        $badge_class = match($neg['statut']) {
                                            'en_cours' => 'bg-warning',
                                            'accepte' => 'bg-success',
                                            'refuse' => 'bg-danger',
                                            'expire' => 'bg-secondary',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?> rounded-pill"><?php echo htmlspecialchars($neg['statut']); ?></span>
                                    </div>

                                    <!-- Action button -->
                                    <a href="<?php echo $base_url; ?>pages/negociation.php?article_id=<?php echo $neg['article_id']; ?>&acheteur_id=<?php echo $neg['acheteur_id']; ?>" class="btn btn-primary w-100">
                                        <i class="bi bi-chat-dots me-1"></i>Voir la conversation
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- All negotiations section -->
        <div>
            <h5 class="fw-bold mb-3">Toutes les négociations</h5>
            <?php if (!empty($negotiations)): ?>
                <div class="row g-4">
                    <?php foreach ($negotiations as $index => $neg): ?>
                        <div class="col-md-6 col-lg-4 animate-on-scroll animate-delay-<?php echo ($index % 3) + 1; ?>">
                            <div class="card shadow-sm h-100" style="border-radius: 12px;">
                                <div class="card-body">
                                    <!-- Article image and title -->
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <img src="<?php echo $base_url . htmlspecialchars($neg['image_url'] ?? 'images/articles/placeholder.png'); ?>"
                                                 alt="<?php echo htmlspecialchars($neg['titre']); ?>"
                                                 style="width: 90px; height: 90px; object-fit: cover; border-radius: 8px;">
                                            <div class="flex-grow-1">
                                                <a href="<?php echo $base_url; ?>pages/article.php?id=<?php echo $neg['article_id']; ?>" class="text-decoration-none fw-semibold">
                                                    <?php echo htmlspecialchars(substr($neg['titre'], 0, 40)); ?><?php echo strlen($neg['titre']) > 40 ? '...' : ''; ?>
                                                </a>
                                                <div class="text-muted small"><?php echo number_format($neg['prix'], 2, ',', ' '); ?> &euro;</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Buyer name -->
                                    <div class="mb-3 pb-3 border-bottom">
                                        <small class="text-muted">Acheteur</small>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($neg['acheteur_prenom'] . ' ' . $neg['acheteur_nom']); ?></div>
                                    </div>

                                    <!-- Last offer -->
                                    <div class="mb-3 pb-3 border-bottom">
                                        <small class="text-muted">Dernière offre</small>
                                        <div class="fw-bold" style="color: var(--omnes-primary);">
                                            <?php echo $neg['derniere_offre'] ? number_format($neg['derniere_offre'], 2, ',', ' ') . ' &euro;' : 'N/A'; ?>
                                        </div>
                                    </div>

                                    <!-- Rounds used -->
                                    <div class="mb-3 pb-3 border-bottom">
                                        <small class="text-muted">Rounds utilisés</small>
                                        <div class="fw-semibold"><?php echo ceil($neg['nb_messages'] / 2); ?> / 5</div>
                                    </div>

                                    <!-- Status badge -->
                                    <div class="mb-3">
                                        <?php
                                        $badge_class = match($neg['statut']) {
                                            'en_cours' => 'bg-warning',
                                            'accepte' => 'bg-success',
                                            'refuse' => 'bg-danger',
                                            'expire' => 'bg-secondary',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?> rounded-pill"><?php echo htmlspecialchars($neg['statut']); ?></span>
                                    </div>

                                    <!-- Action button -->
                                    <a href="<?php echo $base_url; ?>pages/negociation.php?article_id=<?php echo $neg['article_id']; ?>&acheteur_id=<?php echo $neg['acheteur_id']; ?>" class="btn btn-primary w-100">
                                        <i class="bi bi-chat-dots me-1"></i>Voir la conversation
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                         style="width:100px;height:100px;background:rgba(var(--omnes-primary-rgb),0.1);">
                        <i class="bi bi-inbox text-primary" style="font-size:3rem;"></i>
                    </div>
                    <h5 class="mt-2">Aucune négociation</h5>
                    <p class="text-muted mb-4">Vous n'avez pas encore reçu d'offres de négociation.</p>
                    <a href="mes_articles.php" class="btn btn-primary"><i class="bi bi-arrow-left me-2"></i>Voir mes articles</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
