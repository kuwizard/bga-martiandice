<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * MartianDice implementation : © Pavel Kulagin kuzwiz@mail.ru
 * 
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * material.inc.php
 *
 * MartianDice game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *   
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */

$this->dicetypes = array(
  0 => array('set_aside_lexeme' => clienttranslate('${player_name} sets aside ${dice_amount} Death Ray(s)'),
      'tooltip_lexeme' => clienttranslate('Choose all Death Rays'),
      'jsclass' => 'deathray'),
  1 => array('set_aside_lexeme' => clienttranslate('${player_name} sets aside ${dice_amount} Cow(s)'),
      'tooltip_lexeme' => clienttranslate('Choose all Cows'),
      'jsclass' => 'cow'),
  2 => array('set_aside_lexeme' => clienttranslate('${player_name} sets aside ${dice_amount} Tank(s)'),
      'tooltip_lexeme' => clienttranslate('Choose all Tanks'),
      'jsclass' => 'tank'),
  3 => array('set_aside_lexeme' => clienttranslate('${player_name} sets aside ${dice_amount} Chicken(s)'),
      'tooltip_lexeme' => clienttranslate('Choose all Chickens'),
      'jsclass' => 'chicken'),
  4 => array('set_aside_lexeme' => clienttranslate('${player_name} sets aside ${dice_amount} Human(s)'),
      'tooltip_lexeme' => clienttranslate('Choose all Humans'),
      'jsclass' => 'human'),
);