<?php

init(); // Initializes a full environment.

$progression = render_template('progression.tpl', array('WEB_ROOT'=>WEB_ROOT, 'IMAGE_ROOT'=>IMAGE_ROOT));

echo render_page('about.tpl', 'About NinjaWars', array($progression), $options=array('quickstat'=>false, 'private'=>false, 'alive'=>false)); 

?>
