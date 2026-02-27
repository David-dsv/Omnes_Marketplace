<?php
session_start();
$base_url = '../../';
$page_title = 'Détail enchère';
require_once $base_url . 'config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'administrateur') {
    header('Location: ' . $base_url . 'pages/connexion.php');
    exit;
}

$article_id = (int)($_GET['id'] ?? 0);
if ($article_id <= 0) {
    header('Location: gestion_encheres.php?error=' . urlencode('Enchère invalide.'));
    exit;
}

function format_duration_fr(int $seconds): string
{
    if ($seconds <= 0) {
        return '0 min';
    }

    $days = intdiv($seconds, 86400);
    $hours = intdiv($seconds % 86400, 3600);
    $minutes = intdiv($seconds % 3600, 60);

    $parts = [];
    if ($days > 0) {
        $parts[] = $days . 'j';
    }
    if ($hours > 0 || $days > 0) {
        $parts[] = $hours . 'h';
    }
    $parts[] = $minutes . 'min';

    return implode(' ', $parts);
}

try {
    $stmt = $pdo->prepare("
        SELECT a.*, v.prenom AS vendeur_prenom, v.nom AS vendeur_nom, v.email AS vendeur_email,
               (SELECT COUNT(*) FROM encheres e WHERE e.article_id = a.id) AS nb_encheres,
               (SELECT e2.montant_max
                FROM encheres e2
                WHERE e2.article_id = a.id
                ORDER BY e2.montant_max DESC, e2.date_creation ASC, e2.id ASC
                LIMIT 1) AS meilleure_enchere,
               (SELECT e3.prix_paye
                FROM encheres e3
                WHERE e3.article_id = a.id AND e3.statut = 'gagnant'
                LIMIT 1) AS prix_final,
               (SELECT u2.prenom
                FROM encheres e4
                JOIN utilisateurs u2 ON u2.id = e4.acheteur_id
                WHERE e4.article_id = a.id
                ORDER BY e4.montant_max DESC, e4.date_creation ASC, e4.id ASC
                LIMIT 1) AS leader_prenom,
               (SELECT u2.nom
                FROM encheres e4
                JOIN utilisateurs u2 ON u2.id = e4.acheteur_id
                WHERE e4.article_id = a.id
                ORDER BY e4.montant_max DESC, e4.date_creation ASC, e4.id ASC
                LIMIT 1) AS leader_nom,
               (SELECT u2.email
                FROM encheres e4
                JOIN utilisateurs u2 ON u2.id = e4.acheteur_id
                WHERE e4.article_id = a.id
                ORDER BY e4.montant_max DESC, e4.date_creation ASC, e4.id ASC
                LIMIT 1) AS leader_email
        FROM articles a
        JOIN utilisateurs v ON v.id = a.vendeur_id
        WHERE a.id = :id AND a.type_vente = 'meilleure_offre'
        LIMIT 1
    ");
    $stmt->execute([':id' => $article_id]);
    $auction = $stmt->fetch();

    if (!$auction) {
        header('Location: gestion_encheres.php?error=' . urlencode('Enchère introuvable.'));
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT e.id, e.acheteur_id, e.montant_max, e.prix_paye, e.statut, e.date_creation,
               u.prenom, u.nom, u.email
        FROM encheres e
        JOIN utilisateurs u ON u.id = e.acheteur_id
        WHERE e.article_id = :aid
        ORDER BY e.montant_max DESC, e.date_creation ASC, e.id ASC
    ");
    $stmt->execute([':aid' => $article_id]);
    $bids_ranked = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT e.id, e.acheteur_id, e.montant_max, e.prix_paye, e.statut, e.date_creation,
               u.prenom, u.nom, u.email
        FROM encheres e
        JOIN utilisateurs u ON u.id = e.acheteur_id
        WHERE e.article_id = :aid
        ORDER BY e.date_creation DESC, e.id DESC
    ");
    $stmt->execute([':aid' => $article_id]);
    $bids_history = $stmt->fetchAll();
} catch (PDOException $e) {
    header('Location: gestion_encheres.php?error=' . urlencode('Erreur lors du chargement du détail enchère.'));
    exit;
}

$nb_encheres = (int)$auction['nb_encheres'];
$reserve_price = (float)$auction['prix'];
$highest_bid = $auction['meilleure_enchere'] !== null ? (float)$auction['meilleure_enchere'] : null;
$final_price = $auction['prix_final'] !== null ? (float)$auction['prix_final'] : null;

$start_ts = !empty($auction['date_debut_enchere']) ? strtotime((string)$auction['date_debut_enchere']) : null;
$end_ts = !empty($auction['date_fin_enchere']) ? strtotime((string)$auction['date_fin_enchere']) : null;
$now_ts = time();

$status_label = 'En cours';
$status_class = 'bg-info text-dark';
if ($auction['statut'] === 'vendu') {
    $status_label = 'Clôturée / Vendue';
    $status_class = 'bg-success';
} elseif ($end_ts !== null && $end_ts !== false && $now_ts >= $end_ts) {
    if ($nb_encheres > 0) {
        $status_label = 'Terminée (en attente de clôture)';
        $status_class = 'bg-warning text-dark';
    } else {
        $status_label = 'Aucune enchère';
        $status_class = 'bg-secondary';
    }
} elseif ($nb_encheres === 0) {
    $status_label = 'En cours (aucune enchère)';
    $status_class = 'bg-light text-dark border';
}

$time_context = 'Date de fin non définie';
if ($end_ts !== null && $end_ts !== false) {
    if ($now_ts < $end_ts) {
        $time_context = 'Temps restant : ' . format_duration_fr($end_ts - $now_ts);
    } else {
        $time_context = 'Terminée depuis : ' . format_duration_fr($now_ts - $end_ts);
    }
}

$current_leader_name = trim(($auction['leader_prenom'] ?? '') . ' ' . ($auction['leader_nom'] ?? ''));
$current_leader_email = trim((string)($auction['leader_email'] ?? ''));

$proxy_theoretical_price = null;
if ($nb_encheres > 0 && !empty($bids_ranked)) {
    $winner_max = (float)$bids_ranked[0]['montant_max'];
    if (count($bids_ranked) === 1) {
        $proxy_theoretical_price = $reserve_price;
    } else {
        $second_max = (float)$bids_ranked[1]['montant_max'];
        $proxy_theoretical_price = max($reserve_price, $second_max + 1);
        $proxy_theoretical_price = min($proxy_theoretical_price, $winner_max);
    }
}

include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';
?>

<main class="py-4">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="dashboard.php"><i class="bi bi-gear"></i> Administration</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="gestion_encheres.php">Gestion des enchères</a>
                </li>
                <li class="breadcrumb-item active">Détail enchère</li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
            <div>
                <h1 class="h3 mb-1">Détail de l'enchère</h1>
                <p class="text-muted mb-0">Vue administrateur complète de l'article et des enchérisseurs.</p>
            </div>
            <a href="gestion_encheres.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Retour à la liste
            </a>
        </div>

        <section class="card shadow-sm border-0 mb-4 auction-detail-header">
            <div class="card-body p-4">
                <div class="row g-4 align-items-center">
                    <div class="col-md-3">
                        <img src="<?php echo $base_url . htmlspecialchars($auction['image_url'] ?? 'images/articles/placeholder.png'); ?>"
                             alt="<?php echo htmlspecialchars($auction['titre']); ?>"
                             class="img-fluid rounded-3 auction-detail-image">
                    </div>
                    <div class="col-md-6">
                        <h2 class="h4 fw-bold mb-2"><?php echo htmlspecialchars($auction['titre']); ?></h2>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <span class="badge <?php echo $status_class; ?> px-3 py-2"><?php echo htmlspecialchars($status_label); ?></span>
                            <span class="badge badge-meilleure_offre px-3 py-2">Meilleure offre</span>
                        </div>
                        <p class="mb-1 text-muted">
                            <i class="bi bi-person-fill me-1"></i>
                            Vendeur: <strong><?php echo htmlspecialchars($auction['vendeur_prenom'] . ' ' . $auction['vendeur_nom']); ?></strong>
                            (<?php echo htmlspecialchars($auction['vendeur_email']); ?>)
                        </p>
                        <p class="mb-1 text-muted">
                            <i class="bi bi-calendar-event me-1"></i>
                            Début: <?php echo $start_ts ? date('d/m/Y H:i', $start_ts) : 'Non défini'; ?>
                        </p>
                        <p class="mb-0 text-muted">
                            <i class="bi bi-calendar-check me-1"></i>
                            Fin: <?php echo $end_ts ? date('d/m/Y H:i', $end_ts) : 'Non définie'; ?>
                        </p>
                    </div>
                    <div class="col-md-3">
                        <div class="auction-time-context">
                            <small class="text-muted d-block mb-1">Contexte temporel</small>
                            <strong><?php echo htmlspecialchars($time_context); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="row g-3 mb-4">
            <div class="col-sm-6 col-lg-4">
                <div class="auction-kpi-card h-100">
                    <small>Prix de réserve</small>
                    <div class="value"><?php echo number_format($reserve_price, 2, ',', ' '); ?> &euro;</div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="auction-kpi-card h-100">
                    <small>Nombre d'enchères</small>
                    <div class="value"><?php echo $nb_encheres; ?></div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="auction-kpi-card h-100">
                    <small>Meilleure enchère actuelle</small>
                    <div class="value">
                        <?php echo $highest_bid !== null ? number_format($highest_bid, 2, ',', ' ') . ' €' : 'Aucune'; ?>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="auction-kpi-card h-100">
                    <small>Leader théorique</small>
                    <div class="value leader-text">
                        <?php echo $current_leader_name !== '' ? htmlspecialchars($current_leader_name) : 'Aucun'; ?>
                    </div>
                    <?php if ($current_leader_email !== ''): ?>
                        <div class="kpi-sub"><?php echo htmlspecialchars($current_leader_email); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="auction-kpi-card h-100">
                    <small>Prix final</small>
                    <div class="value">
                        <?php echo $final_price !== null ? number_format($final_price, 2, ',', ' ') . ' €' : 'Non clôturée'; ?>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="auction-kpi-card h-100">
                    <small>Prix proxy théorique</small>
                    <div class="value">
                        <?php echo $proxy_theoretical_price !== null ? number_format($proxy_theoretical_price, 2, ',', ' ') . ' €' : 'N/A'; ?>
                    </div>
                </div>
            </div>
        </section>

        <?php if (!empty($bids_ranked)): ?>
            <section class="card shadow-sm mb-4 border-0">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <h5 class="fw-bold mb-0">Classement des enchérisseurs</h5>
                        <span class="badge bg-primary-subtle text-primary"><?php echo count($bids_ranked); ?> participant<?php echo count($bids_ranked) > 1 ? 's' : ''; ?></span>
                    </div>
                    <div class="table-responsive auction-bid-table">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Rang</th>
                                    <th>Acheteur</th>
                                    <th>Email</th>
                                    <th>Montant max</th>
                                    <th>Dernière enchère</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bids_ranked as $index => $bid): ?>
                                    <?php
                                        $rank = $index + 1;
                                        $status_badge_class = match ($bid['statut']) {
                                            'gagnant' => 'bg-success',
                                            'perdant' => 'bg-danger',
                                            default => 'bg-secondary',
                                        };
                                        $status_label_bid = match ($bid['statut']) {
                                            'gagnant' => 'Gagnant',
                                            'perdant' => 'Perdant',
                                            default => 'En attente',
                                        };
                                    ?>
                                    <tr>
                                        <td>
                                            <strong>#<?php echo $rank; ?></strong>
                                            <?php if ($rank === 1 && $auction['statut'] !== 'vendu'): ?>
                                                <span class="auction-leader-badge">Leader actuel</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($bid['prenom'] . ' ' . $bid['nom']); ?></td>
                                        <td><?php echo htmlspecialchars($bid['email']); ?></td>
                                        <td class="fw-semibold text-primary"><?php echo number_format((float)$bid['montant_max'], 2, ',', ' '); ?> &euro;</td>
                                        <td><?php echo date('d/m/Y H:i', strtotime((string)$bid['date_creation'])); ?></td>
                                        <td><span class="badge <?php echo $status_badge_class; ?>"><?php echo $status_label_bid; ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        <?php else: ?>
            <section class="card shadow-sm mb-4 border-0">
                <div class="card-body p-5 text-center">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                         style="width: 82px; height: 82px; background: rgba(var(--omnes-primary-rgb), 0.12);">
                        <i class="bi bi-hammer text-primary fs-2"></i>
                    </div>
                    <h5 class="fw-bold mb-1">Aucune enchère enregistrée</h5>
                    <p class="text-muted mb-0">Aucun acheteur n'a encore placé d'enchère sur cet article.</p>
                </div>
            </section>
        <?php endif; ?>

        <section class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">Historique des offres (état actuel)</h5>
                <p class="text-muted small mb-3">
                    Le système conserve la dernière offre par acheteur. Cette vue reflète l'état actuel des enchères.
                </p>

                <?php if (!empty($bids_history)): ?>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($bids_history as $bid): ?>
                            <?php
                                $history_badge_class = match ($bid['statut']) {
                                    'gagnant' => 'bg-success',
                                    'perdant' => 'bg-danger',
                                    default => 'bg-secondary',
                                };
                                $history_status = match ($bid['statut']) {
                                    'gagnant' => 'Gagnant',
                                    'perdant' => 'Perdant',
                                    default => 'En attente',
                                };
                            ?>
                            <div class="auction-history-item">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                    <div>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($bid['prenom'] . ' ' . $bid['nom']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($bid['email']); ?></div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-primary"><?php echo number_format((float)$bid['montant_max'], 2, ',', ' '); ?> &euro;</div>
                                        <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime((string)$bid['date_creation'])); ?></small>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <span class="badge <?php echo $history_badge_class; ?>"><?php echo $history_status; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">Aucune donnée d'historique à afficher.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>

<?php include $base_url . 'includes/footer.php'; ?>
