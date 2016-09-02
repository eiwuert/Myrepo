<?php
require_once DIRNAME(__FILE__) . '/../../../db_setup.php';
require_once DIRNAME(__FILE__) . '/../../../config.php';
/**
 * ECash_Tokens_TokenManager tests require an actual database behind them
 * 
 */
class ECash_Tokens_TokenManagerTest extends PHPUnit_Framework_TestCase
{
	const DATABASE = './test.db';

	private $_pdo;

	public function setUp()
	{
		$this->_pdo = $this->setupDatabase();
		$this->populateDB();
		$db = new TestingDatabase($this->_pdo);
		TestingDBConfig::setConnection($db);
		$class_config_name = 'TestingConfig';
		ECash::setConfig(new $class_config_name());
		parent::setUp();
	}
	public function tearDown()
	{
		@unlink(self::DATABASE);
	}
	protected function setupDatabase()
	{

		$db = new PDO('sqlite:'.self::DATABASE);
		$sql = file_get_contents(DIRNAME(__FILE__) . '/tables.sql');
  		$db->exec($sql);
  		return $db;
	}
	private function populateDB()
	{
		$sql = "INSERT INTO application (application_id, name_last, loan_type_id, rule_set_id, company_id) values ('119701', 'FRAHMTSSTEST', 1, 1, 1)";
		$this->_pdo->query($sql);
		$sql = "INSERT INTO rule_component (active_status, rule_component_id,name,name_short, grandfathering_enabled) values ('active', 1, 'Test Rule', 'test_rule', 'yes')";
		$this->_pdo->query($sql);
		$sql = "INSERT INTO rule_component (active_status, rule_component_id,name,name_short, grandfathering_enabled) values ('active', 2, 'Multi Part Test Rule', 'multi_test_rule', 'yes')";
		$this->_pdo->query($sql);
		$sql = "INSERT INTO rule_component_parm (active_status, rule_component_parm_id, rule_component_id, parm_name) values ('active', 1, 1, 'test_rule')";
		$this->_pdo->query($sql);
		$sql = "INSERT INTO rule_component_parm (active_status, rule_component_parm_id, rule_component_id, parm_name) values ('active', 2, 2, 'test_rule')";
		$this->_pdo->query($sql);
		$sql = "INSERT INTO rule_component_parm (active_status, rule_component_parm_id, rule_component_id, parm_name) values ('active', 3, 2, 'test_rule2')";
		$this->_pdo->query($sql);
		$sql = "INSERT INTO rule_set (active_status, rule_set_id, name, loan_type_id, date_effective) values ('active', 1, 'test rule set', 1, date('now'))";
		$this->_pdo->query($sql);
		$sql = "INSERT INTO rule_set_component (active_status, rule_set_id, rule_component_id) values ('active', 1, 1)";
		$this->_pdo->query($sql);
		$sql = "INSERT INTO rule_set_component (active_status, rule_set_id, rule_component_id) values ('active', 1, 2)";
		$this->_pdo->query($sql);
		$sql = "INSERT INTO rule_set_component_parm_value (rule_set_id,rule_component_id, rule_component_parm_id,parm_value) values (1, 1, 1, 'works')"; 
		$this->_pdo->query($sql);
		$sql = "INSERT INTO rule_set_component_parm_value (rule_set_id,rule_component_id, rule_component_parm_id,parm_value) values (1, 2, 2, 'worksto')"; 
		$this->_pdo->query($sql);
		$sql = "INSERT INTO rule_set_component_parm_value (rule_set_id,rule_component_id, rule_component_parm_id,parm_value) values (1, 2, 3, 'worksthree')"; 
		$this->_pdo->query($sql);
		$sql = "INSERT INTO company (active_status, company_id, name, name_short) values ('active', 1, 'test company', 'test')"; 
		$this->_pdo->query($sql);
		$sql = "INSERT INTO loan_type (active_status, company_id, loan_type_id, name, name_short) values ('active', 1, 1, 'test loan type', 'testloan')"; 
		$this->_pdo->query($sql);

	}

