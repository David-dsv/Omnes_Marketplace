<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?php echo $base_url; ?>index.php">
            <i class="bi bi-shop"></i> Omnes MarketPlace
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $base_url; ?>index.php">
                        <i class="bi bi-house"></i> Accueil
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $base_url; ?>pages/tout_parcourir.php">
                        <i class="bi bi-grid"></i> Tout Parcourir
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $base_url; ?>pages/notifications.php">
                        <i class="bi bi-bell"></i> Notifications
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $base_url; ?>pages/panier.php">
                        <i class="bi bi-cart3"></i> Panier
                        <span id="cart-count" class="badge bg-danger d-none">0</span>
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['user_prenom']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo $base_url; ?>pages/compte.php">
                                <i class="bi bi-person"></i> Mon Compte
                            </a></li>
                            <?php if ($_SESSION['user_role'] === 'vendeur'): ?>
                                <li><a class="dropdown-item" href="<?php echo $base_url; ?>pages/vendeur/dashboard.php">
                                    <i class="bi bi-speedometer2"></i> Tableau de bord
                                </a></li>
                            <?php elseif ($_SESSION['user_role'] === 'administrateur'): ?>
                                <li><a class="dropdown-item" href="<?php echo $base_url; ?>pages/admin/dashboard.php">
                                    <i class="bi bi-gear"></i> Administration
                                </a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo $base_url; ?>php/auth.php?action=logout">
                                <i class="bi bi-box-arrow-right"></i> Déconnexion
                            </a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_url; ?>pages/connexion.php">
                            <i class="bi bi-box-arrow-in-right"></i> Connexion
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-light btn-sm ms-2 px-3" href="<?php echo $base_url; ?>pages/inscription.php">
                            Inscription
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
