-- ====================================================
-- FREDI - Script Complet de Configuration
-- Version: 1.0
-- Compatible: InfinityFree avec MariaDB 11.4+
-- ====================================================
-- Description: Script non-destructif qui ajoute/met à jour
-- toutes les tables et colonnes nécessaires pour le site FREDI

USE `if0_41723856_fredi`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8;

-- ========================================
-- 1. CRÉATION DES TABLES NON EXISTANTES
-- ========================================

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(191) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `club_id` int(11) DEFAULT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `role` enum('adherent','tresorier','admin') DEFAULT 'adherent',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `league_id` int(11) DEFAULT NULL,
  `league_name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `mission` (
  `id_mission` int(11) NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `debut` date DEFAULT NULL,
  `fin` date DEFAULT NULL,
  PRIMARY KEY (`id_mission`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `remboursement` (
  `id_remboursement` int(11) NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int(11) NOT NULL,
  `id_mission` int(11) DEFAULT NULL,
  `repas_france` decimal(10,2) DEFAULT 0.00,
  `repas_etranger` decimal(10,2) DEFAULT 0.00,
  `transport` decimal(10,2) DEFAULT 0.00,
  `hebergement` decimal(10,2) DEFAULT 0.00,
  `parking` decimal(10,2) DEFAULT 0.00,
  `carburant` decimal(10,2) DEFAULT 0.00,
  `autres_frais` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) DEFAULT 0.00,
  `statut` enum('EN_ATTENTE','ACCEPTEE','REFUSEE','PAYEE') DEFAULT 'EN_ATTENTE',
  `date_demande` timestamp NULL DEFAULT current_timestamp(),
  `validation_status` enum('brouillon','soumis','en_revision','valide','rejete') DEFAULT 'brouillon',
  `submitted_date` datetime DEFAULT NULL,
  `validated_date` datetime DEFAULT NULL,
  `validated_by` int(11) DEFAULT NULL,
  `validation_notes` text DEFAULT NULL,
  PRIMARY KEY (`id_remboursement`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `documents` (
  `id_document` int(11) NOT NULL AUTO_INCREMENT,
  `id_remboursement` int(11) NOT NULL,
  `id_mission` int(11) DEFAULT NULL,
  `categorie` varchar(50) DEFAULT 'autres_frais',
  `nom_fichier` varchar(255) NOT NULL,
  `chemin_fichier` varchar(500) NOT NULL,
  `type_fichier` varchar(50) DEFAULT NULL,
  `taille_fichier` int(11) DEFAULT NULL,
  `montant` decimal(10,2) DEFAULT 0.00,
  `date_upload` timestamp NULL DEFAULT current_timestamp(),
  `line_number` int(11) DEFAULT NULL,
  `is_justified` tinyint(1) DEFAULT 0,
  `validation_status` enum('non_justifie','accepte','rejete') DEFAULT 'non_justifie',
  `is_don` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id_document`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `expense_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `status` enum('brouillon','soumis','valide','rejete') DEFAULT 'brouillon',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `expense_lines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `receipt_file` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `validation_history` (
  `id_history` int(11) NOT NULL AUTO_INCREMENT,
  `id_remboursement` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `montant_initial` decimal(10,2) DEFAULT NULL,
  `montant_final` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_history`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `cerfa_receipts` (
  `id_cerfa` int(11) NOT NULL AUTO_INCREMENT,
  `id_remboursement` int(11) NOT NULL,
  `cerfa_number` varchar(20) NOT NULL,
  `year` int(11) NOT NULL,
  `issued_date` datetime DEFAULT current_timestamp(),
  `issued_by` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `recipient_email` varchar(191) DEFAULT NULL,
  `pdf_path` varchar(500) DEFAULT NULL,
  `copy_number` enum('original','copy') DEFAULT 'original',
  `status` enum('draft','issued','archived') DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_cerfa`),
  UNIQUE KEY `cerfa_number` (`cerfa_number`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `accounting_reports` (
  `id_report` int(11) NOT NULL AUTO_INCREMENT,
  `year` int(11) NOT NULL,
  `league_id` int(11) DEFAULT NULL,
  `generated_by` int(11) NOT NULL,
  `total_receipts` decimal(12,2) DEFAULT 0.00,
  `total_count` int(11) DEFAULT 0,
  `generated_date` datetime DEFAULT current_timestamp(),
  `pdf_path` varchar(500) DEFAULT NULL,
  `status` enum('draft','validated','archived') DEFAULT 'draft',
  PRIMARY KEY (`id_report`),
  UNIQUE KEY `unique_year_league` (`year`,`league_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ========================================
-- 2. AJOUT DES COLONNES MANQUANTES
-- ========================================

ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `league_id` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `league_name` varchar(100) DEFAULT NULL;

ALTER TABLE `remboursement`
  ADD COLUMN IF NOT EXISTS `repas_france` decimal(10,2) DEFAULT 0.00,
  ADD COLUMN IF NOT EXISTS `repas_etranger` decimal(10,2) DEFAULT 0.00,
  ADD COLUMN IF NOT EXISTS `validation_status` enum('brouillon','soumis','en_revision','valide','rejete') DEFAULT 'brouillon',
  ADD COLUMN IF NOT EXISTS `submitted_date` datetime DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `validated_date` datetime DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `validated_by` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `validation_notes` text DEFAULT NULL;

ALTER TABLE `documents`
  ADD COLUMN IF NOT EXISTS `line_number` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `is_justified` tinyint(1) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `validation_status` enum('non_justifie','accepte','rejete') DEFAULT 'non_justifie',
  ADD COLUMN IF NOT EXISTS `is_don` tinyint(1) DEFAULT 0;

-- ========================================
-- 3. INDEXS UTILES
-- ========================================

CREATE UNIQUE INDEX IF NOT EXISTS `idx_users_email` ON `users` (`email`);
CREATE INDEX IF NOT EXISTS `idx_remboursement_user` ON `remboursement` (`id_utilisateur`);
CREATE INDEX IF NOT EXISTS `idx_remboursement_mission` ON `remboursement` (`id_mission`);
CREATE INDEX IF NOT EXISTS `idx_remboursement_status` ON `remboursement` (`validation_status`);
CREATE INDEX IF NOT EXISTS `idx_remboursement_submitted` ON `remboursement` (`submitted_date`);
CREATE INDEX IF NOT EXISTS `idx_remboursement_validated_by` ON `remboursement` (`validated_by`);
CREATE INDEX IF NOT EXISTS `idx_documents_remboursement` ON `documents` (`id_remboursement`);
CREATE INDEX IF NOT EXISTS `idx_documents_mission` ON `documents` (`id_mission`);
CREATE INDEX IF NOT EXISTS `idx_documents_validation_status` ON `documents` (`validation_status`);
CREATE INDEX IF NOT EXISTS `idx_expense_reports_user` ON `expense_reports` (`user_id`);
CREATE INDEX IF NOT EXISTS `idx_expense_lines_report` ON `expense_lines` (`report_id`);
CREATE INDEX IF NOT EXISTS `idx_validation_history_remboursement` ON `validation_history` (`id_remboursement`);
CREATE INDEX IF NOT EXISTS `idx_validation_history_user` ON `validation_history` (`id_utilisateur`);
CREATE INDEX IF NOT EXISTS `idx_cerfa_remboursement` ON `cerfa_receipts` (`id_remboursement`);
CREATE INDEX IF NOT EXISTS `idx_cerfa_issued_by` ON `cerfa_receipts` (`issued_by`);
CREATE INDEX IF NOT EXISTS `idx_cerfa_year` ON `cerfa_receipts` (`year`);
CREATE INDEX IF NOT EXISTS `idx_cerfa_status` ON `cerfa_receipts` (`status`);
CREATE INDEX IF NOT EXISTS `idx_accounting_reports_generated_by` ON `accounting_reports` (`generated_by`);

-- ========================================
-- 4. DONNÉES DE TEST
-- ========================================

-- Compte adhérent de test (si non existant)
INSERT IGNORE INTO `users` (
  `id`, `email`, `password_hash`, `first_name`, `last_name`, `role`, `created_at`
) VALUES (
  1, 'test@email.test', '$2y$10$ihTiJ7HwiqWJTqPO9mVTOO7.6.G3n9BLQD9J8acF95xVtMLwLEPZi', 'Ludovic', 'Conlin', 'adherent', NOW()
);

-- Compte trésorier de test
INSERT IGNORE INTO `users` (
  `email`, `password_hash`, `first_name`, `last_name`, `role`, `created_at`
) VALUES (
  'tresorier@test.test', '$2b$12$umZbv9CIsLS7/jGsI7x/L.mdYzi/QCOPLrfetEnov1CcuUysVtJKi', 'Tresorier', 'Test', 'tresorier', NOW()
);

-- Rapport de test (associé au compte adhérent)
INSERT IGNORE INTO `expense_reports` (
  `id`, `user_id`, `title`, `status`, `created_at`, `updated_at`
) VALUES (
  1, 1, 'Test', 'soumis', NOW(), NOW()
);

COMMIT;
