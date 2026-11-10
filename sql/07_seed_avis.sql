-- Données de démonstration : trois commandes terminées et leurs avis (deux validés, un en attente de modération).
-- Les deux clients ajoutés sont inactifs (mot de passe inutilisable) : ils ne servent qu'à signer des avis.
SET NAMES utf8mb4;

INSERT INTO utilisateur (email, mot_de_passe, nom, prenom, telephone, adresse_postale, code_postal, ville, role_id, actif, consentement_rgpd_at)
VALUES
  ('nicolas.petit@demo.vite-gourmand.test', '!', 'Petit', 'Nicolas', '0611223344', '5 rue de la Paix', '33000', 'Bordeaux',
   (SELECT id FROM role WHERE libelle = 'utilisateur'), 0, NOW()),
  ('sophie.laurent@demo.vite-gourmand.test', '!', 'Laurent', 'Sophie', '0622334455', '18 cours Pasteur', '33000', 'Bordeaux',
   (SELECT id FROM role WHERE libelle = 'utilisateur'), 0, NOW());

INSERT INTO commande
  (numero_commande, utilisateur_id, menu_id, date_commande, date_prestation, heure_livraison,
   adresse_prestation, code_postal_prestation, ville_prestation, distance_km, nombre_personne,
   prix_menu, remise, prix_livraison, prix_total, statut, conditions_acceptees_at)
VALUES
  ('VG-2025-900001', (SELECT id FROM utilisateur WHERE email = 'utilisateur@vite-gourmand.test'), 1,
   '2025-12-05 10:15:00', '2025-12-24', '12:30:00', '12 rue Sainte-Catherine', '33000', 'Bordeaux', 0.0, 6,
   150.00, 0.00, 0.00, 150.00, 'terminee', '2025-12-05 10:15:00'),
  ('VG-2026-900002', (SELECT id FROM utilisateur WHERE email = 'nicolas.petit@demo.vite-gourmand.test'), 3,
   '2026-07-01 09:40:00', '2026-07-20', '19:00:00', '5 rue de la Paix', '33000', 'Bordeaux', 0.0, 4,
   84.00, 0.00, 0.00, 84.00, 'terminee', '2026-07-01 09:40:00'),
  ('VG-2026-900003', (SELECT id FROM utilisateur WHERE email = 'sophie.laurent@demo.vite-gourmand.test'), 6,
   '2026-08-10 16:05:00', '2026-08-30', '12:00:00', '18 cours Pasteur', '33000', 'Bordeaux', 0.0, 4,
   76.00, 0.00, 0.00, 76.00, 'terminee', '2026-08-10 16:05:00');

INSERT INTO commande_statut_historique (commande_id, statut, modifie_par, date_modification)
VALUES
  ((SELECT id FROM commande WHERE numero_commande = 'VG-2025-900001'), 'en_attente',
   (SELECT id FROM utilisateur WHERE email = 'utilisateur@vite-gourmand.test'), '2025-12-05 10:15:00'),
  ((SELECT id FROM commande WHERE numero_commande = 'VG-2025-900001'), 'accepte',
   (SELECT id FROM utilisateur WHERE email = 'employe@vite-gourmand.test'), '2025-12-06 09:00:00'),
  ((SELECT id FROM commande WHERE numero_commande = 'VG-2025-900001'), 'terminee',
   (SELECT id FROM utilisateur WHERE email = 'employe@vite-gourmand.test'), '2025-12-24 15:00:00'),
  ((SELECT id FROM commande WHERE numero_commande = 'VG-2026-900002'), 'en_attente',
   (SELECT id FROM utilisateur WHERE email = 'nicolas.petit@demo.vite-gourmand.test'), '2026-07-01 09:40:00'),
  ((SELECT id FROM commande WHERE numero_commande = 'VG-2026-900002'), 'accepte',
   (SELECT id FROM utilisateur WHERE email = 'employe@vite-gourmand.test'), '2026-07-02 09:00:00'),
  ((SELECT id FROM commande WHERE numero_commande = 'VG-2026-900002'), 'terminee',
   (SELECT id FROM utilisateur WHERE email = 'employe@vite-gourmand.test'), '2026-07-20 21:00:00'),
  ((SELECT id FROM commande WHERE numero_commande = 'VG-2026-900003'), 'en_attente',
   (SELECT id FROM utilisateur WHERE email = 'sophie.laurent@demo.vite-gourmand.test'), '2026-08-10 16:05:00'),
  ((SELECT id FROM commande WHERE numero_commande = 'VG-2026-900003'), 'accepte',
   (SELECT id FROM utilisateur WHERE email = 'employe@vite-gourmand.test'), '2026-08-11 09:00:00'),
  ((SELECT id FROM commande WHERE numero_commande = 'VG-2026-900003'), 'terminee',
   (SELECT id FROM utilisateur WHERE email = 'employe@vite-gourmand.test'), '2026-08-30 14:00:00');

INSERT INTO avis (commande_id, note, commentaire, statut, modere_par, modere_at, created_at)
VALUES
  ((SELECT id FROM commande WHERE numero_commande = 'VG-2025-900001'), 5,
   'Un repas de Noël réussi, tout était délicieux et livré à l''heure.', 'valide',
   (SELECT id FROM utilisateur WHERE email = 'employe@vite-gourmand.test'), '2025-12-28 10:00:00', '2025-12-27 18:20:00'),
  ((SELECT id FROM commande WHERE numero_commande = 'VG-2026-900002'), 5,
   'Menu végétarien savoureux, tous nos invités ont adoré.', 'valide',
   (SELECT id FROM utilisateur WHERE email = 'employe@vite-gourmand.test'), '2026-07-22 09:30:00', '2026-07-21 20:10:00'),
  ((SELECT id FROM commande WHERE numero_commande = 'VG-2026-900003'), 4,
   'Très bon rapport qualité-prix et un accueil chaleureux.', 'en_attente',
   NULL, NULL, '2026-08-31 11:45:00');
