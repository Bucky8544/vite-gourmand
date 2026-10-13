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
