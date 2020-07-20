/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * MartianDiceKW implementation : © Pavel Kulagin kuzwiz@mail.ru
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * martiandicekw.js
 *
 * MartianDiceKW user interface script
 *
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
        "dojo", "dojo/_base/declare",
        "ebg/core/gamegui",
        "ebg/counter"
    ],
    function (dojo, declare) {
        return declare("bgagame.martiandicekw", ebg.core.gamegui, {
            constructor: function () {
                console.log('martiandicekw constructor');
                this.nextDie = 1;
                this.nextDieSetAside = 1;
            },

            /*
                setup:

                This method must set up the game user interface according to current game situation specified
                in parameters.

                The method is called each time the game interface is displayed to a player, ie:
                _ when the game starts
                _ when a player refreshes the game page (F5)

                "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
            */

            setup: function (gamedatas) {
                console.log("Starting game setup");

                const allDice = gamedatas.current_round_dice.map((e) => {
                    const setAsideAmount = gamedatas.set_aside_dice.find((die) => die.type === e.type).amount
                    return {
                        ...e,
                        amount: e.amount + setAsideAmount
                    }
                });

                this.placeNewDice(allDice);
                this.doSetAsideAnimations(gamedatas.set_aside_dice);
                this.updatePossibleMoves(gamedatas.current_round_dice);

                // TODO: Set up your game interface here, according to "gamedatas"

                // Setup game notifications to handle (see "setupNotifications" method below)
                this.setupNotifications();

                console.log("Ending game setup");
            },


            ///////////////////////////////////////////////////
            //// Game & client states

            // onEnteringState: this method is called each time we are entering into a new game state.
            //                  You can use this method to perform some user interface changes at this moment.
            //
            onEnteringState: function (stateName, args) {
                console.log('Entering state: ' + stateName);
                switch (stateName) {
                    case 'continueOrEnd':
                        this.removeAllPossibleMoves();
                        this.removeAllTooltips();
                        break;
                    case 'dummmy':
                        break;
                }
            },

            onUpdateActionButtons: function (stateName, args) {
                console.log('onUpdateActionButtons: ' + stateName);

                if (this.isCurrentPlayerActive()) {
                    switch (stateName) {
                        case 'continueOrEnd':
                            this.addActionButton('reroll', _('roll all available dice'), 'onRerollDice');
                            this.addActionButton('end_turn', _('end your turn'), 'onEndTurn', null, null, 'red');
                            break;
                    }
                }
            },

            onDieClick: function (evt) {
                // Stop this event propagation
                dojo.stopEvent(evt);

                if (dojo.hasClass(evt.currentTarget, 'impossibleMove')) {
                    // This is not a possible move => the click does nothing
                    return;
                }

                if (this.checkAction('diceSetAside'))    // Check that this action is possible at this moment
                {
                    this.ajaxcall("/martiandicekw/martiandicekw/diceSetAside.html", {
                        dice_type: this.findDieTypeClass(evt.currentTarget)
                    }, this, function (result) {
                    });
                }
            },

            onRerollDice: function (evt) {
                dojo.stopEvent(evt);
                if (this.checkAction('rerollDice')) {
                    this.ajaxcall("/martiandicekw/martiandicekw/rerollDice.html", {}, this, function (result) {
                    });
                }
            },

            onEndTurn: function (evt) {
                dojo.stopEvent(evt);
                if (this.checkAction('endTurn')) {
                    this.ajaxcall("/martiandicekw/martiandicekw/endTurn.html", {}, this, function (result) {
                    });
                }
            },

            ///////////////////////////////////////////////////
            //// Utility methods

            /*

                Here, you can defines some utility methods that you can use everywhere in your javascript
                script.

            */

            placeNewDice: function(dice) {
                dice.forEach(die => {
                    this.addDieToPlayArea(die.jsclass, die.amount);
                })
                dojo.query('.die').connect('onclick', this, 'onDieClick');
            },

            addDieToPlayArea: function (type, count) {
                for (i = 0; i < count; i++) {
                    dojo.place(this.format_block('jstpl_die', {
                        n: this.nextDie,
                        type: type
                    }), 'dice');

                    this.placeOnObject('die_' + this.nextDie, dojo.query('#player_boards .player-board')[0]);
                    this.slideToObject('die_' + this.nextDie, 'pa_square_' + this.nextDie).play();
                    dojo.addClass('die_' + this.nextDie, 'play_area');
                    this.nextDie += 1;
                }
            },

            addDieToAsideArea: function(type, count) {
                for (i = 0; i < count; i++) {
                    dojo.place(this.format_block('jstpl_die', {
                        n: this.nextDie,
                        type: type
                    }), 'dice');

                    this.placeOnObject('die_' + this.nextDie, 'overall_player_board_' + this.getActivePlayerId());
                    this.slideToObject('die_' + this.nextDie, 'aside_square_' + this.nextDieSetAside).play();
                    dojo.addClass('die_' + this.nextDie, 'set_aside');
                    dojo.addClass('die_' + this.nextDie, 'impossibleMove');
                    this.nextDieSetAside += 1;
                    this.nextDie += 1;
                }
            },

            ///////////////////////////////////////////////////
            //// Player's action

            /*

                Here, you are defining methods to handle player's action (ex: results of mouse click on
                game objects).

                Most of the time, these methods:
                _ check the action is possible at this game state.
                _ make a call to the game server

            */

            updatePossibleMoves: function (dice) {
                this.removeAllPossibleMoves();
                this.removeAllTooltips();

                dice.filter((die) => die.choosable === '1').forEach((die) => {
                    const jsclass = '.dietype_' + die.jsclass;
                    dojo.query(`${jsclass}.play_area`).removeClass('impossibleMove');
                    this.addTooltipToClass(`${jsclass}:not(.impossibleMove)`, '', _('Choose all ' + die.name));
                });
            },

            removeAllTooltips: function () {
                dojo.forEach(dojo.query('.die'), function (die) {
                    this.removeTooltip(die.id);
                }.bind(this));
            },

            removeAllPossibleMoves: function () {
                dojo.query('.die').addClass('impossibleMove');
            },

            doSetAsideAnimations: function (dice) {
                dice.forEach((die) => {
                    this.doSetAsideAnimation(die.jsclass, die.amount);
                });
            },

            doSetAsideAnimation: function (jsclass, amount) {
                jsclass = '.dietype_' + jsclass + '.play_area'
                for (i = 0; i < amount; i++) {
                    const die = dojo.query(jsclass)[0];
                    this.slideToObject(die, 'aside_square_' + this.nextDieSetAside).play();
                    dojo.removeClass(die, 'play_area');
                    dojo.addClass(die, 'set_aside');
                    this.nextDieSetAside += 1;
                }
                dojo.query('.set_aside').addClass('impossibleMove');
            },

            findDieTypeClass: function(node) {
                return Array.from(node.classList).find(value => /^dietype_/.test(value));
            },

            ///////////////////////////////////////////////////
            //// Reaction to cometD notifications

            /*
                setupNotifications:

                In this method, you associate each of your game notifications with your local method to handle it.

                Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                      your martiandicekw.game.php file.

            */
            setupNotifications: function () {
                console.log('notifications subscriptions setup');
                dojo.subscribe('zeroScoring', this, "notif_scoring");
                dojo.subscribe('newScores', this, "notif_scoring");
                dojo.subscribe('diceThrown', this, "notif_diceThrown");
                this.notifqueue.setSynchronous('diceThrown', 1000);
                dojo.subscribe('diceSetAside', this, "notif_diceSetAside");
                this.notifqueue.setSynchronous('diceSetAside', 800);
                // TODO: here, associate your game notifications with local methods

                // Example 1: standard notification handling
                // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );

                // Example 2: standard notification handling + tell the user interface to wait
                //            during 3 seconds after calling the method in order to let the players
                //            see what is happening in the game.
                // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
                // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
                //
            },

            notif_diceSetAside: function (notif) {
                this.doSetAsideAnimation(notif.args.dice_type_jsclass, notif.args.dice_amount);
            },

            notif_scoring: function (notif) {
                if (notif.args.new_score) {
                    this.scoreCtrl[notif.args.player_id].toValue(notif.args.new_score);
                }
            },

            notif_diceThrown: function (notif) {
                var dice;
                if (notif.args.is_reroll) {
                    dice = dojo.query('.play_area');
                } else {
                    dice = dojo.query('.die');
                    this.nextDieSetAside = 1;
                }

                dojo.forEach(dice, function (die) {
                        dojo.fadeOut({
                            node: die,
                            onEnd: function (node) {
                                dojo.destroy(node);
                            }
                        }).play();
                });
                this.nextDie = 1;
                dojo.forEach(dojo.query('.die'), function (die) {
                    dojo.removeAttr(die, 'id');
                });
                this.placeNewDice(notif.args.dice);
                this.updatePossibleMoves(notif.args.dice);
            },
        });
    });
