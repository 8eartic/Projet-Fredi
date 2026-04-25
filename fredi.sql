-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : ven. 27 fév. 2026 à 12:52
-- Version du serveur : 8.4.7
-- Version de PHP : 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `fredi`
--

-- --------------------------------------------------------

--
-- Structure de la table `expense_lines`
--

DROP TABLE IF EXISTS `expense_lines`;
CREATE TABLE IF NOT EXISTS `expense_lines` (
  `id` int NOT NULL AUTO_INCREMENT,
  `report_id` int NOT NULL,
  `date` date NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_report_lines` (`report_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `expense_reports`
--

DROP TABLE IF EXISTS `expense_reports`;
CREATE TABLE IF NOT EXISTS `expense_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('brouillon','soumis','valide','rejete') COLLATE utf8mb4_unicode_ci DEFAULT 'brouillon',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_reports` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `expense_reports`
--

INSERT INTO `expense_reports` (`id`, `user_id`, `title`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Test', 'soumis', '2026-02-27 12:30:52', '2026-02-27 12:32:07');

-- --------------------------------------------------------

--
-- Structure de la table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `club_id` int DEFAULT NULL,
  `license_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `role` enum('adherent','tresorier','admin') COLLATE utf8mb4_unicode_ci DEFAULT 'adherent',
  `reset_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `first_name`, `last_name`, `club_id`, `license_number`, `birth_date`, `role`, `created_at`) VALUES
(1, 'test@email.test', '$2y$10$ihTiJ7HwiqWJTqPO9mVTOO7.6.G3n9BLQD9J8acF95xVtMLwLEPZi', 'Ludovic', 'Conlin', NULL, NULL, NULL, 'adherent', '2026-02-27 12:30:30');

-- --------------------------------------------------------
-- Ajout des tables de gestion des remboursements et documents
-- --------------------------------------------------------

DROP TABLE IF EXISTS `mission`;
CREATE TABLE IF NOT EXISTS `mission` (
  `id_mission` int NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `debut` date DEFAULT NULL,
  `fin` date DEFAULT NULL,
  PRIMARY KEY (`id_mission`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `remboursement`;
CREATE TABLE IF NOT EXISTS `remboursement` (
  `id_remboursement` int NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int NOT NULL,
  `id_mission` int DEFAULT NULL,
  `transport` decimal(10,2) DEFAULT '0.00',
  `hebergement` decimal(10,2) DEFAULT '0.00',
  `parking` decimal(10,2) DEFAULT '0.00',
  `carburant` decimal(10,2) DEFAULT '0.00',
  `autres_frais` decimal(10,2) DEFAULT '0.00',
  `total` decimal(10,2) DEFAULT '0.00',
  `statut` enum('EN_ATTENTE','ACCEPTEE','REFUSEE','PAYEE') COLLATE utf8mb4_unicode_ci DEFAULT 'EN_ATTENTE',
  `date_demande` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_remboursement`),
  KEY `idx_user` (`id_utilisateur`),
  KEY `idx_mission` (`id_mission`),
  CONSTRAINT `fk_remb_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `users` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `documents`;
CREATE TABLE IF NOT EXISTS `documents` (
  `id_document` int NOT NULL AUTO_INCREMENT,
  `id_remboursement` int NOT NULL,
  `id_mission` int DEFAULT NULL,
  `categorie` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'autres_frais',
  `nom_fichier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `chemin_fichier` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_fichier` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `taille_fichier` int DEFAULT NULL,
  `montant` decimal(10,2) DEFAULT '0.00',
  `date_upload` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_document`),
  KEY `idx_remboursement` (`id_remboursement`),
  KEY `idx_mission` (`id_mission`),
  CONSTRAINT `fk_docs_remb` FOREIGN KEY (`id_remboursement`) REFERENCES `remboursement` (`id_remboursement`) ON DELETE CASCADE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
