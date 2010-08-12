<?php
$private    = false;
$alive      = false;

if ($error = init($private, $alive)) {
	display_error($error);
} else {
$description       = "";
$in_purchase       = in('purchase');
$in_quantity       = in('quantity');
$item              = in('item');
$grammar           = "";
$username          = get_username();
$gold              = first_value(getGold($username), 0);
$current_item_cost = 0;
$quantity          = intval($in_quantity);
$is_logged_in      = is_logged_in();

if (!$quantity || $quantity < 1) {
	$quantity = 1;
} else if ($quantity > 1 && $item != "Shuriken") {
	$grammar = "s";
}

$item_costs = array(
	"Speed Scroll"     => 225
	, "Fire Scroll"    => 175
	, "Stealth Scroll" => 150
	, "Ice Scroll"     => 125
	, "Shuriken"       => 50
//	, "Dim Mak"        => 10000
);

if ($in_purchase == 1 && $item) {
	$current_item_cost  = first_value($item_costs[$item], 0);
	$current_item_cost *= $quantity;

	if ($current_item_cost > $gold){ // Not enough gold.
		$description .= "<p>\"The total comes to $current_item_cost gold,\" the shopkeeper tells you.</p>";
		$description .= "<p>Unfortunately, you do not have that much gold.</p>";
	} else { // Has enough gold.
		addItem($username, $item, $quantity);
		subtractGold($username, $current_item_cost);

		$description .= "<p>The shopkeeper hands over $quantity ".$item.$grammar.".</p>";
		$description .= "<p>\"Will you be needing anything else today?\" he asks you as he puts your gold in a safe.</p>";
	}
} else { // Default, before anything has been bought.
	$description .= "<p>You enter the village shop and the shopkeeper greets you with a watchful eye.</p>";
	$description .= "<p>As you look over his wares he says, \"Don't try anythin' you'd regret.\", waves his hand at a katana on the wall, and grins at you.</p>";
}

$parts = get_certain_vars(get_defined_vars(), array($item_costs));

display_page(
	'shop.tpl'	// *** Main Template ***
	, 'Shop'	// *** Page Title ***
	, $parts	// *** Page Variables ***
	, array(	// *** Page Options ***
		'quickstat' => 'viewinv'
	)
);
}
?>
