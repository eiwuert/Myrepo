<?php
/**
 * Tests the functionality of the OLBlackbox_Rule_UsedABACheck class.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */

require_once('OLPBlackboxTestSetup.php');

/**
 * Class for testing OLBlackbox_Rule_UsedABACheck.
 *
 * @group used_aba_check
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Generic_Rule_UsedABACheckTest extends PHPUnit_Extensions_Database_TestCase
{
	/**
	 * Ensures that the connection is destroyed
	 * @return void
	 */
	public function tearDown()
	{
		parent::tearDown();
		$this->databaseTester = NULL;
	}

	/**
	 * Provides data for testUsedABACheck
	 *
	 * @return array Data for tests.
	 */
	public static function usedABACheckProvider()
	{
		return array(
			array(123456789, 555669999, 12345678901, TRUE),
			array(951753951, 555669999, 45685275321, FALSE)
		);
	}

	/**
	 * General test of OLBlackbox_Rule_UsedABACheck functionality.
	 *
	 * @param int $bank_aba aba number to run test with
	 * @param int $ssn ssn to run test with
	 * @param int $bank_account account number to test with
	 * @param bool $result expected result of the test
	 * @dataProvider UsedABACheckProvider
	 * @return void
	 */
	public function testUsedABACheck($bank_aba, $ssn, $bank_account, $result)
	{
		// vars to be set in data and state_data
		$target_name = 'UFC';
		$application_id = 33;

		// basic data structures needed for running rules
		$data = new OLPBlackbox_Data();
		$data->bank_aba = $bank_aba;
		$data->bank_account = $bank_account;
		$data->social_security_number = $ssn;
		$data->application_id = $application_id;

		// set up the event_log so we can expect a method call (Log_Event)
		$log = $this->getMock('Event_Log',
			array('Log_Event')
		);
		$log->expects($this->once())->method('Log_Event')->with(
			$this->equalTo(OLPBlackbox_Config::EVENT_USED_ABA_CHECK),
			$this->equalTo($result ? OLPBlackbox_Config::EVENT_RESULT_PASS : OLPBlackbox_Config::EVENT_RESULT_FAIL),
			$this->equalTo($target_name),
			$application_id,
			OLPBlackbox_Config::getInstance()->blackbox_mode
		);

		// replace the event log with our mock version, but save the old log for after the test.
		$old_log = OLPBlackbox_Config::getInstance()->event_log;
		unset(OLPBlackbox_Config::getInstance()->event_log);
		OLPBlackbox_Config::getInstance()->event_log = $log;

		$init_data = array('name' => $target_name, 'target_name' => $target_name);
		$state_data = new OLPBlackbox_TargetStateData($init_data);

		// db connection to use instead of live one
		$test_ldb = TEST_DB_MYSQLI();

		// mock the used info, see the OLPBlackbox_Enterprise_Generic_Rule_UsedABACheck
		// class for why it requires names in the constructor.
		$rule = $this->getMock(
			'OLPBlackbox_Enterprise_Generic_Rule_UsedABACheck',
			array('getLdb'),
			array(array('ufc'))
		);
		$rule->expects($this->any())->method('getLdb')->will($this->returnValue($test_ldb));

		$rule->setNow('1 February 2008');

		$this->assertEquals($result, $rule->isValid($data, $state_data));

		unset(OLPBlackbox_Config::getInstance()->event_log);
		OLPBlackbox_Config::getInstance()->event_log = $old_log;
	}

	/**
	 * Gets the data for this test
	 *
	 * @return PHPUnit_Extensions_Database_DataSet_XmlDataSet
	 */
	protected function getDataSet()
	{
		return $this->createXMLDataSet(dirname(__FILE__).'/_fixtures/UsedABACheck.fixture.xml');
	}

	/**
	 * Gets the database PDO connection for this test, so PHPUnit can set the database up.
	 *
	 * @return unknown
	 */
	protected function getConnection()
	{
		return $this->createDefaultDBConnection(TEST_DB_PDO_LDB(), TEST_GET_DB_INFO()->ldb_name);
	}
}
?>
