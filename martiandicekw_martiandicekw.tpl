{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- MartianDiceKW implementation : © Pavel Kulagin kuzwiz@mail.ru
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------

    martiandicekw_martiandicekw.tpl
    
    This is the HTML template of your game.
    
    Everything you are writing in this file will be displayed in the HTML page of your game user interface,
    in the "main game zone" of the screen.
    
    You can use in this template:
    _ variables, with the format {MY_VARIABLE_ELEMENT}.
    _ HTML block, with the BEGIN/END format
    
    See your "view" PHP file to check how to set variables and control blocks
    
    Please REMOVE this comment before publishing your game on BGA
-->

<div id="board">
    <div id="play_area">
        <!-- BEGIN pa_square -->
            <div id="pa_square_{N}" class="pa_square"></div>
        <!-- END pa_square -->
    </div>
    <div></div>
    <div id="aside_area">
        <div id="earthlings">
            <div id="e1_area">
                <!-- BEGIN e1_square -->
                <div id="e1_square_{N}" class="e1_square"></div>
                <!-- END e1_square -->
            </div>
            <div id="e2_area">
                <!-- BEGIN e2_square -->
                <div id="e2_square_{N}" class="e2_square"></div>
                <!-- END e2_square -->
            </div>
            <div id="e3_area">
                <!-- BEGIN e3_square -->
                <div id="e3_square_{N}" class="e3_square"></div>
                <!-- END e3_square -->
            </div>
        </div>
        <div id="military_area">
            <div id="tank_area">
                <!-- BEGIN tank_square -->
                    <div id="tank_square_{N}" class="tank_square"></div>
                <!-- END tank_square -->
            </div>
            <div id="deathray_area">
                <!-- BEGIN deathray_square -->
                    <div id="deathray_square_{N}" class="deathray_square"></div>
                <!-- END deathray_square -->
            </div>
        </div>
    <div id="dice">
    </div>
    </div>
</div>

<script type="text/javascript">

var jstpl_die='<div class="die dietype_${type}" id="die_${n}"></div>';
var jstpl_pa_square='<div class="pa_square" id="pa_square_${n}"></div>';

</script>  

{OVERALL_GAME_FOOTER}
