<?php

class VendorAPI_Blackbox_Rule_UsedABACheckTest extends PHPUnit_Extensions_Database_TestCase
{
	protected $_data;
	protected $_state;
	protected $_rule;

	public function getConnection()
	{
		return new PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection(
			getTestPDODatabase(),
			$GLOBALS['db_name']
		);
	}

	public function getDataset()
	{
		$dir = dirname(__FILE__);
		return new PHPUnit_Extensions_Database_DataSet_XmlDataSet($dir.'/_fixtures/used_info.xml');
	}

	public function setUp()
	{
		$this->markTestSkipped("Unable to test without refactoring tested class");
		parent::setUp();

		$db = getTestDatabase();

		$this->_data = new VendorAPI_Blackbox_Data();
		$this->_state = new VendorAPI_Blackbox_StateData();
		$log = $this->getMock('VendorAPI_Blackbox_EventLog', array(), array(), '', FALSE);

		$this->_rule = new VendorAPI_Blackbox_Rule_UsedABACheck(
			$log,
			1,
			'-1 year'
		);
	}

	public function tearDown()
	{
		$this->_rule = NULL;
		$this->_data = NULL;
		$this->_state = NULL;

		parent::tearDown();
	}

	public function testCannotRunWithoutABA()
	{
		$this->_data->loadFrom(array(
			'permutated_bank_account' => array(1),
			'ssn' => '123467890',
		));

		$this->assertFalse($this->_rule->canRun($this->_data, $this->_state));
	}

	public function testCannotRunWithoutAccount()
	{
		$this->_data->loadFrom(array(
			'bank_aba' => '123123123',
			'ssn' => '123467890',
		));

		$this->assertFalse($this->_rule->canRun($this->_data, $this->_state));
	}

	public function testCannotRunWithoutSSN()
	{
		$this->_data->loadFrom(array(
			'bank_aba' => '123123123',
			'permutated_bank_account' => array(1),
		));

		$this->assertFalse($this->_rule->canRun($this->_data, $this->_state));
	}

	public function testPassesOnException()
	{
		$db = $this->getMock('DB_IConnection_1');
		$db->expects($this->any())
			->method('prepare')
			->will($this->throwException(new Exception()));

		$log = $this->getMock('VendorAPI_Blackbox_EventLog', array(), array(), '', FALSE);
		$rule = new VendorAPI_Blackbox_Rule_UsedABACheck(
			$log
		);
	}

	public function testTwoOutsideOneYearPasses()
	{
		$this->_data->loadFrom(array(
			'bank_aba' => '123123123',
			'bank_account' => '123456789001',
			'ssn' => '123456000',
		));

		$valid = $this->_rule->isValid(
			$this->_data,
			$this->_state
		);
		$this->assertTrue($valid);
	}

	public function testTwoWithinOneYearFails()
	{
		$this->_data->loadFrom(array(
			'bank_aba' => '123123200',
			'bank_account' => '123456789002',
			'ssn' => '123452000',
		));

		$valid = $this->_rule->isValid(
			$this->_data,
			$this->_state
		);
		$this->assertFalse($valid);
	}

	public function testOneSSNIsAllowed()
	{
		$this->_data->loadFrom(array(
			'bank_aba' => '123123300',
			'bank_account' => '123456789003',
			'ssn' => '123453000',
		));

		$valid = $this->_rule->isValid(
			$this->_data,
			$this->_state
		);
		$this->assertTrue($valid);
	}

	/**
	 * Tests that the rule counts distincts SSNs across all databases
	 *
	 * In the past, the rule would count the number of distinct SSNs PER
	 * database; the same SSN existed in more than one database, it would
	 * be counted more than once.
	 *
	 * @return void
	 */
	public function testUniqueAcrossAllDatabases()
	{
		$db = getTestDatabase();

		$log = $this->getMock('VendorAPI_Blackbox_EventLog', array(), array(), '', FALSE);
		$rule = new VendorAPI_Blackbox_Rule_UsedABACheck(
			$log,
			1,
			'-1 year'
		);

		$this->_data->loadFrom(array(
			'bank_aba' => '123123300',
			'bank_account' => '123456789003',
			'ssn' => '123453000',
		));

		$valid = $rule->isValid(
			$this->_data,
			$this->_state
		);
		$this->assertTrue($valid);
	}
}

?>
