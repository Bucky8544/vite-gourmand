# Modèle de données (MySQL)

```mermaid
erDiagram
  ROLE ||--o{ UTILISATEUR : possede
  UTILISATEUR ||--o{ COMMANDE : passe
  UTILISATEUR ||--o{ PASSWORD_RESET_TOKEN : demande
  UTILISATEUR ||--o{ COMMANDE_STATUT_HISTORIQUE : modifie
  MENU ||--o{ COMMANDE : concerne
  COMMANDE ||--o{ COMMANDE_STATUT_HISTORIQUE : historise
  COMMANDE ||--o| AVIS : recoit
  THEME ||--o{ MENU : classe
  REGIME ||--o{ MENU : caracterise
  MENU ||--o{ MENU_IMAGE : illustre
  MENU }o--o{ PLAT : propose
  PLAT }o--o{ ALLERGENE : contient
```

## Collection MongoDB : `commandes_stats`

Un document par commande validée, utilisé pour les statistiques de l'espace administrateur.

```json
{
  "commande_id": 42,
  "menu_id": 3,
  "menu_titre": "Menu de Noël",
  "date_prestation": "2026-12-20",
  "nombre_personne": 12,
  "montant": 245.50
}
```
