<?php
/**
 * Test case for the TSS_ArrayObject class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class TSS_ArrayObjectTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests offsetGet method.
	 *
	 * @return void
	 */
	public function testOffsetGet()
	{
		$array = new TSS_ArrayObject(array('test' => TRUE));
		
		$this->assertTrue($array['test']);
		$this->assertEquals(NULL, $array['not_there']);
	}
	
	/**
	 * Tests the get method.
	 *
	 * @return void
	 */
	public function testGet()
	{
		$array = new TSS_ArrayObject(array('test' => TRUE));
		
		$this->assertTrue($array->get('test', 'foo'));
		$this->assertEquals('foo', $array->get('not there', 'foo'));
	}
}
