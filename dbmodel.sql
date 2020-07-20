
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- MartianDiceKW implementation : © Pavel Kulagin kuzwiz@mail.ru
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- dbmodel.sql

-- This is the file where you are describing the database schema of your game
-- Basically, you just have to export from PhpMyAdmin your table structure and copy/paste
-- this export here.
-- Note that the database itself and the standard tables ("global", "stats", "gamelog" and "player") are
-- already created and must not be created here

-- Note: The database schema is created from this file when the game starts. If you modify this file,
--       you have to restart a game to see your changes in database.

-- Example 1: create a standard "card" table to be used with the "Deck" tools (see example game "hearts"):

CREATE TABLE IF NOT EXISTS `current_round` (
  `dice_type` smallint(5) unsigned NOT NULL,
  `amount` smallint(5) unsigned NOT NULL,
  `choosable` tinyint(2) unsigned NOT NULL DEFAULT true,
  PRIMARY KEY (`dice_type`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `set_aside` (
  `dice_type` smallint(5) unsigned NOT NULL,
  `amount` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`dice_type`)
) ENGINE=InnoDB;

-- Example 2: add a custom field to the standard "player" table
-- ALTER TABLE `player` ADD `player_my_custom_field` INT UNSIGNED NOT NULL DEFAULT '0';

