-- Compléments du schéma pour la commande d'un menu
ALTER TABLE menu
  ADD COLUMN delai_commande_jours INT UNSIGNED NOT NULL DEFAULT 2 AFTER stock;

ALTER TABLE commande
  ADD COLUMN conditions_acceptees_at DATETIME NULL AFTER mode_contact_annulation;
