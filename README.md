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
docker compose exec web php scripts/test_reinitialisation.php
docker compose exec web php scripts/test_menus.php
docker compose exec web php scripts/test_menu_detail.php
docker compose exec web php scripts/test_commande.php
docker compose exec web php scripts/test_espace.php
docker compose exec web php scripts/test_employe.php
docker compose exec web php scripts/test_avis.php
docker compose exec web php scripts/test_catalogue.php
docker compose exec web php scripts/test_admin.php
docker compose exec web php scripts/test_contact.php
```

## Comptes de test (développement)

| Rôle | Identifiant | Mot de passe |
|---|---|---|
| Administrateur | `admin@vite-gourmand.test` | `Admin@Vite2026!` |
| Employé | `employe@vite-gourmand.test` | `Employe@Vite2026!` |
| Utilisateur | `utilisateur@vite-gourmand.test` | `Client@Vite2026!` |

Ces comptes sont créés par `sql/04_seed_users.sql`. Ils ne doivent pas exister en production.

## API des menus

`GET /api/menus` renvoie les menus actifs au format JSON (données publiques uniquement).

Paramètres facultatifs : `prix_max`, `prix_de`, `prix_a`, `theme` (id), `regime` (id), `personnes`.
Réponse : `{"count": n, "menus": [...]}`. En cas de paramètre invalide : code 422 et `{"errors": {...}}`.

Exemple : `/api/menus?prix_max=100&regime=2`

## Filtres dynamiques (page des menus)

Le script `public/assets/js/menus.js` interroge `/api/menus` et met à jour la liste, le compteur et l'adresse
de la page sans rechargement. Sans JavaScript, le formulaire fonctionne par rechargement de page.

## Commande d'un menu

Parcours : `/commande?menu=ID` (formulaire) puis récapitulatif détaillé, puis confirmation.

- Prix calculé côté serveur en centimes : remise de 10 % dès « minimum + 5 » personnes, livraison offerte à Bordeaux,
  5 € + 0,59 €/km ailleurs.
- La distance est calculée avec les services publics de l'IGN (Géoplateforme : géocodage et itinéraire routier),
  sans clé d'API. Variables facultatives : `SHOP_LON` et `SHOP_LAT` (point de départ, centre de Bordeaux par défaut).
- Si l'itinéraire est indisponible, la distance à vol d'oiseau majorée de 30 % est utilisée et signalée.
- Migrations SQL de cette fonctionnalité : `sql/02_schema_commande.sql` et `sql/05_seed_delais.sql`.
  Sur une base existante : `docker compose exec -T mysql mysql -u vg_user -p vite_gourmand < sql/02_schema_commande.sql`.

## Espace utilisateur

- `/mon-espace` : informations personnelles et liste des commandes.
- Une commande peut être modifiée (sauf le menu) ou annulée tant qu'elle est « en attente » ; le statut est vérifié
  dans une transaction avec verrou. L'annulation rend l'unité de stock au menu.
- Le suivi des statuts (avec date et heure) apparaît une fois la commande acceptée.
- Un avis (note de 1 à 5 et commentaire) peut être donné une fois la commande terminée ; il est publié après validation.

## Espace employé : commandes

- `/employe` : synthèse, filtres (statut, client ou numéro) et liste des commandes ; `/employe/commandes/{numero}` : détail et actions.
- Statuts : accepté, en préparation, en cours de livraison, livré, puis terminé (ou attente du retour de matériel si du matériel
  a été prêté). Les transitions sont vérifiées côté serveur dans une transaction.
- Annulation par l'équipe : mode de contact (GSM ou mail), motif et confirmation obligatoires.
- Mails automatiques : retour du matériel (600 € sous 10 jours ouvrés), invitation à donner son avis (commande terminée), annulation.
- Statistiques MongoDB : écrites quand la commande est acceptée. Après une panne : `docker compose exec web php scripts/resync_stats.php --oui`.

## Avis clients

- Un client donne son avis (note de 1 à 5, commentaire) depuis sa commande terminée ; l'avis attend la validation d'un employé.
- `/employe/avis` : les employés et l'administrateur valident, refusent, retirent ou republient un avis.
- La page d'accueil affiche les 6 avis validés les plus récents, signés du prénom et de l'initiale du nom.
- Données de démonstration (`sql/07_seed_avis.sql`) : 3 commandes terminées et 3 avis (2 validés, 1 en attente) ;
  les deux clients ajoutés sont inactifs et ne servent qu'à signer ces avis.

## Espace employé : catalogue

- `/employe/menus` : créer, modifier, désactiver (réversible) ou supprimer (si jamais commandé) un menu, avec sa composition,
  son stock, son délai de commande et sa galerie d'images (JPEG, PNG ou WebP, 2 Mo maximum, texte alternatif obligatoire).
- `/employe/plats` : plats par type avec leurs allergènes ; un plat utilisé par un menu ne peut pas être supprimé.
- `/employe/categories` : thèmes et régimes. `/employe/horaires` : horaires affichés dans le pied de page et appliqués à la livraison.
- Les images téléversées sont stockées dans un volume Docker (`uploads`), hors du dépôt Git.

## Espace administrateur

- `/admin` : statistiques lues dans MongoDB (nombre de commandes et chiffre d'affaires par menu, filtres par menu et par période,
  graphiques en barres accompagnés d'un tableau). Seules les commandes acceptées et non annulées comptent ; la période porte
  sur la date de la prestation ; le montant est le total payé, livraison comprise.
- `/admin/employes` : création de comptes employés (e-mail et mot de passe ; le mail n'indique pas le mot de passe),
  désactivation et réactivation. Aucun compte administrateur ne peut être créé depuis l'application.
- Après une installation neuve ou une panne de MongoDB : `docker compose exec web php scripts/resync_stats.php --oui`
  ou le bouton « Reconstruire les statistiques ».
- L'administrateur accède aussi à toutes les pages de l'espace employé.

## Contact et pages légales

- `/contact` : formulaire (e-mail, objet, message) envoyé à l'adresse `CONTACT_TO` (variable d'environnement), avec l'adresse du
  visiteur en « Répondre à ». Protections : jeton CSRF, champ piège, délai minimal de 2 secondes, 3 envois maximum par session
  toutes les 15 minutes.
- Pages d'information : `/mentions-legales`, `/cgv`, `/confidentialite`, `/accessibilite`.
- Les informations de l'entreprise (adresse, SIREN, hébergeur, médiateur, modalités de paiement) sont dans `config/entreprise.php`.
  Ce sont des exemples fictifs : les remplacer, puis passer `'fictif'` à `false` pour retirer le bandeau d'avertissement.
