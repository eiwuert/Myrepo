<?php
require_once('blackbox_test_setup.php');

/**
 * Tests for the Blackbox_StateData class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Blackbox_StateDataTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests that we can setup a StateData object with data.
	 *
	 * @return void
	 */
	public function testStateDataWithData()
	{
		$immutable_data = 'cantchangeme';
		$mutable_data = 'changeme';
		
		$state_data = new StateDataTest(
			array(
				'immutable_key' => $immutable_data,
				'mutable_key' => $mutable_data
			)
		);
		
		$this->assertEquals($immutable_data, $state_data->immutable_key);
		$this->assertEquals($mutable_data, $state_data->mutable_key);
	}
	
	/**
	 * Tests that we can set a mutable key.
	 *
	 * @return void
	 */
	public function testSetMutableKeys()
	{
		$state_data = new StateDataTest(
			array(
				'immutable_key' => 'cantchangeme',
				'mutable_key' => 'changeme'
			)
		);
		
		$new_value = 'I changed you!';
		$state_data->mutable_key = $new_value;
		$this->assertEquals($new_value, $state_data->mutable_key);
	}
	
	/**
	 * Data provider for testSetUnsettableKeys.
	 *
	 * @return array
	 */
	public static function setUnsettableKeysDataProvider()
	{
		return array(
			array('immutable_key'),
			array('nonexistant_key')
		);
	}
	
	/**
	 * Tests that we get an exception when we try and change an immutable value
	 * or change a key that doesn't exist.
	 *
	 * @param string $key the key we're testing with
	 * @dataProvider setUnsettableKeysDataProvider
	 * @expectedException InvalidArgumentException
	 * @return void
	 */
	public function testSetUnsettableKeys($key)
	{
		$state_data = new StateDataTest(
			array(
				'immutable_key' => 'cantchangeme',
				'mutable_key' => 'changeme'
			)
		);
		
		$new_value = 'I tried to change you!';
		$state_data->$key = $new_value;
	}
	
	/**
	 * Data provider for testStateDataExceptions.
	 *
	 * @return array
	 */
	public static function stateDataExceptionDataProvider()
	{
		return array(
			array(array('no string keys')),
			array(array('one_string_key' => 'test', 'one_not_string_key')),
			array('not an array'),
			array(array('nonexistant_key' => 'test'))
		);
	}
	
	/**
	 * Tests the various scenarios where we will throw an exception.
	 *
	 * @param array $data the data being passed to Blackbox_StateData
	 * @dataProvider stateDataExceptionDataProvider
	 * @expectedException InvalidArgumentException
	 * @return void
	 */
	public function testStateDataExceptions($data)
	{
		$state_data = new Blackbox_StateData($data);
	}
	
	/**
	 * Tests that we can add a state data object inside another and get that information out.
	 * 
	 * We add two state_data objects to the root state_data class. And see that we can retreive
	 * information from both of them.
	 *
	 * @return void
	 */
	public function testAddStateData()
	{
		$state_data = new StateDataTest(
			array(
				'immutable_key' => 'cantchangeme',
				'mutable_key' => 'changeme'
			)
		);
		
		$embedded_data = 'I am beside myself';
		$embedded_state_data = new StateDataTest(
			array(
				'embedded_data' => $embedded_data
			)
		);
		
		$second_embedded_data = 'I am beside myself again';
		$second_embedded_state_data = new StateDataTest(
			array(
				'second_embedded_data' => $second_embedded_data
			)
		);
		
		// Adding the data is a pretty straight forward function, so not testing it specifically
		$state_data->addStateData($embedded_state_data);
		$state_data->addStateData($second_embedded_state_data);
		
		$this->assertEquals($embedded_data, $state_data->embedded_data);
		$this->assertEquals($second_embedded_data, $state_data->second_embedded_data);
	}
	
	/**
	 * Tests that if we have duplicate information in a sub state date object, that we get the top
	 * level value.
	 *
	 * @return void
	 */
	public function testAddStateDataDuplicateData()
	{
		$root_value = 'changeme';
		$state_data = new StateDataTest(
			array(
				'immutable_key' => 'cantchangeme',
				'mutable_key' => $root_value
			)
		);
		
		$mutable_key = 'I am beside myself';
		$embedded_state_data = new StateDataTest(
			array(
				'mutable_key' => $mutable_key
			)
		);
		
		// Adding the data is a pretty straight forward function, so not testing it specifically
		$state_data->addStateData($embedded_state_data);
		
		$this->assertEquals($root_value, $state_data->mutable_key);
	}
	
	/**
	 * Tests that we get back a NULL if the value doesn't exist.
	 *
	 * @return void
	 */
	public function testGetNonExistantVariable()
	{
		$state_data = new Blackbox_StateData();
		
		$value = $state_data->nonexistant_key;
		$this->assertNull($value);
	}
	
	/**
	 * Data provider for the testIsset function.
	 *
	 * @return array
	 */
	public static function issetDataProvider()
	{
		return array(
			array('immutable_key', TRUE),
			array('mutable_key', TRUE),
			array('nonexistant_key', FALSE)
		);
	}
	
	/**
	 * Tests the overloaded __isset function.
	 *
	 * @param string $key the key to check
	 * @param bool $isset whether that key should be set
	 * @dataProvider issetDataProvider
	 * @return void
	 */
	public function testIsset($key, $isset)
	{
		$state_data = new StateDataTest(array('immutable_key' => 'cantchangeme'));
		$embedded_state_data = new StateDataTest(array('mutable_key' => 'changeme'));
		$state_data->addStateData($embedded_state_data);
		
		$this->assertSame(isset($state_data->$key), $isset);
	}

	/**
	 * Tests that we can set a value on a mutable key on a sub-state data object.
	 *
	 * @return void
	 */
	public function testSetOnSubData()
	{
		$state_data = new Blackbox_StateData();
		$embedded_state_data = new StateDataTest();
		
		$state_data->addStateData($embedded_state_data);
		$state_data->embedded_data = 'foo';
		
		$this->assertEquals('foo', $state_data->embedded_data);
	}
	
	/**
	 * Data provider for testSetUnsettableKeysOnSubData test.
	 *
	 * @return array
	 */
	public static function setUnsettableKeysOnSubDataProvider()
	{
		return array(
			array('immutable_key'),
			array('nonexistent_key')
		);
	}
	
	/**
	 * Tests that we still get an exception when we have sub-state data.
	 *
	 * @param string $key the key to test
	 * @expectedException InvalidArgumentException
	 * @dataProvider setUnsettableKeysOnSubDataProvider
	 * @return void
	 */
	public function testSetUnsettableKeysOnSubData($key)
	{
		$state_data = new Blackbox_StateData();
		$embedded_state_data = new StateDataTest(array('immutable_key' => 'cannot change me'));
		$state_data->addStateData($embedded_state_data);
		
		$state_data->$key = "I won't work!";
	}
}

/**
 * Stub class that overloads the Blackbox_StateData constructor so we can specify immutable
 * and mutable keys.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class StateDataTest extends Blackbox_StateData
{
	/**
	 * StateDataTest constructor.
	 *
	 * @param array $data the data we're populating inside state data
	 */
	public function __construct(array $data = NULL)
	{
		$this->immutable_keys[] = 'immutable_key';
		$this->mutable_keys[] = 'embedded_data';
		$this->mutable_keys[] = 'second_embedded_data';
		$this->mutable_keys[] = 'mutable_key';
		parent::__construct($data);
	}
}
?>
