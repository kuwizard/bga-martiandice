<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * MartianDiceKW implementation : © Pavel Kulagin kuzwiz@mail.ru
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 * 
 * martiandicekw.action.php
 *
 * MartianDiceKW main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/martiandicekw/martiandicekw/myAction.html", ...)
 *
 */
  
  
  class action_martiandicekw extends APP_GameAction
  { 
    // Constructor: please do not modify
   	public function __default()
  	{
  	    if( self::isArg( 'notifwindow') )
  	    {
            $this->view = "common_notifwindow";
  	        $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
  	    }
  	    else
  	    {
            $this->view = "martiandicekw_martiandicekw";
            self::trace( "Complete reinitialization of board game" );
      }
  	} 
  	
    public function diceSetAside()
    {
        self::setAjaxMode();
        $js_dice_type = self::getArg( "dice_type", AT_alphanum, true );
        $dice_type = str_replace("dietype_", "", $js_dice_type);
        $this->game->diceSetAside($dice_type);
        self::ajaxResponse();
    }

    public function rerollDice()
    {
        self::setAjaxMode();
        $this->game->rerollDice();
        self::ajaxResponse();
    }

    public function endTurn()
    {
        self::setAjaxMode();
        $this->game->endTurn();
        self::ajaxResponse();
    }
  }
  

