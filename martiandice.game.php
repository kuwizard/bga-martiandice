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
 * martiandice.game.php
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

class MartianDice extends Table
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
            "end_turn_notification_sent" => 10,
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
        return "martiandice";
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
        self::setGameStateInitialValue('end_turn_notification_sent', false);

        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        self::initStat('table', 'turns_number', 0);
        self::initStat('player', 'timesDeathRayChosen', 0);
        self::initStat('player', 'timesEarthlingsChosen', 0);
        self::initStat('player', 'timesEarthlingsAbducted', 0);
        self::initStat('player', 'timesTanksSucceeded', 0);
        self::initStat('player', 'amountOfBonusReceived', 0);
        self::initStat('player', 'timesScoredZeroPoints', 0);
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
            self::incStat(1, 'turns_number');
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
            $types_added = array_merge($die, $this->dicetypes[$die['type']]);
            $types_added['tooltip'] = clienttranslate('Choose all');
            return $types_added;
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

    function addScoreToPlayer($player_id, $delta)
    {
        $old_score = (int)self::getUniqueValueFromDB("SELECT player_score FROM player WHERE player_id = " . $player_id, true);
        $new_score = $old_score + $delta;
        self::DbQuery("UPDATE player SET player_score = $new_score WHERE player_id = $player_id");
        return $new_score;
    }

    function allPlayersPlayedThisRound() {
        $players_left_in_round = self::getObjectListFromDB("SELECT player_id FROM player WHERE player_played_this_round = false", true);
        return count($players_left_in_round) == 0;
    }

    function markPlayerAsPlayedThisRound($player_id)
    {
        self::DbQuery("UPDATE player SET player_played_this_round = true WHERE player_id = " . $player_id);
    }

    function resetRound()
    {
        self::DbQuery("UPDATE player SET player_played_this_round = false");
    }

    function getWinningPlayerCount()
    {
        $max_score = max(self::getScores());
        $winning_players = self::getObjectListFromDB("SELECT player_id FROM player WHERE player_score = " . $max_score, true);
        return count($winning_players);
    }

    function isPlayerWinning($player_id)
    {
        $max_score = max(self::getScores());
        $current_user_score = (int)self::getUniqueValueFromDB("SELECT player_score FROM player WHERE player_id = " . $player_id, true);
        return $max_score == $current_user_score;
    }

    function isPlayerZombie($player_id)
    {
        return self::getUniqueValueFromDB("SELECT player_zombie FROM player WHERE player_id = " . $player_id, true);
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

        if (in_array($dice_type, [CHICKEN, HUMAN, COW]))
        {
            self::incStat(1, 'timesEarthlingsChosen', self::getCurrentPlayerId());
        } elseif ($dice_type == DEATH_RAY) {
            self::incStat(1, 'timesDeathRayChosen', self::getCurrentPlayerId());
        }

        if ($amount == 1) {
            $dice_type_name = $this->dicetypes[$dice_type]['name'];
        } else {
            $dice_type_name = $this->dicetypes[$dice_type]['name_plural'];
        }
        self::notifyAllPlayers("diceSetAside", clienttranslate('${player_name} sets aside '.$amount.' ${dice_type_name}'), array(
            'player_name' => self::getActivePlayerName(),
            'dice_type_name' => $dice_type_name,
            'dice_type_jsclass' => self::jsclass($dice_type),
            'dice_amount' => $amount,
        ));
    }

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

    function jsclass($dice_type)
    {
        return $this->dicetypes[$dice_type]['jsclass'];
    }

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
            self::incStat(1, 'timesTanksSucceeded', self::getCurrentPlayerId());

            self::notifyAllPlayers("runWinLoseAnimation", clienttranslate('${player_name} fails to deal with the Earthling military and comes home empty-tentacled'), array(
                'player_name' => self::getActivePlayerName(),
                'player_id' => self::getActivePlayerId(),
                'winning_dice_type' => self::jsclass(TANK),
                'losing_dice_type' => self::jsclass(DEATH_RAY),
            ));
        } else {
            $player_id = self::getCurrentPlayerId();
            $delta = $set_aside_dice[COW] + $set_aside_dice[CHICKEN] + $set_aside_dice[HUMAN];
            $all_three_types = $set_aside_dice[COW] > 0 && $set_aside_dice[CHICKEN] > 0 && $set_aside_dice[HUMAN] > 0;
            if ($delta == 1)
            {
                $ending = clienttranslate('Earthling');
            } else {
                $ending = clienttranslate('Earthlings');
            }
            if ($delta == 0)
            {
                $notif_message = clienttranslate('${player_name} successfully fended off Earthling military but failed to capture a single Earthling. C\'mon, Commander, we need some samples!');
                self::incStat(1, 'timesScoredZeroPoints', self::getActivePlayerId());
            } else {
                $notif_message = clienttranslate('${player_name} successfully abducts ' . $delta . ' ' . $ending);
            }

            if ($all_three_types) {
                $delta += 3;
                $notif_message .= clienttranslate(" and receives 3 bonus points for having all three Earthling types");
                self::incStat(3, 'amountOfBonusReceived', self::getActivePlayerId());
            }

            $new_score = self::addScoreToPlayer($player_id, $delta);

            self::incStat(1, 'timesEarthlingsAbducted', self::getActivePlayerId());

            self::notifyAllPlayers("runWinLoseAnimation", $notif_message, array(
                'player_name' => self::getActivePlayerName(),
                'player_id' => self::getActivePlayerId(),
                'winning_dice_type' => self::jsclass(DEATH_RAY),
                'losing_dice_type' => self::jsclass(TANK),
            ));
            self::notifyAllPlayers("newScores", '', array(
                'player_name' => self::getActivePlayerName(),
                'player_id' => self::getActivePlayerId(),
                'dice_types_scored' => [self::jsclass(COW), self::jsclass(CHICKEN), self::jsclass(HUMAN)],
                'new_score' => $new_score,
            ));
        }
        self::renewTable('set_aside');
        self::renewTable('current_round');

        self::endGameIfNeeded();
    }

    function endGameIfNeeded()
    {
        self::markPlayerAsPlayedThisRound(self::getActivePlayerId());

        if (max(self::getScores()) >= 25) {
            if (self::allPlayersPlayedThisRound())
            {
                $this->gamestate->nextState('tieBreakingOrEnd');
            } else {
                if (!self::getGameStateValue('end_turn_notification_sent')) {
                    self::notifyAllPlayers("newScores", clienttranslate('${player_name} got more than 25 points, this is the last turn!'), array(
                        'player_name' => self::getActivePlayerName(),
                    ));
                    self::setGameStateValue('end_turn_notification_sent', true);
                }
            }
        }
        if (self::allPlayersPlayedThisRound())
        {
            self::resetRound();
        }
        $this->gamestate->nextState('nextPlayer');
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

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
        if (!self::isPlayerZombie(self::getActivePlayerId())) {
            self::throwDice();
            $dice_thrown = self::getCurrentRoundDice();

            self::notifyAllPlayers("diceThrown", '', array(
                'dice' => $dice_thrown,
                'is_reroll' => false,
            ));

            if ($dice_thrown[TANK]['amount'] > 0) {
                self::markAsSetAside(TANK);
            }
        }
        $this->gamestate->nextState('diceChoosing');
    }

    function stThrowTieBreaker()
    {
        $tie_break_dice_amount = 6;
        self::resetRound();
        if (self::getWinningPlayerCount() > 1) {
            while (!self::allPlayersPlayedThisRound()) {
                self::activeNextPlayer();
                $active_player = self::getActivePlayerId();
                if (self::isPlayerWinning($active_player)) {
                    self::throwDice($tie_break_dice_amount);
                    $dice_thrown = self::getCurrentRoundDice();

                    self::notifyAllPlayers("diceThrown", '', array(
                        'dice' => $dice_thrown,
                        'is_reroll' => false,
                    ));

                    $death_rays_amount = self::getCurrentRoundDice()[DEATH_RAY]['amount'];
                    $new_score = self::addScoreToPlayer($active_player, $death_rays_amount);
                    if ($death_rays_amount == 1)
                    {
                        $ending = clienttranslate('Death Ray');
                    } else {
                        $ending = clienttranslate('Death Rays');
                    }

                    self::notifyAllPlayers("newScoresTie", clienttranslate('${player_name} threw ' . $tie_break_dice_amount . ' dice to break a tie and got ' . $death_rays_amount . ' ' . $ending), array(
                        'player_name' => self::getActivePlayerName(),
                        'player_id' => $active_player,
                        'dice_types_scored' => [self::jsclass(DEATH_RAY)],
                        'new_score' => $new_score,
                        'score_from_play_area' => true,
                    ));
                }
                self::markPlayerAsPlayedThisRound($active_player);
            }
        }
        $this->gamestate->nextState('gameEnd');
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
                case 'diceChoosing':
                    $this->gamestate->nextState("continueOrEnd");
                    break;
                case 'continueOrEnd':
                    self::endGameIfNeeded();
                    break;
                default:
                    $this->gamestate->nextState("zombiePass");
                    break;
            }
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
