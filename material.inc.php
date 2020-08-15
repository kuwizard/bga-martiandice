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
  0 => array('name' => clienttranslate('Death Ray'),
      'name_plural' => clienttranslate('Death Rays'),
      'jsclass' => 'deathray'),
  1 => array('name' => clienttranslate('Cow'),
      'name_plural' => clienttranslate('Cows'),
      'jsclass' => 'cow'),
  2 => array('name' => clienttranslate('Tank'),
      'name_plural' => clienttranslate('Tanks'),
      'jsclass' => 'tank'),
  3 => array('name' => clienttranslate('Chicken'),
      'name_plural' => clienttranslate('Chickens'),
      'jsclass' => 'chicken'),
  4 => array('name' => clienttranslate('Human'),
      'name_plural' => clienttranslate('Humans'),
      'jsclass' => 'human'),
);