<?php
session_start();
$base_url = '../../';
$page_title = 'Éditer mon profil vendeur';
require_once $base_url . 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'vendeur') {
    header('Location: ' . $base_url . 'pages/connexion.php');
    exit;
}

$uid = $_SESSION['user_id'];

try {
    $user = $pdo->prepare("SELECT prenom, nom, photo_url, background_url FROM utilisateurs WHERE id = :uid");
    $user->execute([':uid' => $uid]);
    $user_data = $user->fetch();
    
    // Logging pour déboguer
    error_log("User data loaded for ID $uid: " . json_encode($user_data));
    
    // Si pas de données, créer une structure par défaut
    if (!$user_data) {
        error_log("No user data found, using defaults");
        $user_data = [
            'prenom' => $_SESSION['user_prenom'] ?? 'Vendeur',
            'nom' => $_SESSION['user_nom'] ?? '',
            'photo_url' => null,
            'background_url' => null
        ];
    }
} catch (PDOException $e) {
    error_log("Error loading user data: " . $e->getMessage());
    $user_data = [
        'prenom' => $_SESSION['user_prenom'] ?? 'Vendeur',
        'nom' => $_SESSION['user_nom'] ?? '',
        'photo_url' => null,
        'background_url' => null
    ];
}

include $base_url . 'includes/header.php';
include $base_url . 'includes/navbar.php';
?>

<main class="py-4">
    <div class="container">
        <div class="mb-4">
            <h1 class="h3 mb-1"><i class="bi bi-person-circle me-2"></i>Éditer mon profil vendeur</h1>
            <p class="text-muted mb-0">Personnalisez votre mur de vendeur avec votre photo et votre image de fond</p>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">

                        <!-- Section Photo de profil -->
                        <div class="mb-5">
                            <h5 class="fw-bold mb-3"><i class="bi bi-image me-2"></i>Photo de profil</h5>
                            
                            <div class="row">
                                <div class="col-md-4 text-center mb-3">
                                    <div class="profile-photo-container mb-3" style="border: 2px solid #e9ecef; border-radius: 50%; overflow: hidden; width: 150px; height: 150px; margin: 0 auto;">
                                        <?php if ($user_data && $user_data['photo_url']): ?>
                                            <img id="photoPreview" src="<?php echo htmlspecialchars($user_data['photo_url']); ?>" alt="Photo profil" style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            <div id="photoPreview" style="width: 100%; height: 100%; background-color: #e9ecef; display: flex; align-items: center; justify-content: center;">
                                                <i class="bi bi-person" style="font-size: 3rem; color: #999;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="photoInput" class="form-label">Sélectionner une photo</label>
                                        <input type="file" class="form-control" id="photoInput" accept="image/jpeg,image/png,image/gif,image/webp">
                                        <small class="text-muted">Format: JPEG, PNG, GIF ou WebP. Taille max: 5 MB</small>
                                    </div>
                                    <div id="photoProgress" style="display: none;" class="mb-3">
                                        <div class="progress">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                                        </div>
                                        <small class="text-muted">Téléchargement en cours...</small>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-primary btn-sm" id="uploadPhotoBtn" disabled>
                                            <i class="bi bi-upload me-1"></i>Valider et envoyer
                                        </button>
                                        <?php if ($user_data && $user_data['photo_url']): ?>
                                            <button class="btn btn-outline-danger btn-sm" id="deletePhotoBtn">
                                                <i class="bi bi-trash me-1"></i>Supprimer
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Section Image de fond -->
                        <div class="mb-5">
                            <h5 class="fw-bold mb-3"><i class="bi bi-image me-2"></i>Image de fond</h5>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="background-preview" style="border: 2px solid #e9ecef; border-radius: 8px; overflow: hidden; height: 200px; background-color: #f8f9fa;">
                                        <?php if ($user_data && $user_data['background_url']): ?>
                                            <img id="backgroundPreview" src="<?php echo htmlspecialchars($user_data['background_url']); ?>" alt="Image fond" style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            <div id="backgroundPreview" style="width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white;">
                                                <i class="bi bi-image" style="font-size: 3rem;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="backgroundInput" class="form-label">Sélectionner une image de fond</label>
                                        <input type="file" class="form-control" id="backgroundInput" accept="image/jpeg,image/png,image/gif,image/webp">
                                        <small class="text-muted">Format: JPEG, PNG, GIF ou WebP. Taille max: 10 MB</small>
                                    </div>
                                    <div id="backgroundProgress" style="display: none;" class="mb-3">
                                        <div class="progress">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                                        </div>
                                        <small class="text-muted">Téléchargement en cours...</small>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-primary btn-sm" id="uploadBackgroundBtn" disabled>
                                            <i class="bi bi-upload me-1"></i>Valider et envoyer
                                        </button>
                                        <?php if ($user_data && $user_data['background_url']): ?>
                                            <button class="btn btn-outline-danger btn-sm" id="deleteBackgroundBtn">
                                                <i class="bi bi-trash me-1"></i>Supprimer
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Section Informations -->
                        <div class="mb-3">
                            <h5 class="fw-bold mb-3"><i class="bi bi-info-circle me-2"></i>Informations</h5>
                            <p class="text-muted">
                                Votre <strong>nom</strong> (<?php echo htmlspecialchars($user_data['prenom'] . ' ' . $user_data['nom']); ?>) s'affichera automatiquement sur votre mur de vendeur.<br>
                                Pour changer votre nom, veuillez accéder à votre <a href="<?php echo $base_url; ?>pages/compte.php">page de compte</a>.
                            </p>
                        </div>

                    </div>
                </div>

                <!-- Messages -->
                <div id="alertContainer" class="mt-3"></div>

            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm bg-light">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3"><i class="bi bi-eye me-2"></i>Aperçu de votre mur</h5>
                        <p class="text-muted small mb-3">Ceci est un aperçu de la façon dont votre profil apparaîtra aux clients:</p>
                        
                        <div class="vendor-wall-preview" style="border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden; background: white;">
                            <!-- Background -->
                            <div style="height: 120px; overflow: hidden; background-color: #f8f9fa;">
                                <?php if ($user_data && $user_data['background_url']): ?>
                                    <img id="previewBackground" src="<?php echo htmlspecialchars($user_data['background_url']); ?>" alt="Background" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <div id="previewBackground" style="width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"></div>
                                <?php endif; ?>
                            </div>

                            <!-- Profile info -->
                            <div style="padding: 1.5rem; text-align: center; position: relative; margin-top: -40px;">
                                <div style="border: 3px solid white; border-radius: 50%; overflow: hidden; width: 80px; height: 80px; margin: 0 auto 1rem; background-color: #e9ecef; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                    <?php if ($user_data && $user_data['photo_url']): ?>
                                        <img id="previewPhoto" src="<?php echo htmlspecialchars($user_data['photo_url']); ?>" alt="Photo" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <div id="previewPhoto" style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background-color: #e9ecef;">
                                            <i class="bi bi-person" style="font-size: 2rem; color: #999;"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($user_data['prenom'] . ' ' . $user_data['nom']); ?></h6>
                                <small class="text-muted">Vendeur</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-2"><i class="bi bi-lightbulb me-2"></i>Conseils</h6>
                        <ul class="small text-muted mb-0">
                            <li>Utilisez une photo de profil claire et professionnelle</li>
                            <li>Choisissez une image de fond qui représente votre style</li>
                            <li>Les images de haute qualité donnent une meilleure impression</li>
                            <li>Assurez-vous que les images sont lisibles et appropriées</li>
                        </ul>
                    </div>
                </div>

                <a href="dashboard.php" class="btn btn-outline-secondary w-100 mt-3">
                    <i class="bi bi-arrow-left me-1"></i>Retour au tableau de bord
                </a>
            </div>
        </div>
    </div>
