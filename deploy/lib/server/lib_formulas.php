<?php

// Determine the score for ranking.
function get_score_formula(){
	$score = '(level*1000 + gold/100 + kills*3 - days*5)';
	return $score;
}


// Categorize ninja ranks by level.
function level_category($level){
	$res = '';
	switch (true) {
		case($level<2):
			$res= 'Novice';
			break;
		case($level<6):
			$res= 'Acolyte';
			break;
		case($level<31):
			$res= 'Ninja';
			break;
		case($level<51):
			$res= 'Elder Ninja';
			break;
		case($level<101):
			$res= 'Master Ninja';
			break;
		default:
			$res= 'Shadow Master';
			break;
	}
	return array('display' => $res,
		'css' => strtolower(str_replace(" ", "-", $res)));
}

// Standard location for the formula to determine max health.
function determine_max_health($level){
    return max_health_by_level($level);
}


/** Calculate a max health by a level, will be used in dojo.php and calculating experience.**/
function max_health_by_level($level){
    // TODO: Needs to be aware of white health benefit.
    $health_per_level = 25;
return round($health_per_level*$level);
}

/** Calculates the experience needed for a certain level, to reach the next. **/
function experience_needed_by_level($level){
    return 1000; // Arbitrary and adjustable.
}


/**
  * Takes in damage in/out, enemy and attacker level, uses that to calculate health percentages for both.
  * param $modifiers can contain:
  * 'killed'=>true or 'killed'=>false
  * and: 'experience_today'=>(int)
  * Dependencies: max_health_by_level function.
**/
function calculate_experience($damage, $injury, $enemy_level, $attacker_level, $modifiers=array()){
    /**
    Experience System Goals:
    Allow higher players to get experience from level 1s for now until we have other gaining systems in place.
    Don't give much experience for 
    Reward tactical play over list spamming.
    Reward risk, (e.g. more experience for damage taken, for attacking harder targets when targets can actually be considered harder)
    Flexible minimum gain (for current glut of level 1s), maximum gain (to be adjustable as better defense for higher levels gets put in).
    Rate limiting of experience over short time frame, with diminishing returns (i.e. more than 2 levels per day gets diminishing returns) 

    The system:
    Core Inputs:
    How much damage did you do?
    What is the max health of the opponent? (maybe unnecessary) 
    How much damage did you take?  
    What is your max health?

    Bonus inputs:
    Attacker level
    Enemy Level
    Did you kill the opponent or not?

    Subtraction inputs:
    Amount of experience the player has gained already today.
    **/
    $attacker_max_health = max_health_by_level($attacker_level);
    $enemy_max_health = max_health_by_level($enemy_level);
    $enemy_killed = @$modifiers['killed'];
    $experience_today = @$modifiers['experience_today'];
    $experience_needed_for_this_level = experience_needed_by_level($attacker_level);
    /**
    Here is how the core experience should work in plain terms:  
    No damage % and no injury %: zero experience.
    Low damage % and no injury %: Lowest experience, but not zero.
    Low damage and low injury: low experience.
    High damage and no injury: medium-low experience.
    Low damage and high injury: medium experience.
    High damage and low injury: medium-high experience.
    High damage and high injury: high experience
    100 % damage and high injury %: best experience.

    We can create tests for these conditions and do TDD on the formula based on these expectations.
    Formula base:
    **/

    $full_xp = 500; // arbitrary and adjustable, equivalent to the very best experience.
    $max_xp = 5000; // Arbitrary & Adjustable, maximum experience that you can gain with all modifiers put into effect.

    $xp = 0; // Starting value, default to the least destructive.
    $damage_percent = ($damage/$attacker_max_health); // Perhaps this should be $enemy_max_health, hard to say until we run the numbers.
    $injury_percent = ($injury/$attacker_max_health);

    $base_damage_multiplier = ($injury_percent < 0.01 || $damage_percent < 0.01? 0 : ($injury_percent+$damage_percent/2)); // average of injury & damage if they round down to more than 0%.

    $xp = ($full_xp * $base_damage_multiplier); // Multiplier times the full_xp value.







    /**
    Here's how the bonus system would work: 
    If you killed your target, at a bit of extra experience, but not that much (e.g. we can start off with a 120% multiplier for an actual kill)  (down the line I want to move to "defeat" as the mechanism over "death")
    Formula addition: multiply by kill multiplier.
    **/

    $kill_multiplier = 1.00;
    if($enemy_killed){ 
        $kill_multiplier = 1.20;
    }

    $xp = $xp*$kill_multiplier;
    /**
    Add a bit of bonus experience for difficulty, based on level difference (small for now since defense is non-existent, and we can make it more beneficial later), level difference gives 100% to 150% multiplier to start, right now.

    Again, TDD, with kill > no kill, higher level > lower level.
    Formula addition: multiply by difficulty multiplier.
    **/

    $difficulty_multiplier = 1.00;
    $level_difference = ($attacker_level-$enemy_level);
    if ($level_difference && $level_difference > 0){
        $increase = (0.01 * ($level_difference/2)); // 1% every 2 level difference, to a max of +50%.
        $difficulty_modifier = 1.00 + ($increase >= 0.50? 0.50 : $increase);
    }

    $xp = $xp*$difficulty_multiplier;

    /**
    Here's how the subtraction system would work:
    If you've gained enough experience to level today, you get a 80% multiplier on experience.
    If you've gained double the experience to get a level today, you get a 30% multiplier on your experience.
    //Formula addition: multiply by experience rate limiter.
    **/
    $experience_rate_limiter = 1.00;
    if($experience_today && $experience_needed_for_this_level){
        if($experience_today > 2*$experience_needed_for_this_level){
            $experience_rate_limiter= 0.30;
        }elseif ($experience_today > $experience_needed_for_this_level){ 
            $experience_rate_limiter= 0.80;
        }
    }

    $xp = $xp*$experience_rate_limiter;
    /**
    Finally, round to an integer xp value at the end, and limit it by a maximum.
    **/
    $xp = (int) $xp;
    
    if($xp>$max_xp){
        $xp = $max_xp;
    }


    return $xp;
} // End of calculate_experience function.


/**
  * Takes in damage in/out, enemy and attacker level, uses that to calculate health percentages for both.
  * param $modifiers
  * can contain: 'killed'=>true or 'killed'=>false
  * can contain: 'experience_today'=>(int)
  * Dependencies: max_health_by_level function.
**/
function experience_breakdown(){
    echo calculate_experience(100, 100, 1, 1, $modifiers=array('killed'=>true, 'experience_today'=>0));
    echo ' Different situation: ';
    echo calculate_experience(500, 100, 1, 1, $modifiers=array('killed'=>true, 'experience_today'=>0));
}

// experience_breakdown();







?>
