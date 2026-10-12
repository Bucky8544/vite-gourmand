SET NAMES utf8mb4;

-- Fichier généré par scripts/generate_seed_users.php : ne pas modifier à la main.

INSERT INTO utilisateur (email, mot_de_passe, nom, prenom, telephone, adresse_postale, code_postal, ville, role_id, actif, consentement_rgpd_at)
VALUES ('admin@vite-gourmand.test', '$2y$10$PpFU7RXHTznAU7fEjAX0p.wSmI4b2OLOVxYoU.PuvuCTWpUOrmM2G', 'Admin', 'José', NULL, NULL, NULL, NULL, (SELECT id FROM role WHERE libelle = 'administrateur'), TRUE, NOW());

INSERT INTO utilisateur (email, mot_de_passe, nom, prenom, telephone, adresse_postale, code_postal, ville, role_id, actif, consentement_rgpd_at)
VALUES ('employe@vite-gourmand.test', '$2y$10$/m12l29Y2/YgazQnC1Z02.CJTCXjuHwgfLLYvJNFqX2jATjN5SHTy', 'Employé', 'Julie', NULL, NULL, NULL, NULL, (SELECT id FROM role WHERE libelle = 'employe'), TRUE, NOW());

INSERT INTO utilisateur (email, mot_de_passe, nom, prenom, telephone, adresse_postale, code_postal, ville, role_id, actif, consentement_rgpd_at)
VALUES ('utilisateur@vite-gourmand.test', '$2y$10$zxQsaV/Jm5GrwvD7dFqYIuAd23OwBD83t1PtGciaVjo2R.u5BWRUa', 'Dupont', 'Camille', '0612345678', '12 rue Sainte-Catherine', '33000', 'Bordeaux', (SELECT id FROM role WHERE libelle = 'utilisateur'), TRUE, NOW());