</main>

<style>
    .vendor-wall-preview {
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }

    .profile-photo-container img,
    .background-preview img {
        transition: transform 0.3s ease;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const photoInput = document.getElementById('photoInput');
    const backgroundInput = document.getElementById('backgroundInput');
    const uploadPhotoBtn = document.getElementById('uploadPhotoBtn');
    const uploadBackgroundBtn = document.getElementById('uploadBackgroundBtn');
    const deletePhotoBtn = document.getElementById('deletePhotoBtn');
    const deleteBackgroundBtn = document.getElementById('deleteBackgroundBtn');
    const alertContainer = document.getElementById('alertContainer');

    // Active le bouton quand un fichier est sélectionné
    photoInput.addEventListener('change', function() {
        uploadPhotoBtn.disabled = !this.files.length;
    });

    backgroundInput.addEventListener('change', function() {
        uploadBackgroundBtn.disabled = !this.files.length;
    });

    // Upload photo
    uploadPhotoBtn.addEventListener('click', function() {
        uploadFile('photo', photoInput);
    });

    // Upload background
    uploadBackgroundBtn.addEventListener('click', function() {
        uploadFile('background', backgroundInput);
    });

    // Delete photo
    if (deletePhotoBtn) {
        deletePhotoBtn.addEventListener('click', function() {
            deleteFile('photo');
        });
    }

    // Delete background
    if (deleteBackgroundBtn) {
        deleteBackgroundBtn.addEventListener('click', function() {
            deleteFile('background');
        });
    }

    function uploadFile(type, input) {
        if (!input.files.length) return;

        const formData = new FormData();
        formData.append('action', 'upload_' + type);
        formData.append(type, input.files[0]);

        const progressDiv = document.getElementById(type === 'photo' ? 'photoProgress' : 'backgroundProgress');
        uploadPhotoBtn.disabled = true;
        uploadBackgroundBtn.disabled = true;

        progressDiv.style.display = 'block';

        fetch('<?php echo $base_url; ?>php/vendeur_actions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            progressDiv.style.display = 'none';
            console.log('Upload response:', data);
            if (data.success) {
                showAlert('success', '✓ ' + data.message);
                // Rafraîchir l'image
                if (type === 'photo') {
                    console.log('Updating photo preview with URL:', data.photo_url);
                    updatePhotoPreview(data.photo_url);
                    photoInput.value = '';
                } else {
                    console.log('Updating background preview with URL:', data.background_url);
                    updateBackgroundPreview(data.background_url);
                    backgroundInput.value = '';
                }
            } else {
                console.error('Upload failed:', data.message);
                showAlert('danger', data.message || 'Erreur lors du téléchargement');
            }
        })
        .catch(error => {
            progressDiv.style.display = 'none';
            showAlert('danger', error.message || 'Erreur lors du téléchargement');
        })
        .finally(() => {
            uploadPhotoBtn.disabled = false;
            uploadBackgroundBtn.disabled = false;
        });
    }

    function deleteFile(type) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette image ?')) return;

        const formData = new FormData();
        formData.append('action', 'delete_' + type);

        fetch('<?php echo $base_url; ?>php/vendeur_actions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', data.message);
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert('danger', data.message);
            }
        })
        .catch(error => {
            showAlert('danger', 'Erreur lors de la suppression');
        });
    }

    function updatePhotoPreview(url) {
        // Ajouter un timestamp pour éviter le cache
        const cacheBuster = '?t=' + Date.now();
        const fullUrl = url + cacheBuster;
        console.log('Loading photo preview from URL:', fullUrl);
        
        // Vérifier que les éléments existent
        const preview = document.getElementById('photoPreview');
        const previewPhoto = document.getElementById('previewPhoto');
        
        if (!preview || !previewPhoto) {
            console.error('Éléments de prévisualisation non trouvés');
            return;
        }
        
        const img = new Image();
        img.onload = function() {
            console.log('Image loaded successfully:', fullUrl);
            preview.innerHTML = '<img src="' + fullUrl + '" alt="Photo profil" style="width: 100%; height: 100%; object-fit: cover;">';
            previewPhoto.innerHTML = '<img src="' + fullUrl + '" alt="Photo" style="width: 100%; height: 100%; object-fit: cover;">';
        };
        img.onerror = function() {
            console.error('Impossible de charger l\'image: ' + fullUrl);
            showAlert('danger', 'Erreur: Impossible de charger l\'image');
        };
        img.src = fullUrl;
    }

    function updateBackgroundPreview(url) {
        // Ajouter un timestamp pour éviter le cache
        const cacheBuster = '?t=' + Date.now();
        const fullUrl = url + cacheBuster;
        console.log('Loading background preview from URL:', fullUrl);
        
        // Vérifier que les éléments existent
        const preview = document.getElementById('backgroundPreview');
        const previewBg = document.getElementById('previewBackground');
        
        if (!preview || !previewBg) {
            console.error('Éléments de prévisualisation non trouvés');
            return;
        }
        
        const img = new Image();
        img.onload = function() {
            console.log('Background image loaded successfully:', fullUrl);
            preview.innerHTML = '<img src="' + fullUrl + '" alt="Image fond" style="width: 100%; height: 100%; object-fit: cover;">';
            previewBg.innerHTML = '<img src="' + fullUrl + '" alt="Background" style="width: 100%; height: 100%; object-fit: cover;">';
        };
        img.onerror = function() {
            console.error('Impossible de charger l\'image: ' + fullUrl);
            showAlert('danger', 'Erreur: Impossible de charger l\'image');
        };
        img.src = fullUrl;
    }

    function showAlert(type, message) {
        alertContainer.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <strong>${type === 'success' ? 'Succès!' : 'Erreur!'}</strong> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        // Scroll vers le message
        alertContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        
        // Auto-dismiss après 6 secondes pour les succès
        if (type === 'success') {
            setTimeout(() => {
                const alert = alertContainer.querySelector('.alert');
                if (alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 6000);
        }
    }
});
</script>

<?php include $base_url . 'includes/footer.php'; ?>
