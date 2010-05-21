<?php
$alive      = true;
$private    = true;
$quickstat  = "player";
$page_title = "NPC Battle Status";

include SERVER_ROOT."interface/header.php";
?>

<h1>Battle Status</h1>

<?php
$turn_cost = 1;
$attacked  = in('attacked');
$victim    = in('victim');
$random_encounter = (rand(1, 200) == 200);

if (getTurns($username) > 0) {
	if ($attacked == 1) { // *** Bit to expect that it comes from the form. ***
		echo "<p>Attacking ...</p>\n";

		if (getStatus($username) && $status_array['Stealth']) {
			subtractStatus($username, STEALTH);
		}

		$attacker_str    = getStrength($username);
		$attacker_health = getHealth($username);
		$attacker_gold   = getGold($username);

		if ($random_encounter) { // *** ONI, Mothafucka! ***

			// **********************************************************
			// *** Oni attack! Yay!                                   ***
			// *** They take turns and a kill and do a little damage. ***
			// **********************************************************

			$victim          = 'Oni';
			$oni_turn_loss   = 10;
			$oni_health_loss = rand(1, 20);
			$oni_kill_loss   = 1;
			$player_turns    = subtractTurns($username, $oni_turn_loss);
			$attacker_health = subtractHealth($username, $oni_health_loss);
			$attacker_kills  = subtractKills($username, $oni_kill_loss);

			if ($player_turns > 50 && $attacker_health > 0) { // *** If the turns are high/you are energetic, and you survive, you can kill them. ***
				$oni_killed = true;
				addItem($username, "Dim Mak", 1);
			} else {
				$oni_killed = false;
			}

			echo render_template('oni_result.tpl', array('victory'=>$oni_killed));
		} else if ($victim == "") {
			echo render_template('no_npc_result.tpl');
		} else if ($victim == "villager") { // *** VILLAGER ***
			$villager_attack = rand(0, 10); // *** Villager Damage ***
			$just_villager = rand(0, 20);

			if ($victory = subtractHealth($username, $villager_attack)) {	// *** Player defeated villager ***
				$villager_gold = rand(0, 20);	// *** Vilager Gold ***
				addGold($username, $villager_gold);

				$attacker_level = getLevel($username);

				// *** Bounty or no bounty ***
				if ($attacker_level > 5) {
					if ($attacker_level <= 20) {
						$added_bounty = floor($attacker_level / 3);
						addBounty($username, ($added_bounty));
					}
				}	// *** End of if > 5 ***

				if (!$just_villager) { // *** Something beyond just a villager, drop a shuriken. ***
					addItem($username, 'Shuriken', $quantity = 1);
				}
			} else {	// *** Player lost against villager ***
				$villager_gold  =
				$attacker_level =
				$added_bounty   = 0;
			}

			echo render_template('villager_result.tpl', array('just_villager'=>$just_villager, 'attack'=>$villager_attack, 'gold'=>$villager_gold, 'level'=>$attacker_level, 'bounty'=>$added_bounty, 'victory'=>$victory));
		} else if ($victim == "samurai") {
			$attacker_level = getLevel($username);
			$attacker_kills = getKills($username);

			if ($attacker_level < 6 || $attacker_kills < 1) {
				$turn_cost = 0;
			} else {
				$turn_cost = 1;

				$ninja_str               = getStrength($username);
				$ninja_health            = getHealth($username);

				$samurai_damage_array    = array();

				$samurai_damage_array[0] = rand(1, $ninja_str);
				$samurai_damage_array[1] = rand(10, 10 + round($ninja_str * 1.2));
				$does_ninja_succeed      = rand(0, 1);

				if ($does_ninja_succeed) {
					$samurai_damage_array[2] = rand(30 + round($ninja_str * 0.2), 30 + round($ninja_str * 1.7));
				} else {
					$samurai_damage_array[2] = abs($ninja_health - $samurai_damage_array[0] - $samurai_damage_array[1]);  //Instant death.
				}

				for ($i = 0; $i < 3 && $ninja_health > 0; ++$i) {
					$ninja_health = $ninja_health-$samurai_damage_array[$i];
				}

				if ($ninja_health > 0) {	// *** Ninja still has health after all three attacks. ***
					$victory = true;

					$samurai_gold = rand(50, 50 + $samurai_damage_array[2] + $samurai_damage_array[1]);

					addGold($username, $samurai_gold);
					addKills($username, 1);

					if ($samurai_damage_array[2] > 100) {	// *** If samurai damage was over 100, but the ninja lived, give a speed scroll. ***
						addItem($username, 'Speed Scroll', $quantity = 1);
					}

					if ($samurai_damage_array[3] == $ninja_str * 3) {	// *** If the final damage was the exact max damage... ***
						addItem($username, "Dim Mak", 1);
					}

					setHealth($username, $ninja_health);
				} else {
					setHealth($username, 0);
					$victory = false;
					$ninja_str    =
					$samurai_gold = 0;
				}
			}	// *** End valid turns and kills for the attack. ***

			echo render_template('samurai_result.tpl', array('samurai_damage_array'=>$samurai_damage_array, 'gold'=>$samurai_gold, 'victory'=>$victory, 'ninja_str'=>$ninja_str, 'level'=>$attacker_level, 'attacker_kills'=>$attacker_kills));
		} else if ($victim == "merchant") {
			$merchant_attack = rand(15, 35);  // *** Merchant Damage ***

			if ($victory = subtractHealth($username, $merchant_attack)) {	// *** Player killed merchant ***
				$merchant_gold   = rand(20, 70);  // *** Merchant Gold   ***
				addGold($username, $merchant_gold);

				if ($merchant_attack > 34) {
					addItem($username, 'Fire Scroll', $quantity = 1);
				}

				if (getLevel($username) > 10) {
					$added_bounty = 5*floor((getLevel($username) - 5) / 3);
					addBounty($username, $added_bounty);
				}
			} else {	// *** Merchant killed player
				$merchant_attack =
				$merchant_gold   =
				$added_bounty    = 0;
			}

			echo render_template('merchant_result.tpl', array('attack'=>$merchant_attack, 'gold'=>$merchant_gold, 'bounty'=>$added_bounty, 'victory'=>$victory));
		} else if ($victim == "guard") {	// *** The Player kills the guard ***
			$guard_attack = rand(1, $attacker_str + 10);  // *** Guard Damage ***

			if ($victory = subtractHealth($username, $guard_attack)) {
				$guard_gold = rand(1, $attacker_str + 40);	// *** Guard Gold ***
				addGold($username, $guard_gold);

				if (getLevel($username) > 15) {
					$added_bounty = 10*floor((getLevel($username) - 10) / 5);
					addBounty($username, ($added_bounty));
				}
			} else {	// *** The Guard kills the player ***
				$guard_attack =
				$guard_gold   =
				$added_bounty = 0;
			}

			echo render_template('guard_result.tpl', array('attack'=>$guard_attack, 'gold'=>$guard_gold, 'bounty'=>$added_bounty, 'victory'=>$victory));
		} else if ($victim == "thief") {
			// Check the counter to see whether they've attacked a thief multiple times in a row.
			if (SESSION::is_set('counter')) {
				$counter = SESSION::get('counter');
			} else {
				$counter = 1;
			}

			$counter = $counter + 1;
			SESSION::set('counter', $counter); // Save the current state of the counter.

			if ($counter > 20 && rand(1, 3) == 3) {
				// Only after many attacks do you have the chance to be attacked back by the group of theives.
				SESSION::set('counter', 0); // Reset the counter to zero.
				$group_attack= rand(50, 150);

				if ($victory = subtractHealth($username, $group_attack)) {	// The den of thieves didn't accomplish their goal
					$group_gold = rand(100, 300);

					if ($group_attack > 120) { // Powerful attack gives an additional disadvantage
						subtractKills($username, 1);
					}

					addGold($username, $group_gold);
					addItem($username, 'Fire Scroll', $quantity = 1);
				} else {	// If the den of theives killed the attacker.
					$group_gold = 0;
				}

				echo render_template('thief-group_result.tpl', array('attack'=>$group_attack, 'gold'=>$group_gold, 'victory'=>$victory));
			} else { // Normal attack on a single thief.
				$thief_attack = rand(0, 35);  // *** Thief Damage  ***

				if ($victory = subtractHealth($username, $thief_attack)) {
					$thief_gold = rand(0, 40);  // *** Thief Gold ***

					if ($thief_attack > 30) {
						subtractGold($username, $thief_gold);
					} else if ($thief_attack < 30) {
						addGold($username, $thief_gold);
						addItem($username, 'Shuriken', $quantity = 1);
					}
				} else {
					$thief_gold = 0;
				}

				echo render_template('thief_result.tpl', array('attack'=>$thief_attack, 'gold'=>$thief_gold, 'victory'=>$victory));
			}
		}

		if (getHealth($username) <= 0) {
			sendMessage("SysMsg", $username, "DEATH: You have been killed by a non-player character at $today");
			echo "<p class='ninja-notice'>Go to the <a href=\"shrine.php\">shrine</a> to resurrect.</p>";
		}

		subtractTurns($username, $turn_cost);

		if ($victim && !$random_encounter) {
			echo "<a href=\"attack_npc.php?attacked=1&amp;victim=$victim\">Attack $victim again</a>\n";
			echo "<br>\n";
		}

		echo "<a href=\"attack_player.php\">Return to Combat</a>\n";
	}
}
else
{
	echo "You have no turns left today. Buy a speed scroll or wait for your turns to replenish.\n";
}

include SERVER_ROOT."interface/footer.php";
?>
