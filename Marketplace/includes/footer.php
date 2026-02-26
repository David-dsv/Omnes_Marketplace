    <footer class="bg-dark text-white mt-5 pt-4 pb-3">
        <div class="container">
            <div class="row">
                <!-- Informations -->
                <div class="col-md-4 mb-3">
                    <h5><i class="bi bi-shop"></i> Omnes MarketPlace</h5>
                    <p class="text-muted">La marketplace de la communauté Omnes Education. Achetez et vendez entre étudiants en toute confiance.</p>
                </div>
                <!-- Liens rapides -->
                <div class="col-md-2 mb-3">
                    <h6>Navigation</h6>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo $base_url; ?>index.php" class="text-muted text-decoration-none">Accueil</a></li>
                        <li><a href="<?php echo $base_url; ?>pages/tout_parcourir.php" class="text-muted text-decoration-none">Tout Parcourir</a></li>
                        <li><a href="<?php echo $base_url; ?>pages/connexion.php" class="text-muted text-decoration-none">Connexion</a></li>
                        <li><a href="<?php echo $base_url; ?>pages/inscription.php" class="text-muted text-decoration-none">Inscription</a></li>
                    </ul>
                </div>
                <!-- Contact -->
                <div class="col-md-3 mb-3">
                    <h6>Contact</h6>
                    <ul class="list-unstyled text-muted">
                        <li><i class="bi bi-geo-alt"></i> 37 Quai de Grenelle, 75015 Paris</li>
                        <li><i class="bi bi-telephone"></i> 01 44 39 06 00</li>
                        <li><i class="bi bi-envelope"></i> contact@omnesmarketplace.fr</li>
                    </ul>
                </div>
                <!-- Google Maps -->
                <div class="col-md-3 mb-3">
                    <h6>Campus ECE Paris</h6>
                    <div class="ratio ratio-4x3">
                        <iframe
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2625.4!2d2.2833!3d48.8511!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47e6701b4f58251b%3A0x167f5a60fb94aa76!2sECE%20Paris!5e0!3m2!1sfr!2sfr!4v1700000000000!5m2!1sfr!2sfr"
                            style="border:0; border-radius: 8px;"
                            allowfullscreen=""
                            loading="lazy">
                        </iframe>
                    </div>
                </div>
            </div>
            <hr class="border-secondary">
            <div class="text-center text-muted">
                <small>&copy; 2026 Omnes MarketPlace - Projet Web APP Omnes Education. Tous droits réservés.</small>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo $base_url; ?>js/script.js"></script>
</body>
</html>
