-- Traçabilité de la modération des avis : qui a décidé, et quand
ALTER TABLE avis
  ADD COLUMN modere_par INT UNSIGNED NULL AFTER statut,
  ADD COLUMN modere_at DATETIME NULL AFTER modere_par,
  ADD CONSTRAINT fk_avis_modere_par FOREIGN KEY (modere_par) REFERENCES utilisateur(id) ON DELETE SET NULL;
