# Modèle de données (MySQL)

```mermaid
erDiagram
  ROLE ||--o{ UTILISATEUR : possede
  UTILISATEUR ||--o{ COMMANDE : passe
  UTILISATEUR ||--o{ PASSWORD_RESET_TOKEN : demande
  UTILISATEUR |o--o{ COMMANDE_STATUT_HISTORIQUE : modifie
  MENU ||--o{ COMMANDE : concerne
  COMMANDE ||--o{ COMMANDE_STATUT_HISTORIQUE : historise
  COMMANDE ||--o| AVIS : recoit
  UTILISATEUR |o--o{ AVIS : modere
  THEME ||--o{ MENU : classe
  REGIME ||--o{ MENU : caracterise
  MENU ||--o{ MENU_IMAGE : illustre
  MENU }o--o{ PLAT : propose
  PLAT }o--o{ ALLERGENE : contient
```

Les clés étrangères `modifie_par` (historique de statut) et `modere_par` (avis) sont facultatives : elles passent à
`NULL` si le compte de l'employé est supprimé. Les attributs de chaque table sont décrits dans `sql/` et dans
`docs/documentation-technique.pdf`.

## Tables sans relation

- `horaire` : un jour de la semaine par ligne (1 à 7), heures d'ouverture et de fermeture, ou jour fermé.
- `limitation_acces` : tentatives de connexion, d'inscription et de contact (type, empreinte HMAC de 64 caractères,
  date), purgées au-delà de 24 heures. Aucune adresse IP ni adresse e-mail en clair.

## Collection MongoDB : `commandes_stats`

Un document par commande **acceptée**, utilisé pour les statistiques de l'espace administrateur
(nombre de commandes par menu, chiffre d'affaires par menu et par période).

```json
{
  "commande_id": 42,
  "menu_id": 3,
  "menu_titre": "Menu de Noël traditionnel",
  "date_prestation": { "$date": "2026-12-20T00:00:00Z" },
  "date_acceptation": { "$date": "2026-12-01T10:15:00Z" },
  "nombre_personne": 12,
  "montant_centimes": 24550
}
```

Règles :
- Document créé quand l'employé passe la commande au statut « accepté », et supprimé si la commande
  acceptée est ensuite annulée.
- Écriture en *upsert* sur `commande_id` : aucun doublon, et une resynchronisation depuis MySQL
  est possible sans risque.
- Montants en centimes (entiers) pour éviter les erreurs d'arrondi.
- Le titre du menu est dupliqué volontairement : les statistiques restent lisibles même si le menu
  est renommé ou désactivé.
- Index : `commande_id` (unique) et `(menu_id, date_prestation)`.
- Période du chiffre d'affaires filtrée sur `date_prestation`.
