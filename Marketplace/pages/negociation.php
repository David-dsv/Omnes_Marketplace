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

$uid = $_SESSION['user_id'];

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

// Déterminer le rôle de l'utilisateur dans cette négociation
$is_seller = ($uid == $article['vendeur_id']);
$is_buyer = !$is_seller;

// Charger la négociation
// Si vendeur, on peut recevoir un acheteur_id en paramètre pour voir une négo spécifique
$acheteur_id_param = (int)($_GET['acheteur_id'] ?? 0);

try {
    if ($is_seller && $acheteur_id_param > 0) {
        // Vendeur regarde une négociation spécifique
        $stmt = $pdo->prepare("SELECT n.*,
                               (SELECT COUNT(*) FROM negociation_messages nm WHERE nm.negociation_id = n.id) AS nb_messages
                               FROM negociations n
                               WHERE n.article_id = :aid AND n.acheteur_id = :buyer_id AND n.vendeur_id = :uid");
        $stmt->execute([':aid' => $article_id, ':buyer_id' => $acheteur_id_param, ':uid' => $uid]);
    } elseif ($is_seller) {
        // Vendeur sans acheteur spécifié, prendre la première négo en cours
        $stmt = $pdo->prepare("SELECT n.*,
                               (SELECT COUNT(*) FROM negociation_messages nm WHERE nm.negociation_id = n.id) AS nb_messages
                               FROM negociations n
                               WHERE n.article_id = :aid AND n.vendeur_id = :uid
                               ORDER BY FIELD(n.statut, 'en_cours', 'accepte', 'refuse', 'expire'), n.date_creation DESC
                               LIMIT 1");
        $stmt->execute([':aid' => $article_id, ':uid' => $uid]);
    } else {
        // Acheteur
        $stmt = $pdo->prepare("SELECT n.*,
                               (SELECT COUNT(*) FROM negociation_messages nm WHERE nm.negociation_id = n.id) AS nb_messages
                               FROM negociations n
                               WHERE n.article_id = :aid AND n.acheteur_id = :uid");
        $stmt->execute([':aid' => $article_id, ':uid' => $uid]);
    }
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

// Calcul des rounds
$nb_messages_total = $negociation ? (int)$negociation['nb_messages'] : 0;
$rounds_used = (int)ceil($nb_messages_total / 2);
$rounds_restants = max(0, 5 - $rounds_used);

// Dernier message et son statut
$last_message = !empty($messages) ? end($messages) : null;
$last_message_is_mine = $last_message && ($last_message['auteur_id'] == $uid);
$pending_message_for_me = $last_message && !$last_message_is_mine && $last_message['statut'] === 'en_attente';

// Prix accepté (si négociation terminée avec succès)
$prix_accepte = null;
if ($negociation && $negociation['statut'] === 'accepte') {
    if ($negociation['prix_accorde'] !== null) {
        $prix_accepte = (float)$negociation['prix_accorde'];
    } else {
        foreach (array_reverse($messages) as $msg) {
            if ($msg['statut'] === 'accepte' && $msg['montant_propose']) {
                $prix_accepte = (float)$msg['montant_propose'];
                break;
            }
        }
    }
}

// Nom de l'autre partie
$other_party_name = '';
if ($is_seller && $negociation) {
    try {
        $stmt = $pdo->prepare("SELECT prenom, nom FROM utilisateurs WHERE id = :id");
        $stmt->execute([':id' => $negociation['acheteur_id']]);
        $buyer_info = $stmt->fetch();
        $other_party_name = $buyer_info ? $buyer_info['prenom'] . ' ' . $buyer_info['nom'] : 'Acheteur';
    } catch (PDOException $e) {
        $other_party_name = 'Acheteur';
    }
} else {
    $other_party_name = $article['vendeur_prenom'] . ' ' . $article['vendeur_nom'];
}

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
                    <img src="<?php echo $base_url . htmlspecialchars($article['image_url'] ?? 'images/articles/placeholder.png'); ?>"
                         class="card-img-top" style="height: 200px; object-fit: cover;"
                         alt="<?php echo htmlspecialchars($article['titre']); ?>">
                    <div class="card-body">
                        <h5 class="fw-bold"><?php echo htmlspecialchars($article['titre']); ?></h5>
                        <p class="text-muted mb-2">
                            <?php if ($is_seller): ?>
                                <i class="bi bi-person"></i> Acheteur : <?php echo htmlspecialchars($other_party_name); ?>
                            <?php else: ?>
                                <i class="bi bi-person"></i> <?php echo htmlspecialchars($article['vendeur_prenom'] . ' ' . $article['vendeur_nom']); ?>
                            <?php endif; ?>
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
                            <span>
                                <?php if ($is_buyer): ?>
                                    Si le vendeur accepte votre offre, vous êtes engagé à acheter l'article.
                                <?php else: ?>
                                    Si vous acceptez une offre, l'acheteur est engagé à acheter l'article au prix convenu.
                                <?php endif; ?>
                            </span>
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
                                    $msg_is_mine = ($msg['auteur_id'] == $uid);
                                    $msg_role = $msg_is_mine ? ($is_buyer ? 'buyer' : 'seller') : ($is_buyer ? 'seller' : 'buyer');
                                ?>
                                    <div class="negotiation-message <?php echo $msg_role; ?>">
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
                                    <p class="mt-2">
                                        <?php echo $is_buyer ? 'Commencez la négociation en proposant un prix.' : 'Aucune offre reçue pour le moment.'; ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Zone d'actions -->
                        <div class="border-top p-3" style="background: #f8f9fa;">

                            <?php if ($negociation && $negociation['statut'] === 'accepte'): ?>
                                <!-- Négociation acceptée -->
                                <div class="alert alert-success text-center mb-0" style="border-radius: 10px;">
                                    <i class="bi bi-check-circle-fill fs-4 me-2"></i>
                                    <strong>Offre acceptée !</strong>
                                    <?php if ($prix_accepte): ?>
                                        Prix convenu : <strong><?php echo number_format($prix_accepte, 2, ',', ' '); ?> €</strong>
                                    <?php endif; ?>

                                    <?php if ($is_buyer): ?>
                                        <br>
                                        <button class="btn btn-success mt-2 btn-add-negotiated-to-cart"
                                                data-article-id="<?php echo $article_id; ?>"
                                                data-negotiation-id="<?php echo $negociation['id']; ?>">
                                            <i class="bi bi-cart-plus me-1"></i>Ajouter au panier (<?php echo number_format($prix_accepte, 2, ',', ' '); ?> €)
                                        </button>
                                    <?php else: ?>
                                        <br><small class="text-muted">L'acheteur va finaliser son achat.</small>
                                    <?php endif; ?>
                                </div>

                            <?php elseif ($article['statut'] !== 'disponible' && (!$negociation || $negociation['statut'] !== 'accepte')): ?>
                                <div class="alert alert-warning text-center mb-0" style="border-radius: 10px;">
                                    <i class="bi bi-lock-fill me-2"></i>
                                    Cet article est déjà vendu. La négociation n'est plus disponible.
                                </div>

                            <?php elseif (!$negociation || $negociation['statut'] === 'en_cours'): ?>

                                <?php if ($is_buyer): ?>
                                    <!-- ACHETEUR : Formulaire d'offre ou acceptation de contre-offre -->
                                    <?php if ($pending_message_for_me && $last_message['montant_propose']): ?>
                                        <!-- Le vendeur a fait une contre-offre, l'acheteur peut accepter ou faire une nouvelle offre -->
                                        <div class="card p-3 mb-3" style="border: 1px solid #ffc107; border-radius: 12px; background: #fffbf0;">
                                            <h6 class="fw-semibold mb-2">Contre-offre du vendeur</h6>
                                            <p class="mb-2">Le vendeur propose : <strong class="text-primary fs-5"><?php echo number_format($last_message['montant_propose'], 2, ',', ' '); ?> €</strong></p>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-success btn-sm btn-accept-counter-offer"
                                                        data-message-id="<?php echo $last_message['id']; ?>">
                                                    <i class="bi bi-check-lg"></i> Accepter cette offre
                                                </button>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($nb_messages_total < 10): ?>
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
                                    <?php else: ?>
                                        <div class="alert alert-warning text-center mb-0" style="border-radius: 10px;">
                                            <i class="bi bi-exclamation-circle me-2"></i> Nombre maximum de rounds atteint (5).
                                        </div>
                                    <?php endif; ?>

                                <?php elseif ($is_seller && $negociation): ?>
                                    <!-- VENDEUR : Répondre à une offre -->
                                    <?php if ($pending_message_for_me && $last_message['montant_propose']): ?>
                                        <div class="card p-3 mb-3" style="border: 1px solid var(--omnes-primary); border-radius: 12px;">
                                            <h6 class="fw-semibold mb-2">Offre de l'acheteur</h6>
                                            <p class="mb-2">Montant proposé : <strong class="text-primary fs-5"><?php echo number_format($last_message['montant_propose'], 2, ',', ' '); ?> €</strong></p>
                                            <?php if ($last_message['message']): ?>
                                                <p class="text-muted small mb-2">"<?php echo htmlspecialchars($last_message['message']); ?>"</p>
                                            <?php endif; ?>

                                            <div class="d-flex gap-2 mb-3">
                                                <button class="btn btn-success btn-sm btn-respond-offer"
                                                        data-message-id="<?php echo $last_message['id']; ?>"
                                                        data-response="accepte">
                                                    <i class="bi bi-check-lg"></i> Accepter
                                                </button>
                                                <button class="btn btn-danger btn-sm btn-respond-offer"
                                                        data-message-id="<?php echo $last_message['id']; ?>"
                                                        data-response="refuse">
                                                    <i class="bi bi-x-lg"></i> Refuser
                                                </button>
                                                <button class="btn btn-warning btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#counter-offer-section">
                                                    <i class="bi bi-arrow-counterclockwise"></i> Contre-offre
                                                </button>
                                            </div>

                                            <!-- Formulaire contre-offre (collapsible) -->
                                            <div class="collapse" id="counter-offer-section">
                                                <div class="card card-body" style="border-radius: 12px; background: #fffbf0;">
                                                    <h6 class="fw-semibold mb-2">Proposer un prix</h6>
                                                    <form id="counter-offer-form">
                                                        <input type="hidden" name="action" value="send_counter_offer">
                                                        <input type="hidden" name="negociation_id" value="<?php echo $negociation['id']; ?>">
                                                        <input type="hidden" name="parent_message_id" value="<?php echo $last_message['id']; ?>">
                                                        <div class="d-flex gap-2">
                                                            <div class="input-group" style="max-width: 160px;">
                                                                <input type="number" name="montant" class="form-control" placeholder="Prix €" step="0.01" min="1" required>
                                                                <span class="input-group-text">&euro;</span>
                                                            </div>
                                                            <input type="text" name="message" class="form-control" placeholder="Votre message (optionnel)">
                                                            <button type="submit" class="btn btn-warning">
                                                                <i class="bi bi-send-fill"></i> Envoyer
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php elseif (!$pending_message_for_me && $negociation): ?>
                                        <div class="text-center text-muted py-2">
                                            <small>En attente de la réponse de l'acheteur...</small>
                                        </div>
                                    <?php endif; ?>

                                <?php elseif ($is_seller && !$negociation): ?>
                                    <div class="text-center text-muted py-2">
                                        <small>Aucune offre reçue pour le moment.</small>
                                    </div>
                                <?php endif; ?>

                            <?php else: ?>
                                <!-- Négociation refusée/expirée -->
                                <div class="alert alert-warning text-center mb-0" style="border-radius: 10px;">
                                    <i class="bi bi-exclamation-circle me-2"></i>
                                    Négociation terminée (<?php echo $negociation['statut'] === 'refuse' ? 'refusée' : 'expirée'; ?>).
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
