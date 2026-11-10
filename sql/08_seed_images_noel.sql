-- Images du menu de Noël. La première (position 0) illustre le menu dans la liste.
-- Ne remplace que les images du jeu de données (/assets/img/menus/) : les images téléversées par l'équipe sont conservées.
SET NAMES utf8mb4;

SET @menu = COALESCE((SELECT id FROM menu WHERE titre = 'Menu de Noël traditionnel' LIMIT 1), 1);

DELETE FROM menu_image WHERE menu_id = @menu AND url LIKE '/assets/img/menus/%';

INSERT INTO menu_image (menu_id, url, texte_alternatif, position) VALUES
  (@menu, '/assets/img/menus/menu-noel-1.jpg', 'Table de Noël dressée : foie gras poêlé et toasts, dinde rôtie aux marrons avec ses légumes et bûche au chocolat, devant un sapin illuminé et une cheminée.', 0),
  (@menu, '/assets/img/menus/menu-noel-2.jpg', 'Dinde de Noël farcie aux marrons, rôtie aux herbes sur une planche en bois, avec marrons, échalotes, choux de Bruxelles et carottes, au coin du feu.', 1),
  (@menu, '/assets/img/menus/menu-noel-3.jpg', 'Bûche de Noël au chocolat décorée de champignons en meringue, de houx, de baies rouges et de feuilles d’or, avec une étiquette « Joyeux Noël – Fait maison ».', 2);
