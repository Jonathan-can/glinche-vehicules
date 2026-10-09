# Catalogue de véhicules – Laravel + Vue.js

Petite application qui récupère les véhicules de l'API partenaires Glinche (V1),
les affiche sous forme de cartes et permet de les filtrer par marque.

- **Back-end** : Laravel (dossier `backend/`) – appelle l'API Glinche, normalise les données, expose `GET /api/vehicles`
- **Front-end** : Vue.js 3 + Vite + Bootstrap 5 (dossier `frontend/`)
- **Base de données** : MySQL (utilisée par Laravel pour le cache du token et des véhicules)

## Prérequis

- PHP 8.3+ (extensions `pdo_mysql` et `curl` activées) et Composer
- MySQL 8+ (ou MariaDB 10.6+)
- Node.js 22.18+ et npm

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
mysql -u root -p -e "CREATE DATABASE glinche_automobiles CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

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
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Connexion MySQL (`mysql`, `127.0.0.1`, `3306`, `glinche_automobiles`, …) |
| `GLINCHE_EMAIL` / `GLINCHE_PASSWORD` | Identifiants de l'API (fournis dans l'énoncé). Mettre le mot de passe entre guillemets. |
| `GLINCHE_API_BASE_URL` | URL de base de l'API |
| `GLINCHE_LOGIN_PATH`, `GLINCHE_VEHICLES_PATH` | Chemins des routes de connexion et des véhicules |
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

- **Service dédié** (`app/Services/GlincheApiService.php`) : toute la communication avec l'API (authentification, récupération, normalisation) est isolée du contrôleur, qui reste très court.
- **Token mis en cache** : on ne se ré-authentifie pas à chaque requête ; en cas de 401, le token est renouvelé une fois automatiquement.
- **Cache des véhicules (5 min)** : évite de solliciter l'API à chaque visite. Il est stocké dans MySQL (driver de cache `database`, table créée par `php artisan migrate`).
- **Normalisation** : le front reçoit un format simple et stable (`brand`, `model`, `version`, `year`, `mileage`, `energy`, `gearbox`, `price`, `image`), indépendant du format de l'API.
- **Filtre côté client** : la liste est chargée une fois, les marques sont déduites des véhicules reçus, et le filtrage est instantané (aucune requête supplémentaire).
- **Proxy Vite** en développement : pas de configuration CORS nécessaire.
- **Gestion des états** : chargement, erreur avec bouton « Réessayer », liste vide, image de remplacement.
- **Bootstrap 5** pour le responsive : grille de cartes sur 1, 2, 3 ou 4 colonnes selon la largeur de l'écran.
- **Codes traduits côté serveur** : l'API renvoie des codes (`GO`, `ES`, `AUT`…), convertis en libellés lisibles (Diesel, Essence, Automatique…) lors de la normalisation.

## Difficultés rencontrées

### Des technologies nouvelles pour moi

Je maîtrise Symfony, mais je n'avais jamais utilisé Laravel, ni Vue.js. C'est la principale difficulté de ce test : les notions sont proches de celles que je connais (routes, contrôleurs, services, injection de dépendances), mais la structure du projet, les commandes `artisan` et la syntaxe diffèrent. Côté front, les composants Vue, la réactivité (`ref`, `computed`) et les composables étaient une découverte complète.

Pour tenir le délai, je me suis appuyé sur un assistant IA (Claude) pour développer le site. Je préfère l'indiquer clairement plutôt que de présenter ce code comme entièrement le mien.

### Les obstacles techniques

- **Certificat SSL sous Windows** : PHP refusait la connexion HTTPS à l'API (erreur cURL 60). Il a fallu installer un fichier de certificats (`cacert.pem`) et le déclarer dans `php.ini` (`curl.cainfo` et `openssl.cafile`). Ce réglage dépend de la machine et peut être nécessaire pour lancer le projet sous Windows.
- **Format de la réponse** : les informations utiles ne sont pas à la racine de chaque véhicule mais dans un sous-objet `vehicle` (`vehicle.manufacturer`, `vehicle.finish`, `vehicle.prices.merchantPrice`…). Au premier essai, presque tous les champs étaient vides.
- **Codes à traduire** : l'énergie et la boîte de vitesses arrivent sous forme de codes (`GO`, `ES`, `EH`, `EL`, `AUT`, `MAN`), convertis en libellés lisibles côté serveur.
- **Choix du prix** : l'API fournit deux prix (`merchantPrice` et `catalogPrice`). Le prix catalogue valant 0 sur certains véhicules, c'est `merchantPrice` qui est affiché. Sa nature exacte (HT ou TTC) reste à confirmer.

## Améliorations possibles avec plus de temps

- Enregistrer les véhicules en base (commande de synchronisation planifiée) plutôt qu'un simple cache.
- Pagination ou « charger plus » côté interface, recherche texte, tri par prix / kilométrage.
- Tests front (Vitest) pour le filtre par marque.
- Dockerisation (Laravel + MySQL + front) pour un lancement en une commande.
