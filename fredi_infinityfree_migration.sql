-- Migration non destructive pour FREDI sur InfinityFree
-- Ajoute les colonnes manquantes et un compte trésorier de test

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8;

ALTER TABLE `remboursement`
  ADD COLUMN IF NOT EXISTS `repas_france` decimal(10,2) DEFAULT 0.00,
  ADD COLUMN IF NOT EXISTS `repas_etranger` decimal(10,2) DEFAULT 0.00;

ALTER TABLE `documents`
  ADD COLUMN IF NOT EXISTS `is_don` tinyint(1) DEFAULT 0;

INSERT IGNORE INTO `users` (
  `email`, `password_hash`, `first_name`, `last_name`, `role`, `created_at`, `league_id`, `league_name`
) VALUES (
  'tresorier@test.test',
  '$2b$12$umZbv9CIsLS7/jGsI7x/L.mdYzi/QCOPLrfetEnov1CcuUysVtJKi',
  'Tresorier',
  'Test',
  'tresorier',
  NOW(),
  NULL,
  NULL
);

COMMIT;
