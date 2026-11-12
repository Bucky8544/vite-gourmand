-- Compteur des échecs de connexion et des inscriptions récentes (limitation des tentatives).
-- Aucune donnée en clair : la colonne « cle » contient une empreinte HMAC (SHA-256) ; les lignes sont purgées après 24 heures.
CREATE TABLE IF NOT EXISTS limitation_acces (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  type VARCHAR(30) NOT NULL,
  cle CHAR(64) NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_limitation_cle (type, cle, created_at),
  KEY idx_limitation_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
