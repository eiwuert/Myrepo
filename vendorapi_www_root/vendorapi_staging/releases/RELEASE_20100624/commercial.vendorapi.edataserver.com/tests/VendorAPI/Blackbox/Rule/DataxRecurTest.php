<?php

require_once('PHPUnit/Extensions/Database/TestCase.php');

class VendorAPI_Blackbox_Rule_DataxRecurTest extends PHPUnit_Extensions_Database_TestCase
{
	/**
	 * @var VendorAPI_Blackbox_Rule_DataxRecur
	 */
	protected $rule;
	
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
		$xml = $this->createFlatXmlDataSet($dir.'/_fixtures/datax_recur.xml');

		return new PHPUnit_Extensions_Database_DataSet_ReplacementDataSet(
			$xml, 
			array(
				'[[CURDATE]]' => date('Y-m-d H:i:s'), 
				'[[PASTDATE]]' => date('Y-m-d H:i:s', strtotime('-10 days')),
			)
		);
	}
	
	public function setup()
	{
		$this->markTestIncomplete('The datax recur fixture is no where to be found.');
		parent::setUp();
		$event_log = $this->getMock('VendorAPI_Blackbox_EventLog', array(), array(), '', FALSE);
		$this->rule = new VendorAPI_Blackbox_Rule_DataxRecur($event_log, $this->getMock('ECash_WebService_InquiryClient'), 5, 1, 'test_type');
	}
	
	public static function SsnData()
	{
		return array(
			array('', FALSE, 'Blank SSN'),
			array('123121234', TRUE, 'Failed SSN'),
			array('111223333', TRUE, 'Passed SSN'),
			array('123456789', TRUE, 'Failed SSN Too Far in the Past'),
		);
	}
	
	/**
	 * @dataProvider SsnData
	 */
	public function testVariousSsn($ssn, $expected, $message)
	{
		$data = new VendorAPI_Blackbox_Data();
		$data->ssn = $ssn;
		$this->assertEquals($expected, $this->rule->isValid($data, $this->getMock('Blackbox_IStateData')), $message);
	}
}

?>
