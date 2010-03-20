<?php
$alive      = false;
$private    = false;
$quickstat  = false;
$page_title = "Game Confirmation";

include SERVER_ROOT."interface/header.php";
?>

<h1>Account Confirmation</h1>

<?php

$admin_override_pass       = 'WeAllowIt'; // Just a weak passphrase for simply confirming players.
$admin_override_request    = in('admin_override');
$acceptable_admin_override = ($admin_override_pass === $admin_override_request);
$confirm                   = in('confirm');
$user_to_confirm           = in('username');

DatabaseConnection::getInstance();

$statement = DatabaseConnection::$pdo->prepare("SELECT player_id, uname, confirm, confirmed, email, status, member, days, ip, created_date FROM players WHERE uname = :player");
$statement->bindValue(':player', $user_to_confirm);
$statement->execute();

if ($data = $statement->fetch())
{
	$check     = $data['confirm'];
	$confirmed = $data['confirmed'];
	$username  = $data['uname'];
} else {
	$check     = 
	$confirmed = 
	$username  = null;
}

echo "<div style=\"border: 1 solid #000000;font-weight: bold;\">\n";
if ($confirmed == 1) {
	// Confirmation state from the database is already confirmed.
	echo "That player username (".$username.") is already confirmed in our system.";
	echo "<br><br>Please log in on the main page or contact <a href='staff.php'>the game administrators</a> if you have further issues.\n";
} elseif (($confirm == $check && $check != "" && $confirm != "") || $acceptable_admin_override) {
	// Confirmation number matches whats in the dabase and neither is null, or the admin override was met.
	echo "Confirmation Confirmed\n";
	
	$statement = DatabaseConnection::$pdo->prepare("UPDATE players SET confirmed = 1  WHERE uname = :player");
	$statement->bindValue(':player', $username);
	$statement->execute();	// todo - test for success

	echo "<p>You may now log in from the main page.</p>\n";
} else {
	echo "<p>This account can not be verified or the account was deactivated.  
	Please contact ".htmlentities(SUPPORT_EMAIL)." if you require more information.</p>\n";
}
?>

<div><a target='main' href="tutorial.php">Return to Main?</a></div>

</div>

<?php
include(SERVER_ROOT."interface/footer.php");
?>
