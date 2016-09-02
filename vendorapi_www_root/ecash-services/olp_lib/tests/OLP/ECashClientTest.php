<?php

/**
 * An extension of OLP_ECashClient for unit testing purposes.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class UNITTEST_OLP_ECashClient extends OLP_ECashClient
{
	/**
	 * A way to rerun the constructor since we cannot mocked these objects
	 * during construction of the class.
	 *
	 * @return void
	 */
	public function reload()
	{
		$this->drivers = $this->loadDrivers();
		$this->method_list = $this->loadDriverMethods();
	}
	
	/**
	 * Returns the protected drivers.
	 *
	 * @return array
	 */
	public function getDrivers()
	{
		return $this->drivers;
	}
	
	/**
	 * Returns the protected method list.
	 *
	 * @return array
	 */
	public function getMethodList()
	{
		return $this->method_list;
	}
}

/**
 * A simple test class used for mocking a driver.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class UNITTEST_OLP_ECashClient_Driver implements OLP_ECashClient_IDriver
{
	/**
	 * The constructor for the possible adapters should only take the
	 * same requirements of the facade.
	 *
	 * @param string $mode
	 * @param string $property_short
	 */
	public function __construct($mode, $property_short)
	{
		// Don't need to do anything, as everything should be mocked.
	}
	
	/**
	 * Returns a verbose description of the driver in human readable form.
	 *
	 * @return string
	 */
	public function getDriverDescription()
	{
		throw new Exception("Unittest method was not mocked: " . __METHOD__);
	}
	
	/**
	 * Gets a simple listing of all methods that this class will handle. The
	 * returned array will just be a listing of method names.
	 *
	 * @return array
	 */
	public function getMethodList()
	{
		throw new Exception("Unittest method was not mocked: " . __METHOD__);
	}
	
	/**
	 * Gets a more verbose listing of all methods that can fully describe the
	 * API. The returned array will be a listing of methods that contain a
	 * subarray that fully describe each method in human readable form.
	 *
	 * array()
	 *   array()
	 *     name => string
	 *     parameters => string
	 *     comments => string
	 *
	 * @return array
	 */
	public function getVerboseMethodList()
	{
		throw new Exception("Unittest method was not mocked: " . __METHOD__);
	}
}

