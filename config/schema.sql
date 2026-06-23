-- =====================================================================
-- BCEG — Plateforme Réclamations — Schéma de base de données
-- Compatible MySQL 8.x / MariaDB 10.x  (spec serveur BGFI Services)
-- ---------------------------------------------------------------------
-- À exécuter UNE FOIS sur le serveur pour créer la base et les tables :
--   mysql -u root -p < config/schema.sql
-- (reconstruit à partir de toutes les requêtes présentes dans les pages)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS bceg_reclamations
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bceg_reclamations;

-- ------------------------------------------------------------------
-- UTILISATEURS (agents internes : cec, qualite, direction, departement)
-- ------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS utilisateurs (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    email               VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe        VARCHAR(255) NOT NULL,           -- hash bcrypt (password_hash)
    prenom              VARCHAR(100) DEFAULT NULL,
    nom                 VARCHAR(100) DEFAULT NULL,
    role                VARCHAR(30)  NOT NULL,           -- cec | qualite | direction | departement
    departement         VARCHAR(255) DEFAULT NULL,       -- liste séparée par des virgules pour le rôle "departement"
    actif               TINYINT(1)   NOT NULL DEFAULT 1,
    derniere_connexion  DATETIME     DEFAULT NULL,
    date_creation       DATETIME     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------
-- RECLAMATIONS
-- ------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS reclamations (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    numero_suivi        VARCHAR(40)  NOT NULL UNIQUE,     -- ex : REC-20260623-A1B2
    nom_client          VARCHAR(150) DEFAULT NULL,
    telephone_client    VARCHAR(40)  DEFAULT NULL,
    email_client        VARCHAR(150) DEFAULT NULL,
    type_client         VARCHAR(60)  DEFAULT NULL,
    agence              VARCHAR(100) DEFAULT NULL,
    canal               VARCHAR(80)  DEFAULT NULL,
    departement_assigne VARCHAR(80)  DEFAULT NULL,        -- ex : Operations, Digital, Monetique...
    categorie           VARCHAR(150) DEFAULT NULL,        -- le motif précis
    description         TEXT         DEFAULT NULL,
    fichier_nom         VARCHAR(180) DEFAULT NULL,
    fichier_path        VARCHAR(255) DEFAULT NULL,
    statut              VARCHAR(40)  NOT NULL DEFAULT 'Nouvelle',
    -- statuts utilisés : Nouvelle, Affectee, En traitement, Resolue, Cloturee, Rejetee, Complement requis
    cec_id              INT          DEFAULT NULL,
    date_reception      DATETIME     DEFAULT CURRENT_TIMESTAMP,
    date_affectation    DATETIME     DEFAULT NULL,
    date_traitement     DATETIME     DEFAULT NULL,
    date_cloture        DATETIME     DEFAULT NULL,
    CONSTRAINT fk_rec_cec FOREIGN KEY (cec_id) REFERENCES utilisateurs(id) ON DELETE SET NULL,
    INDEX idx_statut (statut),
    INDEX idx_dept (departement_assigne),
    INDEX idx_date (date_reception)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------
-- TRAITEMENTS (historique des actions sur une réclamation)
-- ------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS traitements (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    reclamation_id  INT          NOT NULL,
    utilisateur_id  INT          DEFAULT NULL,
    action          VARCHAR(120) DEFAULT NULL,
    commentaire     TEXT         DEFAULT NULL,
    fichier_nom     VARCHAR(180) DEFAULT NULL,
    fichier_path    VARCHAR(255) DEFAULT NULL,
    date_action     DATETIME     DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_trait_rec  FOREIGN KEY (reclamation_id) REFERENCES reclamations(id) ON DELETE CASCADE,
    CONSTRAINT fk_trait_user FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL,
    INDEX idx_rec (reclamation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------
-- NOTIFICATIONS (e-mails à envoyer / envoyés)
-- ------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    reclamation_id  INT          DEFAULT NULL,
    destinataire    VARCHAR(150) DEFAULT NULL,
    type            VARCHAR(40)  DEFAULT NULL,           -- ex : email
    message         TEXT         DEFAULT NULL,
    statut          VARCHAR(40)  DEFAULT NULL,           -- ex : envoye, en_attente
    date_creation   DATETIME     DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_rec FOREIGN KEY (reclamation_id) REFERENCES reclamations(id) ON DELETE CASCADE,
    INDEX idx_notif_rec (reclamation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------
-- COMPTES DE DÉPART
-- Mot de passe temporaire pour TOUS : BcegAdmin2026!
-- >>> À CHANGER dès la première connexion via la page « Mon compte ». <<<
-- (le hash bcrypt ci-dessous correspond à ce mot de passe)
-- ------------------------------------------------------------------
INSERT INTO utilisateurs (email, mot_de_passe, prenom, nom, role, departement, actif) VALUES
('hermane@bceg.ga',  '$2b$10$eM4Hjnob1aqbG0And/UyCesw24qh7SDAFMF63FMiRc6ul047fjNDm', 'Hermane',  'PAMBO',    'cec',         NULL, 1),
('qualite@bceg.ga',  '$2b$10$eM4Hjnob1aqbG0And/UyCesw24qh7SDAFMF63FMiRc6ul047fjNDm', 'Marcelle', 'QUALITE',  'qualite',     NULL, 1),
('direction@bceg.ga','$2b$10$eM4Hjnob1aqbG0And/UyCesw24qh7SDAFMF63FMiRc6ul047fjNDm', 'Daisy-Helen','NTOUTOUME','direction',  NULL, 1),
('operations@bceg.ga','$2b$10$eM4Hjnob1aqbG0And/UyCesw24qh7SDAFMF63FMiRc6ul047fjNDm','Agent','OPERATIONS','departement','Operations,Monetique,Digital', 1);
