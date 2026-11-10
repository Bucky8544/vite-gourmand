-- Images du menu végétarien. La première (position 0) illustre le menu dans la liste.
-- Le menu est retrouvé par son régime (« Végétarien ») : la ligne ci-dessous affiche celui qui est ciblé.
-- Ne remplace que les images du jeu de données (/assets/img/menus/) : les images téléversées par l'équipe sont conservées.
SET NAMES utf8mb4;

SET @menu = (SELECT m.id FROM menu m JOIN regime r ON r.id = m.regime_id WHERE r.libelle = 'Végétarien' ORDER BY m.id LIMIT 1);
SELECT id AS menu_cible, titre FROM menu WHERE id = @menu;

DELETE FROM menu_image WHERE menu_id = @menu AND url LIKE '/assets/img/menus/%';

INSERT INTO menu_image (menu_id, url, texte_alternatif, position) VALUES
  (@menu, '/assets/img/menus/menu-vegetarien-1.jpg', 'Table végétarienne : courge farcie au quinoa et aux noix de pécan, salade de pois chiches et carottes à la feta, tajine de légumes, artichauts au citron et planche de houmous de betterave avec craquelins.', 0),
  (@menu, '/assets/img/menus/menu-vegetarien-2.jpg', 'Table végétarienne : chou-fleur entier rôti aux épices et à la grenade, salade de betteraves aux noix et au fromage frais, tajine de pois chiches aux abricots, asperges et halloumi grillés, houmous et crudités.', 1),
  (@menu, '/assets/img/menus/menu-vegetarien-3.jpg', 'Gros plan sur une courge verte farcie de quinoa, de noix de pécan et de grenade, posée sur une assiette avec du thym frais, devant d’autres plats végétariens.', 2);
