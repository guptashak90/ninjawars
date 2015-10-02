<?php
require_once(LIB_ROOT.'control/lib_clan.php');
require_once(CORE.'data/ClanFactory.php');
$alive      = false;
$private    = false;

if ($error = init($private, $alive)) {
	display_error($error);
	die();
}

$dbconn = DatabaseConnection::getInstance();

// *** Possible Input Values ***

$command                  = in('command');
$process                  = in('process');
$clan_name_viewed         = in('clan_name', ''); // View that clan name.
$clan_id_viewed           = (int) in('clan_id', null); // View that clan
$new_clan_name            = trim(in('new_clan_name', ''));
$sure                     = in('sure', '');
$kicked                   = in('kicked', '');
$search_person_invited    = in('person_invited', ''); // A search string
$message                  = in('message', null, null); // Don't filter messages sent in.
$message_sent             = false;
$new_clan_avatar_url      = in('clan-avatar-url');
$new_clan_description     = in('clan-description');
$avatar_or_message_change = in('avatar_or_message_change', false);
$clan_renamed             = false;
$clan_disbanded           = false;

if($command == 'view' && !$clan_id_viewed){
	$command = null; // Can't view a clan if one isn't specified.
}

$action_message = null; // Action or error message for template.

// *** Useful Constants ***
define('CLAN_CREATOR_MIN_LEVEL', 20);
$clan_creator_min_level = CLAN_CREATOR_MIN_LEVEL; // For the template.

// *** Used Variables ***

$ninja_id    = self_char_id();
$ninja       = ($ninja_id ? new Player($ninja_id) : null);
$char_info    = ($ninja_id ? self_info() : null);
$username     = @$char_info['uname'];

if ($clan_id_viewed) {
	$viewed_clan_data = get_clan($clan_id_viewed);
}

$own_clan_id = null;

// Truncate at 500 chars if necessary.
$truncated_clan_desc = substr((string)$new_clan_description, 0, 500);
if ($truncated_clan_desc != (string) $new_clan_description) {
	$new_clan_description = $truncated_clan_desc;
}

// Logical cascade: No player id? Display error message.
// No clan? Display no clan message, clan list, join link, creation limit
// Clan member not leader? Display clan list, view clan link, msg link, leave clan link.
// Clan leader -> Display leader options (make expand/contract), view clan, msg, disband.

// TODO: Made the clan tags list hidden&toggleable when leader or view options are being displayed.
// TODO: Make leader options hidden&toggle-displayable.

$own_clan_id   = null;
$own_clan_info = null;
$own_clan_name = null;
$own_clan_obj  = null;

$led_clan_info         = null;
$leader_of_own_clan    = null;
$led_clan_id           = null;
$self_is_leader        = null;
$leader_of_viewed_clan = null;

if ($ninja_id && $ninja) {
	$ninja_id = $ninja->id();
	// ***** A LOGGED IN CHARACTER *****
	$viewer_level = $ninja->vo->level;
	$can_create_a_clan = ($viewer_level >= CLAN_CREATOR_MIN_LEVEL);

	$own_clan_id = clan_id($ninja->id());

	if ($own_clan_id) {
		// Is a member of a clan.
		$own_clan_info = clan_info($own_clan_id);
		$own_clan_name = $own_clan_info['clan_name'];
		$own_clan_obj  = get_clan_by_player_id($ninja_id); // Own clan.
		$led_clan_info = clan_char_is_leader_of($ninja_id);
		$leader_of_own_clan = !empty($led_clan_info);

		if ($leader_of_own_clan) {
			$led_clan_id = whichever($led_clan_info['clan_id'], null);
			$leader_of_viewed_clan = ($clan_id_viewed && !empty($led_clan_info) && $clan_id_viewed == $led_clan_info['clan_id']);
		}
	}
}

