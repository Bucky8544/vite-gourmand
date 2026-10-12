SET NAMES utf8mb4;

-- ---------- Plats ----------
INSERT INTO plat (id, titre, type) VALUES
  (1,  'Foie gras maison et chutney de figues', 'entree'),
  (2,  'Velouté de potimarron', 'entree'),
  (3,  'Salade de chèvre chaud', 'entree'),
  (4,  'Tartare de légumes d''été', 'entree'),
  (5,  'Dinde rôtie aux marrons', 'plat'),
  (6,  'Gigot d''agneau de sept heures', 'plat'),
  (7,  'Gratin dauphinois et légumes rôtis', 'plat'),
  (8,  'Curry de pois chiches au lait de coco', 'plat'),
  (9,  'Risotto aux champignons', 'plat'),
  (10, 'Bûche de Noël chocolat-marron', 'dessert'),
  (11, 'Tarte au citron meringuée', 'dessert'),
  (12, 'Fondant au chocolat vegan', 'dessert'),
  (13, 'Verrines apéritives', 'entree'),
  (14, 'Mini-burgers de bœuf', 'plat'),
  (15, 'Assortiment de mignardises', 'dessert'),
  (16, 'Asperges et sauce mousseline', 'entree'),
  (17, 'Nid de Pâques au chocolat', 'dessert'),
  (18, 'Poulet fermier rôti et gratin', 'plat');

-- ---------- Allergènes des plats ----------
INSERT INTO plat_allergene (plat_id, allergene_id) VALUES
  (1, 1), (1, 12),
  (2, 7),
  (3, 7), (3, 1),
  (5, 9),
  (6, 9), (6, 12),
  (7, 7),
  (9, 7), (9, 9),
  (10, 1), (10, 3), (10, 7), (10, 8),
  (11, 1), (11, 3), (11, 7),
  (12, 1),
  (13, 4), (13, 7),
  (14, 1), (14, 10), (14, 11),
  (15, 1), (15, 3), (15, 7), (15, 8),
  (16, 3), (16, 10),
  (17, 3), (17, 7), (17, 8),
  (18, 7), (18, 9);

-- ---------- Menus ----------
-- theme : 1 Noël, 2 Pâques, 3 Classique, 4 Événement
-- regime : 1 Classique, 2 Végétarien, 3 Vegan
INSERT INTO menu
  (id, titre, description, nombre_personne_minimum, prix_minimum, conditions, stock, theme_id, regime_id)
VALUES
  (1, 'Menu de Noël traditionnel',
   'Un repas de fête généreux : foie gras, dinde aux marrons et bûche maison.',
   6, 150.00,
   'Commande à passer au moins 14 jours avant la prestation. Conserver entre 0 et 4 °C et consommer sous 48 h.',
   10, 1, 1),
  (2, 'Menu de Pâques',
   'Asperges de saison, gigot d''agneau fondant et nid au chocolat.',
   4, 112.00,
   'Commande à passer au moins 10 jours avant la prestation. Le gigot se réchauffe à 150 °C pendant 20 minutes.',
   8, 2, 1),
  (3, 'Menu végétarien gourmand',
   'Une table sans viande ni poisson, riche en saveurs.',
   4, 84.00,
   'Commande à passer au moins 7 jours avant la prestation. Conserver au frais et consommer sous 48 h.',
   12, 3, 2),
  (4, 'Menu vegan découverte',
   'Un menu 100 % végétal pour les convives les plus exigeants.',
   6, 114.00,
   'Commande à passer au moins 7 jours avant la prestation. Aucun produit d''origine animale.',
   6, 4, 3),
  (5, 'Menu cocktail événement',
   'Verrines, mini-burgers et mignardises pour vos réceptions.',
   10, 220.00,
   'Commande à passer au moins 21 jours avant la prestation. Prêt de matériel de service possible (caution de 600 € en cas de non-restitution sous 10 jours ouvrés).',
   5, 4, 1),
  (6, 'Menu repas de famille',
   'Un classique simple et convivial, sans prise de tête.',
   4, 76.00,
   'Commande à passer au moins 5 jours avant la prestation.',
   15, 3, 1);

-- ---------- Composition des menus ----------
INSERT INTO menu_plat (menu_id, plat_id) VALUES
  (1, 1), (1, 2), (1, 5), (1, 10),
  (2, 16), (2, 6), (2, 17),
  (3, 3), (3, 9), (3, 7), (3, 11),
  (4, 4), (4, 8), (4, 12),
  (5, 13), (5, 14), (5, 15),
  (6, 3), (6, 18), (6, 11);

-- ---------- Images (fichiers à ajouter plus tard) ----------
INSERT INTO menu_image (menu_id, url, texte_alternatif, position) VALUES
  (1, '/assets/img/menus/noel-1.jpg', 'Table de Noël dressée avec une dinde rôtie', 0),
  (1, '/assets/img/menus/noel-2.jpg', 'Bûche de Noël chocolat et marrons', 1),
  (2, '/assets/img/menus/paques-1.jpg', 'Gigot d''agneau et asperges', 0),
  (3, '/assets/img/menus/vegetarien-1.jpg', 'Risotto aux champignons', 0),
  (4, '/assets/img/menus/vegan-1.jpg', 'Curry de pois chiches et riz', 0),
  (5, '/assets/img/menus/cocktail-1.jpg', 'Plateau de verrines et mini-burgers', 0),
  (6, '/assets/img/menus/famille-1.jpg', 'Poulet rôti et gratin sur une table de famille', 0);
