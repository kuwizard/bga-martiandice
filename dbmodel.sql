
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- MartianDice implementation : © Pavel Kulagin kuzwiz@mail.ru
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

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

ALTER TABLE `player` ADD `player_played_this_round` tinyint(2) UNSIGNED NOT NULL DEFAULT false;
