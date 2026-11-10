SET NAMES utf8mb4;

INSERT INTO role (id, libelle) VALUES
  (1, 'utilisateur'),
  (2, 'employe'),
  (3, 'administrateur');

INSERT INTO theme (id, libelle) VALUES
  (1, 'Noël'),
  (2, 'Pâques'),
  (3, 'Classique'),
  (4, 'Événement');

INSERT INTO regime (id, libelle) VALUES
  (1, 'Classique'),
  (2, 'Végétarien'),
  (3, 'Vegan');

-- Les 14 allergènes à déclaration obligatoire (réglementation européenne)
INSERT INTO allergene (id, libelle) VALUES
  (1, 'Gluten'),
  (2, 'Crustacés'),
  (3, 'Œufs'),
  (4, 'Poisson'),
  (5, 'Arachides'),
  (6, 'Soja'),
  (7, 'Lait'),
  (8, 'Fruits à coque'),
  (9, 'Céleri'),
  (10, 'Moutarde'),
  (11, 'Graines de sésame'),
  (12, 'Sulfites'),
  (13, 'Lupin'),
  (14, 'Mollusques');

-- Horaires fictifs, modifiables depuis l'espace employé
INSERT INTO horaire (jour, heure_ouverture, heure_fermeture, ferme) VALUES
  (1, '09:00:00', '19:00:00', FALSE),
  (2, '09:00:00', '19:00:00', FALSE),
  (3, '09:00:00', '19:00:00', FALSE),
  (4, '09:00:00', '19:00:00', FALSE),
  (5, '09:00:00', '19:00:00', FALSE),
  (6, '09:00:00', '18:00:00', FALSE),
  (7, '10:00:00', '14:00:00', FALSE);
