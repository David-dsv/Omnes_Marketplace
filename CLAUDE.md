# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Omnes MarketPlace** - A student marketplace web application (style "Le Bon Coin") for the Omnes Education community. Students can buy and sell anything (electronics, clothing, books, furniture, sports gear, etc.).

### Key Concept
- Three user roles: **Administrateur**, **Vendeur**, **Acheteur**
- Two sale types: **Achat immédiat**, **Transaction vendeur-client** (negotiation, max 5 rounds)
- Three product tiers: **Articles rares**, **Articles hauts de gamme**, **Articles réguliers**
- Product categories: Électronique, Vêtements, Maison, Livres, Sports, Divers

## Tech Stack (Imposed — 100% Native)

- **Front-end**: HTML5, CSS3, Bootstrap 5.3, Bootstrap Icons, Font Awesome 6.5, JavaScript, jQuery 3.7, Google Fonts (Poppins)
- **Back-end**: PHP 8.x (native, via `php -S localhost:8080`)
- **Database**: MySQL 8+ (native, via Homebrew)
- **Version control**: Git + GitHub
- **CDN libraries**: Bootstrap, jQuery, Font Awesome, Bootstrap Icons (loaded in header.php/footer.php)
- **Forbidden**: WordPress, CMS platforms, Docker in production (Docker examples are for reference only)

## Development Setup (Native)

```bash
# Start MySQL
brew services start mysql

# Create and import database
mysql -u root -e "CREATE DATABASE IF NOT EXISTS omnes_marketplace CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root omnes_marketplace < Maquette/create_database.sql

# Start PHP dev server
cd Marketplace && php -S localhost:8080

# Access at http://localhost:8080
```

## Database Configuration

File: `Marketplace/config/database.php`
- Native: `localhost` / `root` / empty password (auto-fallback)
- Database name: `omnes_marketplace`
- SQL schema: `Maquette/create_database.sql`

## Project Structure

```
ProjetWebAPP/
├── Maquette/                  # Wireframes, storyboard, SQL schema
│   └── create_database.sql    # Main DB import script
├── Marketplace/               # Application root (document root for PHP server)
│   ├── config/database.php    # PDO connection config
│   ├── css/style.css          # All custom styles (2000+ lines)
│   ├── js/script.js           # All custom JS (550+ lines)
│   ├── php/                   # Backend action scripts (article_actions, auth, panier, etc.)
│   ├── images/                # Product and site images
│   ├── includes/              # Shared components: header.php, navbar.php, footer.php
│   ├── pages/                 # All page files
│   │   ├── admin/             # Admin dashboard, gestion articles/vendeurs
│   │   ├── vendeur/           # Vendor dashboard, mes articles, ajouter article
│   │   └── *.php              # Public pages (tout_parcourir, article, panier, etc.)
│   └── index.php              # Homepage
├── Dockerfile.example         # Docker reference (not used in dev)
├── docker-compose.example.yml # Docker Compose reference (not used in dev)
└── .gitignore
```

## Key Architecture Patterns

- **Base URL pattern**: Each page sets `$base_url` relative to its depth (e.g., `''` for index, `'../'` for pages/, `'../../'` for pages/admin/)
- **Shared includes**: Every page includes `header.php` → `navbar.php` → content → `footer.php`
- **Session-based auth**: `$_SESSION['user_id']`, `$_SESSION['user_role']` (admin/vendeur/acheteur)
- **PHP action scripts**: Forms POST to `php/*_actions.php` files which redirect back
- **CSS**: Single `style.css` with CSS variables, animations, responsive breakpoints
- **JS**: Single `script.js` with IntersectionObserver animations, form validation, counters

## Design Choices

- **Style**: Modern & Minimalist, violet/navy palette (Omnes Education colors), glassmorphism navbar
- **Color palette**: Navy (#0D0B2B → #1F1B4E), Purple Brand (#3D1A8A → #6B3FBE), Violet Accent (#8B44FF → #A66FFF)
- **Homepage**: Hero carousel + search bar + animated counters + categories + "Sélection du jour" + "Ventes flash" + "Comment ça marche" + testimonials
- **Map**: Google Maps showing Campus ECE Paris
- **Bonus features**: Rating/review system, discount cards (10-20% for purchases > 100€), special event themes

## Design Rules (STRICT)

- **NO emojis anywhere on the site** — no decorative icons in section titles, no heart/star/lightning icons used as decoration
- **NO hover scale/rotate animations** — no `transform: scale()` or `transform: rotate()` on hover for icons, images, or cards
- **Keep it clean and professional** — avoid effects that look AI-generated (excessive gradients, floating shapes, icon animations)

## Navigation Structure

Accueil | Tout Parcourir | Notifications | Panier | Votre Compte

## Business Rules

- Admin creates/deletes vendor accounts; anyone can create a buyer account
- Negotiation: max 5 rounds, buyer is legally bound if seller accepts
- Payment validation checks credentials exist in DB (no real bank API)
- Purchase confirmation sent via email/SMS simulation
- **No auction system** — only achat immédiat and négociation are supported

## Recent Features Added (Vendor Profile Wall)

### 04/03/2026 - Vendor Profile Wall Feature
- **Database**: Added `photo_url` and `background_url` columns to `utilisateurs` table
- **New Files**:
  - `pages/vendeur/editer_profil.php` — Profile editing page with image upload interface
  - `php/vendeur_actions.php` — Backend image upload/deletion handler
  - Image directories: `images/vendeurs/photos/` and `images/vendeurs/backgrounds/`
- **Updated Files**:
  - `pages/vendeur/dashboard.php` — Displays vendor wall with profile photo and background at the top
  - `includes/navbar.php` — Added "Mon profil" link in vendor dropdown menu
- **Features**:
  - Vendors can upload profile photo (max 5 MB, JPEG/PNG/GIF/WebP)
  - Vendors can upload background image (max 10 MB, JPEG/PNG/GIF/WebP)
  - Auto-deletion of old images when uploading new ones
  - Real-time preview in edit page
  - Vendor wall displays name, photo, and background on dashboard
