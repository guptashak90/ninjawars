<?php
use NinjaWars\core\data\Account;
use NinjaWars\core\data\Player;

class AccountTest extends NWTest {
    var $testAccountId;

    public function setUp() {
        $_SERVER['REMOTE_ADDR']=isset($_SERVER['REMOTE_ADDR'])? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
        $this->test_email = TestAccountCreateAndDestroy::$test_email;
        $this->test_password = TestAccountCreateAndDestroy::$test_password;
        $this->test_ninja_name = TestAccountCreateAndDestroy::$test_ninja_name;
        TestAccountCreateAndDestroy::purge_test_accounts(TestAccountCreateAndDestroy::$test_ninja_name);
        $this->testAccountId = TestAccountCreateAndDestroy::account_id();
    }

    public function tearDown() {
        TestAccountCreateAndDestroy::purge_test_accounts();
    }

    public function testCreatingAnAccount() {
        $account_id = $this->testAccountId;
        $acc = Account::findById($account_id);
        $this->assertTrue($acc instanceof Account); $this->assertNotEmpty($acc->getIdentity());
    }

    public function testAccountHasIdentity() {
        $account = Account::findById($this->testAccountId);
        $this->assertNotEmpty($account->getIdentity());
    }

    public function testAccountHasAType() {
        $account = Account::findById($this->testAccountId);
        $this->assertTrue(gettype($account->getType()) === 'integer');
    }

    public function testAccountHasAnId() {
        $account = Account::findById($this->testAccountId);
        $this->assertGreaterThan(0, $account->getId());
    }

    public function testAccountReturnsAccount() {
        $account = Account::findById($this->testAccountId);
        $this->assertTrue($account instanceof Account);
        $this->assertNotEmpty($account->getIdentity());
    }

    public function testAccountReturnsAccountWithMatchingIdentity() {
        $identity = $this->test_email;
        $acc = Account::findByIdentity($identity);
        $this->assertEquals($identity, $acc->getIdentity());
    }

    public function testAccountHasActiveEmail() {
        $account = Account::findById($this->testAccountId);
        $this->assertNotEmpty($account->getActiveEmail());
    }

    public function testAccountCanHaveOauthAddedInMemory() {
        $account = Account::findById($this->testAccountId);
        $oauth_id = 88888888888888;
        $account->setOauthId($oauth_id, 'facebook');
        $this->assertEquals($oauth_id, $account->getOauthId());
    }

    public function testSetAndGetOauthProvider(){
        $account = new Account();
        $account->setOauthProvider('facebook');
        $this->assertEquals('facebook', $account->getOauthProvider());
    }

    public function testAccountCanSaveNewOauthIdAfterHavingItAdded() {
        $account = Account::findById($this->testAccountId);
        $oauth_id = 88888888888888;
        $account->setOauthId($oauth_id, 'facebook');
        $account->save();
        $account_dupe = Account::findById($this->testAccountId);
        $this->assertEquals($oauth_id, $account_dupe->getOauthId());
    }

    public function testAccountPasswordCanBeChanged() {
        $account = Account::findById($this->testAccountId);
        $updated = $account->changePassword('whatever gibberish');
        $this->assertTrue((bool)$updated);
    }

    public function testFindAccountByEmail() {
        $account = Account::findById($this->testAccountId);
        $account2 = Account::findByEmail($account->email());
        $this->assertEquals($account->id(), $account2->id());
    }

    public function testFindAccountByEmailWithEmptyInput() {
        $account = Account::findByEmail('   ');
        $this->assertNull($account);
    }

    public function testFindAccountByNinja() {
        $player = Player::findByName($this->test_ninja_name);
        $account = Account::findByChar($player);
        $this->assertNotNull($account);
    }

    public function testFindAccountByNinjaName() {
        $account = Account::findByNinjaName($this->test_ninja_name);
        $this->assertNotNull($account);
    }

    public function testFindAccountByNonexistentId() {
        $account = Account::findById(-120);
        $this->assertNull($account);
    }


    public function testThanAccountCanBeSetAsDifferentType(){
        $account = new Account();
        $account->setType(2);
        $this->assertEquals(2, $account->type);
    }

    public function testAuthenticationOfAccountWithNoDatabaseAnalogFails(){
        $account = new Account();
        $this->assertFalse($account->authenticate('an invalid password'));
    }

    public function testAccountCanHavePlayers(){
        $account = Account::findByNinjaName($this->test_ninja_name);
        $pcs = $account->getCharacters();
        $this->assertNotEmpty($pcs);
        $this->assertInstanceOf(Player::class, reset($pcs));
    }

}
