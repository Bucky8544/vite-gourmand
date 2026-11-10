-- Images du menu repas de famille. La première (position 0) illustre le menu dans la liste.
-- Le menu est retrouvé par son titre (qui contient « famille »), à défaut par son thème et son régime « Classique » :
-- la ligne ci-dessous affiche celui qui est ciblé. Pour en cibler un autre, remplacez la ligne SET @menu par : SET @menu = 6;
-- Ne remplace que les images du jeu de données (/assets/img/menus/) : les images téléversées par l'équipe sont conservées.
SET NAMES utf8mb4;

SET @menu = 6;
SELECT id AS menu_cible, titre FROM menu WHERE id = @menu;

DELETE FROM menu_image WHERE menu_id = @menu AND url LIKE '/assets/img/menus/%';

INSERT INTO menu_image (menu_id, url, texte_alternatif, position) VALUES
  (@menu, '/assets/img/menus/menu-famille-1.jpg', 'Table de repas de famille : salade verte aux toasts de chèvre chaud et aux noix, terrine de campagne et cornichons, ratatouille, pain de campagne et poulet rôti aux pommes de terre en cocotte.', 0),
  (@menu, '/assets/img/menus/menu-famille-2.jpg', 'Poulet rôti aux herbes servi avec des pommes de terre grenaille et des carottes, gratin dauphinois dans un plat en terre cuite, salade verte et pain de campagne sur une table de famille.', 1),
  (@menu, '/assets/img/menus/menu-famille-3.jpg', 'Tarte au citron meringuée dont une part est découpée, sur un présentoir argenté, avec des framboises, des myrtilles et de la menthe fraîche.', 2);
