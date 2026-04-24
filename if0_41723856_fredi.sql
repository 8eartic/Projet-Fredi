-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Hôte : sql211.infinityfree.com
-- Généré le :  ven. 24 avr. 2026 à 07:46
-- Version du serveur :  11.4.10-MariaDB
-- Version de PHP :  7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données :  `if0_41723856_fredi`
--

-- --------------------------------------------------------

--
-- Structure de la table `accounting_reports`
--

CREATE TABLE `accounting_reports` (
  `id_report` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `league_id` int(11) DEFAULT NULL,
  `generated_by` int(11) NOT NULL,
  `total_receipts` decimal(12,2) DEFAULT 0.00,
  `total_count` int(11) DEFAULT 0,
  `generated_date` datetime DEFAULT current_timestamp(),
  `pdf_path` varchar(500) DEFAULT NULL,
  `status` enum('draft','validated','archived') DEFAULT 'draft'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `cerfa_receipts`
--

CREATE TABLE `cerfa_receipts` (
  `id_cerfa` int(11) NOT NULL,
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
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `documents`
--

CREATE TABLE `documents` (
  `id_document` int(11) NOT NULL,
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
  `is_don` tinyint(1) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `documents`
--

INSERT INTO `documents` (`id_document`, `id_remboursement`, `id_mission`, `categorie`, `nom_fichier`, `chemin_fichier`, `type_fichier`, `taille_fichier`, `montant`, `date_upload`, `line_number`, `is_justified`, `validation_status`, `is_don`) VALUES
(1, 1, NULL, 'transport', 'filigrane (1).png', 'uploads/documents/TT_1_69e8726cc157e4.96923999.png', 'png', 1155836, '88.99', '2026-04-22 07:02:04', NULL, 0, 'non_justifie', 0),
(2, 1, NULL, 'autres_frais', 'filigrane.png', 'uploads/documents/TT_1_69e8726cc1a969.20440179.png', 'png', 1114189, '259.00', '2026-04-22 07:02:04', NULL, 0, 'non_justifie', 0),
(3, 3, NULL, 'transport', 'Ghost rider photo icon.jpg', 'uploads/documents/TT_3_69e876607ffe44.46020435.jpg', 'jpg', 163543, '150.00', '2026-04-22 07:18:56', NULL, 0, 'non_justifie', 1),
(4, 3, NULL, 'parking', 'WhatsApp Image 2025-09-29 at 11.34.22.jpeg', 'uploads/documents/TT_3_69e87660803938.44530337.jpeg', 'jpeg', 147327, '789.00', '2026-04-22 07:18:56', NULL, 0, 'non_justifie', 1),
(5, 3, NULL, 'autres_frais', 'F78F1176-4A0E-40A6-9A06-66EC8B97ABCC.png', 'uploads/documents/TT_3_69e87660806273.27799913.png', 'png', 990110, '5194.00', '2026-04-22 07:18:56', NULL, 0, 'non_justifie', 1),
(6, 6, NULL, 'transport', 'filigrane.png', 'uploads/documents/TT_6_69e8b26ec257d1.59381219.png', 'png', 11425981, '99999999.99', '2026-04-22 11:35:11', NULL, 0, 'non_justifie', 0);

-- --------------------------------------------------------

--
-- Structure de la table `expense_lines`
--

CREATE TABLE `expense_lines` (
  `id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `receipt_file` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `expense_reports`
--

CREATE TABLE `expense_reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `status` enum('brouillon','soumis','valide','rejete') DEFAULT 'brouillon',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `expense_reports`
--

INSERT INTO `expense_reports` (`id`, `user_id`, `title`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Test', 'soumis', '2026-02-27 12:30:52', '2026-02-27 12:32:07');

-- --------------------------------------------------------

--
-- Structure de la table `mission`
--

CREATE TABLE `mission` (
  `id_mission` int(11) NOT NULL,
  `titre` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `debut` date DEFAULT NULL,
  `fin` date DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `remboursement`
--

CREATE TABLE `remboursement` (
  `id_remboursement` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `id_mission` int(11) DEFAULT NULL,
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
  `repas_france` decimal(10,2) DEFAULT 0.00,
  `repas_etranger` decimal(10,2) DEFAULT 0.00
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `remboursement`
--

INSERT INTO `remboursement` (`id_remboursement`, `id_utilisateur`, `id_mission`, `transport`, `hebergement`, `parking`, `carburant`, `autres_frais`, `total`, `statut`, `date_demande`, `validation_status`, `submitted_date`, `validated_date`, `validated_by`, `validation_notes`, `repas_france`, `repas_etranger`) VALUES
(1, 3, NULL, '88.99', '28.00', '2859.00', '39.00', '259.00', '3691.99', 'EN_ATTENTE', '2026-04-22 07:02:04', 'brouillon', NULL, NULL, NULL, NULL, '159.00', '259.00'),
(2, 3, NULL, '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', 'EN_ATTENTE', '2026-04-22 07:02:45', 'brouillon', NULL, NULL, NULL, NULL, '0.00', '0.00'),
(3, 3, NULL, '150.00', '299.98', '789.00', '45.00', '5194.00', '6477.98', 'EN_ATTENTE', '2026-04-22 07:18:56', 'brouillon', NULL, NULL, NULL, NULL, '0.00', '0.00'),
(4, 3, NULL, '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', 'EN_ATTENTE', '2026-04-22 07:33:14', 'brouillon', NULL, NULL, NULL, NULL, '0.00', '0.00'),
(5, 3, NULL, '458.00', '847948.00', '7.00', '45611456.00', '541.00', '46460410.00', 'EN_ATTENTE', '2026-04-22 07:33:45', 'brouillon', NULL, NULL, NULL, NULL, '0.00', '0.00'),
(6, 3, NULL, '99999999.99', '0.00', '0.00', '0.00', '0.00', '99999999.99', 'EN_ATTENTE', '2026-04-22 11:35:11', 'brouillon', NULL, NULL, NULL, NULL, '0.00', '0.00');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
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
  `league_name` varchar(100) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `first_name`, `last_name`, `address`, `phone`, `club_id`, `license_number`, `birth_date`, `role`, `created_at`, `league_id`, `league_name`) VALUES
(1, 'test@email.test', '$2y$10$ihTiJ7HwiqWJTqPO9mVTOO7.6.G3n9BLQD9J8acF95xVtMLwLEPZi', 'Ludovic', 'Conlin', NULL, NULL, NULL, NULL, NULL, 'adherent', '2026-02-27 12:30:30', NULL, NULL),
(2, 'test2@email.test', '$2y$10$ihTiJ7HwiqWJTqPO9mVTOO7.6.G3n9BLQD9J8acF95xVtMLwLEPZi', 'Bertran', 'Luc', 'Chez MOI', '00000000', NULL, NULL, NULL, 'adherent', '2026-04-22 06:49:56', NULL, NULL),
(3, 'tresorier@test.test', '$2y$10$ihTiJ7HwiqWJTqPO9mVTOO7.6.G3n9BLQD9J8acF95xVtMLwLEPZi', 'Tresorier', 'Test', NULL, NULL, NULL, NULL, NULL, 'tresorier', '2026-04-22 07:00:41', NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `validation_history`
--

CREATE TABLE `validation_history` (
  `id_history` int(11) NOT NULL,
  `id_remboursement` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `montant_initial` decimal(10,2) DEFAULT NULL,
  `montant_final` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `accounting_reports`
--
ALTER TABLE `accounting_reports`
  ADD PRIMARY KEY (`id_report`),
  ADD UNIQUE KEY `unique_year_league` (`year`,`league_id`),
  ADD KEY `idx_accounting_reports_generated_by` (`generated_by`);

--
-- Index pour la table `cerfa_receipts`
--
ALTER TABLE `cerfa_receipts`
  ADD PRIMARY KEY (`id_cerfa`),
  ADD UNIQUE KEY `cerfa_number` (`cerfa_number`),
  ADD KEY `idx_year` (`year`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_cerfa_remboursement` (`id_remboursement`),
  ADD KEY `idx_cerfa_issued_by` (`issued_by`),
  ADD KEY `idx_cerfa_year` (`year`),
  ADD KEY `idx_cerfa_status` (`status`);

--
-- Index pour la table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id_document`),
  ADD KEY `idx_remboursement` (`id_remboursement`),
  ADD KEY `idx_mission` (`id_mission`),
  ADD KEY `idx_validation_status` (`validation_status`),
  ADD KEY `idx_documents_remboursement` (`id_remboursement`),
  ADD KEY `idx_documents_mission` (`id_mission`),
  ADD KEY `idx_documents_validation_status` (`validation_status`);

--
-- Index pour la table `expense_lines`
--
ALTER TABLE `expense_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_report_lines` (`report_id`),
  ADD KEY `idx_expense_lines_report` (`report_id`);

--
-- Index pour la table `expense_reports`
--
ALTER TABLE `expense_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_reports` (`user_id`),
  ADD KEY `idx_expense_reports_user` (`user_id`);

--
-- Index pour la table `mission`
--
ALTER TABLE `mission`
  ADD PRIMARY KEY (`id_mission`);

--
-- Index pour la table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `remboursement`
--
ALTER TABLE `remboursement`
  ADD PRIMARY KEY (`id_remboursement`),
  ADD KEY `idx_user` (`id_utilisateur`),
  ADD KEY `idx_mission` (`id_mission`),
  ADD KEY `idx_validation_status` (`validation_status`),
  ADD KEY `idx_submitted_date` (`submitted_date`),
  ADD KEY `idx_validated_by` (`validated_by`),
  ADD KEY `idx_remboursement_user` (`id_utilisateur`),
  ADD KEY `idx_remboursement_mission` (`id_mission`),
  ADD KEY `idx_remboursement_status` (`validation_status`),
  ADD KEY `idx_remboursement_submitted` (`submitted_date`),
  ADD KEY `idx_remboursement_validated_by` (`validated_by`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `idx_users_email` (`email`);

--
-- Index pour la table `validation_history`
--
ALTER TABLE `validation_history`
  ADD PRIMARY KEY (`id_history`),
  ADD KEY `idx_validation_history_remboursement` (`id_remboursement`),
  ADD KEY `idx_validation_history_user` (`id_utilisateur`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `accounting_reports`
--
ALTER TABLE `accounting_reports`
  MODIFY `id_report` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `cerfa_receipts`
--
ALTER TABLE `cerfa_receipts`
  MODIFY `id_cerfa` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `documents`
--
ALTER TABLE `documents`
  MODIFY `id_document` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `expense_lines`
--
ALTER TABLE `expense_lines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `expense_reports`
--
ALTER TABLE `expense_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `mission`
--
ALTER TABLE `mission`
  MODIFY `id_mission` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `remboursement`
--
ALTER TABLE `remboursement`
  MODIFY `id_remboursement` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `validation_history`
--
ALTER TABLE `validation_history`
  MODIFY `id_history` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
