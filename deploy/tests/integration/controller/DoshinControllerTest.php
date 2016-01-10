<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use NinjaWars\core\environment\RequestWrapper;
use NinjaWars\core\control\SessionFactory;
use NinjaWars\core\control\DoshinController;
use NinjaWars\core\control\Doshin;

class DoshinControllerTest extends PHPUnit_Framework_TestCase {
	public function setUp() {
        // Mock the post request.
        $request = new Request([], []);
        RequestWrapper::inject($request);
		SessionFactory::init(new MockArraySessionStorage());
        $this->char = TestAccountCreateAndDestroy::char();
	}

	public function tearDown() {
        RequestWrapper::inject(new Request([]));
        TestAccountCreateAndDestroy::purge_test_accounts();
    }

    public function testInstantiateDoshinController() {
        $doshin = new DoshinController();
        $this->assertInstanceOf('NinjaWars\core\control\DoshinController', $doshin);
    }

    public function testDoshinIndex() {
        $doshin = new DoshinController();
        $output = $doshin->index();
        $this->assertNotEmpty($output);
    }

    public function testDoshinOfferBounty() {
        $doshin = new DoshinController();
        $output = $doshin->offerBounty();
        $this->assertNotEmpty($output);
    }

    public function testBribeCallInDoshinController() {
        $doshin = new DoshinController();
        $output = $doshin->offerBounty();
        $this->assertNotEmpty($output);
    }

    public function testDoshinAttack(){
    }

    public function testDoshinOfferSomeBountyOnATestPlayer() {
        $target_id = TestAccountCreateAndDestroy::char_id_2();
        $this->char->set_gold(434343);
        $this->char->save();
        $target = new Player($target_id);
        $request = new Request([
            'target'=>$target->name(),
            'amount'=>600
            ]);
        RequestWrapper::inject($request);

        $doshin = new DoshinController($this->char);
        $response = $doshin->offerBounty();
        $new_bounty = getBounty($target->id());
        TestAccountCreateAndDestroy::destroy();

        $this->assertEquals(600, $new_bounty);
    }

    public function testBribeDownABounty() {
        $target_id = TestAccountCreateAndDestroy::char_id_2();
        $this->char->set_gold(434343);
        $this->char->save();
        $target = new Player($target_id);

        addBounty($this->char->id(), 400);
        $this->assertEquals(400, getBounty($this->char->id()));

        $request = new Request([
            'bribe'=>300
            ]);
        RequestWrapper::inject($request);

        $doshin = new DoshinController($this->char);
        $response = $doshin->bribe();

        $current_bounty = getBounty($this->char->id());

        // Bounty should be less now

        $this->assertLessThan(400, $current_bounty);
        $this->assertGreaterThan(0, $current_bounty);
    }

    public function testOfferOfBadNegativeBribe(){
        $request = new Request(['bribe'=>-40]);
        RequestWrapper::inject($request);

        $bounty_set = 4444;
        $initial_gold = 7777;
        $this->char->set_bounty($bounty_set);
        $this->char->set_gold($initial_gold);
        $initial_health = $this->char->health();
        $this->char->save();

        $doshin = new DoshinController($this->char);
        $response = $doshin->bribe();
        $parts = $response['parts'];
        $char = $parts['char'];
        $this->assertLessThan(7777, $this->char->gold());
        $modified_bounty = getBounty($this->char->id());
        $this->assertLessThan($bounty_set, $modified_bounty);
        $this->assertGreaterThan(0, $modified_bounty);
    }

}
