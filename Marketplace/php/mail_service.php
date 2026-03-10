<?php
/**
 * Service d'envoi d'emails - Omnes MarketPlace
 * Utilise mail() avec msmtp configuré vers MailHog (SMTP localhost:1025)
 */

require_once __DIR__ . '/helpers.php';

function envoyer_email_confirmation_commande(PDO $pdo, int $commande_id, int $acheteur_id): bool
{
    // Récupérer les infos de l'acheteur
    $stmt = $pdo->prepare("SELECT prenom, nom, email FROM utilisateurs WHERE id = :id");
    $stmt->execute([':id' => $acheteur_id]);
    $acheteur = $stmt->fetch();
    if (!$acheteur || empty($acheteur['email'])) {
        return false;
    }

    // Valider le format email avant d'envoyer
    if (!is_valid_email($acheteur['email'])) {
        return false;
    }

    // Récupérer la commande
    $stmt = $pdo->prepare("SELECT id, total, adresse_livraison, statut, date_creation
                           FROM commandes WHERE id = :id AND acheteur_id = :uid");
    $stmt->execute([':id' => $commande_id, ':uid' => $acheteur_id]);
    $commande = $stmt->fetch();
    if (!$commande) {
        return false;
    }

    // Récupérer les articles de la commande
    $stmt = $pdo->prepare("SELECT ca.prix, a.titre
                           FROM commande_articles ca
                           JOIN articles a ON ca.article_id = a.id
                           WHERE ca.commande_id = :cid");
    $stmt->execute([':cid' => $commande_id]);
    $articles = $stmt->fetchAll();

    // Construire le contenu de l'email
    $date = date('d/m/Y à H:i', strtotime($commande['date_creation']));
    $total = number_format((float)$commande['total'], 2, ',', ' ');
    $prenom = htmlspecialchars($acheteur['prenom']);

    $lignes_articles = '';
    foreach ($articles as $article) {
        $prix_article = number_format((float)$article['prix'], 2, ',', ' ');
        $lignes_articles .= "  - {$article['titre']} : {$prix_article} EUR\n";
    }

    $sujet = "Confirmation de commande #{$commande_id} - Omnes MarketPlace";

    $corps = "Bonjour {$prenom},\n\n"
        . "Votre commande a ete confirmee avec succes !\n\n"
        . "--- Details de la commande ---\n"
        . "Numero : #{$commande_id}\n"
        . "Date : {$date}\n\n"
        . "Articles :\n"
        . $lignes_articles . "\n"
        . "Total : {$total} EUR\n"
        . "Adresse de livraison : {$commande['adresse_livraison']}\n\n"
        . "Merci pour votre achat sur Omnes MarketPlace !\n"
        . "L'equipe Omnes MarketPlace\n";

    $from = getenv('MAIL_FROM') ?: 'no-reply@marketplace.local';
    $headers = "From: Omnes MarketPlace <{$from}>\r\n"
        . "Reply-To: {$from}\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n"
        . "MIME-Version: 1.0\r\n";

    return @mail($acheteur['email'], $sujet, $corps, $headers);
}
