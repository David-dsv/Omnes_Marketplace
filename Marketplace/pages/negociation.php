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

$rounds_restants = $negociation ? max(0, 5 - floor($negociation['nb_messages'] / 2)) : 5;
$rounds_used = 5 - $rounds_restants;

$page_title = 'Négociation - ' . $article['titre'];
include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';
?>

<main class="py-4">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>index.php"><i class="bi bi-house"></i> Accueil</a></li>
                <li class="breadcrumb-item"><a href="article.php?id=<?php echo $article_id; ?>"><?php echo htmlspecialchars($article['titre']); ?></a></li>
                <li class="breadcrumb-item active">Négociation</li>
            </ol>
        </nav>

        <div class="row g-4">
            <!-- Article sidebar -->
            <div class="col-lg-4 mb-4 animate-on-scroll">
                <div class="card shadow-sm" style="border-radius: 16px; position: sticky; top: 100px;">
                    <img src="<?php echo $base_url . htmlspecialchars($article['image_url'] ?? 'images/placeholder.png'); ?>"
                         class="card-img-top" style="height: 200px; object-fit: cover;"
                         alt="<?php echo htmlspecialchars($article['titre']); ?>">
                    <div class="card-body">
                        <h5 class="fw-bold"><?php echo htmlspecialchars($article['titre']); ?></h5>
                        <p class="text-muted mb-2">
                            <i class="bi bi-person"></i> <?php echo htmlspecialchars($article['vendeur_prenom'] . ' ' . $article['vendeur_nom']); ?>
                        </p>
                        <p class="fs-4 fw-bold text-primary mb-3"><?php echo number_format($article['prix'], 2, ',', ' '); ?> &euro;</p>

                        <!-- Round tracker -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-uppercase text-muted">Progression des rounds</label>
                            <div class="round-tracker">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <div class="round-dot <?php
                                        if ($i <= $rounds_used) echo 'used';
                                        elseif ($i === $rounds_used + 1 && $rounds_restants > 0) echo 'current';
                                    ?>">
                                        <?php echo $i; ?>
                                    </div>
                                <?php endfor; ?>
                            </div>
                            <small class="text-muted"><?php echo $rounds_restants; ?> round<?php echo $rounds_restants > 1 ? 's' : ''; ?> restant<?php echo $rounds_restants > 1 ? 's' : ''; ?></small>
                        </div>

                        <div class="alert alert-info d-flex align-items-start gap-2 small mb-0" style="border-radius: 10px;">
                            <i class="bi bi-info-circle-fill mt-1"></i>
                            <span>Si le vendeur accepte votre offre, vous êtes engagé à acheter l'article.</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chat -->
            <div class="col-lg-8 animate-on-scroll animate-delay-1">
                <div class="card shadow-sm" style="border-radius: 16px; overflow: hidden;">
                    <div class="card-header py-3 px-4" style="background: linear-gradient(135deg, var(--omnes-primary), var(--omnes-primary-dark)); color: white;">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-chat-dots me-2"></i>Négociation</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="negotiation-chat p-4" style="min-height: 350px;">
                            <?php if (!empty($messages)):
                                foreach ($messages as $msg):
                                    $is_buyer = ($msg['auteur_id'] == $_SESSION['user_id']);
                                ?>
                                    <div class="negotiation-message <?php echo $is_buyer ? 'buyer' : 'seller'; ?>">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <strong class="small"><?php echo htmlspecialchars($msg['prenom']); ?></strong>
                                        </div>
                                        <?php if ($msg['montant_propose']): ?>
                                            <div class="msg-offer">
                                                <?php echo number_format($msg['montant_propose'], 2, ',', ' '); ?> &euro;
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($msg['message']): ?>
                                            <p class="mb-0 mt-1"><?php echo htmlspecialchars($msg['message']); ?></p>
                                        <?php endif; ?>
                                        <?php if ($msg['statut'] === 'accepte'): ?>
                                            <span class="badge bg-success mt-1"><i class="bi bi-check-circle"></i> Accepté</span>
                                        <?php elseif ($msg['statut'] === 'refuse'): ?>
                                            <span class="badge bg-danger mt-1"><i class="bi bi-x-circle"></i> Refusé</span>
                                        <?php endif; ?>
                                        <div class="msg-time"><?php echo date('d/m H:i', strtotime($msg['date_creation'])); ?></div>
                                    </div>
                                <?php endforeach;
                            else: ?>
                                <div class="text-center text-muted py-5">
                                    <i class="bi bi-chat-dots display-4"></i>
                                    <p class="mt-2">Commencez la négociation en proposant un prix.</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Input area -->
                        <div class="border-top p-3" style="background: #f8f9fa;">
                            <?php if ($rounds_restants > 0 && (!$negociation || $negociation['statut'] === 'en_cours')): ?>
                                <form id="negotiation-form">
                                    <input type="hidden" name="action" value="send_offer">
                                    <input type="hidden" name="article_id" value="<?php echo $article_id; ?>">
                                    <div class="d-flex gap-2">
                                        <div class="input-group" style="max-width: 160px;">
                                            <input type="number" name="montant" class="form-control" placeholder="Offre €" step="0.01" min="1" required>
                                            <span class="input-group-text">&euro;</span>
                                        </div>
                                        <input type="text" name="message" class="form-control" placeholder="Votre message (optionnel)">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-send-fill"></i> Envoyer
                                        </button>
                                    </div>
                                </form>
                            <?php elseif ($negociation && $negociation['statut'] === 'accepte'): ?>
                                <div class="alert alert-success text-center mb-0" style="border-radius: 10px;">
                                    <i class="bi bi-check-circle-fill fs-4 me-2"></i>
                                    <strong>Offre acceptée !</strong> Finalisez votre achat.
                                    <br>
                                    <a href="panier.php" class="btn btn-success mt-2"><i class="bi bi-cart me-1"></i>Aller au panier</a>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning text-center mb-0" style="border-radius: 10px;">
                                    <i class="bi bi-exclamation-circle me-2"></i>
                                    Négociation terminée (5 rounds atteints ou offre refusée).
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
