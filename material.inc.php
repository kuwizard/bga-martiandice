<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * MartianDiceKW implementation : © Pavel Kulagin kuzwiz@mail.ru
 * 
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * material.inc.php
 *
 * MartianDiceKW game material description
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
      'jsclass' => self::_('deathray')),
  1 => array('name' => clienttranslate('cow'),
      'name_plural' => clienttranslate('cows'),
      'jsclass' => self::_('cow')),
  2 => array('name' => clienttranslate('tank'),
      'name_plural' => clienttranslate('tanks'),
      'jsclass' => self::_('tank')),
  3 => array('name' => clienttranslate('chicken'),
      'name_plural' => clienttranslate('chickens'),
      'jsclass' => self::_('chicken')),
  4 => array('name' => clienttranslate('human'),
      'name_plural' => clienttranslate('humans'),
      'jsclass' => self::_('human'))
);