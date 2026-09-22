# TechStore Legacy

## Présentation

TechStore Legacy est une boutique fictive de matériel informatique conçue comme support pédagogique pour des exercices de maintenance et d'évolution logicielle.

L'application permet de consulter un catalogue, rechercher des produits, se connecter, gérer un panier, valider une commande et consulter un historique de commandes. Un compte responsable magasin permet aussi de consulter les commandes.

## Prérequis

- PHP 8.1 ou supérieur
- Composer
- Extensions PHP : `pdo`, `pdo_sqlite`, `json`

## Installation

```bash
composer install
cp .env.example .env
php scripts/reset_database.php
```

Sous Windows PowerShell :

```powershell
Copy-Item .env.example .env
php scripts/reset_database.php
```

## Base de données

La base SQLite locale est recréée avec :

```bash
php scripts/reset_database.php
```

La commande supprime la base existante, recrée le schéma et recharge les données fictives.

## Lancement

```bash
php -S localhost:8001 -t public
```

Ouvrir ensuite :

```text
http://localhost:8001
```

## Tests

```bash
vendor/bin/phpunit
```

ou :

```bash
composer test
```

## Comptes de démonstration

| Nom | Email | Mot de passe | Rôle |
| --- | --- | --- | --- |
| Alice Martin | alice.martin@example.test | password | customer |
| Bob Dupont | bob.dupont@example.test | password | customer |
| Sophie Bernard | sophie.bernard@example.test | password | manager |

## Fonctionnalités

- Catalogue public de produits informatiques
- Recherche dynamique par nom et tri simple
- Connexion avec jeton JWT
- Panier local avec quantités et remises
- Validation de commande
- Historique client
- Consultation des commandes pour le responsable magasin

## Structure

- `public/` : point d'entrée HTTP, endpoints API, assets CSS et JavaScript
- `src/Controller/` : contrôleurs web et API
- `src/Repository/` : accès aux données via PDO
- `src/Service/` : logique applicative principale
- `src/Auth/` : authentification et jetons
- `src/Database/` : connexion SQLite
- `src/Support/` : helpers historiques
- `views/` : vues PHP
- `database/` : schéma et données SQL
- `scripts/` : scripts utilitaires locaux
- `tests/` : tests PHPUnit existants
