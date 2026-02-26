    <footer class="mt-5 pt-5 pb-4">
        <div class="container">
            <div class="row g-4">
                <!-- Brand & Description -->
                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="footer-brand">
                        <img src="<?php echo $base_url; ?>images/Logo_Omnes_Éducation.svg.png" alt="Omnes Education" class="footer-logo"> MarketPlace
                    </div>
                    <p class="text-muted mb-3" style="font-size: 0.9rem; color: rgba(255,255,255,0.6) !important;">
                        La marketplace de la communauté Omnes Education. Achetez et vendez entre étudiants en toute confiance, avec négociation et achat immédiat.
                    </p>
                    <div class="social-links">
                        <a href="#" title="Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="#" title="Instagram"><i class="bi bi-instagram"></i></a>
                        <a href="#" title="Twitter"><i class="bi bi-twitter-x"></i></a>
                        <a href="#" title="LinkedIn"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="col-lg-2 col-md-6 col-6 mb-3">
                    <h6>Navigation</h6>
                    <ul class="list-unstyled footer-links">
                        <li><a href="<?php echo $base_url; ?>index.php"><i class="bi bi-chevron-right"></i> Accueil</a></li>
                        <li><a href="<?php echo $base_url; ?>pages/tout_parcourir.php"><i class="bi bi-chevron-right"></i> Tout Parcourir</a></li>
                        <li><a href="<?php echo $base_url; ?>pages/avis.php"><i class="bi bi-chevron-right"></i> Avis</a></li>
                        <li><a href="<?php echo $base_url; ?>pages/connexion.php"><i class="bi bi-chevron-right"></i> Connexion</a></li>
                        <li><a href="<?php echo $base_url; ?>pages/inscription.php"><i class="bi bi-chevron-right"></i> Inscription</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div class="col-lg-3 col-md-6 mb-3">
                    <h6>Contact</h6>
                    <ul class="list-unstyled footer-contact">
                        <li>
                            <i class="bi bi-geo-alt-fill"></i>
                            <span>37 Quai de Grenelle,<br>75015 Paris, France</span>
                        </li>
                        <li>
                            <i class="bi bi-telephone-fill"></i>
                            <span>01 44 39 06 00</span>
                        </li>
                        <li>
                            <i class="bi bi-envelope-fill"></i>
                            <span>contact@omnesmarketplace.fr</span>
                        </li>
                    </ul>
                </div>

                <!-- Newsletter + Map -->
                <div class="col-lg-3 col-md-6 mb-3">
                    <h6>Newsletter</h6>
                    <p style="font-size: 0.85rem; color: rgba(255,255,255,0.55);">Recevez nos offres et nouveautés</p>
                    <form class="newsletter-form" onsubmit="event.preventDefault(); this.querySelector('input').value=''; if(typeof showToast === 'function') showToast('Inscription réussie !', 'success');">
                        <input type="email" class="form-control" placeholder="Votre email..." required>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i></button>
                    </form>
                    <div class="mt-3">
                        <h6 class="mb-2" style="font-size: 0.85rem;">Campus ECE Paris</h6>
                        <div class="ratio ratio-16x9" style="border-radius: 10px; overflow: hidden;">
                            <iframe
                                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2625.4!2d2.2833!3d48.8511!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47e6701b4f58251b%3A0x167f5a60fb94aa76!2sECE%20Paris!5e0!3m2!1sfr!2sfr!4v1700000000000!5m2!1sfr!2sfr"
                                style="border:0;"
                                allowfullscreen=""
                                loading="lazy">
                            </iframe>
                        </div>
                    </div>
                </div>
            </div>

            <div class="footer-bottom text-center">
                <small style="color: rgba(255,255,255,0.45);">
                    &copy; 2026 Omnes MarketPlace - Projet Web APP Omnes Education. Tous droits réservés.
                    <span class="d-none d-md-inline mx-2">|</span>
                    <br class="d-md-none">
                    Fait par les étudiants ECE
                </small>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button class="back-to-top" id="backToTop" title="Retour en haut">
        <i class="bi bi-chevron-up"></i>
    </button>

    <!-- Confirmation Modal (reusable) -->
    <div class="modal fade modal-confirm" id="confirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <i class="bi bi-exclamation-triangle-fill text-warning display-4"></i>
                    <h5 class="mt-3 mb-2" id="confirmModalTitle">Confirmer</h5>
                    <p class="text-muted" id="confirmModalText">Êtes-vous sûr de vouloir effectuer cette action ?</p>
                </div>
                <div class="modal-footer justify-content-center border-0 pt-0 pb-4">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-danger" id="confirmModalBtn">Confirmer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo $base_url; ?>js/script.js"></script>
</body>
</html>
