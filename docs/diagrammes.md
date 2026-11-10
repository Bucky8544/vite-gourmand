# Diagrammes

## 1. Diagramme de cas d'utilisation

L'utilisateur connecté conserve les possibilités du visiteur. L'administrateur peut faire
tout ce que fait un employé.

```mermaid
flowchart LR
  V(["Visiteur"])
  U(["Utilisateur"])
  E(["Employé"])
  A(["Administrateur"])

  U -. "hérite de" .-> V
  A -. "hérite de" .-> E

  subgraph Public["Espace public"]
    V1["Consulter l'accueil et les avis validés"]
    V2["Consulter la liste des menus"]
    V3["Filtrer les menus"]
    V4["Voir le détail d'un menu"]
    V5["Créer un compte"]
    V6["Se connecter"]
    V7["Réinitialiser son mot de passe"]
    V8["Contacter l'entreprise"]
    V9["Consulter horaires, mentions légales et CGV"]
  end

  subgraph Client["Espace utilisateur"]
    U1["Commander un menu"]
    U2["Consulter ses commandes"]
    U3["Modifier ou annuler une commande non acceptée"]
    U4["Suivre l'état d'une commande acceptée"]
    U5["Modifier ses informations personnelles"]
    U6["Donner un avis sur une commande terminée"]
  end

  subgraph Employe["Espace employé"]
    E1["Gérer les menus"]
    E2["Gérer les plats"]
    E3["Modifier les horaires"]
    E4["Filtrer les commandes par statut ou client"]
    E5["Mettre à jour le statut d'une commande"]
    E6["Annuler une commande avec motif et mode de contact"]
    E7["Valider ou refuser un avis"]
  end

  subgraph Admin["Espace administrateur"]
    A1["Créer un compte employé"]
    A2["Désactiver un compte employé"]
    A3["Comparer le nombre de commandes par menu"]
    A4["Consulter le chiffre d'affaires par menu et par période"]
  end

  V --- V1 & V2 & V3 & V4 & V5 & V6 & V7 & V8 & V9
  U --- U1 & U2 & U3 & U4 & U5 & U6
  E --- E1 & E2 & E3 & E4 & E5 & E6 & E7
  A --- A1 & A2 & A3 & A4
```

## 2. Diagramme de séquence : connexion

```mermaid
sequenceDiagram
  actor V as Visiteur
  participant N as Navigateur
  participant S as Serveur PHP
  participant DB as MySQL

  V->>N: Saisit son email et son mot de passe
  N->>S: POST /connexion (email, mot de passe, jeton CSRF)
  S->>S: Vérifie le jeton CSRF
  S->>DB: SELECT utilisateur WHERE email = ? (requête préparée)
  DB-->>S: Utilisateur (hash, rôle, actif)
  alt Compte introuvable, inactif ou mot de passe invalide
    S-->>N: Message d'erreur générique
    N-->>V: Affiche l'erreur
  else Identifiants valides
    S->>S: password_verify puis régénération de l'ID de session
    S-->>N: Redirection vers l'espace correspondant au rôle
    N-->>V: Affiche l'espace utilisateur, employé ou administrateur
  end
```

## 3. Diagramme de séquence : commande d'un menu

```mermaid
sequenceDiagram
  actor U as Utilisateur
  participant N as Navigateur
  participant S as Serveur PHP
  participant G as API de géolocalisation
  participant DB as MySQL
  participant M as Serveur mail

  U->>N: Clique sur Commander (vue détaillée d'un menu)
  N->>S: GET /commande?menu=ID
  alt Visiteur non authentifié
    S-->>N: Redirection vers connexion ou création de compte
  else Utilisateur authentifié
    S->>DB: Lit le menu et les informations du compte
    DB-->>S: Menu, nom, email, téléphone
    S-->>N: Formulaire pré-rempli (menu, identité, contact)
    U->>N: Saisit adresse, date, heure et nombre de personnes
    N->>S: POST /commande
    S->>S: Vérifie le nombre de personnes minimum
    opt Livraison hors Bordeaux
      S->>G: Calcule la distance
      G-->>S: Distance en km
    end
    S->>S: Calcule remise de 10 % si minimum + 5 personnes, et frais de livraison
    S->>DB: BEGIN
    S->>DB: Lit le stock du menu avec verrou
    alt Stock épuisé
      S->>DB: ROLLBACK
      S-->>N: Message : menu indisponible
    else Stock disponible
      S->>DB: INSERT commande et historique de statut (en attente)
      S->>DB: UPDATE stock du menu (moins 1)
      S->>DB: COMMIT
      S->>M: Envoie le mail de confirmation
      S-->>N: Page de confirmation avec détail du prix
      N-->>U: Affiche la confirmation
    end
  end
```

## 4. Diagramme de séquence : changement de statut d'une commande

```mermaid
sequenceDiagram
  actor E as Employé
  participant N as Navigateur
  participant S as Serveur PHP
  participant DB as MySQL
  participant MG as MongoDB
  participant M as Serveur mail

  E->>N: Choisit un nouveau statut pour une commande
  N->>S: POST /employe/commandes/ID/statut (jeton CSRF)
  S->>S: Vérifie la session et le rôle (employé ou administrateur)
  alt Rôle non autorisé
    S-->>N: Accès refusé
  else Rôle autorisé
    alt Annulation
      S->>S: Exige un motif et le mode de contact (GSM ou mail)
    end
    S->>DB: UPDATE statut de la commande
    S->>DB: INSERT historique (statut, date, heure, auteur)
    opt Statut accepté
      S->>MG: Insère le document de statistiques de la commande
    end
    opt Statut en attente du retour de matériel
      S->>M: Mail : 600 euros de frais si matériel non restitué sous 10 jours ouvrés
    end
    opt Statut terminée
      S->>M: Mail invitant à donner un avis
    end
    S-->>N: Confirmation de la mise à jour
    N-->>E: Affiche le nouveau statut
  end
```
