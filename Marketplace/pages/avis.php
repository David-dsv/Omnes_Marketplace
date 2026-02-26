<?php
session_start();
$base_url = '../';
$page_title = 'Avis';
require_once $base_url . 'config/database.php';
include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';

// Récupérer les derniers avis
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
?>

<main class="py-4">
    <div class="container">
        <h1 class="mb-4"><i class="bi bi-chat-square-text"></i> Derniers Avis</h1>

        <?php if (!empty($avis_list)): ?>
            <div class="row g-4">
                <?php foreach ($avis_list as $avis): ?>
                    <div class="col-md-6">
                        <div class="card p-3 shadow-sm h-100">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong><?php echo htmlspecialchars($avis['prenom'] . ' ' . $avis['nom']); ?></strong>
                                    <span class="text-muted small ms-2">sur
                                        <a href="article.php?id=<?php echo $avis['article_id']; ?>"><?php echo htmlspecialchars($avis['article_titre']); ?></a>
                                    </span>
                                </div>
                                <div class="star-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi <?php echo $i <= $avis['note'] ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p class="mt-2 mb-1"><?php echo htmlspecialchars($avis['commentaire']); ?></p>
                            <small class="text-muted"><?php echo date('d/m/Y', strtotime($avis['date_creation'])); ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-chat-square display-4"></i>
                <p class="mt-3">Aucun avis pour le moment.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
