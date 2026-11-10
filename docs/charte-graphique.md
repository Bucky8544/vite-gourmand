# Charte graphique : Vite & Gourmand

Inspirations : deux visuels d'ambiance (traiteur chaleureux, crème et bordeaux). Les couleurs
ont été mesurées sur les pixels, puis les contrastes calculés selon les critères RGAA/WCAG.

## 1. Identité

- Ambiance : chaleureuse, élégante, artisanale (traiteur bordelais, 25 ans d'expérience).
- Logo : texte « Vite & Gourmand » en Playfair Display, bordeaux, sur deux lignes (à décliner
  en SVG).

## 2. Palette

| Nom | Code | Usage |
|---|---|---|
| Bordeaux | `#742431` | Boutons principaux, liens, titres, logo |
| Bordeaux foncé | `#5A1B26` | Survol et état actif |
| Crème | `#FAF4E6` | Fond de page |
| Carte | `#FCF9F2` | Fond des cartes |
| Blanc | `#FFFFFF` | Champs de formulaire, texte sur bordeaux |
| Sable | `#E5CEA5` | En-têtes de panneaux, fonds de sections |
| Encre | `#2E2110` | Texte principal |
| Gris | `#5C5148` | Texte secondaire, bordures de champs |
| Or | `#EAA830` | Étoiles uniquement (jamais seul porteur d'information) |
| Or foncé | `#8A5A00` | Texte ou contour doré lisible |
| Succès | `#2F6B3A` | Messages de confirmation |
| Erreur | `#B3261E` | Messages d'erreur |

### Contrastes vérifiés (seuil RGAA : 4,5:1 texte normal, 3:1 grand texte et composants)

| Combinaison | Ratio | Résultat |
|---|---|---|
| Encre sur crème | 14,3 | Conforme |
| Gris sur crème | 7,0 | Conforme |
| Bordeaux sur crème | 9,4 | Conforme |
| Blanc sur bordeaux | 10,3 | Conforme |
| Bordeaux sur sable | 6,7 | Conforme |
| Encre sur sable | 10,2 | Conforme |
| Or foncé sur crème | 5,4 | Conforme |
| Succès sur crème | 5,8 | Conforme |
| Erreur sur crème | 6,0 | Conforme |
| Or sur crème | 1,9 | **Non conforme** : décor uniquement |
| Sable sur crème | 1,4 | **Non conforme** : jamais pour du texte ni des bordures |

## 3. Typographie

- Titres : **Playfair Display** (400, 600, 700), repli `Georgia, serif`
- Texte : **Inter** (400, 500, 600), repli `system-ui, sans-serif`
- Polices **hébergées localement** (fichiers woff2 dans le projet) : un chargement depuis les
  serveurs de Google transmettrait l'adresse IP des visiteurs, ce qui pose un problème RGPD.

| Niveau | Taille | Police |
|---|---|---|
| h1 | 2,5 rem | Playfair Display 700 |
| h2 | 2 rem | Playfair Display 600 |
| h3 | 1,5 rem | Playfair Display 600 |
| Texte | 1 rem (16 px), interligne 1,6 | Inter 400 |
| Petit texte | 0,875 rem minimum | Inter 400 |

## 4. Composants

- **Bouton principal** : fond bordeaux, texte blanc, rayon 8 px. Survol : bordeaux foncé.
- **Bouton secondaire** : contour bordeaux 2 px, fond transparent, texte bordeaux.
- **Carte de menu** : fond carte, rayon 12 px, ombre légère, image, titre, description, nombre de
  personnes minimum et prix, bouton « Voir le détail ».
- **Panneau de filtres** : en-tête sable avec texte encre, corps blanc.
- **Pastille de filtre actif** : fond bordeaux, texte blanc, rayon complet.
- **Champs de formulaire** : fond blanc, bordure grise 1 px, libellé toujours visible.
- **Conditions d'un menu** : encadré bien visible (bordure bordeaux, fond sable), exigé par le sujet.
- **Pied de page** : horaires du lundi au dimanche, liens vers mentions légales et CGV.

## 5. Accessibilité (RGAA)

- Focus clavier visible sur tous les éléments interactifs : contour 3 px bordeaux, décalage 2 px.
- Aucune information transmise par la seule couleur (statuts, erreurs, étoiles : toujours un texte).
- Notes en étoiles accompagnées d'un texte (« Note : 5 sur 5 »).
- Images informatives avec texte alternatif ; décors avec `alt=""`.
- Cibles tactiles d'au moins 44 x 44 px sur mobile.
- Taille de texte agrandissable sans perte de contenu.

## 6. Variables CSS

```css
:root {
  --vg-bordeaux: #742431;
  --vg-bordeaux-fonce: #5A1B26;
  --vg-creme: #FAF4E6;
  --vg-carte: #FCF9F2;
  --vg-blanc: #FFFFFF;
  --vg-sable: #E5CEA5;
  --vg-encre: #2E2110;
  --vg-gris: #5C5148;
  --vg-or: #EAA830;
  --vg-or-fonce: #8A5A00;
  --vg-succes: #2F6B3A;
  --vg-erreur: #B3261E;

  --vg-font-titre: "Playfair Display", Georgia, serif;
  --vg-font-texte: "Inter", system-ui, sans-serif;

  --bs-body-bg: var(--vg-creme);
  --bs-body-color: var(--vg-encre);
  --bs-body-font-family: var(--vg-font-texte);
  --bs-link-color: var(--vg-bordeaux);
}

h1, h2, h3 { font-family: var(--vg-font-titre); color: var(--vg-bordeaux); }

:focus-visible { outline: 3px solid var(--vg-bordeaux); outline-offset: 2px; }

.btn-primary {
  --bs-btn-bg: var(--vg-bordeaux);
  --bs-btn-border-color: var(--vg-bordeaux);
  --bs-btn-color: #fff;
  --bs-btn-hover-bg: var(--vg-bordeaux-fonce);
  --bs-btn-hover-border-color: var(--vg-bordeaux-fonce);
  --bs-btn-active-bg: var(--vg-bordeaux-fonce);
  --bs-btn-border-radius: 8px;
}
```

## 7. Maquettes (à ajouter)

Wireframes et mockups : 3 desktop et 3 mobile (accueil, liste des menus avec filtres, commande
ou espace administrateur). Les exports seront ajoutés dans `docs/maquettes/`.
