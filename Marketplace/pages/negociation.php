<?php
session_start();
$base_url = '../';
$page_title = 'Négociation';
require_once $base_url . 'config/database.php';

$article_id = (int)($_GET['article_id'] ?? 0);
if (!isset($_SESSION['user_id']) || $article_id <= 0) {
    header('Location: connexion.php');
    exit;
}

// Récupérer l'article
try {
    $stmt = $pdo->prepare("SELECT a.*, u.prenom AS vendeur_prenom, u.nom AS vendeur_nom
                           FROM articles a
                           JOIN utilisateurs u ON a.vendeur_id = u.id
                           WHERE a.id = :id AND a.type_vente = 'negociation'");
    $stmt->execute([':id' => $article_id]);
    $article = $stmt->fetch();
} catch (PDOException $e) {
    $article = null;
}

if (!$article) {
    header('Location: tout_parcourir.php');
    exit;
}

// Récupérer la négociation existante ou en créer une
try {
    $stmt = $pdo->prepare("SELECT n.*,
                           (SELECT COUNT(*) FROM negociation_messages nm WHERE nm.negociation_id = n.id) AS nb_messages
                           FROM negociations n
                           WHERE n.article_id = :aid AND n.acheteur_id = :uid");
    $stmt->execute([':aid' => $article_id, ':uid' => $_SESSION['user_id']]);
    $negociation = $stmt->fetch();
} catch (PDOException $e) {
    $negociation = null;
}

// Messages de la négociation
$messages = [];
if ($negociation) {
    try {
        $stmt = $pdo->prepare("SELECT nm.*, u.prenom, u.nom
                               FROM negociation_messages nm
                               JOIN utilisateurs u ON nm.auteur_id = u.id
                               WHERE nm.negociation_id = :nid
                               ORDER BY nm.date_creation ASC");
        $stmt->execute([':nid' => $negociation['id']]);
        $messages = $stmt->fetchAll();
    } catch (PDOException $e) {
        $messages = [];
    }
}

$rounds_restants = $negociation ? (5 - floor($negociation['nb_messages'] / 2)) : 5;

$page_title = 'Négociation - ' . $article['titre'];
include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';
?>

<main class="py-4">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>index.php">Accueil</a></li>
                <li class="breadcrumb-item"><a href="article.php?id=<?php echo $article_id; ?>"><?php echo htmlspecialchars($article['titre']); ?></a></li>
                <li class="breadcrumb-item active">Négociation</li>
            </ol>
        </nav>

        <div class="row">
            <!-- Article résumé -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm">
                    <img src="<?php echo $base_url . htmlspecialchars($article['image_url'] ?? 'images/placeholder.png'); ?>"
                         class="card-img-top" alt="<?php echo htmlspecialchars($article['titre']); ?>">
                    <div class="card-body">
                        <h5><?php echo htmlspecialchars($article['titre']); ?></h5>
                        <p class="text-muted">Vendeur : <?php echo htmlspecialchars($article['vendeur_prenom'] . ' ' . $article['vendeur_nom']); ?></p>
                        <p class="fs-4 fw-bold text-primary"><?php echo number_format($article['prix'], 2, ',', ' '); ?> &euro;</p>
                        <div class="alert alert-info small">
                            <i class="bi bi-info-circle"></i> Rounds restants : <strong><?php echo max(0, $rounds_restants); ?> / 5</strong>
                            <br>Si le vendeur accepte votre offre, vous êtes engagé à acheter.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chat de négociation -->
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-chat-dots"></i> Négociation</h5>
                    </div>
                    <div class="card-body">
                        <div class="negotiation-chat mb-3">
                            <?php if (!empty($messages)):
                                foreach ($messages as $msg):
                                    $is_buyer = ($msg['auteur_id'] == $_SESSION['user_id']);
                                ?>
                                    <div class="negotiation-message <?php echo $is_buyer ? 'buyer' : 'seller'; ?>">
                                        <div class="d-flex justify-content-between">
                                            <strong><?php echo htmlspecialchars($msg['prenom']); ?></strong>
                                            <small class="text-muted"><?php echo date('d/m H:i', strtotime($msg['date_creation'])); ?></small>
                                        </div>
                                        <?php if ($msg['montant_propose']): ?>
                                            <p class="mb-0 mt-1">Offre : <strong><?php echo number_format($msg['montant_propose'], 2, ',', ' '); ?> &euro;</strong></p>
                                        <?php endif; ?>
                                        <?php if ($msg['message']): ?>
                                            <p class="mb-0 mt-1"><?php echo htmlspecialchars($msg['message']); ?></p>
                                        <?php endif; ?>
                                        <?php if ($msg['statut'] === 'accepte'): ?>
                                            <span class="badge bg-success mt-1">Accepté</span>
                                        <?php elseif ($msg['statut'] === 'refuse'): ?>
                                            <span class="badge bg-danger mt-1">Refusé</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach;
                            else: ?>
                                <p class="text-muted text-center">Commencez la négociation en proposant un prix.</p>
                            <?php endif; ?>
                        </div>

                        <!-- Formulaire de négociation -->
                        <?php if ($rounds_restants > 0 && (!$negociation || $negociation['statut'] === 'en_cours')): ?>
                            <form id="negotiation-form">
                                <input type="hidden" name="action" value="send_offer">
                                <input type="hidden" name="article_id" value="<?php echo $article_id; ?>">
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <input type="number" name="montant" class="form-control" placeholder="Votre offre (€)" step="0.01" min="1" required>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" name="message" class="form-control" placeholder="Message (optionnel)">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary w-100">Envoyer</button>
                                    </div>
                                </div>
                            </form>
                        <?php elseif ($negociation && $negociation['statut'] === 'accepte'): ?>
                            <div class="alert alert-success text-center">
                                <i class="bi bi-check-circle"></i> Offre acceptée ! Finalisez votre achat.
                                <br><a href="panier.php" class="btn btn-success mt-2">Aller au panier</a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning text-center">
                                <i class="bi bi-exclamation-circle"></i> Négociation terminée (5 rounds atteints ou offre refusée).
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
