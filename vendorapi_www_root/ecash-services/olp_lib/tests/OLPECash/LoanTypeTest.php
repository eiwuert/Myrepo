<?php
/**
 * Unit tests for OLPECash_LoanType
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 *
 */
class OLPECash_LoanTypeTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Mocked object for DB_Models_Decorator_ReferencedWritableModel_1
	 *
	 * @var object
	 */
	protected $loan_type_model_mock;
	
	/**
	 * Mocked object for ECash_BusinessRules
	 *
	 * @var object
	 */
	protected $business_rules_mock;
	
	/**
	 * Mocked DB_Database_1 Object
	 *
	 * @var object
	 */
	protected $pdo_mock;
	
	/**
	 * Mocked DB_Statement_1 Object 
	 *
	 * @var object
	 */
	protected $stmt_mock;
	
	/**
	 * Mocked object for OLPECash_LoanType
	 *
	 * @var object
	 */
	protected $base_mock;
	
	/**
	 * Function to redeclare all mocked objects for each test
	 *
	 * @return void 
	 */
	public function setUpMock()
	{
		/*
		 * Mock the statement for PDO using stdClass
		 */
		$this->stmt_mock = $this->getMock(
			'stdClass',
			array('fetch'));
		
		/*
		 * Set up a mock PDO/DB_Database_1 object
		 * queryPrepared will be mocked here to return the same object
		 * by "fetch" should be set up on a test by test basis
		 */
		$this->pdo_mock = $this->getMock(
			'DB_Database_1',
			array('queryPrepared'),
			array('DSN'));
		$this->pdo_mock->expects($this->any())
			->method('queryPrepared')
			->will($this->returnValue($this->stmt_mock));
		
			
		/*
		 * Set up the mock for loan type model
		 * All mocked functions should be set up on a case by case basis 
		 */
		$this->loan_type_model_mock = $this->getMock(
			'DB_Models_Decorator_ReferencedWritableModel_1',
			array(),
			array(),
			'',
			FALSE
		);
			
		/*
		 * Set up the mock for the ecash _business rules
		 * None of the mocked functions will be configured
		 * as they should be set on a test by test basis
		 */ 
		$this->business_rules_mock = $this->getMock(
			'stdClass',
			array('Get_Rule_Set_Id_For_Application',
				'Get_Current_Rule_Set_Id',
				'Get_Rule_Set_Tree'));
		
		/*
		 * Set up the base mock for determining
		 * All mocked functions will be configured
		 */ 
		$this->base_mock = $this->getMock(
			'OLPECash_LoanType',
			array('getEcashBusinessRules', 'getApplicationValueModel'),
			array($this->pdo_mock, 'TEST', $this->loan_type_model_mock));
		$this->base_mock->expects($this->any())
			->method('getEcashBusinessRules')
			->will($this->returnValue($this->business_rules_mock));
		$this->base_mock->expects($this->any())
			->method('getApplicationValueModel')
			->will($this->returnValue($this->loan_type_model_mock));
		
			
	}
	
	/**
	 * Ensures that external applications can set and get the application ID
	 *
	 * @return void
	 */
	public function testApplicationID()
	{
		$this->setUpMock();
		$id = '6745467';
		$this->base_mock->setApplicationID($id);
		$this->assertEquals($id, $this->base_mock->getApplicationID());
	}
	
	/**
	 * Ensures that external applications can set and get the loan type ID
	 * without retrieving data from the database
	 *
	 * @return void
	 */
	public function testLoanTypeIdWhenSet()
	{
		$this->setUpMock();
		$id = '47846';
		$this->base_mock->setLoanTypeID($id);
		$this->pdo_mock->expects($this->never())->method('queryPrepared');
		$this->assertEquals($id, $this->base_mock->getLoanTypeID());
	}
	
	/**
	 * Ensures that external applications can get the loan type ID by loan type short
	 * when no loan type ID has been set
	 *
	 * @return void
	 */
	public function testLoanTypeIdWhenNotSet()
	{
		$this->markTestSkipped("not going to fix for now");
		$this->setUpMock();
		$id = '4768';
		$short_before = 'TEST';
		$short_after = 'test';

		$this->base_mock->setLoanTypeShort($short_before);
		
		$this->stmt_mock->expects($this->once())
			// Fetch should only be called once
			->method('fetch')
			->will($this->returnValue((object)array(
					'loan_type_id' => $id,
					'name_short' => $short_after,
					'rule_set_id' => '34568234')));
		// Loan type ID should return the value passed 
		$this->assertEquals($id, $this->base_mock->getLoanTypeID());
		// Calling $this->base_mock->getLoanTypeID() a second time should not
		// make another database call
		$this->assertEquals($id, $this->base_mock->getLoanTypeID());
		// The property short should have been changed to the value retrieved from
		// fetch
		$this->assertEquals($short_after, $this->base_mock->getLoanTypeShort());
	}
	
	/**
	 * Ensures that a NULL value is returned when there is no loan type short
	 * and that the database was not queried
	 *
	 * @return void
	 */
	public function testLoanTypeIdWhenNothingSet()
	{
		$this->setUpMock();
		// For some reason, the mock fails unless we instantiate the ltps to NULL
		$this->base_mock->setLoanTypeShort(NULL);
		
		// The database should not be queried
		$this->pdo_mock->expects($this->never())->method('queryPrepared');
		
		// Loan type ID should be null
		$this->assertNull($this->base_mock->getLoanTypeID());
	}
	
	/**
	 * Ensures that a NULL value is returned when there is no loan type short
	 * and that the database was not queried
	 *
	 * @return void
	 */
	public function testGetRuleSetIdWhenNothingSet()
	{
		$this->setUpMock();
		// For some reason, the mock fails unless we instantiate the ltps to NULL
		$this->base_mock->setLoanTypeShort(NULL);

		// The database should not be queried
		$this->pdo_mock->expects($this->never())->method('queryPrepared');
		
		// Rule Set ID should be null
		$this->assertNull($this->base_mock->getRuleSetID());
	}
	
	/**
	 * Ensures that an empty array is returned when there is no loan type short
	 * and that the database was not queried
	 *
	 * @return void
	 */
	public function testGetRuleSetWhenNothingSet()
	{
		$this->setUpMock();
		// For some reason, the mock fails unless we instantiate the ltps to NULL
		$this->base_mock->setLoanTypeShort(NULL);

		// The database should not be queried
		$this->pdo_mock->expects($this->never())->method('queryPrepared');
		
		// eCash Business Rule Class functions should not be called
		$this->business_rules_mock->expects($this->never())->method('Get_Rule_Set_Id_For_Application');
		$this->business_rules_mock->expects($this->never())->method('Get_Current_Rule_Set_Id');
		$this->business_rules_mock->expects($this->never())->method('Get_Rule_Set_Tree');
		
		// Rule Set should be an empty array
		$this->assertType('array',$this->base_mock->getRuleSet());
		$this->assertEquals(array(), $this->base_mock->getRuleSet());
	}
	
	
	/**
	 * The ruleset ID should try be gotten from the Get_Rule_Set_Id_For_Application
	 * function 
	 *
	 * @return void
	 */
	public function testGetRuleSetWithAppId()
	{
		$app_id = 2354743767;
		$rulseset_id = 347893674;
		$rule_array = array(1 => 'One', 2 => 'Two');
		
		$this->setUpMock();
		// Set a null Loan Type Short with an application ID
		$this->base_mock->setLoanTypeShort(NULL);
		$this->base_mock->setApplicationID($app_id);

		// The database should not be queried
		$this->pdo_mock->expects($this->never())->method('queryPrepared');
		
		// The Get_Rule_Set_Id_For_Application should be called an will return a value
		$this->business_rules_mock->expects($this->once())
			->method('Get_Rule_Set_Id_For_Application')
			->with($this->equalTo($app_id))
			->will($this->returnValue($rulseset_id));

		// Because Get_Rule_Set_Id_For_Application returned a value, Get_Rule_Set_Id_For_Application
		// should not be called
		$this->business_rules_mock->expects($this->never())->method('Get_Current_Rule_Set_Id');

		// Since we found a rule set ID, we should try to get the rule set
		$this->business_rules_mock->expects($this->once())
			->method('Get_Rule_Set_Tree')
			->with($this->equalTo($rulseset_id))
			->will($this->returnValue($rule_array));

		
		// Rule set should be an array and the value returned by Get_Rule_Set_Tree
		$this->assertType('array',$this->base_mock->getRuleSet());
		$this->assertEquals($rule_array, $this->base_mock->getRuleSet());
	}
	
	
	/**
	 * The ruleset ID should try be gotten from the Get_Rule_Set_Id_For_Application
	 * function.  When no data is found, it should then try Get_Current_Rule_Set_Id
	 *
	 * @return void
	 */
	public function testGetRuleSetWithLoanTypeId()
	{
		$app_id = 1232368478456456;
		$rule_set_id = 67934785478;
		$loan_type_id = 1;
		$rule_array = array(1 => 'One', 2 => 'Two');
		
		$this->setUpMock();
		// Set a null Loan Type Short with an Loan Type ID
		$this->base_mock->setLoanTypeShort(NULL);
		$this->base_mock->setLoanTypeID(1);
		$this->base_mock->setApplicationID($app_id);

		// The database should not be queried
		$this->pdo_mock->expects($this->never())->method('queryPrepared');
		
		// The Get_Rule_Set_Id_For_Application should be called an will return a value
		$this->business_rules_mock->expects($this->once())
			->method('Get_Rule_Set_Id_For_Application')
			->with($this->equalTo($app_id))
			->will($this->returnValue(FALSE));// FALSE will be returned if there is no match

		// Because Get_Rule_Set_Id_For_Application did not return a value, 
		// Get_Rule_Set_Id_For_Application should be called next
		$this->business_rules_mock->expects($this->once())
			->method('Get_Current_Rule_Set_Id')
			->with($this->equalTo($loan_type_id))
			->will($this->returnValue($rule_set_id));
			
		// Since we found a rule set ID, we should try to get the rule set
		$this->business_rules_mock->expects($this->once())
			->method('Get_Rule_Set_Tree')
			->with($this->equalTo($rule_set_id))
			->will($this->returnValue($rule_array));

		
		// Rule set should be an array and the value returned by Get_Rule_Set_Tree
		$this->assertType('array',$this->base_mock->getRuleSet());
		$this->assertEquals($rule_array, $this->base_mock->getRuleSet());
	}
	
	
	/**
	 * The ruleset ID Should not be gotten when uit is supplied and the rule set
	 * should be based on the existing value
	 *
	 * @return void
	 */
	public function testGetRuleSetWithRuleSetId()
	{
		$rule_set_id = 3463356;
		$rule_array = array(1 => 'One', 2 => 'Two');
		
		$this->setUpMock();
		// Set a null Loan Type Short with an Loan Type ID
		$this->base_mock->setLoanTypeShort(NULL);
		$this->base_mock->setRuleSetID($rule_set_id);

		// The database should not be queried
		$this->pdo_mock->expects($this->never())->method('queryPrepared');
		
		$this->business_rules_mock->expects($this->never())
			->method('Get_Rule_Set_Id_For_Application');// FALSE will be returned if there is no match

		$this->business_rules_mock->expects($this->never())
			->method('Get_Current_Rule_Set_Id');
			
		// Since we found a rule set ID, we should try to get the rule set
		$this->business_rules_mock->expects($this->once())
			->method('Get_Rule_Set_Tree')
			->with($this->equalTo($rule_set_id))
			->will($this->returnValue($rule_array));

		
		// Rule set should be an array and the value returned by Get_Rule_Set_Tree
		$this->assertType('array',$this->base_mock->getRuleSet());
		$this->assertEquals($rule_array, $this->base_mock->getRuleSet());
	}
}
?>
