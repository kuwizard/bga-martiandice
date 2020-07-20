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
 * martiandicekw.game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 *
 */


require_once(APP_GAMEMODULE_PATH . 'module/table/table.game.php');

define('DEATH_RAY', 0);
define('COW', 1);
define('TANK', 2);
define('CHICKEN', 3);
define('HUMAN', 4);

class MartianDiceKW extends Table
{
    const TURN_START_DICE_AMOUNT = 13;

    function __construct()
    {
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();

        self::initGameStateLabels(array(
            //    "my_first_global_variable" => 10,
            //    "my_second_global_variable" => 11,
            //      ...
            //    "my_first_game_variant" => 100,
            //    "my_second_game_variant" => 101,
            //      ...
        ));
    }

    protected function getGameName()
    {
        // Used for translations and stuff. Please do not modify.
        return "martiandicekw";
    }

    protected function setupNewGame($players, $options = array())
    {
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach ($players as $player_id => $player) {
            $color = array_shift($default_colors);
            $values[] = "('" . $player_id . "','$color','" . $player['player_canal'] . "','" . addslashes($player['player_name']) . "','" . addslashes($player['player_avatar']) . "')";
        }
        $sql .= implode($values, ',');
        self::DbQuery($sql);
        self::renewTable('set_aside');
        self::reattributeColorsBasedOnPreferences($players, $gameinfos['player_colors']);
        self::reloadPlayersBasicInfos();
        /************ Start the game initialization *****/

        // Init global values with their initial values
        //self::setGameStateInitialValue( 'my_first_global_variable', 0 );

        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        // TODO: setup the initial game situation here


        /************ End of the game initialization *****/
    }

    function renewTable($table_name)
    {
        self::DbQuery("DELETE FROM $table_name");
        $this->fillTable($table_name);
    }

    function throwDice($count = null)
    {
        if ($count == null)
        {
            $count = self::TURN_START_DICE_AMOUNT;
        }
        self::fillTable('current_round', $count);
    }

    function fillTable($tableName, $count = null)
    {
        $sql = "INSERT INTO $tableName (dice_type,amount) VALUES ";
        $sql_values = array();

        $dice_thrown = array(
            DEATH_RAY => 0,
            COW => 0,
            TANK => 0,
            CHICKEN => 0,
            HUMAN => 0
        );

        if ($count != null) {
            for ($i = 0; $i < $count; $i++) {
                $rand_value = bga_rand(0, 5);
                if ($rand_value == 5) {
                    $rand_value = 0; // There are 2 death rays on any die
                }
                $dice_thrown[$rand_value] += 1;
            }
        }

        foreach ($dice_thrown as $dice_type => $amount) {
            $sql_values[] = "($dice_type,$amount)";
        }

        $sql .= implode($sql_values, ',');
        $sql .= "ON DUPLICATE KEY UPDATE dice_type = VALUES(dice_type), amount = VALUES(amount)";
        self::DbQuery($sql);
    }

    function getCurrentRoundDice()
    {
        $current_round_dice = self::getObjectListFromDB("SELECT dice_type as type, amount, choosable FROM current_round");
        $current_round_dice = self::addDiceTypes($current_round_dice);
        return self::integerize($current_round_dice, array('amount'));
    }

    function getSetAsideDice()
    {
        $set_aside_dice = self::getObjectListFromDB("SELECT dice_type as type, amount FROM set_aside");
        $set_aside_dice = self::addDiceTypes($set_aside_dice);
        return self::integerize($set_aside_dice, array('amount'));
    }

    function addDiceTypes($array)
    {
        return array_map(function ($die) {
            return array_merge($die, $this->dicetypes[$die['type']]);
        }, $array);
    }

    function getAvailableDiceTypes()
    {
        $available_types = self::getObjectListFromDB("SELECT dice_type as type FROM current_round WHERE choosable = true", true);
        return self::integerize($available_types);
    }

    function getSetAsideSum()
    {
        return (int)self::getUniqueValueFromDB("SELECT SUM(amount) FROM set_aside", true);
    }

    function getScores()
    {
        $scores = self::getObjectListFromDB("SELECT player_score score FROM player", true);
        return self::integerize($scores);
    }

    function integerize($object, $fields = null)
    {
        foreach ($object as $j => &$value) {
            if ($fields == null) {
                $value = (int)$value;
            } else {
                foreach ($fields as $k => $field) {
                    $value[$field] = (int)$value[$field];
                }
            }
        }
        return $object;
    }

    function markAsSetAside($dice_type)
    {
        $sql = "SELECT amount FROM current_round WHERE dice_type = " . $dice_type;
        $amount = self::getUniqueValueFromDB($sql);
        $sql = "UPDATE set_aside SET amount = amount + " . $amount . " WHERE dice_type = " . $dice_type;
        self::DbQuery($sql);
        $sql = "UPDATE current_round SET amount = amount - " . $amount;
        if ($dice_type != DEATH_RAY) {
            $sql .= ", choosable = false";
        }
        $sql .= " WHERE dice_type = " . $dice_type;
        self::DbQuery($sql);

        self::notifyAllPlayers("diceSetAside", clienttranslate('${player_name} sets aside '.$amount.' ${dice_type_name}'), array(
            'player_name' => self::getActivePlayerName(),
            'dice_type_name' => $this->dicetypes[$dice_type]['name'],
            'dice_type_jsclass' => $this->dicetypes[$dice_type]['jsclass'],
            'dice_amount' => $amount,
        ));
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array();

        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score FROM player ";
        $result['players'] = self::getCollectionFromDb($sql);
        $result['set_aside_dice'] = self::getSetAsideDice();
        $result['current_round_dice'] = self::getCurrentRoundDice();
        $result['dice_types'] = $this->dicetypes;

        return $result;
    }

