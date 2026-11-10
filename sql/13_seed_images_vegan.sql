-- Images du menu « vegan » : menu n° 4. Le numéro est explicite (aucune recherche par devinette).
-- Garde-fou : le menu n° 4 doit avoir un titre contenant « vegan ». Sinon, RIEN n'est modifié
-- et la ligne de vérification ci-dessous l'indique.
-- Ne remplace que les images du jeu de données (/assets/img/menus/) : les images téléversées par l'équipe sont conservées.
SET NAMES utf8mb4;

SET @menu = (SELECT id FROM menu WHERE id = 4 AND titre LIKE '%vegan%');
SELECT IF(@menu IS NULL,
          'ERREUR : le menu n° 4 ne correspond pas, rien n''a été modifié',
          CONCAT('Menu ciblé : n° ', @menu)) AS verification;

DELETE FROM menu_image WHERE menu_id = @menu AND url LIKE '/assets/img/menus/%';

INSERT INTO menu_image (menu_id, url, texte_alternatif, position)
SELECT m.id, v.url, v.alt, v.pos
FROM menu m
CROSS JOIN (
  SELECT '/assets/img/menus/menu-vegan-1.jpg' AS url, 'Table de repas : tartare de légumes aux fleurs comestibles et aux pignons, curry de pois chiches, patate douce et épinards au lait de coco avec riz et pain naan, fondant au chocolat coulant avec glace et framboises.' AS alt, 0 AS pos
  UNION ALL SELECT '/assets/img/menus/menu-vegan-2.jpg', 'Gros plan sur un curry de pois chiches, patate douce et épinards au lait de coco, garni de coriandre, de copeaux de noix de coco et de citron vert, servi avec du riz basmati et du pain naan.', 1
  UNION ALL SELECT '/assets/img/menus/menu-vegan-3.jpg', 'Fondant au chocolat au cœur coulant sur une ardoise, accompagné d’une boule de glace à la vanille, de framboises et de menthe fraîche.', 2
) v
WHERE m.id = @menu;
