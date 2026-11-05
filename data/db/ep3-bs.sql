SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Datenbank: `ep3-bs`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `bs_bookings`
--

CREATE TABLE IF NOT EXISTS `bs_bookings` (
  `bid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `sid` int(10) unsigned NOT NULL,
  `status` varchar(64) NOT NULL COMMENT 'single|subscription|cancelled',
  `status_billing` varchar(64) NOT NULL COMMENT 'pending|paid|cancelled|uncollectable',
  `visibility` varchar(64) NOT NULL COMMENT 'public|private',
  `quantity` int(10) unsigned NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`bid`),
  KEY `sid` (`sid`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `bs_bookings_bills`
--

CREATE TABLE IF NOT EXISTS `bs_bookings_bills` (
  `bbid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bid` int(10) unsigned NOT NULL,
  `description` varchar(512) NOT NULL,
  `quantity` int(10) unsigned DEFAULT NULL,
  `time` int(10) unsigned DEFAULT NULL,
  `price` int(10) NOT NULL,
  `rate` int(10) unsigned NOT NULL,
  `gross` tinyint(1) NOT NULL,
  PRIMARY KEY (`bbid`),
  KEY `bid` (`bid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `bs_bookings_meta`
--

CREATE TABLE IF NOT EXISTS `bs_bookings_meta` (
  `bmid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bid` int(10) unsigned NOT NULL,
  `key` varchar(64) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`bmid`),
  KEY `bid` (`bid`),
  KEY `key` (`key`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `bs_events`
--

CREATE TABLE IF NOT EXISTS `bs_events` (
  `eid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sid` int(10) unsigned DEFAULT NULL COMMENT 'NULL for all',
  `status` varchar(64) NOT NULL DEFAULT 'enabled' COMMENT 'enabled',
  `datetime_start` datetime NOT NULL,
  `datetime_end` datetime NOT NULL,
  `capacity` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`eid`),
  KEY `sid` (`sid`),
  KEY `datetime_start` (`datetime_start`),
  KEY `datetime_end` (`datetime_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `bs_events_meta`
--

CREATE TABLE IF NOT EXISTS `bs_events_meta` (
  `emid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `eid` int(10) unsigned NOT NULL,
  `key` varchar(64) NOT NULL,
  `value` text NOT NULL,
  `locale` varchar(8) DEFAULT NULL,
  PRIMARY KEY (`emid`),
  KEY `eid` (`eid`),
  KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `bs_options`
--

CREATE TABLE IF NOT EXISTS `bs_options` (
  `oid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(64) NOT NULL,
  `value` text NOT NULL,
  `locale` varchar(8) DEFAULT NULL,
  PRIMARY KEY (`oid`),
  KEY `key` (`key`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `bs_reservations`
--

CREATE TABLE IF NOT EXISTS `bs_reservations` (
  `rid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bid` int(10) unsigned NOT NULL,
  `date` date NOT NULL,
  `time_start` time NOT NULL,
  `time_end` time NOT NULL,
  PRIMARY KEY (`rid`),
  KEY `bid` (`bid`),
  KEY `date` (`date`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `bs_reservations_meta`
--

CREATE TABLE IF NOT EXISTS `bs_reservations_meta` (
  `rmid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rid` int(10) unsigned NOT NULL,
  `key` varchar(64) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`rmid`),
  KEY `rid` (`rid`),
  KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `bs_squares`
--

CREATE TABLE IF NOT EXISTS `bs_squares` (
  `sid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `status` varchar(64) NOT NULL DEFAULT 'enabled' COMMENT 'disabled|readonly|enabled',
  `priority` float NOT NULL DEFAULT '1',
  `capacity` int(10) unsigned NOT NULL,
  `capacity_heterogenic` tinyint(1) NOT NULL,
  `allow_notes` tinyint(1) NOT NULL DEFAULT 0,
  `time_start` time NOT NULL,
  `time_end` time NOT NULL,
  `time_block` int(10) unsigned NOT NULL,
  `time_block_bookable` int(10) unsigned NOT NULL,
  `time_block_bookable_max` int(10) unsigned DEFAULT NULL,
  `min_range_book` int(10) unsigned DEFAULT 0,
  `range_book` int(10) unsigned DEFAULT NULL,
  `max_active_bookings` int(10) unsigned DEFAULT 0,
  `range_cancel` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`sid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `bs_squares_coupons`
--

CREATE TABLE IF NOT EXISTS `bs_squares_coupons` (
  `scid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sid` int(10) unsigned DEFAULT NULL COMMENT 'NULL for all',
  `code` varchar(64) NOT NULL,
  `date_start` datetime DEFAULT NULL,
  `date_end` datetime DEFAULT NULL,
  `discount_for_booking` int(10) unsigned NOT NULL,
  `discount_for_products` int(10) unsigned NOT NULL,
  `discount_in_percent` tinyint(1) NOT NULL,
  PRIMARY KEY (`scid`),
  KEY `sid` (`sid`),
  KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `bs_squares_meta`
--

CREATE TABLE IF NOT EXISTS `bs_squares_meta` (
  `smid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sid` int(10) unsigned NOT NULL,
  `key` varchar(64) NOT NULL,
  `value` text NOT NULL,
  `locale` varchar(8) DEFAULT NULL,
  PRIMARY KEY (`smid`),
  KEY `sid` (`sid`),
  KEY `key` (`key`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `bs_squares_pricing`
--

CREATE TABLE IF NOT EXISTS `bs_squares_pricing` (
  `spid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sid` int(10) unsigned DEFAULT NULL COMMENT 'NULL for all',
  `priority` int(10) unsigned NOT NULL,
  `date_start` date NOT NULL,
  `date_end` date NOT NULL,
  `day_start` tinyint(3) unsigned DEFAULT NULL COMMENT 'Day of the week',
  `day_end` tinyint(3) unsigned DEFAULT NULL,
  `time_start` time DEFAULT NULL,
  `time_end` time DEFAULT NULL,
  `price` int(10) unsigned DEFAULT NULL,
  `rate` int(10) unsigned DEFAULT NULL,
  `gross` tinyint(1) DEFAULT NULL,
  `per_time_block` int(10) unsigned DEFAULT NULL,
  `per_quantity` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`spid`),
  KEY `sid` (`sid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `bs_squares_products`
--

CREATE TABLE IF NOT EXISTS `bs_squares_products` (
  `spid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sid` int(10) unsigned DEFAULT NULL COMMENT 'NULL for all',
  `priority` int(10) unsigned NOT NULL,
  `date_start` date DEFAULT NULL,
  `date_end` date DEFAULT NULL,
  `name` varchar(128) NOT NULL,
  `description` text,
  `options` varchar(512) NOT NULL,
  `price` int(10) unsigned NOT NULL,
  `rate` int(10) unsigned NOT NULL,
  `gross` tinyint(1) NOT NULL,
  `locale` varchar(8) DEFAULT NULL,
  PRIMARY KEY (`spid`),
  KEY `sid` (`sid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `bs_tournaments`
--

CREATE TABLE IF NOT EXISTS `bs_tournaments` (
  `tid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `status` varchar(64) NOT NULL DEFAULT 'draft' COMMENT 'draft|registration-open|registration-closed|group-phase|knockout-phase|completed|cancelled',
  `date_start` date NOT NULL,
  `date_end` date NOT NULL,
  `registration_deadline` datetime DEFAULT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`tid`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `bs_tournaments_meta`
--

CREATE TABLE IF NOT EXISTS `bs_tournaments_meta` (
  `tmid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tid` int(10) unsigned NOT NULL,
  `key` varchar(64) NOT NULL,
  `value` text NOT NULL,
  `locale` varchar(8) DEFAULT NULL,
  PRIMARY KEY (`tmid`),
  KEY `tid` (`tid`),
  KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `bs_tournament_categories`
--

CREATE TABLE IF NOT EXISTS `bs_tournament_categories` (
  `tcid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tid` int(10) unsigned NOT NULL,
  `gender` varchar(16) NOT NULL COMMENT 'male|female',
  `status` varchar(64) NOT NULL DEFAULT 'registration-open' COMMENT 'registration-open|registration-closed|group-phase|knockout-phase|completed',
  `group_size` tinyint(3) unsigned NOT NULL DEFAULT 4,
  `registration_deadline` datetime DEFAULT NULL COMMENT 'overrides tournament-level deadline if set',
  PRIMARY KEY (`tcid`),
  UNIQUE KEY `tid_gender` (`tid`,`gender`),
  KEY `tid` (`tid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `bs_tournament_categories_meta`
--

CREATE TABLE IF NOT EXISTS `bs_tournament_categories_meta` (
  `tcmid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tcid` int(10) unsigned NOT NULL,
  `key` varchar(64) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`tcmid`),
  KEY `tcid` (`tcid`),
  KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `bs_tournament_participants`
--

CREATE TABLE IF NOT EXISTS `bs_tournament_participants` (
  `tpid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tcid` int(10) unsigned NOT NULL,
  `uid` int(10) unsigned NOT NULL,
  `status` varchar(64) NOT NULL DEFAULT 'registered' COMMENT 'registered|withdrawn|disqualified',
  `registered_via` varchar(16) NOT NULL DEFAULT 'self' COMMENT 'self|admin',
  `created` datetime NOT NULL,
  PRIMARY KEY (`tpid`),
  UNIQUE KEY `tcid_uid` (`tcid`,`uid`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `bs_tournament_participants_meta`
--

CREATE TABLE IF NOT EXISTS `bs_tournament_participants_meta` (
  `tpmid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tpid` int(10) unsigned NOT NULL,
  `key` varchar(64) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`tpmid`),
  KEY `tpid` (`tpid`),
  KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `bs_tournament_groups`
--

CREATE TABLE IF NOT EXISTS `bs_tournament_groups` (
  `tgid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tcid` int(10) unsigned NOT NULL,
  `label` varchar(32) NOT NULL COMMENT 'e.g. Group A',
  `created` datetime NOT NULL,
  PRIMARY KEY (`tgid`),
  KEY `tcid` (`tcid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `bs_tournament_group_participants`
--

CREATE TABLE IF NOT EXISTS `bs_tournament_group_participants` (
  `tgpid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tgid` int(10) unsigned NOT NULL,
  `tpid` int(10) unsigned NOT NULL,
  `locked` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'true if manually pinned by admin, draw will not move it',
  `created` datetime NOT NULL,
  PRIMARY KEY (`tgpid`),
  UNIQUE KEY `tpid` (`tpid`),
  KEY `tgid` (`tgid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `bs_tournament_matches`
--

CREATE TABLE IF NOT EXISTS `bs_tournament_matches` (
  `tmaid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tcid` int(10) unsigned NOT NULL,
  `phase` varchar(16) NOT NULL COMMENT 'group|knockout',
  `tgid` int(10) unsigned DEFAULT NULL COMMENT 'set for phase=group only',
  `round` varchar(16) DEFAULT NULL COMMENT 'qf|sf|final, set for phase=knockout only',
  `bracket_slot` tinyint(3) unsigned DEFAULT NULL COMMENT 'position within round, used for seeding/feed wiring',
  `player_a_tpid` int(10) unsigned DEFAULT NULL,
  `player_b_tpid` int(10) unsigned DEFAULT NULL COMMENT 'nullable: bye, or knockout slot not yet fed',
  `winner_tpid` int(10) unsigned DEFAULT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'scheduled' COMMENT 'scheduled|completed|walkover|cancelled',
  `feeds_into_tmaid` int(10) unsigned DEFAULT NULL COMMENT 'self-FK: knockout match the winner advances into',
  `feeds_into_slot` varchar(1) DEFAULT NULL COMMENT 'A or B',
  `created` datetime NOT NULL,
  PRIMARY KEY (`tmaid`),
  KEY `tcid` (`tcid`),
  KEY `tgid` (`tgid`),
  KEY `phase` (`phase`),
  KEY `feeds_into_tmaid` (`feeds_into_tmaid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `bs_tournament_matches_meta`
--

CREATE TABLE IF NOT EXISTS `bs_tournament_matches_meta` (
  `tmamid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tmaid` int(10) unsigned NOT NULL,
  `key` varchar(64) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`tmamid`),
  KEY `tmaid` (`tmaid`),
  KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `bs_tournament_match_sets`
--

CREATE TABLE IF NOT EXISTS `bs_tournament_match_sets` (
  `tmsid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tmaid` int(10) unsigned NOT NULL,
  `set_number` tinyint(3) unsigned NOT NULL,
  `games_a` tinyint(3) unsigned NOT NULL,
  `games_b` tinyint(3) unsigned NOT NULL,
  `is_match_tiebreak` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`tmsid`),
  UNIQUE KEY `tmaid_set_number` (`tmaid`,`set_number`),
  KEY `tmaid` (`tmaid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `bs_users`
--

CREATE TABLE IF NOT EXISTS `bs_users` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `alias` varchar(128) NOT NULL,
  `status` varchar(64) NOT NULL DEFAULT 'placeholder' COMMENT 'placeholder|deleted|blocked|disabled|enabled|assist|admin',
  `email` varchar(128) DEFAULT NULL,
  `pw` varchar(256) DEFAULT NULL,
  `login_attempts` tinyint(3) unsigned DEFAULT NULL,
  `login_detent` datetime DEFAULT NULL,
  `last_activity` datetime DEFAULT NULL,
  `last_ip` varchar(64) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`uid`),
  KEY `alias` (`alias`),
  KEY `email` (`email`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `bs_users_meta`
--

CREATE TABLE IF NOT EXISTS `bs_users_meta` (
  `umid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `key` varchar(64) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`umid`),
  KEY `key` (`key`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `bs_bookings`
--
ALTER TABLE `bs_bookings`
  ADD CONSTRAINT `bs_bookings_ibfk_3` FOREIGN KEY (`sid`) REFERENCES `bs_squares` (`sid`),
  ADD CONSTRAINT `bs_bookings_ibfk_4` FOREIGN KEY (`uid`) REFERENCES `bs_users` (`uid`);

--
-- Constraints der Tabelle `bs_bookings_bills`
--
ALTER TABLE `bs_bookings_bills`
  ADD CONSTRAINT `bs_bookings_bills_ibfk_1` FOREIGN KEY (`bid`) REFERENCES `bs_bookings` (`bid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `bs_bookings_meta`
--
ALTER TABLE `bs_bookings_meta`
  ADD CONSTRAINT `bs_bookings_meta_ibfk_1` FOREIGN KEY (`bid`) REFERENCES `bs_bookings` (`bid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `bs_events`
--
ALTER TABLE `bs_events`
  ADD CONSTRAINT `bs_events_ibfk_1` FOREIGN KEY (`sid`) REFERENCES `bs_squares` (`sid`);

--
-- Constraints der Tabelle `bs_events_meta`
--
ALTER TABLE `bs_events_meta`
  ADD CONSTRAINT `bs_events_meta_ibfk_1` FOREIGN KEY (`eid`) REFERENCES `bs_events` (`eid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `bs_reservations`
--
ALTER TABLE `bs_reservations`
  ADD CONSTRAINT `bs_reservations_ibfk_1` FOREIGN KEY (`bid`) REFERENCES `bs_bookings` (`bid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `bs_reservations_meta`
--
ALTER TABLE `bs_reservations_meta`
  ADD CONSTRAINT `bs_reservations_meta_ibfk_1` FOREIGN KEY (`rid`) REFERENCES `bs_reservations` (`rid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `bs_squares_coupons`
--
ALTER TABLE `bs_squares_coupons`
  ADD CONSTRAINT `bs_squares_coupons_ibfk_1` FOREIGN KEY (`sid`) REFERENCES `bs_squares` (`sid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `bs_squares_meta`
--
ALTER TABLE `bs_squares_meta`
  ADD CONSTRAINT `bs_squares_meta_ibfk_1` FOREIGN KEY (`sid`) REFERENCES `bs_squares` (`sid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `bs_squares_pricing`
--
ALTER TABLE `bs_squares_pricing`
  ADD CONSTRAINT `bs_squares_pricing_ibfk_1` FOREIGN KEY (`sid`) REFERENCES `bs_squares` (`sid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `bs_squares_products`
--
ALTER TABLE `bs_squares_products`
  ADD CONSTRAINT `bs_squares_products_ibfk_1` FOREIGN KEY (`sid`) REFERENCES `bs_squares` (`sid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `bs_users_meta`
--
ALTER TABLE `bs_users_meta`
  ADD CONSTRAINT `bs_users_meta_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `bs_users` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `bs_tournaments_meta`
--
ALTER TABLE `bs_tournaments_meta`
  ADD CONSTRAINT `bs_tournaments_meta_ibfk_1` FOREIGN KEY (`tid`) REFERENCES `bs_tournaments` (`tid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `bs_tournament_categories`
--
ALTER TABLE `bs_tournament_categories`
  ADD CONSTRAINT `bs_tournament_categories_ibfk_1` FOREIGN KEY (`tid`) REFERENCES `bs_tournaments` (`tid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `bs_tournament_categories_meta`
--
ALTER TABLE `bs_tournament_categories_meta`
  ADD CONSTRAINT `bs_tournament_categories_meta_ibfk_1` FOREIGN KEY (`tcid`) REFERENCES `bs_tournament_categories` (`tcid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `bs_tournament_participants`
--
ALTER TABLE `bs_tournament_participants`
  ADD CONSTRAINT `bs_tournament_participants_ibfk_1` FOREIGN KEY (`tcid`) REFERENCES `bs_tournament_categories` (`tcid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `bs_tournament_participants_ibfk_2` FOREIGN KEY (`uid`) REFERENCES `bs_users` (`uid`);

--
-- Constraints der Tabelle `bs_tournament_participants_meta`
--
ALTER TABLE `bs_tournament_participants_meta`
  ADD CONSTRAINT `bs_tournament_participants_meta_ibfk_1` FOREIGN KEY (`tpid`) REFERENCES `bs_tournament_participants` (`tpid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `bs_tournament_groups`
--
ALTER TABLE `bs_tournament_groups`
  ADD CONSTRAINT `bs_tournament_groups_ibfk_1` FOREIGN KEY (`tcid`) REFERENCES `bs_tournament_categories` (`tcid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `bs_tournament_group_participants`
--
ALTER TABLE `bs_tournament_group_participants`
  ADD CONSTRAINT `bs_tournament_group_participants_ibfk_1` FOREIGN KEY (`tgid`) REFERENCES `bs_tournament_groups` (`tgid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `bs_tournament_group_participants_ibfk_2` FOREIGN KEY (`tpid`) REFERENCES `bs_tournament_participants` (`tpid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `bs_tournament_matches`
--
ALTER TABLE `bs_tournament_matches`
  ADD CONSTRAINT `bs_tournament_matches_ibfk_1` FOREIGN KEY (`tcid`) REFERENCES `bs_tournament_categories` (`tcid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `bs_tournament_matches_ibfk_2` FOREIGN KEY (`tgid`) REFERENCES `bs_tournament_groups` (`tgid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `bs_tournament_matches_ibfk_3` FOREIGN KEY (`player_a_tpid`) REFERENCES `bs_tournament_participants` (`tpid`),
  ADD CONSTRAINT `bs_tournament_matches_ibfk_4` FOREIGN KEY (`player_b_tpid`) REFERENCES `bs_tournament_participants` (`tpid`),
  ADD CONSTRAINT `bs_tournament_matches_ibfk_5` FOREIGN KEY (`winner_tpid`) REFERENCES `bs_tournament_participants` (`tpid`),
  ADD CONSTRAINT `bs_tournament_matches_ibfk_6` FOREIGN KEY (`feeds_into_tmaid`) REFERENCES `bs_tournament_matches` (`tmaid`) ON DELETE SET NULL;

--
-- Constraints der Tabelle `bs_tournament_matches_meta`
--
ALTER TABLE `bs_tournament_matches_meta`
  ADD CONSTRAINT `bs_tournament_matches_meta_ibfk_1` FOREIGN KEY (`tmaid`) REFERENCES `bs_tournament_matches` (`tmaid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `bs_tournament_match_sets`
--
ALTER TABLE `bs_tournament_match_sets`
  ADD CONSTRAINT `bs_tournament_match_sets_ibfk_1` FOREIGN KEY (`tmaid`) REFERENCES `bs_tournament_matches` (`tmaid`) ON DELETE CASCADE ON UPDATE CASCADE;
