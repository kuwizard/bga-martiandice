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
            <div id="pa_square_{N}" class="pa_square" style="left: {LEFT}px; top: 1px;"></div>
        <!-- END pa_square -->
    </div>
    <div id="aside_area">
        <!-- BEGIN aside_square -->
            <div id="aside_square_{N}" class="aside_square" style="left: {LEFT}px; top: 57px;"></div>
        <!-- END aside_square -->
    <div id="dice">
    </div>
    </div>
</div>


<script type="text/javascript">

var jstpl_die='<div class="die dietype_${type}" id="die_${n}"></div>';

</script>  

{OVERALL_GAME_FOOTER}
