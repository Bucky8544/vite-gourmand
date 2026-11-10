# Réflexions technologiques

## 1. Analyse du besoin

« Vite & Gourmand » est un traiteur bordelais. L'application doit présenter des menus,
permettre de les commander, et offrir trois espaces connectés (utilisateur, employé,
administrateur). Les contraintes techniques qui en découlent :

- données fortement liées (menus, plats, allergènes, commandes, avis) : base relationnelle ;
- statistiques de commandes pour l'administrateur, issues d'une base non relationnelle (imposé) ;
- filtres de menus mis à jour sans rechargement de page ;
- envoi de mails automatiques (bienvenue, confirmation, fin de commande, retour de matériel) ;
- gestion des rôles et sécurité des données personnelles (RGPD) ;
- accessibilité (RGAA) ;
- application déployée en ligne ;
- projet mené seul en environ 70 h : privilégier des outils connus et peu coûteux à mettre en œuvre.

## 2. Choix techniques

### Front-end : HTML5, Bootstrap, JavaScript
- **Pourquoi** : le besoin est celui d'un site de contenu avec quelques interactions (filtres,
  calcul de prix en direct, graphiques). Du HTML sémantique facilite l'accessibilité (RGAA).
  Bootstrap fournit une grille responsive et des composants accessibles, ce qui accélère le
  travail pour la version mobile.
- **Alternatives écartées** : React ou Vue apporteraient une complexité (build, état, routage)
  disproportionnée pour ce périmètre.
- **Limites** : le JavaScript natif demande de la discipline pour rester organisé.

### Back-end : PHP 8.3 avec PDO
- **Pourquoi** : langage adapté au web, hébergement simple, exemple proposé par le sujet.
  PDO permet d'écrire des requêtes préparées, qui protègent contre l'injection SQL.
- **Alternatives** : Symfony ou Laravel (sécurité et ORM intégrés, mais courbe d'apprentissage
  plus longue), Node.js/Express, Django.
- **Limites** : sans framework, le routage, la validation et la protection CSRF sont à écrire
  à la main ; une architecture claire (séparation contrôleurs, services, accès aux données)
  est nécessaire.

### Base relationnelle : MySQL 8
- **Pourquoi** : intégrité référentielle (clés étrangères, contraintes), transactions
  (création de commande et décrément du stock), types adaptés aux montants (DECIMAL).
- **Alternatives** : MariaDB, PostgreSQL (tout à fait possible, PDO les supporte).

### Base non relationnelle : MongoDB 7
- **Rôle** : stocker un document par commande validée pour les statistiques de l'espace
  administrateur (nombre de commandes par menu, chiffre d'affaires par période).
- **Pourquoi** : documents autonomes, schéma souple, agrégation simple, sans surcharger la
  base transactionnelle.
- **Limites assumées** : les données sont dupliquées entre les deux bases, d'où un risque de
  désynchronisation à gérer (journalisation des erreurs, script de resynchronisation).
  Pour un volume aussi faible, SQL seul suffirait ; ce choix répond aussi à l'exigence du sujet.

### Environnement : Docker et Docker Compose
- **Pourquoi** : environnement identique pour toute personne qui clone le projet, démarrage en
  quelques commandes, services isolés (PHP-Apache, MySQL, MongoDB, Mailpit).
- **Mailpit** : capture les mails en développement, sans envoyer de vrais messages.

### Gestion des dépendances et des mails : Composer, PHPMailer
- Composer installe le driver MongoDB (`mongodb/mongodb`) et PHPMailer.
- PHPMailer est une librairie éprouvée pour l'envoi de mails en SMTP.

### Gestion de version et de projet : Git/GitHub, Trello
- Dépôt GitHub public, branches `main`, `develop` et `feature/*`.
- Trello (Kanban) pour le backlog, les sprints et le suivi.

### Déploiement : à confirmer
- **Piste envisagée** : Heroku (déploiement par conteneur Docker ou buildpack PHP), avec un
  add-on MySQL et MongoDB Atlas (palier gratuit).
- **Points de vigilance** : Heroku est payant, ne fournit ni SMTP ni stockage de fichiers
  persistant (images à héberger ailleurs), et les applications d'un plan d'entrée peuvent se
  mettre en veille après inactivité.
- **Alternatives étudiées** : fly.io (pas de MySQL managé disponible : la base serait à
  administrer soi-même), autre hébergeur avec MySQL managé.
- Le choix définitif sera consigné dans la documentation de déploiement.

## 3. Sécurité : principes retenus

- requêtes préparées (PDO) contre l'injection SQL ;
- échappement des données affichées contre les failles XSS ;
- mots de passe hashés (`password_hash`), jamais stockés en clair ;
- politique de mot de passe : 10 caractères minimum, majuscule, minuscule, chiffre, caractère spécial ;
- jetons de réinitialisation de mot de passe à durée limitée, stockés sous forme de hash ;
- contrôle d'accès par rôle vérifié côté serveur ;
- jetons CSRF sur les formulaires ;
- secrets (identifiants des bases, clés) hors du dépôt, dans des variables d'environnement ;
- compte administrateur créé uniquement par script, jamais depuis l'application.

## 4. RGPD et accessibilité

- collecte limitée aux données nécessaires, consentement à l'inscription, politique de
  confidentialité, droit d'accès et de suppression ;
- HTML sémantique, textes alternatifs sur les images (champ dédié en base), labels sur les
  formulaires, navigation au clavier, contrastes vérifiés (Lighthouse, WAVE).

## 5. Conclusion

La stack retenue est volontairement simple et classique : elle couvre toutes les exigences du
sujet, reste maîtrisable seul en 70 h, et chaque choix est justifié et réversible.
