<?php
/**
 * Tests the OLPBlackbox_StateData_CombineKeyTest class
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class OLPBlackbox_StateData_CombineKeyTest extends PHPUnit_Framework_TestCase
{

	/**
	 * Tests adding a single item
	 * @return void
	 */
	public function testAddItem()
	{
		$item_before = 'TEST ACTION';
		
		$obj = new OLPBlackbox_StateData_CombineKey();
		$obj->addDataItem($item_before);
		$item_array = $obj->getData();
		$item_after = $item_array[0];
		
		$this->assertEquals($item_before, $item_after);
		$this->assertEquals(1,count($item_array));
	}

	/**
	 * Tests adding a set of items
	 * @return void
	 */
	public function testAdd()
	{
		$before = array(1, 2, 'three', new stdClass());
		
		$obj = new OLPBlackbox_StateData_CombineKey();
		$obj->addData($before);
		$after = $obj->getData();
		
		$this->assertEquals($before, $after);
	}

	/**
	 * Test combine after add
	 * @return void
	 */
	public function testCombineMulti()
	{
		$obj1 = new OLPBlackbox_StateData_CombineKey();
		$array1 = array(1, 2, 3, 4, 4, 'four');
		$obj1->addData($array1);
		$obj2 = new OLPBlackbox_StateData_CombineKey();
		$array2 = array(5, 5, 6, 6, 6, 6, 7, 8, new stdClass(), array('blah'));
		$obj2->addData($array2);
		
		$combined_array = array_merge($array1, $array2);
		
		$this->assertEquals($combined_array, $obj1->combine($obj2)->getData());
	}
	
	/**
	 * Test combine with blank multi
	 * @return void
	 */
	public function testCombineMultiBlank()
	{
		$obj1 = new OLPBlackbox_StateData_CombineKey();
		$array = array(1, 2, 3, 4, 4, 'four');
		$obj1->addData($array);
		$obj2 = new OLPBlackbox_StateData_CombineKey();
		
		$this->assertEquals($array, $obj1->combine($obj2)->getData());
	}
		
	/**
	 * Test construct
	 * @return void
	 */
	public function testConstruct()
	{
		$array1 = array(1, 2, 3, 4, 4, 'four');
		$obj = new OLPBlackbox_StateData_CombineKey($array1);
		$array2 = array(5, 5, 6, 6, 6, 6, 7, 8, new stdClass(), array('blah'));
		$obj->addData($array2);
		
		$merged_arrays = array_merge($array1, $array2);
		
		$this->assertEquals($merged_arrays, $obj->getData());
	}
}

?>