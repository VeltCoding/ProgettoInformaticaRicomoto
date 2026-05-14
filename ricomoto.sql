-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Mag 14, 2026 alle 21:44
-- Versione del server: 10.4.32-MariaDB
-- Versione PHP: 8.2.12

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
-- Struttura della tabella `acquisto`
--

CREATE TABLE `acquisto` (
  `id` int(11) NOT NULL,
  `prodotto_id` int(11) NOT NULL,
  `offerta_id` int(11) DEFAULT NULL,
  `utente_id` int(11) NOT NULL,
  `officina_id` int(11) NOT NULL,
  `prezzo_pagato` decimal(10,2) NOT NULL,
  `stato_pagamento` varchar(30) NOT NULL DEFAULT 'pagato',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `stato_ordine` varchar(30) NOT NULL DEFAULT 'pagato',
  `corriere` varchar(100) DEFAULT NULL,
  `codice_spedizione` varchar(100) DEFAULT NULL,
  `note_ordine` text DEFAULT NULL,
  `nome_spedizione` varchar(120) DEFAULT NULL,
  `telefono_spedizione` varchar(30) DEFAULT NULL,
  `indirizzo_spedizione` varchar(255) DEFAULT NULL,
  `citta_spedizione` varchar(100) DEFAULT NULL,
  `provincia_spedizione` varchar(50) DEFAULT NULL,
  `cap_spedizione` varchar(10) DEFAULT NULL,
  `note_spedizione` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `email` varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `admin`
--

INSERT INTO `admin` (`id`, `utente_id`, `nome`, `cognome`, `numero`, `email`) VALUES
(1, 1, 'Admin', 'Ricomoto', '0000000000', 'admin@ricomoto.local');

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

-- --------------------------------------------------------

--
-- Struttura della tabella `notifica_utente`
--

CREATE TABLE `notifica_utente` (
  `id` int(11) NOT NULL,
  `utente_id` int(11) NOT NULL,
  `prodotto_id` int(11) DEFAULT NULL,
  `messaggio` text NOT NULL,
  `letto` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `offerta_prezzo`
--

CREATE TABLE `offerta_prezzo` (
  `id` int(11) NOT NULL,
  `prodotto_id` int(11) NOT NULL,
  `utente_id` int(11) NOT NULL,
  `officina_id` int(11) NOT NULL,
  `prezzo_offerto` decimal(10,2) NOT NULL,
  `messaggio` text DEFAULT NULL,
  `stato` varchar(30) NOT NULL DEFAULT 'in_attesa',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `officina`
--

CREATE TABLE `officina` (
  `id` int(30) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `iva` varchar(20) NOT NULL,
  `indirizzo` varchar(80) NOT NULL,
  `utente_id` int(11) NOT NULL,
  `cellulare` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `officina`
--

INSERT INTO `officina` (`id`, `nome`, `iva`, `indirizzo`, `utente_id`, `cellulare`) VALUES
(1, 'GrenaMoto', 'JKG345890JF', 'via milano 35', 2, '945762385'),
(2, 'MarcoMoto', '234234234', 'via milano 55, Bologna', 4, '2342344');

-- --------------------------------------------------------

--
-- Struttura della tabella `ordine_messaggio`
--

CREATE TABLE `ordine_messaggio` (
  `id` int(11) NOT NULL,
  `acquisto_id` int(11) NOT NULL,
  `mittente_utente_id` int(11) NOT NULL,
  `messaggio` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(12, 'utenti.gestisci', 'Gestire utenti e tenant'),
(13, 'prodotto.disattiva', 'Disattivare prodotto'),
(14, 'prodotto.elimina', 'Eliminare prodotto'),
(15, 'prodotto.leggi', 'Leggere tutti i prodotti'),
(16, 'acquisto.crea', 'Acquistare prodotto'),
(17, 'notifica.leggi', 'Leggere notifiche');

-- --------------------------------------------------------

--
-- Struttura della tabella `prodotto`
--

CREATE TABLE `prodotto` (
  `id` int(11) NOT NULL,
  `officina_id` int(11) NOT NULL,
  `shop_id` int(11) DEFAULT NULL,
  `titolo` varchar(150) NOT NULL,
  `descrizione` text NOT NULL,
  `prezzo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `quantita` int(11) NOT NULL DEFAULT 1,
  `trattabile` tinyint(1) NOT NULL DEFAULT 1,
  `immagine` varchar(255) NOT NULL,
  `stato` enum('disponibile','venduto') NOT NULL DEFAULT 'disponibile',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `prodotto`
--

INSERT INTO `prodotto` (`id`, `officina_id`, `shop_id`, `titolo`, `descrizione`, `prezzo`, `quantita`, `trattabile`, `immagine`, `stato`, `created_at`) VALUES
(5, 1, 1, 'Marmitta Polini', 'marmitta polini nuova per aprilia sr50', 155.00, 5, 1, 'uploads/p_616684a8a1b091a5.jpg', 'disponibile', '2026-05-14 19:36:53'),
(6, 1, 1, 'Scarico KTM', 'Scarico KTMxFMF per tutti i modelli 2t 125cc di ktm.', 80.00, 1, 1, 'uploads/p_503552e2a63e81e8.jpg', 'disponibile', '2026-05-14 19:37:59'),
(7, 1, 1, 'Manubrio Renthal', 'Manubrio arancione renthal', 80.00, 10, 1, 'uploads/p_1c68e9d70bf5a22c.jpg', 'disponibile', '2026-05-14 19:38:42'),
(8, 1, 1, 'Carene nere Ktm 125 2020', 'carene per tutti i modelli ktm 125 2020  NERE', 110.00, 2, 1, 'uploads/p_f5a7707f672cc668.jpg', 'disponibile', '2026-05-14 19:40:07'),
(9, 1, 1, 'Cerchi Stradali da 17', 'Cerchi non gommati da strada per tutte le moto. da 17 pollici', 1200.00, 1, 1, 'uploads/p_4ddf1c6e81338f75.jpg', 'disponibile', '2026-05-14 19:41:26');

-- --------------------------------------------------------

--
-- Struttura della tabella `recensione_shop`
--

CREATE TABLE `recensione_shop` (
  `id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `utente_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL,
  `commento` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(2, 'cliente', 'Cliente: compra prodotti'),
(3, 'officina', 'Officina: gestisce shop e catalogo');

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
(3, 5),
(3, 7),
(3, 8),
(3, 9),
(3, 10),
(3, 11),
(3, 15),
(3, 16),
(3, 17);

-- --------------------------------------------------------

--
-- Struttura della tabella `shop`
--

CREATE TABLE `shop` (
  `id` int(11) NOT NULL,
  `officina_id` int(30) NOT NULL,
  `nome` varchar(120) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `localita` varchar(120) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `descrizione` text DEFAULT NULL,
  `status` enum('active','suspended') NOT NULL DEFAULT 'active',
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `shop`
--

INSERT INTO `shop` (`id`, `officina_id`, `nome`, `slug`, `localita`, `logo`, `descrizione`, `status`, `featured`, `created_at`) VALUES
(1, 1, 'GrenaMoto', 'grena-moto', 'Bergamo', 'uploads/loghi/shop_f1e24f77094e961e.png', 'Ricambi di moto.', 'active', 0, '2026-05-11 06:20:37'),
(2, 2, 'MarcoMoto', 'marco-moto', 'Bologna', 'uploads/loghi/shop_b73ff6e405c77846.png', 'Rivenditore da bologna. tutti i ricambi', 'active', 0, '2026-05-14 19:43:55');

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
(1, 1),
(2, 3),
(3, 2),
(4, 3);

-- --------------------------------------------------------

--
-- Struttura della tabella `utente`
--

CREATE TABLE `utente` (
  `ID` int(30) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `cognome` varchar(50) NOT NULL,
  `telefono` varchar(15) NOT NULL,
  `email` varchar(80) NOT NULL,
  `indirizzo` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `utente`
--

INSERT INTO `utente` (`ID`, `nome`, `cognome`, `telefono`, `email`, `indirizzo`, `password`) VALUES
(1, 'Admin', 'Ricomoto', '0000000000', 'admin@ricomoto.local', 'Ricomoto HQ', '$2y$10$tfnI4RxrJclN2jlf5TmEg.ptlnrwvWWUO5dhygShp3T0lqt71LJgK'),
(2, 'Officina', 'GrenaMoto', '945762385', 'grenamoto@gmail.com', 'via milano 35', '$2y$10$898jaZO8TFqGW8NzkZB.1Oq/c4r.Vq2t9wuXglBEAU1eGh6Kwc1CO'),
(3, 'Marco', 'Grena', '23430298', 'grena@gmail.com', 'via carducci 3', '$2y$10$tfnI4RxrJclN2jlf5TmEg.ptlnrwvWWUO5dhygShp3T0lqt71LJgK'),
(4, 'Officina', 'MarcoMoto', '2342344', 'bolognamoto@gmail.com', 'via milano 55, Bologna', '$2y$10$eDNNx5l3jBz6Rld2hsxM/uFTXLw8EnCO9KDFlIUR20BUtvnAN7vge');

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `acquisto`
--
ALTER TABLE `acquisto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prodotto_id` (`prodotto_id`),
  ADD KEY `utente_id` (`utente_id`),
  ADD KEY `officina_id` (`officina_id`);

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
-- Indici per le tabelle `notifica_utente`
--
ALTER TABLE `notifica_utente`
  ADD PRIMARY KEY (`id`),
  ADD KEY `utente_id` (`utente_id`),
  ADD KEY `prodotto_id` (`prodotto_id`);

--
-- Indici per le tabelle `offerta_prezzo`
--
ALTER TABLE `offerta_prezzo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prodotto_id` (`prodotto_id`),
  ADD KEY `utente_id` (`utente_id`),
  ADD KEY `officina_id` (`officina_id`);

--
-- Indici per le tabelle `officina`
--
ALTER TABLE `officina`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_officina_utente` (`utente_id`),
  ADD UNIQUE KEY `uq_officina_iva` (`iva`);

--
-- Indici per le tabelle `ordine_messaggio`
--
ALTER TABLE `ordine_messaggio`
  ADD PRIMARY KEY (`id`),
  ADD KEY `acquisto_id` (`acquisto_id`),
  ADD KEY `mittente_utente_id` (`mittente_utente_id`);

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
  ADD KEY `officina_id` (`officina_id`),
  ADD KEY `shop_id` (`shop_id`);

--
-- Indici per le tabelle `recensione_shop`
--
ALTER TABLE `recensione_shop`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_recensione_shop_utente` (`shop_id`,`utente_id`),
  ADD KEY `shop_id` (`shop_id`),
  ADD KEY `utente_id` (`utente_id`);

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
-- Indici per le tabelle `shop`
--
ALTER TABLE `shop`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_shop_officina` (`officina_id`),
  ADD UNIQUE KEY `uq_shop_slug` (`slug`);

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
-- AUTO_INCREMENT per la tabella `acquisto`
--
ALTER TABLE `acquisto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT per la tabella `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT per la tabella `notifica`
--
ALTER TABLE `notifica`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT per la tabella `notifica_utente`
--
ALTER TABLE `notifica_utente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT per la tabella `offerta_prezzo`
--
ALTER TABLE `offerta_prezzo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT per la tabella `officina`
--
ALTER TABLE `officina`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT per la tabella `ordine_messaggio`
--
ALTER TABLE `ordine_messaggio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT per la tabella `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT per la tabella `prodotto`
--
ALTER TABLE `prodotto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT per la tabella `recensione_shop`
--
ALTER TABLE `recensione_shop`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
-- AUTO_INCREMENT per la tabella `shop`
--
ALTER TABLE `shop`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT per la tabella `utente`
--
ALTER TABLE `utente`
  MODIFY `ID` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `acquisto`
--
ALTER TABLE `acquisto`
  ADD CONSTRAINT `fk_acquisto_officina` FOREIGN KEY (`officina_id`) REFERENCES `officina` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_acquisto_prodotto` FOREIGN KEY (`prodotto_id`) REFERENCES `prodotto` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_acquisto_utente` FOREIGN KEY (`utente_id`) REFERENCES `utente` (`ID`) ON DELETE CASCADE;

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
-- Limiti per la tabella `notifica_utente`
--
ALTER TABLE `notifica_utente`
  ADD CONSTRAINT `fk_notifica_utente_prodotto` FOREIGN KEY (`prodotto_id`) REFERENCES `prodotto` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_notifica_utente_user` FOREIGN KEY (`utente_id`) REFERENCES `utente` (`ID`) ON DELETE CASCADE;

--
-- Limiti per la tabella `offerta_prezzo`
--
ALTER TABLE `offerta_prezzo`
  ADD CONSTRAINT `fk_offerta_officina` FOREIGN KEY (`officina_id`) REFERENCES `officina` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_offerta_prodotto` FOREIGN KEY (`prodotto_id`) REFERENCES `prodotto` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_offerta_utente` FOREIGN KEY (`utente_id`) REFERENCES `utente` (`ID`) ON DELETE CASCADE;

--
-- Limiti per la tabella `officina`
--
ALTER TABLE `officina`
  ADD CONSTRAINT `fk_officina_utente` FOREIGN KEY (`utente_id`) REFERENCES `utente` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `ordine_messaggio`
--
ALTER TABLE `ordine_messaggio`
  ADD CONSTRAINT `fk_msg_ordine_acquisto` FOREIGN KEY (`acquisto_id`) REFERENCES `acquisto` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_msg_ordine_utente` FOREIGN KEY (`mittente_utente_id`) REFERENCES `utente` (`ID`) ON DELETE CASCADE;

--
-- Limiti per la tabella `prodotto`
--
ALTER TABLE `prodotto`
  ADD CONSTRAINT `fk_prodotto_officina` FOREIGN KEY (`officina_id`) REFERENCES `officina` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_prodotto_shop` FOREIGN KEY (`shop_id`) REFERENCES `shop` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Limiti per la tabella `recensione_shop`
--
ALTER TABLE `recensione_shop`
  ADD CONSTRAINT `fk_recensione_shop` FOREIGN KEY (`shop_id`) REFERENCES `shop` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_recensione_utente` FOREIGN KEY (`utente_id`) REFERENCES `utente` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

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
-- Limiti per la tabella `shop`
--
ALTER TABLE `shop`
  ADD CONSTRAINT `fk_shop_officina` FOREIGN KEY (`officina_id`) REFERENCES `officina` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
