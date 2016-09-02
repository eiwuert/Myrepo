<?php
/**
 * Unit tests for VendorAPI_CFE_Conditions_StatusHistoryIncludes
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class VendorAPI_CFE_Conditions_StatusHistoryIncludesTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var ECash_CFE_IContext
	 */
	private $context;

	/**
	 * Set up the mocks for the tests
	 */
	public function setUp()
	{
		$this->context = $this->getMock('ECash_CFE_IContext');
	}

	/**
	 * Reset the mocks 
	 */
	public function tearDown()
	{
		$this->context = NULL;
	}

	/**
	 * Data provider for all tests
	 * @return array
	 */
	public function dataProvider()
	{
		$status1 = "status1";
		$status2 = "status2";
		$status3 = "status3";
		$not_found = "not found";
		
		$normal_history = array(
			array('name' => $status1, 'created' => 1111111),
			array('name' => $status2, 'created' => 2222222),
			array('name' => $status3, 'created' => 3333333),
			array('name' => $status3, 'created' => 4444444),
		);
		
		$object_history = new ArrayObject();
		
		foreach ($normal_history as $row)
		{
			$object = new StdClass();
			$object->name = $row['name'];
			$object->created = $row['date'];
			$object_history->append($object);
		}
		
		$empty_history = array();
		
		return array(
			array($object_history, $status1, TRUE),
			array($object_history, $status2, TRUE),
			array($object_history, $status3, TRUE),
			array($object_history, $not_found, FALSE),
			array($normal_history, $status1, TRUE),
			array($normal_history, $status2, TRUE),
			array($normal_history, $status3, TRUE),
			array($normal_history, $not_found, FALSE),
			array($empty_history, $status1, FALSE),
			array($empty_history, $status2, FALSE),
			array($empty_history, $status3, FALSE),
			array($empty_history, $not_found, FALSE),
		);
	}

	/**
	 * Test that isValid returnes the expected results for particular
	 * status/history compbinations
	 * 
	 * @dataProvider dataProvider
	 * @param array $history Status history
	 * @param string $status
	 * @param bool $expected
	 */
	public function testIsValid($history, $status, $expected)
	{
		// Have the mocked context return the provided history 
		$this->context->status_history = $history;
		// Instantiate the condition object with the provided status
		$condition = new VendorAPI_CFE_Conditions_StatusHistoryIncludes($status);
		// Determine if the response for isValid matches the expected result
		$this->assertEquals($expected, $condition->isValid($this->context));
	}
}