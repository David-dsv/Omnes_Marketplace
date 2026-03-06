/**
 * Omnes MarketPlace - Scripts principaux
 * Animations, interactions, validations
 */
$(document).ready(function () {

    // ========================
    // Utilitaires
    // ========================
    function getBaseUrl() {
        var path = window.location.pathname;
        if (path.indexOf('/pages/admin/') !== -1 || path.indexOf('/pages/vendeur/') !== -1) {
            return '../../';
        } else if (path.indexOf('/pages/') !== -1) {
            return '../';
        }
        return '';
    }

    // ========================
    // Toast notifications
    // ========================
    window.showToast = function(message, type) {
        type = type || 'info';
        var iconMap = {
            success: 'bi-check-circle-fill',
            danger: 'bi-exclamation-triangle-fill',
            warning: 'bi-exclamation-circle-fill',
            info: 'bi-info-circle-fill'
        };
        var icon = iconMap[type] || iconMap.info;
        var toastHtml = '<div class="toast-notification alert alert-' + type + '">' +
            '<i class="bi ' + icon + ' me-2"></i>' + message + '</div>';
        var $toast = $(toastHtml);
        $('body').append($toast);
        setTimeout(function () {
            $toast.css({ opacity: 0, transform: 'translateX(50px)' });
            setTimeout(function() { $toast.remove(); }, 300);
        }, 3000);
    };

    // ========================
    // Navbar scroll effect + Back to Top
    // ========================
    var $navbar = $('#main-navbar');
    var $backToTop = $('#backToTop');
    $(window).on('scroll', function () {
        var scrollTop = $(this).scrollTop();
        $navbar.toggleClass('scrolled', scrollTop > 50);
        $backToTop.toggleClass('visible', scrollTop > 400);
    });
    $backToTop.on('click', function () {
        $('html, body').animate({ scrollTop: 0 }, 600);
    });

    // ========================
    // Smooth scroll for anchor links
    // ========================
    $('a[href^="#"]').not('[data-bs-toggle]').on('click', function (e) {
        var target = $(this.getAttribute('href'));
        if (target.length) {
            e.preventDefault();
            $('html, body').animate({ scrollTop: target.offset().top - 80 }, 500);
        }
    });

    // ========================
    // Scroll animations (Intersection Observer)
    // ========================
    if ('IntersectionObserver' in window) {
        var animateObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animated');
                    animateObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

        document.querySelectorAll('.animate-on-scroll').forEach(function (el) {
            animateObserver.observe(el);
        });
    } else {
        // Fallback: show all elements
        $('.animate-on-scroll').addClass('animated');
    }

    // ========================
    // Lazy loading images
    // ========================
    if ('IntersectionObserver' in window) {
        var lazyObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    var img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        img.classList.remove('lazy');
                    }
                    lazyObserver.unobserve(img);
                }
            });
        });
        document.querySelectorAll('img[data-src]').forEach(function (img) {
            lazyObserver.observe(img);
        });
    }

    // ========================
    // Animated counters
    // ========================
    function animateCounter($el) {
        var target = parseInt($el.data('target'), 10);
        var duration = 2000;
        var start = 0;
        var startTime = null;
        var suffix = $el.data('suffix') || '';

        function step(timestamp) {
            if (!startTime) startTime = timestamp;
            var progress = Math.min((timestamp - startTime) / duration, 1);
            var eased = 1 - Math.pow(1 - progress, 3); // easeOutCubic
            var current = Math.floor(eased * target);
            $el.text(current.toLocaleString('fr-FR') + suffix);
            if (progress < 1) {
                requestAnimationFrame(step);
            }
        }
        requestAnimationFrame(step);
    }

    if ('IntersectionObserver' in window) {
        var counterObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    animateCounter($(entry.target));
                    counterObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        document.querySelectorAll('.counter-animate').forEach(function (el) {
            counterObserver.observe(el);
        });
    }

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
        var $btn = $(this);
        var articleId = $btn.data('article-id');
        $btn.prop('disabled', true).html('<span class="loading-spinner" style="width:18px;height:18px;border-width:2px;"></span>');

        $.ajax({
            url: getBaseUrl() + 'php/panier_actions.php',
            method: 'POST',
            data: { action: 'add', article_id: articleId },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    updateCartCount();
                    showToast('Article ajouté au panier !', 'success');
                    $btn.html('<i class="bi bi-check-lg"></i> Ajouté');
                    setTimeout(function() {
                        $btn.prop('disabled', false).html('<i class="bi bi-cart-plus"></i> Ajouter au panier');
                    }, 2000);
                } else {
                    showToast(response.message || 'Erreur lors de l\'ajout.', 'danger');
                    $btn.prop('disabled', false).html('<i class="bi bi-cart-plus"></i> Ajouter au panier');
                }
            },
            error: function () {
                showToast('Erreur de connexion.', 'danger');
                $btn.prop('disabled', false).html('<i class="bi bi-cart-plus"></i> Ajouter au panier');
            }
        });
    });

    // ========================
    // Panier - Supprimer un article (avec animation)
    // ========================
    $(document).on('click', '.btn-remove-cart', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var articleId = $btn.data('article-id');
        var relistOnRemove = Number($btn.data('relist-on-remove') || 0) === 1;
        var $cartItem = $btn.closest('.cart-item');

        var runRemove = function () {
            $cartItem.addClass('removing');
            setTimeout(function() {
                $.ajax({
                    url: getBaseUrl() + 'php/panier_actions.php',
                    method: 'POST',
                    data: { action: 'remove', article_id: articleId },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            updateCartCount();
                            if (response.message) {
                                showToast(response.message, 'info');
                            }
                            location.reload();
                        } else {
                            $cartItem.removeClass('removing');
                            showToast(response.message || 'Erreur lors de la suppression.', 'danger');
                        }
                    },
                    error: function () {
                        $cartItem.removeClass('removing');
                        showToast('Erreur de connexion.', 'danger');
                    }
                });
            }, 400);
        };

        if (relistOnRemove) {
            showConfirmModal(
                'Annuler la négociation',
                'Retirer cet article va annuler l\'accord et remettre l\'article en vente. Continuer ?',
                function () {
                    runRemove();
                }
            );
            return;
        }

        runRemove();
    });

    // ========================
    // Confirmation modal (replaces confirm())
    // ========================
    window.showConfirmModal = function(title, text, callback) {
        $('#confirmModalTitle').text(title);
        $('#confirmModalText').text(text);
        var modal = new bootstrap.Modal(document.getElementById('confirmModal'));
        $('#confirmModalBtn').off('click').on('click', function() {
            modal.hide();
            if (callback) callback();
        });
        modal.show();
    };

    // Replace confirm() calls on forms with data-confirm
    $(document).on('submit', 'form[data-confirm]', function(e) {
        var $form = $(this);
        if ($form.data('confirmed')) return true;
        e.preventDefault();
        var msg = $form.data('confirm');
        showConfirmModal('Confirmer', msg, function() {
            $form.data('confirmed', true);
            $form[0].submit();
        });
    });

    // ========================
    // Négociation + Enchères
    // ========================
    $('#negotiation-form').on('submit', function (e) {
        e.preventDefault();
        var form = $(this);
        var $btn = form.find('button[type="submit"]');
        $btn.prop('disabled', true).html('<span class="loading-spinner" style="width:16px;height:16px;border-width:2px;"></span>');

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
                    $btn.prop('disabled', false).html('<i class="bi bi-send-fill"></i> Envoyer');
                }
            },
            error: function() {
                showToast('Erreur de connexion.', 'danger');
                $btn.prop('disabled', false).html('<i class="bi bi-send-fill"></i> Envoyer');
            }
        });
    });

    $(document).on('click', '.btn-respond-offer', function () {
        var messageId = $(this).data('message-id');
        var response = $(this).data('response');
        var actionLabel = response === 'accepte' ? 'accepter' : 'refuser';

        showConfirmModal('Confirmer', 'Voulez-vous vraiment ' + actionLabel + ' cette offre ?', function () {
            $.ajax({
                url: getBaseUrl() + 'php/negociation_actions.php',
                method: 'POST',
                data: { action: 'respond', message_id: messageId, response: response },
                dataType: 'json',
                success: function (res) {
                    if (res.success) {
                        showToast('Action enregistrée.', 'success');
                        setTimeout(function () { location.reload(); }, 600);
                    } else {
                        showToast(res.message || 'Erreur.', 'danger');
                    }
                },
                error: function () {
                    showToast('Erreur de connexion.', 'danger');
                }
            });
        });
    });

    $('#counter-offer-form').on('submit', function (e) {
        e.preventDefault();
        var form = $(this);
        var $btn = form.find('button[type="submit"]');
        $btn.prop('disabled', true).html('<span class="loading-spinner" style="width:16px;height:16px;border-width:2px;"></span>');

        $.ajax({
            url: getBaseUrl() + 'php/negociation_actions.php',
            method: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    showToast('Contre-offre envoyée.', 'success');
                    setTimeout(function () { location.reload(); }, 600);
                } else {
                    showToast(res.message || 'Erreur.', 'danger');
                    $btn.prop('disabled', false).html('<i class="bi bi-send-fill"></i> Envoyer');
                }
            },
            error: function () {
                showToast('Erreur de connexion.', 'danger');
                $btn.prop('disabled', false).html('<i class="bi bi-send-fill"></i> Envoyer');
            }
        });
    });

    $(document).on('click', '.btn-accept-counter-offer', function () {
        var messageId = $(this).data('message-id');
        showConfirmModal('Confirmer', 'En acceptant cette contre-offre, vous vous engagez à acheter l\'article.', function () {
            $.ajax({
                url: getBaseUrl() + 'php/negociation_actions.php',
                method: 'POST',
                data: { action: 'accept_counter_offer', message_id: messageId },
                dataType: 'json',
                success: function (res) {
                    if (res.success) {
                        showToast('Contre-offre acceptée.', 'success');
                        setTimeout(function () { location.reload(); }, 600);
                    } else {
                        showToast(res.message || 'Erreur.', 'danger');
                    }
                },
                error: function () {
                    showToast('Erreur de connexion.', 'danger');
                }
            });
        });
    });

    $(document).on('click', '.btn-add-negotiated-to-cart', function () {
        var $btn = $(this);
        var articleId = $btn.data('article-id');
        var negotiationId = $btn.data('negotiation-id');
        var originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<span class="loading-spinner" style="width:16px;height:16px;border-width:2px;"></span>');

        $.ajax({
            url: getBaseUrl() + 'php/panier_actions.php',
            method: 'POST',
            data: { action: 'add', article_id: articleId, negotiation_id: negotiationId },
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    updateCartCount();
                    showToast('Article ajouté au panier.', 'success');
                    setTimeout(function () {
                        window.location.href = getBaseUrl() + 'pages/panier.php';
                    }, 500);
                } else {
                    showToast(res.message || 'Erreur.', 'danger');
                    $btn.prop('disabled', false).html(originalHtml);
                }
            },
            error: function () {
                showToast('Erreur de connexion.', 'danger');
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    $('#submit-bid-btn').on('click', function () {
        var $btn = $(this);
        var $form = $('#bid-form');
        if (!$form.length) return;

        var originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<span class="loading-spinner" style="width:16px;height:16px;border-width:2px;"></span>');

        $.ajax({
            url: getBaseUrl() + 'php/enchere_actions.php',
            method: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    showToast(res.message || 'Enchère enregistrée.', 'success');
                    setTimeout(function () { location.reload(); }, 700);
                } else {
                    showToast(res.message || 'Erreur.', 'danger');
                    $btn.prop('disabled', false).html(originalHtml);
                }
            },
            error: function () {
                showToast('Erreur de connexion.', 'danger');
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    var $countdown = $('#countdown-timer');
    if ($countdown.length) {
        var endTimeRaw = ($countdown.data('end-time') || '').toString().trim();
        var $display = $('#countdown-display');
        var endTime = endTimeRaw ? new Date(endTimeRaw.replace(' ', 'T')) : null;

        if (endTime && !isNaN(endTime.getTime())) {
            var tickCountdown = function () {
                var now = new Date();
                var diffMs = endTime.getTime() - now.getTime();

                if (diffMs <= 0) {
                    $display.text('00:00:00');
                    clearInterval(countdownInterval);
                    setTimeout(function () { location.reload(); }, 1200);
                    return;
                }

                var totalSeconds = Math.floor(diffMs / 1000);
                var hours = Math.floor(totalSeconds / 3600);
                var minutes = Math.floor((totalSeconds % 3600) / 60);
                var seconds = totalSeconds % 60;

                $display.text(
                    String(hours).padStart(2, '0') + ':' +
                    String(minutes).padStart(2, '0') + ':' +
                    String(seconds).padStart(2, '0')
                );

                if (totalSeconds <= 3600) {
                    $countdown.addClass('is-urgent');
                }
            };

            tickCountdown();
            var countdownInterval = setInterval(tickCountdown, 1000);
        }
    }

    window.resolveAuction = function (articleId) {
        showConfirmModal('Clôturer l\'enchère', 'Confirmer la clôture de cette enchère et la désignation du gagnant ?', function () {
            $.ajax({
                url: getBaseUrl() + 'php/enchere_actions.php',
                method: 'POST',
                data: { action: 'resolve_auction', article_id: articleId },
                dataType: 'json',
                success: function (res) {
                    var $result = $('#result-' + articleId);
                    if (res.success) {
                        if (res.no_bids) {
                            $result.html('<span class=\"text-muted\">Aucune enchère à clôturer.</span>');
                            showToast(res.message || 'Aucune enchère.', 'info');
                        } else {
                            var price = Number(res.winning_price || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            $result.html('<span class=\"text-success\">Clôturée: ' + (res.winner_name || 'N/A') + ' à ' + price + ' €.</span>');
                            showToast(res.message || 'Enchère clôturée.', 'success');
                        }
                        setTimeout(function () { location.reload(); }, 1200);
                    } else {
                        $result.html('<span class=\"text-danger\">' + (res.message || 'Erreur.') + '</span>');
                        showToast(res.message || 'Erreur.', 'danger');
                    }
                },
                error: function () {
                    showToast('Erreur de connexion.', 'danger');
                }
            });
        });
    };

    // Ensure auction modal is attached to body to avoid z-index/transform stacking issues
    var $bidModal = $('#bidModal');
    if ($bidModal.length) {
        $bidModal.appendTo('body');
    }

    // Auto-scroll negotiation chat to bottom
    var $chatBox = $('.negotiation-chat');
    if ($chatBox.length) {
        $chatBox.scrollTop($chatBox[0].scrollHeight);
    }

    // ========================
    // Avis - Soumission
    // ========================
    $('#review-form').on('submit', function (e) {
        e.preventDefault();
        var form = $(this);
        var $btn = form.find('button[type="submit"]');
        $btn.prop('disabled', true);

        $.ajax({
            url: getBaseUrl() + 'php/avis_actions.php',
            method: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    showToast('Avis envoyé !', 'success');
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    showToast(response.message || 'Erreur.', 'danger');
                    $btn.prop('disabled', false);
                }
            },
            error: function() {
                showToast('Erreur de connexion.', 'danger');
                $btn.prop('disabled', false);
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

    $('.star-rating-input i').on('mouseenter', function () {
        var value = $(this).data('value');
        $('.star-rating-input i').each(function () {
            if ($(this).data('value') <= value) {
                $(this).css('color', '#ffc107');
            } else {
                $(this).css('color', '#dee2e6');
            }
        });
    });
    $('.star-rating-input').on('mouseleave', function () {
        var selectedValue = parseInt($('#rating-value').val()) || 0;
        $('.star-rating-input i').each(function () {
            if ($(this).data('value') <= selectedValue) {
                $(this).css('color', '#ffc107');
            } else {
                $(this).css('color', '#dee2e6');
            }
        });
    });

    // ========================
    // Password toggle visibility
    // ========================
    $(document).on('click', '.toggle-password', function () {
        var $input = $(this).siblings('input');
        var type = $input.attr('type') === 'password' ? 'text' : 'password';
        $input.attr('type', type);
        $(this).toggleClass('bi-eye bi-eye-slash');
    });

    // ========================
    // Password strength indicator
    // ========================
    $(document).on('input', '#password', function () {
        var $bar = $(this).closest('.mb-3').find('.password-strength-bar');
        if (!$bar.length) return;

        var val = $(this).val();
        var strength = 0;
        if (val.length >= 6) strength++;
        if (val.length >= 10) strength++;
        if (/[A-Z]/.test(val) && /[a-z]/.test(val)) strength++;
        if (/[0-9]/.test(val)) strength++;
        if (/[^A-Za-z0-9]/.test(val)) strength++;

        $bar.removeClass('weak fair good strong');
        if (strength <= 1) $bar.addClass('weak');
        else if (strength === 2) $bar.addClass('fair');
        else if (strength === 3) $bar.addClass('good');
        else $bar.addClass('strong');
    });

    // ========================
    // Real-time form validation
    // ========================
    $(document).on('blur', 'input[required], textarea[required]', function () {
        var $input = $(this);
        var value = ($input.val() || '').toString().trim();

        if (value === '') {
            $input.addClass('is-invalid').removeClass('is-valid');
            return;
        }

        if (this.checkValidity()) {
            $input.addClass('is-valid').removeClass('is-invalid');
        } else {
            $input.addClass('is-invalid').removeClass('is-valid');
        }
    });

    $(document).on('blur', 'input[type="email"]', function () {
        var $input = $(this);
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if ($input.val() && !emailRegex.test($input.val())) {
            $input.addClass('is-invalid').removeClass('is-valid');
        } else if ($input.val()) {
            $input.addClass('is-valid').removeClass('is-invalid');
        }
    });

    // Password confirmation match
    $(document).on('input', '#password_confirm', function () {
        var $input = $(this);
        var password = $('#password').val();
        if ($input.val() && $input.val() !== password) {
            $input.addClass('is-invalid').removeClass('is-valid');
        } else if ($input.val()) {
            $input.addClass('is-valid').removeClass('is-invalid');
        }
    });

    // ========================
    // Credit card formatting
    // ========================
    $(document).on('input', 'input[name="numero_carte"]', function () {
        var val = $(this).val().replace(/\D/g, '').substring(0, 16);
        if (this.id === 'card-number-input') {
            // Sur la page paiement, on garde 16 chiffres bruts (sans espaces).
            $(this).val(val);
        } else {
            var formatted = val.replace(/(\d{4})(?=\d)/g, '$1 ');
            $(this).val(formatted);
        }

        // Detect card type
        var cardIcons = $(this).closest('.card, .mb-3').find('.card-type-icons i');
        cardIcons.removeClass('active');
        if (val.startsWith('4')) {
            cardIcons.filter('.fa-cc-visa').addClass('active');
        } else if (val.startsWith('5') || val.startsWith('2')) {
            cardIcons.filter('.fa-cc-mastercard').addClass('active');
        } else if (val.startsWith('3')) {
            cardIcons.filter('.fa-cc-amex').addClass('active');
        }
    });

    // Expiration date formatting
    $(document).on('input', 'input[name="expiration"]', function () {
        var val = $(this).val().replace(/\D/g, '').substring(0, 4);
        if (val.length >= 2) {
            val = val.substring(0, 2) + '/' + val.substring(2);
        }
        $(this).val(val);
    });

    // CVV limit
    $(document).on('input', 'input[name="cvv"]', function () {
        $(this).val($(this).val().replace(/\D/g, '').substring(0, 3));
    });

    // ========================
    // View toggle (grid/list)
    // ========================
    $(document).on('click', '.view-toggle button', function () {
        var view = $(this).data('view');
        $('.view-toggle button').removeClass('active');
        $(this).addClass('active');
        if (view === 'list') {
            $('#articles-container').addClass('list-view');
        } else {
            $('#articles-container').removeClass('list-view');
        }
    });

    // ========================
    // Dynamic search with debounce
    // ========================
    var searchTimeout;
    $(document).on('input', '.search-dynamic', function () {
        var $input = $(this);
        var query = $input.val().toLowerCase();
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function () {
            $('.searchable-item').each(function () {
                var text = $(this).text().toLowerCase();
                if (text.indexOf(query) !== -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }, 300);
    });

    // ========================
    // Image error handler (placeholder)
    // ========================
    $(document).on('error', 'img', function () {
        var $img = $(this);
        if (!$img.data('fallback-applied')) {
            $img.data('fallback-applied', true);
            $img.attr('src', 'data:image/svg+xml,' + encodeURIComponent(
                '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300" viewBox="0 0 400 300">' +
                '<rect fill="#e9ecef" width="400" height="300"/>' +
                '<g fill="#adb5bd" transform="translate(150,100)">' +
                '<rect x="10" y="30" width="80" height="60" rx="5" stroke="#adb5bd" stroke-width="3" fill="none"/>' +
                '<circle cx="35" cy="50" r="8" fill="#adb5bd"/>' +
                '<polygon points="20,85 50,60 80,85" fill="#adb5bd" opacity="0.5"/>' +
                '<polygon points="45,85 70,65 90,85" fill="#adb5bd" opacity="0.3"/>' +
                '</g>' +
                '<text x="200" y="220" font-family="Arial,sans-serif" font-size="14" fill="#adb5bd" text-anchor="middle">Image non disponible</text>' +
                '</svg>'
            ));
        }
    });

    // ========================
    // Quantity controls (cart)
    // ========================
    $(document).on('click', '.qty-minus, .qty-plus', function () {
        var $control = $(this).closest('.qty-control');
        var $qty = $control.find('.qty-value');
        var current = parseInt($qty.text(), 10);
        if ($(this).hasClass('qty-minus') && current > 1) {
            $qty.text(current - 1);
        } else if ($(this).hasClass('qty-plus')) {
            $qty.text(current + 1);
        }
    });

    // ========================
    // Initialisation
    // ========================
    if ($('#cart-count').length) {
        updateCartCount();
    }

    // Trigger scroll animations for elements already visible on page load
    setTimeout(function() {
        $(window).trigger('scroll');
    }, 100);
});