if(positive_int($ninja_id)){ // Logged in player.
	if ($leader_of_own_clan && $avatar_or_message_change) {
		$action_message = 'Clan avatar or message changed.';

		// Saving incoming changes to clan leader edits.
		if (clan_avatar_is_valid($new_clan_avatar_url)) {
			save_clan_avatar_url($new_clan_avatar_url, $own_clan_id);
		} else {
			$error = 'That avatar url is not valid.';
		}

		if ($new_clan_description) {
			save_clan_description($new_clan_description, $own_clan_id);
		}
	}

	// *** Commands Section ***

	if ($command == 'new') {
		// *** Clan Creation Action ***
		if ($can_create_a_clan) {
			$default_clan_name = 'Clan '.$username;

			while (!is_unique_clan_name($default_clan_name)) {
				$default_clan_name = $default_clan_name.rand(1,999);
			}

			$clan              = createClan($ninja_id, $default_clan_name);
			$command           = 'rename'; // *** Shortcut to rename after. ***
			$action_message    = 'You have created a new clan!';

			$own_clan_id   = $clan->getID();
			$own_clan_info = clan_info($own_clan_id);
			$own_clan_name = $own_clan_info['clan_name'];
			$own_clan_obj  = $clan;
			$led_clan_info = $own_clan_id;
			$leader_of_own_clan = true;
		} else {	// *** Level req wasn't met. ***
			$error = 'You do not have enough renown to create a clan. You must be at least level '.CLAN_CREATOR_MIN_LEVEL.'.';
		}
	}

	if ($message) {
		message_to_clan($message);
		$message_sent = true;
		$action_message = 'Message sent to clan.';
	}

	if ($own_clan_id) {
		if ($leader_of_own_clan) {
			if ($command == 'rename') {
				//Clan Leader Action Rename
				if (is_valid_clan_name($new_clan_name)) {
					if (is_unique_clan_name($new_clan_name)) {
						// *** Rename the clan if it is valid.
						$clan_renamed = true;
						$new_clan_name = rename_clan($own_clan_obj->getID(), $new_clan_name);

						$own_clan_obj->setName($new_clan_name); // Store the renamed value for the rest of this document.
					} else {
						$error = 'That clan name is already in use!';
					}
				} elseif($new_clan_name) {
					$error = 'Sorry, too many special symbols in your clan name.';
				}
			} else if ($command == 'kick') {
				//Clan Leader Action Kick a chosen member
				if (!$kicked) {
					// Get the member info for the select dropdown list.
					$members_and_ids = clan_member_names_and_ids($own_clan_id, self_char_id());
				} else {	// *** An actual successful kick of a member. ***
					$kicked_name = get_char_name($kicked);
					$own_clan_obj->kickMember($kicked, $ninja);
					$kick_success = true;
				}
			} else if ($command == 'disband') {	// *** Clan Leader Confirmation of Disbanding of the Clan ***
				if (!$sure) {
					$display_disband_form = true;

				} elseif ($sure == 'yes' && $leader_of_own_clan) {	// **** Clan Leader Action Disbanding of the Clan ***
					$own_clan_obj->disband();
					$clan_disbanded = true;
					$action_message = 'Your clan has been disbanded.';

					$own_clan_id   = null;
					$own_clan_info = null;
					$own_clan_name = null;
					$own_clan_obj  = null;

					$led_clan_info         = null;
					$leader_of_own_clan    = null;
					$led_clan_id           = null;
					$self_is_leader        = null;
					$leader_of_viewed_clan = null;

				}
			} else if ($command == 'invite') {	// *** Clan Leader Invite Input ***
				$person_to_invite = new Player($search_person_invited);
				if ($person_to_invite->id()) {
					// *** Clan leader Invite Action ***
					$error = $own_clan_obj->invite($person_to_invite, $ninja);
					if($error === null){
						$action_message = 'Invited '.$person_to_invite->name().' to your clan.';
					}
				} elseif($search_person_invited) {
					$error = 'Sorry, unable to find a ninja to invite by that name.';
				}
			} // End of invite command.

			if ($leader_of_own_clan) {
				// ******* CLAN LEADER OPTIONS ******
				$clan_avatar_current = whichever($new_clan_avatar_url, @$own_clan_info['clan_avatar_url']);
				$clan_description_current = whichever($new_clan_description, @$own_clan_info['description']);
			}
		} else {
			// ***  NON LEADER CLAN MEMBER OPTIONS ***

			if ($command == 'leave') {
				$own_clan_obj->leave($ninja); // *** Clan Member Action to Leave their Clan ***

				$clan_id = $clan = $own_clan_obj = null;

				$action_message = 'You have left your clan.';

				// *** Zero all the clan vars so that the rest of this page displays as if in no clan. ***
				$own_clan_id   = null;
				$own_clan_info = null;
				$own_clan_name = null;
				$own_clan_obj  = null;
			}
		} // End of non-leader clan options.
	} else {
		// ****** NOT-MEMBER OF ANY CLAN *******
		if ($command == 'join') {	// *** Clan Joining Action ***
			if ($process == 1) {
				send_clan_join_request($ninja_id, $clan_id_viewed);
			} else {
				$clan_leaders = get_clan_leaders(null, true);
				$leader_ids = array();

				$clan_leaders = $clan_leaders->fetchAll();

				foreach ($clan_leaders AS $leader) {
					$leader_ids[] = $leader['player_id'];
				}

				$leaders_data = get_players_info($leader_ids);

				$leaders = array();

				foreach ($clan_leaders AS $leader) {
					$leaders[] = $leaders_data[$leader['player_id']]->vo + $leader;
				}
			}
		} // End of join command code.

		if ($clan_id_viewed) {
			// Provide a link to join any clan that you're currently viewing.
			$viewed_clan = get_clan($clan_id_viewed);
			$clan_obj = new Clan($viewed_clan['clan_id']);
			$leader      = $clan_obj? $clan_obj->getLeaderInfo() : null;
			$viewed_clan_name = @$viewed_clan['clan_name'];
		}	// End of clan_id_viewed as a non-member code.
	} // End of not-a member code
}	// End of logged-in code

$clan_name = null;
if ($command == 'view') {
	// *** A view of the member list of any clan ***
	$clan = ClanFactory::find($clan_id_viewed);
	$clan_name = $clan->getName();
	$members = $clan->getMembers();
}

$clans = clans_ranked();
$title = $clan_name? 'Clan '.$clan_name : 'Clans';
display_page('clan.tpl', $title, get_defined_vars(array('leaders', 'members', 'clan', 'ninja_id', 'error', 'action_message')), array('quickstat'=>false));
