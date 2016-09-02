<?php
/** PHPUnit test class for the DataX_Config class.
 *
 * @author Adam L. Englander <adam.englander@sellingsource.com>
 */
class DataX_ConfigTest extends PHPUnit_Framework_TestCase
{
	const TEST_SOURCE_ID = -99999;
	
	/** Check the getSourceId function
	 *
	 * @return void
	 */
	public function testGetSourceIdFromCallType()
	{
		$source_id = DataX_Config::getSourceId('unit-test-call-type');
		$this->assertEquals(self::TEST_SOURCE_ID, $source_id);
	}
	
	/** Checks the INVALID packet to make sure functions return NULL.
	 *
	 * @return void
	 */
	public function testGetCallTypeFromSourceId()
	{
		$call_type = DataX_Config::getCallTypeFromSourceId(self::TEST_SOURCE_ID);
		$this->assertEquals('unit-test-call-type', $call_type);
	}
	
	/** Check getting a call type with a bad source ID
	 *
	 * @return void
	 */
	public function testBadSourceId()
	{
		$call_type = DataX_Config::getCallTypeFromSourceId(-1000000000000);
		$this->assertEquals('UNDEFINED', $call_type);
	}
	
	/** Check getting a cource id with a bad source call type
	 *
	 * @return void
	 */
	public function testBadCallType()
	{
		$source_id = DataX_Config::getSourceId('this-is-a-bad-call-type-for-testing-purposes');
		$this->assertEquals(FALSE, $source_id);
	}
	
	/**
	 * Check getting a DataX type from a Source ID
	 * 
	 * @return void
	 */
	public function testGetDataxType()
	{
		$type = DataX_Config::getDataxType(self::TEST_SOURCE_ID);
		$this->assertEquals(DataX_Config::DATAX_TYPE_IDV,$type);
	}
}
?>
