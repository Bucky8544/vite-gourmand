-- Images du menu vegan. La première (position 0) illustre le menu dans la liste.
-- Le menu est retrouvé par son régime (« Vegan »), à défaut par un titre contenant « vegan » :
-- la ligne ci-dessous affiche celui qui est ciblé. Pour en cibler un autre, remplacez la ligne SET @menu par : SET @menu = 3;
-- Ne remplace que les images du jeu de données (/assets/img/menus/) : les images téléversées par l'équipe sont conservées.
SET NAMES utf8mb4;

SET @menu = 3;
SELECT id AS menu_cible, titre FROM menu WHERE id = @menu;

DELETE FROM menu_image WHERE menu_id = @menu AND url LIKE '/assets/img/menus/%';

INSERT INTO menu_image (menu_id, url, texte_alternatif, position) VALUES
  (@menu, '/assets/img/menus/menu-vegan-1.jpg', 'Table de repas : tartare de légumes aux fleurs comestibles et aux pignons, curry de pois chiches, patate douce et épinards au lait de coco avec riz et pain naan, fondant au chocolat coulant avec glace et framboises.', 0),
  (@menu, '/assets/img/menus/menu-vegan-2.jpg', 'Gros plan sur un curry de pois chiches, patate douce et épinards au lait de coco, garni de coriandre, de copeaux de noix de coco et de citron vert, servi avec du riz basmati et du pain naan.', 1),
  (@menu, '/assets/img/menus/menu-vegan-3.jpg', 'Fondant au chocolat au cœur coulant sur une ardoise, accompagné d’une boule de glace à la vanille, de framboises et de menthe fraîche.', 2);