/**
 * Tests the OLP_ECashClient_Encryption class.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_ECashClientTest extends PHPUnit_Framework_TestCase
{
	const TEST_PROPERTY_SHORT = 'test';
	const TEST_MODE = 'unittest';
	
	/**
	 * A random method name generator, because some classes need them.
	 *
	 * @return string
	 */
	protected function getRandomMethodName()
	{
		return "fakeMethod" . sha1(uniqid(mt_rand()));
	}
	
	/**
	 * This test just makes sure that reload it working fine, and that all
	 * the functions in the class can support non-array returns even though
	 * everything should be returning an array.
	 *
	 * @return void
	 */
	public function XXXtestReloadInExtendedUnittestClass()
	{
		$test_driver = $this->getMock(
			'UNITTEST_OLP_ECashClient_Driver',
			array(
				'getMethodList',
			),
			array(
				self::TEST_MODE,
				self::TEST_PROPERTY_SHORT,
			)
		);
		
		$class_names = array(get_class($test_driver));
		
		$ecash_client = $this->getMock(
			'UNITTEST_OLP_ECashClient',
			array(
				'loadPossibleClassNames',
			),
			array(
				self::TEST_MODE,
				self::TEST_PROPERTY_SHORT,
			)
		);
		$ecash_client->expects($this->once())
			->method('loadPossibleClassNames')
			->will($this->returnValue($class_names));
		$ecash_client->reload();
	}
	
	/**
	 * Data provider for testLoadDrivers().
	 *
	 * @return array
	 */
	public static function dataProviderLoadDrivers()
	{
		return array(
			array(0, 0),
			array(1, 0),
			array(0, 1),
			array(1, 1),
			array(5, 0),
			array(0, 5),
			array(5, 5),
		);
	}
	
	/**
	 * Tests loadDrivers().
	 *
	 * @dataProvider dataProviderLoadDrivers
	 *
	 * @param int $num_valid_classes
	 * @param int $num_invalid_classes
	 * @return void
	 */
	public function testLoadDrivers($num_valid_classes, $num_invalid_classes)
	{
		// Convert all class names to mocked class names
		$class_names = array();
		$valid_class_names = array();
		$invalid_class_names = array();
		
		for ($i = 0; $i < $num_valid_classes; $i++)
		{
			$mocked_driver = $this->getMock(
				'UNITTEST_OLP_ECashClient_Driver',
				array(
					$this->getRandomMethodName(),
				),
				array(
					self::TEST_MODE,
					self::TEST_PROPERTY_SHORT,
				)
			);
			
			$valid_class_names[] = get_class($mocked_driver);
		}
		for ($i = 0; $i < $num_invalid_classes; $i++)
		{
			$invalid_class_names[] = 'stdClass';
		}
		
		$class_names = array_merge($valid_class_names, $invalid_class_names);
		
		$ecash_client = $this->getMock(
			'UNITTEST_OLP_ECashClient',
			array(
				'loadPossibleClassNames',
				'loadDriverMethods',
			),
			array(
				self::TEST_MODE,
				self::TEST_PROPERTY_SHORT,
			)
		);
		$ecash_client->expects($this->once())
			->method('loadPossibleClassNames')
			->will($this->returnValue($class_names));
		$ecash_client->expects($this->once())
			->method('loadDriverMethods');
		
		$ecash_client->reload();
		
		$drivers = $ecash_client->getDrivers();
		
		$this->assertEquals($valid_class_names, array_keys($drivers), "Verify that we only loaded the valid drivers.");
		foreach ($drivers AS $driver_name => $driver)
		{
			$this->assertType($driver_name, $driver, "Each class should be an instance of the right mocked class.");
			$this->assertType('OLP_ECashClient_IDriver', $driver, "Each class should implement OLP_ECashClient_IDriver.");
		}
	}
	
	/**
	 * Returns a driver that is randomly generated with X number of methods.
	 * If you want, can require that all methods be called at least once.
	 *
	 * @param int $num_methods
	 * @param bool $will_methods_be_called
	 * @return array
	 */
	protected function getRandomDriver($num_methods, $will_methods_be_called = FALSE)
	{
		$return_data = array(
			'driver' => NULL,
			'methods' => array(),
		);
		
		for ($i = 0; $i < $num_methods; $i++)
		{
			$return_data['methods'][] = $this->getRandomMethodName();
		}
		
		$return_data['driver'] = $this->getMock(
			'UNITTEST_OLP_ECashClient_Driver',
			array_merge(array('getMethodList'), $return_data['methods']),
			array(self::TEST_MODE, self::TEST_PROPERTY_SHORT)
		);
		$return_data['driver']->expects($this->once())
			->method('getMethodList')
			->will($this->returnValue($return_data['methods']));
		
		$return_data['class_name'] = get_class($return_data['driver']);
		
		if ($will_methods_be_called)
		{
			foreach ($return_data['methods'] AS $method_name)
			{
				$return_data['driver']->expects($this->atLeastOnce())
					->method($method_name)
					->with($return_data['class_name'])
					->will($this->returnValue($method_name));
			}
		}
		
		return $return_data;
	}
	
	/**
	 * Tests loadDriverMethods().
	 *
	 * @return void
	 */
	public function testLoadDriverMethods()
	{
		$drivers = array();
		$methods = array();
		
		for ($i = 0; $i < 5; $i++)
		{
			$test_driver = $this->getRandomDriver($i);
			$drivers[$test_driver['class_name']] = $test_driver['driver'];
			$methods = array_merge($methods, $test_driver['methods']);
		}
		
		$ecash_client = $this->getMock(
			'UNITTEST_OLP_ECashClient',
			array(
				'loadDrivers',
			),
			array(
				self::TEST_MODE,
				self::TEST_PROPERTY_SHORT,
			)
		);
		$ecash_client->expects($this->once())
			->method('loadDrivers')
			->will($this->returnValue($drivers));
		$ecash_client->reload();
		
		$method_list = $ecash_client->getMethodList();
		
		$this->assertEquals($methods, array_keys($method_list), "The internal method list will contain all methods.");
	}
	
	/**
	 * Tests __call().
	 *
	 * @return void
	 */
	public function testCall()
	{
		$drivers = array();
		$methods = array();
		
		for ($i = 0; $i < 5; $i++)
		{
			$test_driver = $this->getRandomDriver($i, TRUE);
			$drivers[get_class($test_driver['driver'])] = $test_driver['driver'];
			
			foreach ($test_driver['methods'] AS $method)
			{
				$methods[$method] = $test_driver['class_name'];
			}
		}
		
		$ecash_client = $this->getMock(
			'UNITTEST_OLP_ECashClient',
			array(
				'loadDrivers',
			),
			array(
				self::TEST_MODE,
				self::TEST_PROPERTY_SHORT,
			)
		);
		$ecash_client->expects($this->once())
			->method('loadDrivers')
			->will($this->returnValue($drivers));
		$ecash_client->reload();
		
		foreach ($methods AS $method => $class_name)
		{
			$data = call_user_func_array(array($ecash_client, $method), array($class_name));
			$this->assertEquals($method, $data, "Each method requires its class name as the parameter and returns its own method name.");
		}
	}
}

?>
