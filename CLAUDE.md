# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Omnes MarketPlace** - A student marketplace web application (style "Le Bon Coin") for the Omnes Education community. Students can buy and sell anything (electronics, clothing, books, furniture, sports gear, etc.).

### Key Concept
- Three user roles: **Administrateur**, **Vendeur**, **Acheteur**
- Three sale types: **Achat immédiat**, **Transaction vendeur-client** (negotiation, max 5 rounds), **Meilleure offre** (auction with auto-bidding)
- Three product tiers: **Articles rares**, **Articles hauts de gamme**, **Articles réguliers**
- Product categories: Électronique, Vêtements, Maison, Livres, Sports, Divers

## Tech Stack (Imposed)

- **Front-end**: HTML5, CSS3, Bootstrap, JavaScript, jQuery, AJAX
- **Back-end**: PHP
- **Database**: MySQL
- **Version control**: Git
- **Forbidden**: WordPress or any "ready-made" CMS platform

## Project Structure

```
ProjetWebAPP/
├── Maquette/            # Wireframes, storyboard, design mockups
├── Marketplace/         # Main application code
│   ├── css/             # Stylesheets
│   ├── js/              # JavaScript files
│   ├── php/             # PHP backend scripts
│   ├── images/          # Product and site images
│   ├── sql/             # Database creation and import scripts
│   └── includes/        # Shared PHP components (header, footer, nav)
└── Projet Web APP 2026 - Sujet 1.pdf
```

## Database

MySQL database. Import script located in `Marketplace/sql/`. To set up:
```bash
mysql -u root -p < Marketplace/sql/create_database.sql
```

## Design Choices

- **Style**: Modern & Minimalist, blue & white palette (ECE/Omnes colors)
- **Homepage**: Sélection du jour + Ventes flash/Best-sellers combined
- **Map**: Google Maps showing Campus ECE Paris
- **Bonus features**: Rating/review system, discount cards (10-20% for purchases > 100€), special event themes (Christmas, Valentine's, etc.)

## Navigation Structure

Accueil | Tout Parcourir | Notifications | Panier | Votre Compte

## Business Rules

- Admin creates/deletes vendor accounts; anyone can create a buyer account
- An item cannot be simultaneously sold by auction AND negotiation
- Negotiation: max 5 rounds, buyer is legally bound if seller accepts
- Auction: buyer sets max price, system auto-bids (winner pays current highest + 1€, not their max)
- Payment validation checks credentials exist in DB (no real bank API)
- Purchase confirmation sent via email/SMS simulation
