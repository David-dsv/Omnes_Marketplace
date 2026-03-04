# 🚀 Guide de Test - Vendor Profile Wall

## Prérequis

✅ Base de données `omnes_marketplace` créée avec la dernière version de `Maquette/create_database.sql`
✅ Les colonnes `photo_url` et `background_url` existent dans la table `utilisateurs`
✅ Les dossiers `/Marketplace/images/vendeurs/photos/` et `/Marketplace/images/vendeurs/backgrounds/` sont créés
✅ Le serveur PHP est en cours d'exécution

## Étape 1: Vérifier la Structure de la Base de Données

### Option A: Via MySQL
```bash
mysql -u root -p omnes_marketplace
> DESCRIBE utilisateurs;
```

Vérifiez que les colonnes existent:
```
photo_url      | varchar(500) | NO   | NULL
background_url | varchar(500) | NO   | NULL
```

### Option B: Via phpMyAdmin
1. Aller sur `http://localhost/phpmyadmin`
2. Sélectionner la BDD `omnes_marketplace`
3. Table `utilisateurs`
4. Onglet "Structure"
5. Chercher `photo_url` et `background_url` (doivent être présentes)

## Étape 2: Vérifier les Dossiers d'Upload

Vérifier que ces dossiers existent:
```
Marketplace/
├── images/
│   └── vendeurs/
│       ├── photos/          ✅ Doit exister
│       └── backgrounds/     ✅ Doit exister
```

Vérifier les permissions (sous Linux):
```bash
ls -la Marketplace/images/vendeurs/
# Doit montrer: drwxr-xr-x pour photos et backgrounds
```

## Étape 3: Se Connecter en tant que Vendeur

1. Aller sur `http://localhost:8080` (ou votre adresse)
2. Cliquer sur "Connexion"
3. Utiliser les identifiants d'un vendeur de test:
   - Email: `vendeur@vendeur.com`
   - Mot de passe: `vendeur123`
4. ✅ Vous êtes connecté en tant que vendeur

## Étape 4: Vérifier le Dashboard (pas de Warning)

1. Vous êtes automatiquement redirigé vers `/pages/vendeur/dashboard.php`
2. **Important:** Vérifier dans la console serveur qu'il n'y a pas de warning:
   ```
   ❌ Warning: Trying to access array offset on value of type null...
   ```
3. ✅ Vous devez voir le "Mur du vendeur" avec:
   - Image de fond (dégradé bleu-violet par défaut)
   - Photo circulaire (icône de personne par défaut)
   - Nom du vendeur
   - Bouton "Éditer mon profil"

## Étape 5: Éditer le Profil

1. Sur le dashboard, cliquer sur "Éditer mon profil"
   - OU menu dropdown en haut à droite → "Mon profil"
2. Page `/pages/vendeur/editer_profil.php` doit charger
3. ✅ Vérifier qu'il n'y a pas d'erreurs PHP

## Étape 6: Télécharger une Photo de Profil

1. Section "Photo de profil"
2. Cliquer sur "Sélectionner une photo"
3. Choisir une image locale (JPEG, PNG, GIF ou WebP)
4. **Important:** Le bouton doit maintenant dire "**Valider et envoyer**" (pas "Télécharger")
5. Cliquer sur "**Valider et envoyer**"
6. ✅ Observer:
   - Barre de progression s'affiche
   - Message "**✓ Succès! Photo uploadée avec succès**" apparaît
   - La photo dans le preview se met à jour en temps réel
7. ✅ L'image ne doit PAS être visible dans le dossier avant d'être validée

## Étape 7: Vérifier le Fichier Uploadé

Dans le dossier `/Marketplace/images/vendeurs/photos/`:
```
vendeur_[ID]_[TIMESTAMP].[EXT]
```
Exemple: `vendeur_13_1709550123.jpg`

## Étape 8: Télécharger une Image de Fond

Même processus qu'Étape 6, mais pour l'image de fond:
1. Section "Image de fond"
2. Cliquer "Sélectionner une image de fond"
3. Cliquer "**Valider et envoyer**"
4. ✅ L'image de fond doit s'afficher dans le preview

