<?php

class index_test extends PHPUnit_Extensions_Selenium2TestCase {

	public function set_up()
	{
		$this->setBrowser('firefox');
		
		// Change below url to your servername, eg : $this->url('http://nw.local/');
		$this->setBrowserUrl('http://127.0.0.1/ninjawars/deploy/www/index.php');
	}
	
	public function test_title()
	{
		// Change below url to your servername, eg : $this->url('http://nw.local/');
		$this->url('http://127.0.0.1/ninjawars/deploy/www/index.php');

		$this->assertEquals('Live', $this->title());
	}
}