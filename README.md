# Catalogue de véhicules – Laravel + Vue.js

Petite application qui récupère les véhicules de l'API partenaires Glinche (V1),
les affiche sous forme de cartes et permet de les filtrer par marque.

- **Back-end** : Laravel (dossier `backend/`) – appelle l'API Glinche, normalise les données, expose `GET /api/vehicles`
- **Front-end** : Vue.js 3 + Vite (dossier `frontend/`)
- **Base de données** : MySQL (utilisée par Laravel pour le cache du token et des véhicules)

## Prérequis

- PHP 8.2+ (extension `pdo_mysql` activée) et Composer
- MySQL 8+ (ou MariaDB 10.6+)
- Node.js 20+ et npm

## Installation

```bash
git clone <url-du-depot>
cd <nom-du-depot>

# Back-end
cd backend
composer install
cp .env.example .env
php artisan key:generate

# Créer la base MySQL (adapter l'utilisateur si besoin)
mysql -u root -p -e "CREATE DATABASE glinche_vehicules CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Renseigner DB_* dans backend/.env (voir ci-dessous), puis :
php artisan migrate

# Front-end
cd ../frontend
npm install
```

## Variables d'environnement

### `backend/.env`

| Variable | Rôle |
|---|---|
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Connexion MySQL (`mysql`, `127.0.0.1`, `3306`, `glinche_vehicules`, …) |
| `GLINCHE_EMAIL` / `GLINCHE_PASSWORD` | Identifiants de l'API (fournis dans l'énoncé). Mettre le mot de passe entre guillemets. |
| `GLINCHE_API_BASE_URL` | URL de base de l'API |
| `GLINCHE_AUTH_MODE` | `token` (login puis Bearer) ou `basic` |
| `GLINCHE_LOGIN_PATH`, `GLINCHE_VEHICLES_PATH` | Chemins des endpoints V1 |
| `GLINCHE_VEHICLES_CACHE_TTL` | Durée du cache des véhicules en secondes (`0` pour le désactiver) |

Les identifiants restent **uniquement côté serveur** : le front ne les voit jamais.
`.env` est ignoré par Git ; seul `.env.example` (sans secret) est versionné.

### `frontend/.env` (optionnel)

`VITE_API_BASE_URL` : vide en développement (le proxy Vite redirige `/api` vers Laravel).

## Lancer le projet

Deux terminaux :

```bash
# Terminal 1 – Laravel (http://127.0.0.1:8000)
cd backend
php artisan serve

# Terminal 2 – Vue.js (http://localhost:5173)
cd frontend
npm run dev
```

Ouvrir http://localhost:5173.

Utile pour vérifier la connexion à l'API : `php artisan glinche:inspect`
(affiche la réponse brute et la version normalisée d'un véhicule).
Tests : `php artisan test`.

## Choix techniques

- **Service dédié** (`app/Services/GlincheApiService.php`) : toute la communication avec l'API (authentification, pagination, normalisation) est isolée du contrôleur, qui reste très court.
- **Token mis en cache** : on ne se ré-authentifie pas à chaque requête ; en cas de 401, le token est renouvelé une fois automatiquement.
- **Cache des véhicules (5 min)** : évite de solliciter l'API à chaque visite. Il est stocké dans MySQL (driver de cache `database`, table créée par `php artisan migrate`).
- **Normalisation** : le front reçoit un format simple et stable (`brand`, `model`, `version`, `year`, `mileage`, `energy`, `gearbox`, `price`, `image`), indépendant du format de l'API.
- **Filtre côté client** : la liste est chargée une fois, les marques sont déduites des véhicules reçus, et le filtrage est instantané (aucune requête supplémentaire).
- **Proxy Vite** en développement : pas de configuration CORS nécessaire.
- **Gestion des états** : chargement, erreur avec bouton « Réessayer », liste vide, image de remplacement.

## Difficultés rencontrées

> À COMPLÉTER avec ton expérience réelle (authentification, format de la réponse, images, etc.).

## Améliorations possibles avec plus de temps

- Enregistrer les véhicules en base (commande de synchronisation planifiée) plutôt qu'un simple cache.
- Pagination ou « charger plus » côté interface, recherche texte, tri par prix / kilométrage.
- Tests front (Vitest) pour le filtre par marque.
- Dockerisation (Laravel + MySQL + front) pour un lancement en une commande.
