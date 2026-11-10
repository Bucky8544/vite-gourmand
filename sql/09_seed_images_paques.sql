-- Images du menu de Pâques. La première (position 0) illustre le menu dans la liste.
-- Le menu est retrouvé par son thème (« Pâques ») : la ligne ci-dessous affiche celui qui est ciblé.
-- Ne remplace que les images du jeu de données (/assets/img/menus/) : les images téléversées par l'équipe sont conservées.
SET NAMES utf8mb4;

SET @menu = (SELECT m.id FROM menu m JOIN theme t ON t.id = m.theme_id WHERE t.libelle = 'Pâques' ORDER BY m.id LIMIT 1);
SELECT id AS menu_cible, titre FROM menu WHERE id = @menu;

DELETE FROM menu_image WHERE menu_id = @menu AND url LIKE '/assets/img/menus/%';

INSERT INTO menu_image (menu_id, url, texte_alternatif, position) VALUES
  (@menu, '/assets/img/menus/menu-paques-1.jpg', 'Table de Pâques dressée : gigot d’agneau rôti aux herbes, asperges vertes aux amandes, nid en chocolat garni de petits œufs et de poussins, brioches et bouquet de jonquilles et de tulipes.', 0),
  (@menu, '/assets/img/menus/menu-paques-2.jpg', 'Gigot d’agneau rôti aux herbes, en cours de découpe, servi avec de l’ail confit, des échalotes et des carottes glacées, avec en arrière-plan un gratin de pommes de terre et des haricots verts aux amandes.', 1),
  (@menu, '/assets/img/menus/menu-paques-3.jpg', 'Gâteau au chocolat en forme de nid, garni de petits œufs pastel, d’éclats de chocolat et d’amandes, décoré de poussins en massepain et de fleurs comestibles.', 2);
