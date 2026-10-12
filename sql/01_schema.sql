-- Vite & Gourmand : schéma MySQL 8
SET NAMES utf8mb4;

-- ---------- Référentiels ----------
CREATE TABLE role (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  libelle VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE theme (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  libelle VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE regime (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  libelle VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE allergene (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  libelle VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE horaire (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  jour TINYINT UNSIGNED NOT NULL UNIQUE,          -- 1 = lundi ... 7 = dimanche
  heure_ouverture TIME NULL,
  heure_fermeture TIME NULL,
  ferme BOOLEAN NOT NULL DEFAULT FALSE,
  CONSTRAINT chk_horaire_jour CHECK (jour BETWEEN 1 AND 7)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Utilisateurs ----------
-- Les coordonnées sont facultatives en base car un compte employé
-- n'est créé qu'avec un email et un mot de passe.
-- Pour un compte "utilisateur", l'application les rend obligatoires.
CREATE TABLE utilisateur (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  mot_de_passe VARCHAR(255) NOT NULL,             -- hash (password_hash), jamais en clair
  nom VARCHAR(100) NULL,
  prenom VARCHAR(100) NULL,
  telephone VARCHAR(20) NULL,
  adresse_postale VARCHAR(255) NULL,
  code_postal VARCHAR(10) NULL,
  ville VARCHAR(100) NULL,
  pays VARCHAR(100) NOT NULL DEFAULT 'France',
  role_id INT UNSIGNED NOT NULL,
  actif BOOLEAN NOT NULL DEFAULT TRUE,            -- FALSE = compte rendu inutilisable
  consentement_rgpd_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_utilisateur_role FOREIGN KEY (role_id) REFERENCES role(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_reset_token (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  utilisateur_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,            -- SHA-256 du jeton envoyé par mail
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  CONSTRAINT fk_prt_utilisateur FOREIGN KEY (utilisateur_id)
    REFERENCES utilisateur(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Menus et plats ----------
CREATE TABLE menu (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  titre VARCHAR(150) NOT NULL,
  description TEXT NOT NULL,
  nombre_personne_minimum INT UNSIGNED NOT NULL,
  prix_minimum DECIMAL(8,2) NOT NULL,             -- prix pour le nombre de personnes minimum
  conditions TEXT NOT NULL,                       -- délai de commande, stockage...
  stock INT UNSIGNED NOT NULL DEFAULT 0,          -- commandes encore possibles
  theme_id INT UNSIGNED NOT NULL,
  regime_id INT UNSIGNED NOT NULL,
  actif BOOLEAN NOT NULL DEFAULT TRUE,
  CONSTRAINT chk_menu_min CHECK (nombre_personne_minimum >= 1),
  CONSTRAINT chk_menu_prix CHECK (prix_minimum >= 0),
  CONSTRAINT fk_menu_theme FOREIGN KEY (theme_id) REFERENCES theme(id),
  CONSTRAINT fk_menu_regime FOREIGN KEY (regime_id) REFERENCES regime(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE menu_image (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  menu_id INT UNSIGNED NOT NULL,
  url VARCHAR(255) NOT NULL,
  texte_alternatif VARCHAR(255) NOT NULL,         -- accessibilité (RGAA)
  position TINYINT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT fk_menu_image_menu FOREIGN KEY (menu_id)
    REFERENCES menu(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE plat (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  titre VARCHAR(150) NOT NULL,
  type ENUM('entree','plat','dessert') NOT NULL,
  image_url VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE menu_plat (
  menu_id INT UNSIGNED NOT NULL,
  plat_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (menu_id, plat_id),
  CONSTRAINT fk_menu_plat_menu FOREIGN KEY (menu_id) REFERENCES menu(id) ON DELETE CASCADE,
  CONSTRAINT fk_menu_plat_plat FOREIGN KEY (plat_id) REFERENCES plat(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE plat_allergene (
  plat_id INT UNSIGNED NOT NULL,
  allergene_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (plat_id, allergene_id),
  CONSTRAINT fk_pa_plat FOREIGN KEY (plat_id) REFERENCES plat(id) ON DELETE CASCADE,
  CONSTRAINT fk_pa_allergene FOREIGN KEY (allergene_id) REFERENCES allergene(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Commandes ----------
CREATE TABLE commande (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  numero_commande VARCHAR(20) NOT NULL UNIQUE,
  utilisateur_id INT UNSIGNED NOT NULL,
  menu_id INT UNSIGNED NOT NULL,
  date_commande DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_prestation DATE NOT NULL,
  heure_livraison TIME NOT NULL,
  adresse_prestation VARCHAR(255) NOT NULL,
  code_postal_prestation VARCHAR(10) NOT NULL,
  ville_prestation VARCHAR(100) NOT NULL,
  distance_km DECIMAL(6,1) NOT NULL DEFAULT 0,    -- 0 si livraison à Bordeaux
  nombre_personne INT UNSIGNED NOT NULL,
  prix_menu DECIMAL(8,2) NOT NULL,                -- après remise éventuelle
  remise DECIMAL(8,2) NOT NULL DEFAULT 0,         -- 10 % si minimum + 5 personnes
  prix_livraison DECIMAL(8,2) NOT NULL DEFAULT 0, -- 5 € + 0,59 €/km hors Bordeaux
  prix_total DECIMAL(8,2) NOT NULL,
  statut ENUM('en_attente','accepte','en_preparation','en_cours_livraison',
              'livre','attente_retour_materiel','terminee','annulee')
         NOT NULL DEFAULT 'en_attente',
  pret_materiel BOOLEAN NOT NULL DEFAULT FALSE,
  materiel_restitue BOOLEAN NOT NULL DEFAULT FALSE,
  motif_annulation TEXT NULL,
  mode_contact_annulation ENUM('gsm','mail') NULL,
  CONSTRAINT chk_commande_nb CHECK (nombre_personne >= 1),
  CONSTRAINT fk_commande_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(id),
  CONSTRAINT fk_commande_menu FOREIGN KEY (menu_id) REFERENCES menu(id),
  INDEX idx_commande_statut (statut),
  INDEX idx_commande_date_prestation (date_prestation)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE commande_statut_historique (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  commande_id INT UNSIGNED NOT NULL,
  statut ENUM('en_attente','accepte','en_preparation','en_cours_livraison',
              'livre','attente_retour_materiel','terminee','annulee') NOT NULL,
  modifie_par INT UNSIGNED NULL,                  -- employé ou admin à l'origine du changement
  date_modification DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_csh_commande FOREIGN KEY (commande_id)
    REFERENCES commande(id) ON DELETE CASCADE,
  CONSTRAINT fk_csh_utilisateur FOREIGN KEY (modifie_par)
    REFERENCES utilisateur(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Avis ----------
CREATE TABLE avis (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  commande_id INT UNSIGNED NOT NULL UNIQUE,       -- un seul avis par commande
  note TINYINT UNSIGNED NOT NULL,
  commentaire TEXT NOT NULL,
  statut ENUM('en_attente','valide','refuse') NOT NULL DEFAULT 'en_attente',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_avis_note CHECK (note BETWEEN 1 AND 5),
  CONSTRAINT fk_avis_commande FOREIGN KEY (commande_id) REFERENCES commande(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
