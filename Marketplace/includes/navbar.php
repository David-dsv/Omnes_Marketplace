<nav class="navbar navbar-expand-lg navbar-dark sticky-top" id="main-navbar">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?php echo $base_url; ?>index.php">
            <img src="<?php echo $base_url; ?>images/Logo_Omnes_Éducation.svg.png" alt="Omnes Education" class="brand-logo"> MarketPlace
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $base_url; ?>index.php">
                        <i class="bi bi-house-door"></i> Accueil
                    </a>
                </li>
                <!-- Tout Parcourir with Mega Menu -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="<?php echo $base_url; ?>pages/tout_parcourir.php" id="navCategoriesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-grid-3x3-gap"></i> Tout Parcourir
                    </a>
                    <div class="dropdown-menu mega-menu p-4" aria-labelledby="navCategoriesDropdown">
                        <div class="row g-0">
                            <div class="col-md-6">
                                <h6 class="text-uppercase text-muted small fw-bold mb-3">Catégories</h6>
                                <a class="mega-menu-category" href="<?php echo $base_url; ?>pages/tout_parcourir.php?categorie=Électronique">
                                    <i class="bi bi-laptop" style="background: rgba(107,63,190,0.1); color: #6B3FBE;"></i>
                                    <div>
                                        <strong>Électronique</strong>
                                        <small class="d-block text-muted">PC, smartphones, accessoires</small>
                                    </div>
                                </a>
                                <a class="mega-menu-category" href="<?php echo $base_url; ?>pages/tout_parcourir.php?categorie=Vêtements">
                                    <i class="bi bi-bag" style="background: rgba(111,66,193,0.1); color: #6f42c1;"></i>
                                    <div>
                                        <strong>Vêtements</strong>
                                        <small class="d-block text-muted">Mode, chaussures, accessoires</small>
                                    </div>
                                </a>
                                <a class="mega-menu-category" href="<?php echo $base_url; ?>pages/tout_parcourir.php?categorie=Maison">
                                    <i class="bi bi-house-heart" style="background: rgba(25,135,84,0.1); color: #198754;"></i>
                                    <div>
                                        <strong>Maison</strong>
                                        <small class="d-block text-muted">Déco, meubles, électroménager</small>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-uppercase text-muted small fw-bold mb-3">&nbsp;</h6>
                                <a class="mega-menu-category" href="<?php echo $base_url; ?>pages/tout_parcourir.php?categorie=Livres">
                                    <i class="bi bi-book" style="background: rgba(253,126,20,0.1); color: #fd7e14;"></i>
                                    <div>
                                        <strong>Livres</strong>
                                        <small class="d-block text-muted">Manuels, romans, BD</small>
                                    </div>
                                </a>
                                <a class="mega-menu-category" href="<?php echo $base_url; ?>pages/tout_parcourir.php?categorie=Sports">
                                    <i class="bi bi-dribbble" style="background: rgba(220,53,69,0.1); color: #dc3545;"></i>
                                    <div>
                                        <strong>Sports</strong>
                                        <small class="d-block text-muted">Équipements, vêtements sport</small>
                                    </div>
                                </a>
                                <a class="mega-menu-category" href="<?php echo $base_url; ?>pages/tout_parcourir.php?categorie=Divers">
                                    <i class="bi bi-three-dots" style="background: rgba(108,117,125,0.1); color: #6c757d;"></i>
                                    <div>
                                        <strong>Divers</strong>
                                        <small class="d-block text-muted">Tout le reste</small>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <hr class="my-3">
                        <div class="text-center">
                            <a href="<?php echo $base_url; ?>pages/tout_parcourir.php" class="btn btn-primary btn-sm">
                                <i class="bi bi-grid"></i> Voir tous les articles
                            </a>
                        </div>
                    </div>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $base_url; ?>pages/notifications.php">
                        <i class="bi bi-bell"></i> Notifications
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link position-relative" href="<?php echo $base_url; ?>pages/panier.php">
                        <i class="bi bi-cart3"></i> Panier
                        <span id="cart-count" class="badge bg-danger rounded-pill d-none">0</span>
                    </a>
                </li>
            </ul>

            <!-- Search bar in navbar -->
            <form class="navbar-search me-3 d-none d-lg-flex" action="<?php echo $base_url; ?>pages/tout_parcourir.php" method="GET">
                <i class="bi bi-search search-icon"></i>
                <input type="text" name="q" class="form-control" placeholder="Rechercher un article..." autocomplete="off">
            </form>

            <ul class="navbar-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
                            <div class="d-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-25" style="width:30px;height:30px;">
                                <i class="bi bi-person-fill" style="font-size:0.9rem;"></i>
                            </div>
                            <?php echo htmlspecialchars($_SESSION['user_prenom']); ?>
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
                            <li><a class="dropdown-item" href="<?php echo $base_url; ?>pages/notifications.php">
                                <i class="bi bi-bell"></i> Notifications
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo $base_url; ?>php/auth.php?action=logout">
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
                    <li class="nav-item ms-2">
                        <a class="nav-link btn btn-outline-light btn-sm px-3 py-1" href="<?php echo $base_url; ?>pages/inscription.php" style="margin-top:4px;">
                            <i class="bi bi-person-plus"></i> Inscription
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
