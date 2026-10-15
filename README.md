# Vite & Gourmand

Application web de présentation et de commande de menus (ECF Développeur Web et Web Mobile).

## Stack

- Front : HTML5, Bootstrap, JavaScript
- Back : PHP 8.3 (PDO)
- Bases : MySQL 8 (relationnelle) et MongoDB 7 (statistiques)
- Environnement : Docker, Mailpit (mails de test)
- Déploiement : Heroku

## Lancer le projet en local

### Prérequis

- Docker Desktop installé et démarré
- Git

### Installation

```bash
git clone https://github.com/Bucky8544/vite-gourmand.git
cd vite-gourmand
cp .env.example .env
docker compose up -d --build
docker compose exec web composer install
```

### Accès

| Service | URL |
|---|---|
| Application | http://localhost:8081 |
| Mails de test (Mailpit) | http://localhost:8025 |
| MySQL (depuis le Mac) | localhost:3307 |
| MongoDB (depuis le Mac) | localhost:27017 |

### Commandes utiles

```bash
docker compose logs -f web     # logs de l'application
docker compose down            # arrêter
docker compose down -v         # arrêter et supprimer les données
```

Les fichiers `.sql` du dossier `sql/` sont exécutés automatiquement à la première création de la base MySQL.

## Organisation Git

- `main` : version stable
- `develop` : intégration
- `feature/*` : une branche par fonctionnalité
  
## Gestion de projet
Tableau Trello : https://trello.com/b/FCXtxHt2

## Lien vers les maquettes Figma
Lien maquettes (lecture seule) : https://www.figma.com/design/wNQ1vRkypejmMimmiuI5sR/Vite-et-gourmnd?node-id=2-3&t=Ng1d2KTTmreLdWE2-1

## Initialiser MongoDB

Après le premier lancement (ou après `docker compose down -v`), créer les index :

```bash
docker compose exec web php scripts/init_mongo.php
```

## Structure du projet

- `public/` : seul dossier exposé par Apache (point d'entrée `index.php`, assets)
- `src/Core/` : routeur, connexion PDO, sessions, protection CSRF, vues
- `src/Controller/` : contrôleurs
- `templates/` : vues PHP
- `config/routes.php` : déclaration des routes
- `sql/` : schéma et données de test
- `docs/` : documentation et livrables

Vérifier que l'application et les deux bases répondent : http://localhost:8081/sante

## Mails en développement

Tous les mails envoyés par l'application sont capturés par Mailpit : http://localhost:8025
(aucun mail réel n'est envoyé). En production, renseigner `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`
et `SMTP_PASS` dans les variables d'environnement.

## Tests

```bash
docker compose exec web php scripts/test_mongo_stats.php
docker compose exec web php scripts/test_inscription.php
docker compose exec web php scripts/test_connexion.php
```

## Comptes de test (développement)

| Rôle | Identifiant | Mot de passe |
|---|---|---|
| Administrateur | `admin@vite-gourmand.test` | `Admin@Vite2026!` |
| Employé | `employe@vite-gourmand.test` | `Employe@Vite2026!` |
| Utilisateur | `utilisateur@vite-gourmand.test` | `Client@Vite2026!` |

Ces comptes sont créés par `sql/04_seed_users.sql`. Ils ne doivent pas exister en production.
