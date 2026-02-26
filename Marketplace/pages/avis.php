<?php
session_start();
$base_url = '../';
$page_title = 'Avis';
require_once $base_url . 'config/database.php';
include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';

try {
    $stmt = $pdo->query("SELECT av.*, a.titre AS article_titre, a.id AS article_id,
                                u.prenom, u.nom
                         FROM avis av
                         JOIN articles a ON av.article_id = a.id
                         JOIN utilisateurs u ON av.auteur_id = u.id
                         ORDER BY av.date_creation DESC
                         LIMIT 20");
    $avis_list = $stmt->fetchAll();
} catch (PDOException $e) {
    $avis_list = [];
}

// Stats
$avg_global = 0;
if (!empty($avis_list)) {
    $avg_global = array_sum(array_column($avis_list, 'note')) / count($avis_list);
}
?>

<main class="py-4">
    <div class="container">
        <div class="section-title mb-4 animate-on-scroll">
            <h1 class="h3"><i class="bi bi-chat-square-text me-2"></i>Derniers Avis</h1>
            <p>Ce que pensent les utilisateurs de nos articles</p>
        </div>

        <!-- Global stats -->
        <?php if (!empty($avis_list)): ?>
            <div class="row mb-4 g-3 animate-on-scroll">
                <div class="col-md-4">
                    <div class="card p-4 text-center shadow-sm" style="border-radius: 16px;">
                        <div class="fs-1 fw-bold text-primary"><?php echo number_format($avg_global, 1); ?></div>
                        <div class="star-rating fs-4 mb-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi <?php echo $i <= round($avg_global) ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="text-muted mb-0"><?php echo count($avis_list); ?> avis au total</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($avis_list)): ?>
            <div class="row g-4">
                <?php foreach ($avis_list as $index => $avis): ?>
                    <div class="col-md-6 animate-on-scroll animate-delay-<?php echo ($index % 4) + 1; ?>">
                        <div class="review-card h-100">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
                                         style="width:40px;height:40px;background:linear-gradient(135deg,var(--omnes-primary),var(--omnes-accent));font-size:0.85rem;">
                                        <?php echo strtoupper(substr($avis['prenom'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($avis['prenom'] . ' ' . $avis['nom']); ?></strong>
                                        <small class="d-block text-muted">sur
                                            <a href="article.php?id=<?php echo $avis['article_id']; ?>" class="text-primary"><?php echo htmlspecialchars($avis['article_titre']); ?></a>
                                        </small>
                                    </div>
                                </div>
                                <div class="star-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi <?php echo $i <= $avis['note'] ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p class="mt-2 mb-1"><?php echo htmlspecialchars($avis['commentaire']); ?></p>
                            <small class="text-muted"><i class="bi bi-clock"></i> <?php echo date('d/m/Y', strtotime($avis['date_creation'])); ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-chat-square display-3"></i>
                <h5 class="mt-3">Aucun avis pour le moment</h5>
                <p>Achetez un article et laissez votre premier avis !</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
