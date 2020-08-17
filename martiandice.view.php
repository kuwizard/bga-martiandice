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
 * martiandice.view.php
 *
 * This is your "view" file.
 *
 * The method "build_page" below is called each time the game interface is displayed to a player, ie:
 * _ when the game starts
 * _ when a player refreshes the game page (F5)
 *
 * "build_page" method allows you to dynamically modify the HTML generated for the game interface. In
 * particular, you can set here the values of variables elements defined in martiandice_martiandice.tpl (elements
 * like {MY_VARIABLE_ELEMENT}), and insert HTML block elements (also defined in your HTML template file)
 *
 * Note: if the HTML of your game interface is always the same, you don't have to place anything here.
 *
 */
  
  require_once( APP_BASE_PATH."view/common/game.view.php" );
  
  class view_martiandice_martiandice extends game_view
  {
    function getGameName() {
        return "martiandice";
    }    
  	function build_page( $viewArgs )
  	{
        $this->page->begin_block( "martiandice_martiandice", "pa_square" );
        $this->page->begin_block( "martiandice_martiandice", "tank_square" );
        $this->page->begin_block( "martiandice_martiandice", "deathray_square" );
        $this->page->begin_block( "martiandice_martiandice", "e1_square" );
        $this->page->begin_block( "martiandice_martiandice", "e2_square" );
        $this->page->begin_block( "martiandice_martiandice", "e3_square" );
        $this->page->begin_block( "martiandice_martiandice", "boom" );

        for( $x=1; $x<=13; $x++ )
        {
            $this->page->insert_block("pa_square", array(
                'N' => $x,
            ));
            $this->page->insert_block("tank_square", array(
                'N' => $x,
            ));
            $this->page->insert_block("deathray_square", array(
                'N' => $x,
            ));
        }
        for( $x=1; $x<=6; $x++ )
        {
            $this->page->insert_block("boom", array(
                'N' => $x,
            ));
        }
        for( $x=1; $x<=4; $x++ ) {
            $this->page->insert_block("e1_square", array(
                'N' => $x,
            ));
            $this->page->insert_block("e2_square", array(
                'N' => $x,
            ));
            $this->page->insert_block("e3_square", array(
                'N' => $x,
            ));
        }
  	}
  }
  

