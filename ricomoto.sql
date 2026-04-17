-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Creato il: Gen 29, 2026 alle 22:48
-- Versione del server: 10.11.14-MariaDB-0ubuntu0.24.04.1
-- Versione PHP: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ricomoto`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `utente_id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `cognome` varchar(50) NOT NULL,
  `numero` varchar(15) NOT NULL,
  `email` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `notifica`
--

CREATE TABLE `notifica` (
  `id` int(11) NOT NULL,
  `officina_id` int(11) NOT NULL,
  `prodotto_id` int(11) NOT NULL,
  `utente_id` int(11) NOT NULL,
  `messaggio` varchar(255) NOT NULL,
  `letto` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `notifica`
--

INSERT INTO `notifica` (`id`, `officina_id`, `prodotto_id`, `utente_id`, `messaggio`, `letto`, `created_at`) VALUES
(1, 3, 1, 10, 'Hai ricevuto un acquisto per: marmitta', 0, '2026-01-29 17:40:00'),
(2, 3, 2, 10, 'Hai ricevuto un acquisto per: Cilindro', 0, '2026-01-29 20:28:23'),
(3, 3, 3, 10, 'Hai ricevuto un acquisto per: Cilindro 2', 0, '2026-01-29 22:40:17');

-- --------------------------------------------------------

--
-- Struttura della tabella `officina`
--

CREATE TABLE `officina` (
  `id` int(30) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `iva` varchar(11) NOT NULL,
  `indirizzo` varchar(80) NOT NULL,
  `utente_id` int(11) NOT NULL,
  `cellulare` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `officina`
--

INSERT INTO `officina` (`id`, `nome`, `iva`, `indirizzo`, `utente_id`, `cellulare`) VALUES
(3, 'ilyasmoto', 'ciao', 'via roma 55, bergamo', 11, '3667136638');

-- --------------------------------------------------------

--
-- Struttura della tabella `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `code` varchar(80) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `permissions`
--

INSERT INTO `permissions` (`id`, `code`, `description`) VALUES
(1, 'catalogo.leggi', 'Vedere e cercare prodotti'),
(2, 'carrello.gestisci', 'Gestire carrello'),
(3, 'ordine.crea', 'Acquistare prodotti'),
(4, 'ordine.leggi', 'Vedere i propri ordini'),
(5, 'recensione.crea', 'Scrivere recensione'),
(6, 'offerta.crea', 'Fare offerta di prezzo'),
(7, 'prodotto.crea', 'Caricare prodotto'),
(8, 'prodotto.aggiorna', 'Modificare prodotto (proprio)'),
(9, 'prodotto.leggi_miei', 'Vedere i propri prodotti'),
(10, 'offerta.gestisci', 'Accettare/rifiutare offerte ricevute'),
(11, 'recensione.rispondi', 'Rispondere alle recensioni'),
(12, 'utenti.gestisci', 'Bannare utenti'),
(13, 'prodotto.disattiva', 'Disattivare prodotto'),
(14, 'prodotto.elimina', 'Eliminare prodotto'),
(15, 'prodotto.leggi', NULL),
(16, 'acquisto.crea', NULL),
(17, 'notifica.leggi', NULL);

-- --------------------------------------------------------

--
-- Struttura della tabella `prodotto`
--

CREATE TABLE `prodotto` (
  `id` int(11) NOT NULL,
  `officina_id` int(11) NOT NULL,
  `titolo` varchar(150) NOT NULL,
  `descrizione` text NOT NULL,
  `immagine` varchar(255) NOT NULL,
  `stato` enum('disponibile','venduto') NOT NULL DEFAULT 'disponibile',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `prodotto`
--

INSERT INTO `prodotto` (`id`, `officina_id`, `titolo`, `descrizione`, `immagine`, `stato`, `created_at`) VALUES
(1, 3, 'marmitta', 'marmitta nuova', 'uploads/p_05880219423ce9d7.jpeg', 'venduto', '2026-01-29 17:39:31'),
(2, 3, 'Cilindro', 'cilindro motore', 'uploads/p_f87a26431fca4c9e.jpg', 'venduto', '2026-01-29 20:02:57'),
(3, 3, 'Cilindro 2', 'sd', 'uploads/p_af24ec55970c0459.jpg', 'venduto', '2026-01-29 22:32:32');

-- --------------------------------------------------------

--
-- Struttura della tabella `refresh_token`
--

CREATE TABLE `refresh_token` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'admin', 'Amministratore del sito'),
(2, 'cliente', 'Cliente: compra/recensioni/offerte'),
(3, 'officina', 'Officina: carica prodotti/gestisce offerte/risponde');

-- --------------------------------------------------------

--
-- Struttura della tabella `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(1, 8),
(1, 9),
(1, 10),
(1, 11),
(1, 12),
(1, 13),
(1, 14),
(1, 15),
(1, 16),
(1, 17),
(2, 1),
(2, 2),
(2, 3),
(2, 4),
(2, 5),
(2, 6),
(2, 15),
(2, 16),
(3, 1),
(3, 7),
(3, 8),
(3, 9),
(3, 10),
(3, 11),
(3, 15),
(3, 17);

-- --------------------------------------------------------

--
-- Struttura della tabella `user_permissions`
--

CREATE TABLE `user_permissions` (
  `user_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `user_roles`
--

CREATE TABLE `user_roles` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `user_roles`
--

INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES
(10, 2),
(11, 3),
(12, 2),
(13, 2);

-- --------------------------------------------------------

--
-- Struttura della tabella `utente`
--

CREATE TABLE `utente` (
  `ID` int(30) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `cognome` varchar(50) NOT NULL,
  `telefono` varchar(15) NOT NULL,
  `email` varchar(50) NOT NULL,
  `indirizzo` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `utente`
--

INSERT INTO `utente` (`ID`, `nome`, `cognome`, `telefono`, `email`, `indirizzo`, `password`) VALUES
(10, 'ilyas', 'ouajidi', '3667136638', 'ouajidi.ilyas.studente@itispaleocapa.it', 'via milano 11, bergamo', '$2y$10$i1ba.4B/c0DVre3dhGCVleYPLq3dhGzXoh/lSASK.XOKC.fXnWiVi'),
(11, 'Officina', 'ilyasmoto', '3667136638', 'ilyasmoto@gmail.com', 'via roma 55, bergamo', '$2y$10$klGQhQejLNFKVCjdFA1DBuJmmbQQYoMYwDrDkU9KJrYkSWD8294Ae'),
(12, 'ettore', 'teramo', '123', 'ettore@gmail.com', '123', '$2y$10$HDeUZbPLyOFAbbpHKPBBEe6rSlzO6hcQjlneOy6QCQHaWUNrODdRK'),
(13, 'kevin', 'mecja', '23423', 'ciao1@gmail.com', 'via milano 11 bergamo', '$2y$10$9.ha5t/kPfPdUadEK1IEeeVkhlSLkirB3T.sZNInqTdKdzBrbkKe.');

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `utente_id` (`utente_id`);

--
-- Indici per le tabelle `notifica`
--
ALTER TABLE `notifica`
  ADD PRIMARY KEY (`id`),
  ADD KEY `officina_id` (`officina_id`),
  ADD KEY `prodotto_id` (`prodotto_id`),
  ADD KEY `utente_id` (`utente_id`);

--
-- Indici per le tabelle `officina`
--
ALTER TABLE `officina`
  ADD PRIMARY KEY (`id`,`iva`),
  ADD UNIQUE KEY `uq_officina_utente` (`utente_id`),
  ADD UNIQUE KEY `uq_officina_iva` (`iva`);

--
-- Indici per le tabelle `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indici per le tabelle `prodotto`
--
ALTER TABLE `prodotto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `officina_id` (`officina_id`);

--
-- Indici per le tabelle `refresh_token`
--
ALTER TABLE `refresh_token`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `token_hash` (`token_hash`);

--
-- Indici per le tabelle `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indici per le tabelle `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indici per le tabelle `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD PRIMARY KEY (`user_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indici per le tabelle `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`user_id`,`role_id`),
  ADD KEY `role_id` (`role_id`);

--
-- Indici per le tabelle `utente`
--
ALTER TABLE `utente`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `uq_utente_email` (`email`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `notifica`
--
ALTER TABLE `notifica`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT per la tabella `officina`
--
ALTER TABLE `officina`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT per la tabella `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT per la tabella `prodotto`
--
ALTER TABLE `prodotto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT per la tabella `refresh_token`
--
ALTER TABLE `refresh_token`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT per la tabella `utente`
--
ALTER TABLE `utente`
  MODIFY `ID` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`utente_id`) REFERENCES `utente` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `notifica`
--
ALTER TABLE `notifica`
  ADD CONSTRAINT `fk_notifica_officina` FOREIGN KEY (`officina_id`) REFERENCES `officina` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notifica_prodotto` FOREIGN KEY (`prodotto_id`) REFERENCES `prodotto` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notifica_utente` FOREIGN KEY (`utente_id`) REFERENCES `utente` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `officina`
--
ALTER TABLE `officina`
  ADD CONSTRAINT `fk_officina_utente` FOREIGN KEY (`utente_id`) REFERENCES `utente` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `prodotto`
--
ALTER TABLE `prodotto`
  ADD CONSTRAINT `fk_prodotto_officina` FOREIGN KEY (`officina_id`) REFERENCES `officina` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `refresh_token`
--
ALTER TABLE `refresh_token`
  ADD CONSTRAINT `fk_refresh_user` FOREIGN KEY (`user_id`) REFERENCES `utente` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD CONSTRAINT `user_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `utente` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `utente` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

-- Aggiungi colonne mancanti a tenancy (se non esistono)
ALTER TABLE tenancy ADD COLUMN IF NOT EXISTS descrizione TEXT DEFAULT NULL;
ALTER TABLE tenancy ADD COLUMN IF NOT EXISTS contatti VARCHAR(255) DEFAULT NULL;
ALTER TABLE tenancy ADD COLUMN IF NOT EXISTS colore_primario VARCHAR(20) DEFAULT NULL;
ALTER TABLE tenancy ADD COLUMN IF NOT EXISTS colore_secondario VARCHAR(20) DEFAULT NULL;
ALTER TABLE tenancy ADD COLUMN IF NOT EXISTS logo VARCHAR(255) DEFAULT NULL;

-- Aggiungi colonna mancante a prodotto (se non esiste)
ALTER TABLE prodotto ADD COLUMN IF NOT EXISTS descrizione TEXT NOT NULL DEFAULT '';

-- 1. Creare tabella tenancy (solo se non esiste)
CREATE TABLE IF NOT EXISTS `tenancy` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome_shop` varchar(255) NOT NULL,
  `subdomain` varchar(100) NOT NULL UNIQUE,
  `logo` varchar(255) DEFAULT NULL,
  `colore_primario` varchar(20) DEFAULT NULL,
  `colore_secondario` varchar(20) DEFAULT NULL,
  `descrizione` text DEFAULT NULL,
  `contatti` varchar(255) DEFAULT NULL,
  `stato` enum('attivo','sospeso','disattivato') NOT NULL DEFAULT 'attivo',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tenancy principale (solo se non esiste)
INSERT INTO `tenancy` (`nome_shop`, `subdomain`, `stato`) 
VALUES ('Ricomoto Centrale', 'centrale', 'attivo')
ON DUPLICATE KEY UPDATE `nome_shop` = `nome_shop`;

-- 3. Aggiungere ruolo admin_tenancy (solo se non esiste)
INSERT INTO `roles` (`name`, `description`) VALUES ('admin_tenancy', 'Amministratore shop/officina')
ON DUPLICATE KEY UPDATE `name` = `name`;

-- 4. Permessi per admin_tenancy (solo se non esistono)
INSERT INTO `permissions` (`code`, `description`) VALUES 
    ('tenancy.gestisci', 'Gestisce la tenancy'),
    ('tenancy.leggi', 'Legge dati tenancy'),
    ('prodotto.modifica', 'Modifica prodotti'),
    ('officina.leggi', 'Legge dati officina'),
    ('officina.modifica', 'Modifica officina')
ON DUPLICATE KEY UPDATE `code` = `code`;

-- 5. Assegna permessi al ruolo admin_tenancy
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p 
WHERE r.name = 'admin_tenancy' 
AND p.code IN ('tenancy.gestisci', 'tenancy.leggi', 'prodotto.modifica', 'officina.leggi', 'officina.modifica')
ON DUPLICATE KEY UPDATE `role_id` = `role_id`;

-- 6. Creare utente Admin (solo se non esiste)
-- Password: Admin123!
INSERT INTO `utente` (`nome`, `cognome`, `telefono`, `email`, `indirizzo`, `password`, `tenancy_id`)
SELECT 'Admin', 'Sistema', '0000000000', 'admin@ricomoto.it', 'Sede centrale', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyYqKxLQv2Gi', NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `utente` WHERE `email` = 'admin@ricomoto.it');

-- 7. Assegna ruolo admin all'utente admin
INSERT INTO `user_roles` (`user_id`, `role_id`)
SELECT u.ID, r.id FROM `utente` u, `roles` r 
WHERE u.email = 'admin@ricomoto.it' AND r.name = 'admin'
ON DUPLICATE KEY UPDATE `user_id` = `user_id`;

-- 8. Aggiorna AUTO_INCREMENT
ALTER TABLE `roles` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
ALTER TABLE `permissions` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;
ALTER TABLE `utente` MODIFY `ID` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
