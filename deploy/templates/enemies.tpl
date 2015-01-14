<link rel="stylesheet" type="text/css" href="css/enemies.css" media="Screen">

<h1>Fight</h1>

{if count($recent_attackers) gt 0}
	{include file="enemies-recent-attackers.tpl" recent_attackers=$recent_attackers}
{/if}

<section id='enemies-stuff' class='clearfix'>
{if $enemyCount gt 0}
<div class='enemies-lefthalf'>
  <h3>Enemies</h3>
  <ul id='current-enemies-list'>
	{foreach from=$enemy_list item="loop_enemy"}
		{if $loop_enemy.active}
			{if $loop_enemy.health gt 0}
				{assign var="status_class" value=""}
				{assign var="action" value="Attack"}
			{else}
				{assign var="status_class" value="enemy-dead"}
				{assign var="action" value="View"}
			{/if}
    <li class="{$status_class}">
      <a href="enemies.php?remove_enemy={$loop_enemy.player_id|escape}"><img src="{$smarty.const.IMAGE_ROOT}icons/mono/stop32.png" height='16' width='16' alt="remove" title='Remove'></a>
      <span class='enemy-action-box'>{$action} <a class='enemy-name' title="View {$loop_enemy.uname|escape}'s info" href="player.php?player_id={$loop_enemy.player_id|escape}">{$loop_enemy.uname|escape}</a></span>
      <span class='enemy-stats-box'>
        {include file="health_bar.tpl" health=$loop_enemy.health health_percent=$loop_enemy.health_percent}
      </span>
      <em title='Level {$loop_enemy.level}'>{$loop_enemy.level}</em>
    </li>
		{/if}
	{/foreach}
  </ul>
</div>
{else}
<p class='enemies-lefthalf'>You haven't decided who your enemies are yet, pick some below.</p>
{/if}

{if count($peers) gt 0}
<div class='enemies-righthalf'>
  <h3>Nearby Ninja</h3>
  <ul id='peer-chars'>
	{foreach from=$peers item="loop_peer"}
    <li class='peer'>
       <a class='peer-name' title='View {$loop_peer.uname|escape} to attack them' href='player.php?player_id={$loop_peer.player_id}' target='main'>{$loop_peer.uname|escape}</a>
		{if $char_info.health}
       <span class='stats-block'>
         {include file="health_bar.tpl" health=$loop_peer.health health_percent=$loop_peer.health_percent}
       </span>
<!-- (level {$loop_peer.level}) -->
		{/if}
       	<em title='Level {$loop_peer.level}'>{$loop_peer.level}</em>
    </li>
    {/foreach}
  </ul>
</div>
{else}
<p class='enemies-righthalf'>No nearby ninja, <em class='char-name'>{$username|escape}</em>.</p>
{/if}
</section><!-- End of clearfix section -->

<div id="ninja-enemy" class='solo-box'>
  <form id="enemy-add" action="enemies.php" method="get" name="enemy_add">
    <input id='enemy-match' type="text" maxlength="50" name="enemy_match" class="textField" placeholder='Search by ninja name'>
    <input type="submit" value="Find Enemies" class="formButton">
  </form>    
</div>
<!-- Js at bottom -->


<section id='ninja-matches' class='cf'>
	<ul>
		<li id='sample-enemy-match' class='enemy' class='hidden'>
			Duel <strong class='char-name'><a class='char-name-link' href='/attack_mod.php?duel=1&amp;target='>Someone</a></strong>
		</li>
	</ul>
	<div id='more-matches' class='hidden'>
		...with more live matches...
	</div>
	<br style='clear:both'>
{if $found_enemies && count($found_enemies) gt 0}
	{include file="enemy-matches.tpl" enemies=$found_enemies}
{elseif $match_string}
	<div class='hidden'>
	  Your search returned no ninja. maybe you should make an enemy of someone who recently attacked you.
		{include file="enemy-matches.tpl" enemies=$recent_attackers}
	</div>
{/if}
</section>

<section id='npc-list-section'>
  <h3>Attack a:</h3>
  <div class='centered'>
  <ul id='npc-list'>
{foreach name="person" from=$npcs key="idx" item="npc"}
      <li class='person'><a href='npc.php?attacked=1&amp;victim={$npc.identity|escape}' target='main'><img alt='' src='images/characters/{$npc.image|escape:'url'|escape}'> {$npc.name|escape}</a></li>
{/foreach}
{foreach name="creatures" from=$other_npcs key="idx" item="npc"}
      <li><a href='npc.php?attacked=1&amp;victim={$idx|escape}' target='main'>
      	{if isset($npc.img) && $npc.img}
      	<img alt='' class='creature-image' src='images/characters/{$npc.img|escape:'url'|escape}'>
      	{else}<span style='width:25px;height:46px'>&#9733;</span>
      	{/if} 
      	{$npc.name|escape}</a></li>
{/foreach}
  </ul>
  </div>
</section>

<!-- Display recently active ninja -->
{* {include file="list.active.tpl" active_ninja=$active_ninjas} *}


<script src='js/enemies.js'></script>