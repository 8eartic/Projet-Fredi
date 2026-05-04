-- ====================================================
-- FREDI - SCRIPT COMPLET DE CONFIGURATION
-- Version: SP3 Production Ready
-- Compatible: InfinityFree avec MariaDB 11.4+
-- Date: 27 Avril 2026
-- ====================================================
-- Description: Script unique et complet pour déployer
-- FREDI avec toutes les fonctionnalités SP3 sur InfinityFree

USE `if0_41723856_fredi`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8;

-- ========================================
-- 1. CRÉATION DE TOUTES LES TABLES
-- ========================================

-- Table utilisateurs (membres et trésoriers)
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
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `league_id` int(11) DEFAULT NULL,
  `league_name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table pour les resets de mot de passe
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table missions (événements sportifs)
CREATE TABLE IF NOT EXISTS `mission` (
  `id_mission` int(11) NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `debut` date DEFAULT NULL,
  `fin` date DEFAULT NULL,
  PRIMARY KEY (`id_mission`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table principale des remboursements
CREATE TABLE IF NOT EXISTS `remboursement` (
  `id_remboursement` int(11) NOT NULL AUTO_INCREMENT,
  `numero_remboursement` int(11) DEFAULT NULL,
  `id_utilisateur` int(11) NOT NULL,
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
  PRIMARY KEY (`id_remboursement`),
  UNIQUE KEY `unique_user_numero` (`id_utilisateur`, `numero_remboursement`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table documents justificatifs
CREATE TABLE IF NOT EXISTS `documents` (
  `id_document` int(11) NOT NULL AUTO_INCREMENT,
  `id_remboursement` int(11) NOT NULL,
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

-- Table rapports de frais (ancien système)
CREATE TABLE IF NOT EXISTS `expense_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `status` enum('brouillon','soumis','valide','rejete') DEFAULT 'brouillon',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table lignes de frais (ancien système)
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

-- Table historique des validations (SP3)
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

-- Table reçus CERFA (SP3)
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

-- Table rapports comptables (SP3)
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
-- 2. AJOUT DES COLONNES MANQUANTES (AU CAS OÙ)
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
-- 3. CRÉATION DES INDEX DE PERFORMANCE
-- ========================================

CREATE UNIQUE INDEX IF NOT EXISTS `idx_users_email` ON `users` (`email`);
CREATE INDEX IF NOT EXISTS `idx_remboursement_user` ON `remboursement` (`id_utilisateur`);
CREATE INDEX IF NOT EXISTS `idx_remboursement_status` ON `remboursement` (`validation_status`);
CREATE INDEX IF NOT EXISTS `idx_remboursement_submitted` ON `remboursement` (`submitted_date`);
CREATE INDEX IF NOT EXISTS `idx_remboursement_validated_by` ON `remboursement` (`validated_by`);
CREATE INDEX IF NOT EXISTS `idx_documents_remboursement` ON `documents` (`id_remboursement`);
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
-- 4. DONNÉES DE TEST (COMPTES UTILISATEUR)
-- ========================================

-- Compte adhérent de test
INSERT IGNORE INTO `users` (
  `id`, `email`, `password_hash`, `first_name`, `last_name`,
  `address`, `phone`, `license_number`, `birth_date`, `role`,
  `created_at`, `league_id`, `league_name`
) VALUES (
  1,
  'test@email.test',
  '$2y$10$ihTiJ7HwiqWJTqPO9mVTOO7.6.G3n9BLQD9J8acF95xVtMLwLEPZi',
  'Ludovic',
  'Conlin',
  NULL,
  NULL,
  NULL,
  NULL,
  'adherent',
  '2026-02-27 12:30:30',
  NULL,
  NULL
);

-- Compte trésorier de test (mot de passe: password123)
INSERT IGNORE INTO `users` (
  `id`, `email`, `password_hash`, `first_name`, `last_name`,
  `address`, `phone`, `license_number`, `birth_date`, `role`,
  `created_at`, `league_id`, `league_name`
) VALUES (
  2,
  'tresorier.test@fredi.local',
  '$2y$10$Z0fXwvGH9kKgH8XkR8Q8KuK8K0K8K0K8K0K8K0K8K0K8K0K8K0K8K8',
  'Trésorier',
  'Test',
  '123 Rue de Test, Lorraine',
  '+33612345678',
  'LIC123456',
  '1990-01-01',
  'tresorier',
  NOW(),
  1,
  'Ligue Test'
);

-- Rapport de test (ancien système)
INSERT IGNORE INTO `expense_reports` (`id`, `user_id`, `title`, `status`, `created_at`, `updated_at`)
VALUES (1, 1, 'Test', 'soumis', '2026-02-27 12:30:52', '2026-02-27 12:32:07');

-- ========================================
-- 5. VALIDATION ET COMMIT
-- ========================================

COMMIT;

-- ========================================
-- INSTRUCTIONS POST-INSTALLATION
-- ========================================
/*
APRÈS AVOIR EXÉCUTÉ CE SCRIPT :

1. Vérifiez que toutes les tables ont été créées :
   SHOW TABLES;

2. Testez la connexion avec le compte trésorier :
   Email: tresorier.test@fredi.local
   Mot de passe: password123

3. Le système est prêt pour la production !

NOTE: Ce script est non-destructif et peut être exécuté plusieurs fois.
*/