	public function testTokenCreationReturnsTrue()
	{
		$manager = ECash::getFactory()->getTokenManager();
		
		$new_token = $manager->getNewToken('static');
		$new_token->setName('StaticTest');
		$new_token->setValue('Universal Static Value');
		$this->assertTrue($new_token->save());
		
	}
	public function testTokenDeletionReturnsTrue()
	{
		$manager = ECash::getFactory()->getTokenManager();
		
		$new_token = $manager->getNewToken('static');
		$new_token->setName('StaticTest');
		$new_token->setValue('Universal Static Value');
		$new_token->save();
		$this->assertEquals($new_token->delete(), 1);
		
	}
		
	public function testUniversalTokenRetrieval()
	{
		$manager = ECash::getFactory()->getTokenManager();
		$new_token = $manager->getNewToken('static');
		$new_token->setName('StaticTest');
		$new_token->setValue('Universal Static Value');
		$new_token->save();
		
		$tokens = $manager->getTokensByLoanTypeId(1,1);
		foreach($tokens as $name => $token)
		{
			$this->assertEquals($token->getValue(), 'Universal Static Value');
		}
	}
	
	public function testCompnayOverrideTokenRetrieval()
	{
		$manager = ECash::getFactory()->getTokenManager();
		$new_token = $manager->getNewToken('static');
		$new_token->setName('StaticTest');
		$new_token->setValue('Universal Static Value');
		$new_token->save();
		
		$new_token = $manager->getNewToken('static');
		$new_token->setName('StaticTest');
		$new_token->setValue('Company Static Value');
		$new_token->setCompanyId(1);
		$new_token->save();
		
		$tokens = $manager->getTokensByCompanyId(1);
		foreach($tokens as $name => $token)
		{
			$this->assertEquals($token->getValue(), 'Company Static Value');
		}
	}
	public function testLoanTypeOverrideTokenRetrieval()
	{
		$manager = ECash::getFactory()->getTokenManager();
		$new_token = $manager->getNewToken('static');
		$new_token->setName('StaticTest');
		$new_token->setValue('Universal Static Value');
		$new_token->save();
		
		$new_token = $manager->getNewToken('static');
		$new_token->setName('StaticTest');
		$new_token->setValue('Company Static Value');
		$new_token->setCompanyId(1);
		$new_token->save();

		$new_token = $manager->getNewToken('static');
		$new_token->setName('StaticTest');
		$new_token->setValue('Loan Static Value');
		$new_token->setCompanyId(1);
		$new_token->setLoanTypeId(1);
		$new_token->save();
				
		$tokens = $manager->getTokensByLoanTypeId(1, 1);
		foreach($tokens as $name => $token)
		{
			$this->assertEquals($token->getValue(), 'Loan Static Value');
		}
	}
	public function testgetToken()
	{
		$manager = ECash::getFactory()->getTokenManager();
		$new_token = $manager->getNewToken('static');
		$new_token->setName('StaticTest');
		$new_token->setValue('Universal Static Value');
		$new_token->save();
		
		$token = $manager->getToken('StaticTest');

		$this->assertEquals($token->getValue(), 'Universal Static Value');
		
	}
	public function testgetTokenById()
	{
		$manager = ECash::getFactory()->getTokenManager();
		$new_token = $manager->getNewToken('static');
		$new_token->setName('StaticTest');
		$new_token->setValue('Universal Static Value');
		$new_token->save();
		
		$token = $manager->getTokenById(1);

		$this->assertEquals($token->getValue(), 'Universal Static Value');
		
	}
	public function testApplicationToken()
	{

		
		$manager = ECash::getFactory()->getTokenManager();
		
		$new_token = $manager->getNewToken('application');
		$new_token->setName('ApplicationTest');
		$new_token->setValue('name_last');
		$new_token->setCompanyId(1);
		$new_token->setLoanTypeId(1);
		$new_token->save();
		
		$tokens = $manager->getTokensByLoanTypeId(1,1);
		
		foreach($tokens as $name => $token)
		{
			$token->setApplicationId(119701);
			$this->assertEquals($token->getValue(), 'FRAHMTSSTEST');
		}
		
	}
	public function testApplicationTokenValueNotFound()
	{
		
		$manager = ECash::getFactory()->getTokenManager();
		
		$new_token = $manager->getNewToken('application');
		$new_token->setName('ApplicationTest');
		$new_token->setValue('name_last');
		$new_token->setCompanyId(1);
		$new_token->setLoanTypeId(1);
		$new_token->save();
		
		$tokens = $manager->getTokensByLoanTypeId(1,1);
		
		foreach($tokens as $name => $token)
		{
			$token->setApplicationId(119702);
			$this->assertEquals($token->getValue(), NULL);
		}
		
	}
	public function testBusinessRuleToken()
	{

		
		$manager = ECash::getFactory()->getTokenManager();
		
		$new_token = $manager->getNewToken('business_rule');
		$new_token->setName('BusinessRuleTest');
		$new_token->setValue('test_rule', 'test_rule');
		$new_token->setCompanyId(1);
		$new_token->setLoanTypeId(1);
		$new_token->save();
		
		$tokens = $manager->getTokensByLoanTypeId(1,1);
		
		foreach($tokens as $name => $token)
		{
			$token->setApplicationId(119701);
			$this->assertEquals($token->getValue(), 'works');
		}
		
	}
	public function testBusinessRuleTokenMulti()
	{

		
		$manager = ECash::getFactory()->getTokenManager();
		
		$new_token = $manager->getNewToken('business_rule');
		$new_token->setName('BusinessRuleTest');
		$new_token->setValue('multi_test_rule', 'test_rule');
		$new_token->setCompanyId(1);
		$new_token->setLoanTypeId(1);
		$new_token->save();
		
		$tokens = $manager->getTokensByLoanTypeId(1,1);
		
		foreach($tokens as $name => $token)
		{
			$token->setApplicationId(119701);
			$this->assertEquals($token->getValue(), 'worksto');
			$this->assertEquals($token->getApplicationId(), 119701);
		}
		
	}
	public function testGetTokenByApplication()
	{
		
		$manager = ECash::getFactory()->getTokenManager();
		
		$new_token = $manager->getNewToken('application');
		$new_token->setName('ApplicationTest');
		$new_token->setValue('name_last');
		$new_token->setCompanyId(1);
		$new_token->setLoanTypeId(1);
		$new_token->save();
		
		$tokens = $manager->getTokensbyApplicationId(119701, 'test', 'testloan');
		
		foreach($tokens as $name => $token)
		{
			$this->assertEquals($token->getValue(), 'FRAHMTSSTEST');
			$this->assertEquals($token->getApplicationId(), 119701);
		}		
	}
	public function testGetTokenByApplicationThatDoesNotExist()
	{
		
		$manager = ECash::getFactory()->getTokenManager();
		
		$new_token = $manager->getNewToken('application');
		$new_token->setName('ApplicationTest');
		$new_token->setValue('name_last');
		$new_token->setCompanyId(1);
		$new_token->setLoanTypeId(1);
		$new_token->save();
		
		$tokens = $manager->getTokensbyApplicationId(119702, 'test', 'testloan');
		
		foreach($tokens as $name => $token)
		{
			$this->assertEquals($token->getValue(), null);
		}		
	}
	public function testGetTokenByApplicationOnBadColumn()
	{
		
		$manager = ECash::getFactory()->getTokenManager();
		
		$new_token = $manager->getNewToken('application');
		$new_token->setName('ApplicationTest');
		$new_token->setValue('does_not_exist');
		$new_token->setCompanyId(1);
		$new_token->setLoanTypeId(1);
		$new_token->save();
		
		$tokens = $manager->getTokensbyApplicationId(119701, 'test', 'testloan');
		
		foreach($tokens as $name => $token)
		{
			$this->assertEquals($token->getValue(), null);
		}		
	}
	public function testGetName()
	{
		$manager = ECash::getFactory()->getTokenManager();
		$new_token = $manager->getNewToken('static');
		$new_token->setName('StaticTest');
		$new_token->setValue('Universal Static Value');
		$new_token->save();
		
		$tokens = $manager->getTokensByLoanTypeId(1,1);
		foreach($tokens as $name => $token)
		{
			$this->assertEquals($token->getName(), 'StaticTest');
		}		
	}
	public function testGetId()
	{
		$manager = ECash::getFactory()->getTokenManager();
		$new_token = $manager->getNewToken('static');
		$new_token->setName('StaticTest');
		$new_token->setValue('Universal Static Value');
		$new_token->save();
		
		$tokens = $manager->getTokensByLoanTypeId(1,1);
		foreach($tokens as $name => $token)
		{
			$this->assertEquals($token->getId(), 1);
		}		
	}
}

    ?>
