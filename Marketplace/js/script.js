/**
 * Omnes MarketPlace - Scripts principaux
 */
$(document).ready(function () {

    // ========================
    // Panier - Mise à jour du compteur
    // ========================
    function updateCartCount() {
        $.ajax({
            url: getBaseUrl() + 'php/panier_actions.php',
            method: 'GET',
            data: { action: 'count' },
            dataType: 'json',
            success: function (response) {
                if (response.count > 0) {
                    $('#cart-count').text(response.count).removeClass('d-none');
                } else {
                    $('#cart-count').addClass('d-none');
                }
            }
        });
    }

    // ========================
    // Panier - Ajouter un article
    // ========================
    $(document).on('click', '.btn-add-cart', function (e) {
        e.preventDefault();
        var articleId = $(this).data('article-id');
        $.ajax({
            url: getBaseUrl() + 'php/panier_actions.php',
            method: 'POST',
            data: { action: 'add', article_id: articleId },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    updateCartCount();
                    showToast('Article ajouté au panier !', 'success');
                } else {
                    showToast(response.message || 'Erreur lors de l\'ajout.', 'danger');
                }
            },
            error: function () {
                showToast('Erreur de connexion.', 'danger');
            }
        });
    });

    // ========================
    // Panier - Supprimer un article
    // ========================
    $(document).on('click', '.btn-remove-cart', function (e) {
        e.preventDefault();
        var articleId = $(this).data('article-id');
        $.ajax({
            url: getBaseUrl() + 'php/panier_actions.php',
            method: 'POST',
            data: { action: 'remove', article_id: articleId },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    updateCartCount();
                    location.reload();
                }
            }
        });
    });

    // ========================
    // Toast notifications
    // ========================
    function showToast(message, type) {
        type = type || 'info';
        var toastHtml = '<div class="toast-notification alert alert-' + type + ' position-fixed" ' +
            'style="top: 80px; right: 20px; z-index: 9999; min-width: 280px;">' +
            message + '</div>';
        $('body').append(toastHtml);
        setTimeout(function () {
            $('.toast-notification').fadeOut(300, function () { $(this).remove(); });
        }, 3000);
    }

    // ========================
    // Enchères - Mise à jour en temps réel
    // ========================
    if ($('#auction-timer').length) {
        var endTime = new Date($('#auction-timer').data('end-time')).getTime();
        var timerInterval = setInterval(function () {
            var now = new Date().getTime();
            var distance = endTime - now;
            if (distance <= 0) {
                clearInterval(timerInterval);
                $('#auction-timer').text('Enchère terminée');
                return;
            }
            var hours = Math.floor(distance / (1000 * 60 * 60));
            var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((distance % (1000 * 60)) / 1000);
            $('#auction-timer').text(
                String(hours).padStart(2, '0') + ':' +
                String(minutes).padStart(2, '0') + ':' +
                String(seconds).padStart(2, '0')
            );
        }, 1000);
    }

    // ========================
    // Négociation - Envoi d'offre
    // ========================
    $('#negotiation-form').on('submit', function (e) {
        e.preventDefault();
        var form = $(this);
        $.ajax({
            url: getBaseUrl() + 'php/negociation_actions.php',
            method: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    location.reload();
                } else {
                    showToast(response.message || 'Erreur.', 'danger');
                }
            }
        });
    });

    // ========================
    // Avis - Soumission
    // ========================
    $('#review-form').on('submit', function (e) {
        e.preventDefault();
        var form = $(this);
        $.ajax({
            url: getBaseUrl() + 'php/avis_actions.php',
            method: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    showToast('Avis envoyé !', 'success');
                    location.reload();
                } else {
                    showToast(response.message || 'Erreur.', 'danger');
                }
            }
        });
    });

    // ========================
    // Star rating interactive
    // ========================
    $('.star-rating-input i').on('click', function () {
        var value = $(this).data('value');
        $('#rating-value').val(value);
        $('.star-rating-input i').each(function () {
            if ($(this).data('value') <= value) {
                $(this).removeClass('bi-star').addClass('bi-star-fill');
            } else {
                $(this).removeClass('bi-star-fill').addClass('bi-star');
            }
        });
    });

    // ========================
    // Utilitaires
    // ========================
    function getBaseUrl() {
        // Détecte le base URL selon la page actuelle
        var path = window.location.pathname;
        if (path.indexOf('/pages/admin/') !== -1 || path.indexOf('/pages/vendeur/') !== -1) {
            return '../../';
        } else if (path.indexOf('/pages/') !== -1) {
            return '../';
        }
        return '';
    }

    // Initialisation
    updateCartCount();
});
