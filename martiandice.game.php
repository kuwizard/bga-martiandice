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
        self::initStat('player', 'amountOfTanksRolled', 0);
        self::initStat('player', 'amountOfDeathRaysRolled', 0);
        self::initStat('player', 'amountOfEarthlingsRolled', 0);
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

        $dice_thrown = self::getCurrentRoundDice();
        // Adding to rolled stats
        foreach ($dice_thrown as $dice_type => $dice_info) {
            $amount = $dice_info['amount'];
            if ($amount > 0) {
                if ($dice_type == DEATH_RAY) {
                    self::incStat($amount, 'amountOfDeathRaysRolled', self::getActivePlayerId());
                } elseif ($dice_type == TANK) {
                    self::incStat($amount, 'amountOfTanksRolled', self::getActivePlayerId());
                } else {
                    self::incStat($amount, 'amountOfEarthlingsRolled', self::getActivePlayerId());
                }
            }
        }
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
        $sql .= " ON DUPLICATE KEY UPDATE dice_type = VALUES(dice_type), amount = VALUES(amount)";
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

    function getSetAsideDiceAmounts()
    {
        return array_map(function ($element) {
            return $element['amount'];
        }, self::getSetAsideDice());
    }

    function addDiceTypes($array)
    {
        return array_map(function ($die) {
            $types_added = array_merge($die, $this->dicetypes[$die['type']]);
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

    function userCanWinThisTurn()
    {
        $available_dice = (int)self::getUniqueValueFromDB("SELECT SUM(amount) FROM current_round", true);
        $tanks_set_aside = (int)self::getUniqueValueFromDB("SELECT amount FROM set_aside WHERE dice_type = ".TANK, true);
        $death_rays_set_aside = (int)self::getUniqueValueFromDB("SELECT amount FROM set_aside WHERE dice_type = ".DEATH_RAY, true);
        return $available_dice + $death_rays_set_aside >= $tanks_set_aside;
    }

    function isStupidToEndTurnNow()
    {
        return self::userCanWinThisTurn() && self::isMoreTanksThanDeathRays();
    }

    function isMoreTanksThanDeathRays()
    {
        $set_aside_dice = self::getSetAsideDiceAmounts();
        return $set_aside_dice[TANK] > $set_aside_dice[DEATH_RAY];
    }

    function getScores()
    {
        $scores = self::getObjectListFromDB("SELECT player_score score FROM player", true);
        return self::integerize($scores);
    }

    function addScoreToPlayer($player_id, $delta)
    {
        return self::addScore($player_id, $delta, false);
    }

    function addTieBreakerScoreToPlayer($player_id, $delta)
    {
        return self::addScore($player_id, $delta, true);
    }

    function addScore($player_id, $delta, $tie_breaker)
    {
        $old_score = (int)self::getUniqueValueFromDB("SELECT player_score FROM player WHERE player_id = " . $player_id, true);
        $new_score = $old_score + $delta;
        $player_table = 'player_score';
        if ($tie_breaker)
        {
            $player_table = 'player_score_aux';
        }
        self::DbQuery("UPDATE player SET ". $player_table ." = $new_score WHERE player_id = $player_id");
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
        $sql = "UPDATE player SET player_played_this_round = false";
        self::DbQuery($sql);
    }

    function hasPlayerTie($player_id)
    {
        $current_user_score = (int)self::getUniqueValueFromDB("SELECT player_score FROM player WHERE player_id = " . $player_id, true);
        $players_with_this_score = (int)self::getUniqueValueFromDB("SELECT COUNT(*) FROM player WHERE player_score = " . $current_user_score, true);
        return $players_with_this_score > 1;
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

        $is_stupid_to_end_turn = self::isStupidToEndTurnNow();
        self::notifyAllPlayers("diceSetAside", $this->dicetypes[$dice_type]['set_aside_lexeme'], array(
            'player_name' => self::getActivePlayerName(),
            'dice_type_jsclass' => self::jsclass($dice_type),
            'dice_amount' => $amount,
            'is_stupid_to_end_turn' => $is_stupid_to_end_turn,
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
        $result['turn_order'] = $this->getTableOrder();

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

    function getTableOrder() {
        $table  = $this->getNextPlayerTable();
        $result = array();

        $nb_players = count($table) - 1;
        $previous = $table[0];

        for ($i = 1 ; $i <= $nb_players ; $i++ ) {
            $result[$previous] = $i;
            $previous = $table[$previous];
        }

        return $result;
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
                self::notifyAllPlayers("newScores", clienttranslate('${player_name} sets aside all ${dice_amount} dice, their turn is over'), array(
                    'player_name' => self::getActivePlayerName(),
                    'dice_amount' => self::TURN_START_DICE_AMOUNT,
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
            $current_round_dice = self::getCurrentRoundDice();

            self::notifyAllPlayers("diceThrown", '', array(
                'dice' => $current_round_dice,
                'is_reroll' => true,
            ));

            if ($current_round_dice[TANK]['amount'] > 0)
            {
                self::markAsSetAside(TANK);
            }
            // There might be some tanks just set aside therefore $current_round_dice should be recalculated
            $current_round_dice = self::getCurrentRoundDice();

            $available_dice = array_filter($current_round_dice, function ($i) {
                return $i['amount'] > 0 && $i['choosable'] == '1';
            });
            // If there's nothing to set aside - end turn
            if (empty($available_dice)) {
                self::notifyAllPlayers("newScores", clienttranslate('${player_name} cannot set aside any dice after reroll, their turn is over'), array(
                    'player_name' => self::getActivePlayerName(),
                ));
                self::endTurn();
            } elseif (!self::userCanWinThisTurn()) {
                self::giveExtraTime(self::getActivePlayerId());
                $this->gamestate->nextState('endOrEnd');
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

        if (self::isMoreTanksThanDeathRays()) {
            self::incStat(1, 'timesTanksSucceeded', self::getCurrentPlayerId());

            $notif_message = clienttranslate('${player_name} fails to deal with the Earthling military and comes home empty-tentacled');
            self::runWinLoseAnimation($notif_message, TANK, DEATH_RAY, null);
        } else {
            $player_id = self::getCurrentPlayerId();
            $set_aside_dice = self::getSetAsideDiceAmounts();
            $captured_amount = $set_aside_dice[COW] + $set_aside_dice[CHICKEN] + $set_aside_dice[HUMAN];
            $all_three_types = $set_aside_dice[COW] > 0 && $set_aside_dice[CHICKEN] > 0 && $set_aside_dice[HUMAN] > 0;

            if ($captured_amount == 0)
            {
                $notif_message = clienttranslate('${player_name} successfully fended off Earthling military but failed to capture a single Earthling. C\'mon, Commander, we need some samples!');
                self::incStat(1, 'timesScoredZeroPoints', self::getActivePlayerId());
            } else {
                $notif_message = clienttranslate('${player_name} successfully abducts ${delta} Earthling(s)');
            }

            $points_scored = $captured_amount;
            if ($all_three_types) {
                $points_scored += 3;
                $notif_message = clienttranslate('${player_name} successfully abducts ${delta} Earthlings and receives 3 bonus points for having all three Earthling types');
                self::incStat(3, 'amountOfBonusReceived', self::getActivePlayerId());
            }

            $new_score = self::addScoreToPlayer($player_id, $points_scored);

            self::incStat(1, 'timesEarthlingsAbducted', self::getActivePlayerId());

            self::runWinLoseAnimation($notif_message, DEATH_RAY, TANK, $captured_amount);
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

    function runWinLoseAnimation($message, $winning_type, $losing_type, $points_scored)
    {
        self::notifyAllPlayers("runWinLoseAnimation", $message, array(
            'player_name' => self::getActivePlayerName(),
            'player_id' => self::getActivePlayerId(),
            'winning_dice_type' => self::jsclass($winning_type),
            'losing_dice_type' => self::jsclass($losing_type),
            'delta' => $points_scored,
        ));
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
                    self::notifyAllPlayers("newScores", clienttranslate('${player_name} reached 25 points, this turn is the last one!'), array(
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
        if (self::userCanWinThisTurn()) {
            $this->gamestate->nextState('diceChoosing');
        } else {
            $this->gamestate->nextState('endOrEnd');
        }
    }

    function stThrowTieBreaker()
    {
        $tie_break_dice_amount = 6;
        self::resetRound();
        while (!self::allPlayersPlayedThisRound()) {
            self::activeNextPlayer();
            $active_player = self::getActivePlayerId();
            if (self::hasPlayerTie($active_player)) {
                self::throwDice($tie_break_dice_amount);
                $dice_thrown = self::getCurrentRoundDice();

                self::notifyAllPlayers("diceThrown", '', array(
                    'dice' => $dice_thrown,
                    'is_reroll' => false,
                ));

                $death_rays_amount = self::getCurrentRoundDice()[DEATH_RAY]['amount'];
                $new_score = self::addTieBreakerScoreToPlayer($active_player, $death_rays_amount);

                self::notifyAllPlayers("newScoresTie", clienttranslate('${player_name} threw ${tie_break_dice_amount} dice to break a tie and got ${death_rays_amount} Death Ray(s)'), array(
                    'player_name' => self::getActivePlayerName(),
                    'player_id' => $active_player,
                    'dice_types_scored' => [self::jsclass(DEATH_RAY)],
                    'new_score' => $new_score,
                    'score_from_play_area' => true,
                    'tie_break_dice_amount' => $tie_break_dice_amount,
                    'death_rays_amount' => $death_rays_amount,
                ));
            }
            self::markPlayerAsPlayedThisRound($active_player);
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
                    self::renewTable('set_aside');
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