## Étape 9: Vérifier le Dashboard

1. Retourner au dashboard (`/pages/vendeur/dashboard.php`)
2. ✅ Le "Mur du vendeur" doit afficher:
   - ✅ L'image de fond personnalisée
   - ✅ La photo de profil personnalisée
   - ✅ Le nom du vendeur
   - ✅ Badge "Vendeur"
   - ✅ Bouton "Éditer mon profil"

## Étape 10: Tester la Suppression

1. Retourner à "Éditer mon profil"
2. Cliquer "Supprimer" pour la photo ou l'image de fond
3. Confirmer la suppression
4. ✅ Message "✓ Image supprimée avec succès"
5. L'image par défaut doit réapparaître
6. Vérifier le dossier: le fichier doit être supprimé

## Checklist de Validation

### Base de Données
- [ ] Colonnes `photo_url` et `background_url` existent
- [ ] Table `utilisateurs` contient ces colonnes

### Dossiers
- [ ] `/Marketplace/images/vendeurs/photos/` existe
- [ ] `/Marketplace/images/vendeurs/backgrounds/` existe
- [ ] Permissions correctes (755)

### Dashboard (Pas d'Erreurs)
- [ ] Page charge sans warning
- [ ] Pas de message d'erreur PHP
- [ ] Mur du vendeur s'affiche

### Édition de Profil
- [ ] Page charge sans erreur
- [ ] Bouton dit "Valider et envoyer" (pas "Télécharger")
- [ ] Barre de progression s'affiche
- [ ] Message de succès s'affiche

### Upload Photo
- [ ] Photo se met à jour en temps réel
- [ ] Fichier créé dans `images/vendeurs/photos/`
- [ ] Nom du fichier suit le format `vendeur_ID_TIMESTAMP.EXT`
- [ ] Chemin sauvegardé en BD (`images/vendeurs/photos/...`)

### Upload Fond
- [ ] Fond se met à jour en temps réel
- [ ] Fichier créé dans `images/vendeurs/backgrounds/`
- [ ] Chemin sauvegardé en BD

### Dashboard Après Upload
- [ ] Photo personnalisée s'affiche
- [ ] Fond personnalisé s'affiche
- [ ] Pas de rechargement brutal

### Suppression
- [ ] Fichier supprimé du serveur
- [ ] BD mise à jour (colonne = NULL)
- [ ] Image par défaut réapparaît

---

## Dépannage

### Warning "Trying to access array offset"
**Cause:** La BDD ne retourne pas de données
**Solution:** Vérifier que le vendeur connecté existe dans la table `utilisateurs`

### Photos ne s'affichent pas
**Cause:** Chemins incorrects ou permissions insuffisantes
**Solution:** 
- Vérifier les chemins dans la BD: `SELECT photo_url FROM utilisateurs WHERE id = ...`
- Vérifier les permissions des dossiers: `chmod 755 images/vendeurs/*`

### Bouton dit encore "Télécharger"
**Cause:** Vieux cache du navigateur
**Solution:** Forcer le rechargement: `Ctrl+F5` ou `Cmd+Shift+R`

### Fichiers uploadés mais photos ne s'affichent pas
**Cause:** Chemins absolus vs relatifs
**Solution:** Les chemins en BD doivent être relatifs: `images/vendeurs/photos/...` (pas `/var/www/html/...`)

---

## Logs Utiles

Vérifier les erreurs PHP:
```bash
# Sur le serveur
tail -f /var/log/php-fpm/error.log
# Ou dans Apache
tail -f /var/log/apache2/error.log
```

Vérifier les permissions:
```bash
ls -la Marketplace/images/vendeurs/
chmod 755 Marketplace/images/vendeurs/photos/
chmod 755 Marketplace/images/vendeurs/backgrounds/
```

---

**Dernière mise à jour:** 04/03/2026
**Version:** 1.0 (Corrigée et optimisée)
