<?php
require_once('OLPBlackboxTestSetup.php');

/**
 * PHPUnit test class for the OLPBlackbox_Rule_NoChecksSSN class.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class OLPBlackbox_Rule_NoChecksSSNTest extends PHPUnit_Extensions_Database_TestCase
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
	 * Data provider for the testNoChecksSSN test.
	 *
	 * @return array
	 */
	public static function noChecksSSNDataProvider()
	{
		return array(
			array('111225555', TRUE), // SSN Found, Pass Rule
			array('999999999', FALSE), // SSN Not Found, Fail Rule
		);
	}

	/**
	 * Tests the no checks ssn rule to make sure the correct response is returned.
	 *
	 * @param string $ssn the ssn to use for the test
	 * @param bool $expected_value the expected value returned from isValid
	 * @dataProvider noChecksSSNDataProvider
	 * @return void
	 */
	public function testNoChecksSSN($ssn, $expected_value)
	{
		$data = new OLPBlackbox_Data();
		$data->social_security_number = $ssn;
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock(
			'OLPBlackbox_Rule_NoChecksSSN',
			array('getDbInstance', 'getDbName', 'hitEvent', 'hitStat')
		);
		// Return our db instance
		$rule->expects($this->any())->method('getDbInstance')
			->will($this->returnValue(TEST_DB_MYSQL4()));
		// Return our db name
		$rule->expects($this->any())->method('getDbName')
			->will($this->returnValue(TEST_GET_DB_INFO()->name));

		$rule->setupRule(
			array(
				Blackbox_StandardRule::PARAM_FIELD => 'social_security_number',
			)
		);

		$valid = $rule->isValid($data, $state_data);

		$this->assertSame($expected_value, $valid);
	}

	/**
	 * Gets the database connection for this test.
	 *
	 * @return PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection
	 */
	protected function getConnection()
	{
		return $this->createDefaultDBConnection(TEST_DB_PDO(), TEST_GET_DB_INFO()->name);
	}

	/**
	 * Gets the data set for this test.
	 *
	 * @return PHPUnit_Extensions_Database_DataSet_XmlDataSet
	 */
	protected function getDataSet()
	{
		return $this->createXMLDataSet(dirname(__FILE__).'/_fixtures/NoChecksSSN.fixture.xml');
	}
}
?>

