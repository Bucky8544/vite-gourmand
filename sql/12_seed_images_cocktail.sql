-- Images du menu cocktail. La première (position 0) illustre le menu dans la liste.
-- Le menu est retrouvé par son titre (qui contient « cocktail »), à défaut par son thème « Événement » :
-- la ligne ci-dessous affiche celui qui est ciblé. Pour en cibler un autre, remplacez la ligne SET @menu par : SET @menu = 5;
-- Ne remplace que les images du jeu de données (/assets/img/menus/) : les images téléversées par l'équipe sont conservées.
SET NAMES utf8mb4;

SET @menu = 5;
SELECT id AS menu_cible, titre FROM menu WHERE id = @menu;

DELETE FROM menu_image WHERE menu_id = @menu AND url LIKE '/assets/img/menus/%';

INSERT INTO menu_image (menu_id, url, texte_alternatif, position) VALUES
  (@menu, '/assets/img/menus/menu-cocktail-1.jpg', 'Buffet de cocktail dans une salle de réception : verrines colorées, mini-burgers, macarons, éclairs et tartelettes sur des ardoises, avec une ardoise « Menu cocktail : verrines, mini-burgers, mignardises ».', 0),
  (@menu, '/assets/img/menus/menu-cocktail-2.jpg', 'Plateau de mini-burgers au bœuf, au cheddar, au bacon et à la roquette, piqués de petites brochettes en bambou, avec un bol de sauce, sur une planche en bois.', 1),
  (@menu, '/assets/img/menus/menu-cocktail-3.jpg', 'Présentoir à trois étages en ardoise garni de mignardises : macarons, tartelettes aux fruits, éclairs, opéras, choux à la crème, financiers aux amandes et truffes au chocolat.', 2);
