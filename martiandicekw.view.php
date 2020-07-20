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
 * martiandicekw.view.php
 *
 * This is your "view" file.
 *
 * The method "build_page" below is called each time the game interface is displayed to a player, ie:
 * _ when the game starts
 * _ when a player refreshes the game page (F5)
 *
 * "build_page" method allows you to dynamically modify the HTML generated for the game interface. In
 * particular, you can set here the values of variables elements defined in martiandicekw_martiandicekw.tpl (elements
 * like {MY_VARIABLE_ELEMENT}), and insert HTML block elements (also defined in your HTML template file)
 *
 * Note: if the HTML of your game interface is always the same, you don't have to place anything here.
 *
 */
  
  require_once( APP_BASE_PATH."view/common/game.view.php" );
  
  class view_martiandicekw_martiandicekw extends game_view
  {
    function getGameName() {
        return "martiandicekw";
    }    
  	function build_page( $viewArgs )
  	{
        $this->page->begin_block( "martiandicekw_martiandicekw", "pa_square" );
        $this->page->begin_block( "martiandicekw_martiandicekw", "aside_square" );

        $hor_scale = 57;
        for( $x=1; $x<=13; $x++ )
        {
            $this->page->insert_block( "pa_square", array(
                'N' => $x,
                'LEFT' => ($x-1)*$hor_scale,
            ));
            $this->page->insert_block( "aside_square", array(
                'N' => $x,
                'LEFT' => ($x-1)*$hor_scale,
            ));     
        }
  	}
  }
  

