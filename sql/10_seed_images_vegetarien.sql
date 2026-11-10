-- Images du menu « végétarien » : menu n° 3. Le numéro est explicite (aucune recherche par devinette).
-- Garde-fou : le menu n° 3 doit avoir un titre contenant « végétarien ». Sinon, RIEN n'est modifié
-- et la ligne de vérification ci-dessous l'indique.
-- Ne remplace que les images du jeu de données (/assets/img/menus/) : les images téléversées par l'équipe sont conservées.
SET NAMES utf8mb4;

SET @menu = (SELECT id FROM menu WHERE id = 3 AND titre LIKE '%végétarien%');
SELECT IF(@menu IS NULL,
          'ERREUR : le menu n° 3 ne correspond pas, rien n''a été modifié',
          CONCAT('Menu ciblé : n° ', @menu)) AS verification;

DELETE FROM menu_image WHERE menu_id = @menu AND url LIKE '/assets/img/menus/%';

INSERT INTO menu_image (menu_id, url, texte_alternatif, position)
SELECT m.id, v.url, v.alt, v.pos
FROM menu m
CROSS JOIN (
  SELECT '/assets/img/menus/menu-vegetarien-1.jpg' AS url, 'Table végétarienne : courge farcie au quinoa et aux noix de pécan, salade de pois chiches et carottes à la feta, tajine de légumes, artichauts au citron et planche de houmous de betterave avec craquelins.' AS alt, 0 AS pos
  UNION ALL SELECT '/assets/img/menus/menu-vegetarien-2.jpg', 'Table végétarienne : chou-fleur entier rôti aux épices et à la grenade, salade de betteraves aux noix et au fromage frais, tajine de pois chiches aux abricots, asperges et halloumi grillés, houmous et crudités.', 1
  UNION ALL SELECT '/assets/img/menus/menu-vegetarien-3.jpg', 'Gros plan sur une courge verte farcie de quinoa, de noix de pécan et de grenade, posée sur une assiette avec du thym frais, devant d’autres plats végétariens.', 2
) v
WHERE m.id = @menu;