    function getGameProgression()
    {
        $scores = self::getScores();
        $max = max($scores);
        return $max * 4; // We play until 25 points
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */


//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 
    function diceSetAside($dice_type)
    {
        self::checkAction('diceSetAside');

        // Checking if this is a possible move
        $available_types = self::getAvailableDiceTypes();
        $dice_type = array_search($dice_type, array_column($this->dicetypes, 'jsclass'));

        if (in_array($dice_type, $available_types)) {
            self::markAsSetAside($dice_type);

            if (self::getSetAsideSum() == self::TURN_START_DICE_AMOUNT) {
                self::notifyAllPlayers("newScores", clienttranslate('${player_name} sets aside all ' . self::TURN_START_DICE_AMOUNT . ' dice, their turn is over'), array(
                    'player_name' => self::getActivePlayerName(),
                ));

                self::endTurn();
            } else {
                self::giveExtraTime(self::getActivePlayerId());
                $this->gamestate->nextState('continueOrEnd');
            }
        } else
            throw new feException("Impossible move");
    }

    function rerollDice()
    {
        self::checkAction('rerollDice');
        $available_types = self::getAvailableDiceTypes();

        if (count($available_types) > 0) {
            $set_aside_count = self::getSetAsideSum();
            self::throwDice(self::TURN_START_DICE_AMOUNT - $set_aside_count);

            // If there's nothing to set aside - end turn
            $current_round_dice = self::getCurrentRoundDice();

            $available_dice = array_filter($current_round_dice, function ($i) {
                return $i['amount'] > 0 && $i['choosable'] == '1';
            });

            self::notifyAllPlayers("diceThrown", '', array(
                'dice' => $current_round_dice,
                'is_reroll' => true,
            ));

            if ($current_round_dice[TANK]['amount'] > 0)
            {
                self::markAsSetAside(TANK);
            }

            if (empty($available_dice)) {
                self::notifyAllPlayers("newScores", clienttranslate('${player_name} cannot set aside any dice after reroll, their turn is over'), array(
                    'player_name' => self::getActivePlayerName(),
                ));
                self::endTurn();
            } else {
                self::giveExtraTime(self::getActivePlayerId());
                $this->gamestate->nextState('diceChoosing');
            }
        } else
            throw new feException("Impossible move");
    }


    function endTurn()
    {
        self::checkAction('endTurn');

        $set_aside_dice = array_map(function ($element) {
            return $element['amount'];
        }, self::getSetAsideDice());

        if ($set_aside_dice[DEATH_RAY] < $set_aside_dice[TANK]) {
            self::notifyAllPlayers("diceSetAside", clienttranslate('${player_name} fails to deal with the Earthling military and comes home empty-tentacled'), array(
                'player_name' => self::getActivePlayerName(),
            ));
        } else {
            $player_id = self::getCurrentPlayerId();
            $delta = $set_aside_dice[COW] + $set_aside_dice[CHICKEN] + $set_aside_dice[HUMAN];
            $all_three_types = $set_aside_dice[COW] > 0 && $set_aside_dice[CHICKEN] > 0 && $set_aside_dice[HUMAN] > 0;
            $ending = '';
            if ($delta > 1)
            {
                $ending = 's';
            }
            $notif_message = clienttranslate('${player_name} successfully abducts ' . $delta . ' Earthling' . $ending);
            if ($all_three_types) {
                $delta += 3;
                $notif_message .= clienttranslate(" and receives 3 bonus points for having all three Earthling types");
            }

            $old_score = (int)self::getUniqueValueFromDB("SELECT player_score FROM player WHERE player_id = " . self::getActivePlayerId(), true);
            $new_score = $old_score + $delta;
            self::DbQuery("UPDATE player SET player_score = $new_score WHERE player_id = $player_id");

            self::notifyAllPlayers("newScores", $notif_message, array(
                'player_name' => self::getActivePlayerName(),
                'player_id' => self::getActivePlayerId(),
                'new_score' => $new_score,
            ));
        }
        self::renewTable('current_round');
        self::renewTable('set_aside');
        if (max(self::getScores()) >= 25) {
            $this->gamestate->nextState('gameEnd');
        }
        $this->gamestate->nextState('nextPlayer');
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    /*
    
    Example for game state "MyGameState":
    
    function argMyGameState()
    {
        // Get some values from the current game situation in database...
    
        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }    
    */
    function argPlayerTurn()
    {
        $set_aside = self::getAvailableDiceTypes();
        $set_aside_types = array_intersect_key($this->dicetypes, array_flip($set_aside));

        return array(
            'setAsideTypes' => $set_aside_types,
        );
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stThrowAllDice()
    {
        self::activeNextPlayer();
        self::throwDice();
        $dice_thrown = self::getCurrentRoundDice();

        self::notifyAllPlayers("diceThrown", '', array(
            'dice' => $dice_thrown,
            'is_reroll' => false,
        ));

        if ($dice_thrown[TANK]['amount'] > 0)
        {
            self::markAsSetAside(TANK);
        }
        $this->gamestate->nextState('diceChoosing');
    }
//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
        
        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message. 
    */

    function zombieTurn($state, $active_player)
    {
        $statename = $state['name'];

        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState("zombiePass");
                    break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive($active_player, '');

            return;
        }

        throw new feException("Zombie mode not supported at this game state: " . $statename);
    }

///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */

    function upgradeTableDb($from_version)
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345

        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        // Please add your future database scheme changes here
//
//


    }
}
