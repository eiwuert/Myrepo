<?php

/**
 * Simple tests for the autoloader
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class AutoLoad1Test extends PHPUnit_Framework_TestCase
{
	/**
	 * @var AutoLoadMock
	 */
	protected static $al1;

	/**
	 * @var AutoLoadMock
	 */
	protected static $al2;

	/**
	 * @var string
	 */
	protected $include_path;

	/**
	 * Does setup stuff
	 * @return void
	 */
	public function setUp()
	{
		if (!self::$al1)
		{
			self::$al1 = new AutoLoadMock();
			AutoLoad_1::addLoader(self::$al1, (LIB_AUTOLOAD_PRIORITY - 2));
		}

		if (!self::$al2)
		{
			self::$al2 = new AutoLoadMock();
			AutoLoad_1::addLoader(self::$al2, (LIB_AUTOLOAD_PRIORITY - 1));
		}

		// preserve include path, we'll reset it in tearDown
		$this->include_path = get_include_path();
	}

	/**
	 * Does teardown stuff
	 * @return void
	 */
	public function tearDown()
	{
		self::$al1->clear();
		self::$al2->clear();

		set_include_path($this->include_path);
	}

	/**
	 * Tests that addSearchPath will not add paths that don't exist
	 *
	 */
	public function testAddSearchPathFiltersBadPaths()
	{
		AutoLoad_1::addSearchPath('/hahahahahaha');

		$actual = get_include_path();
		$this->assertEquals($this->include_path, $actual);
	}

	/**
	 * Ensure that we're not adding relative paths
	 *
	 */
	public function testAddSearchPathUsesRealPaths()
	{
		$path = '../DB/';
		$expected = $this->include_path.':'.realpath($path);

		AutoLoad_1::addSearchPath($path);
		$actual = get_include_path();

		$this->assertEquals($expected, $actual);
	}

	/**
	 * Tests that the loader with the lower priority gets called first
	 * Also tests that the second loader doesn't get called... no way around that
	 * @return void
	 */
	public function testLoaderWithLowerPriorityGetsCalledFirst()
	{
		$al1 = $this->getMock(
			'AutoLoad_1',
			array('load')
		);
		$al1->expects($this->once())
			->method('load')
			->will($this->returnValue(TRUE));

		$al2 = $this->getMock(
			'AutoLoad_1',
			array('load')
		);
		$al2->expects($this->never())
			->method('load');

		// add with lower priority
		self::$al1->setLoader($al1);
		self::$al2->setLoader($al2);

		AutoLoad_1::runLoad('test');
	}

	/**
	 * Tests that the loader with the lower priority gets called first
	 * Also tests that the second loader doesn't get called... no way around that
	 * @return void
	 */
	public function testSubsequentLoadersGetCalledIfFirstReturnsFalse()
	{
		$al1 = $this->getMock(
			'AutoLoad_1',
			array('load')
		);
		$al1->expects($this->once())
			->method('load')
			->will($this->returnValue(FALSE));

		$al2 = $this->getMock(
			'AutoLoad_1',
			array('load')
		);
		$al2->expects($this->once())
			->method('load')
			->will($this->returnValue(TRUE));

		// add with lower priority
		self::$al1->setLoader($al1);
		self::$al2->setLoader($al2);

		AutoLoad_1::runLoad('test');
	}

	/**
	 * Provides proper class->file name mappings
	 * @return array
	 */
	public static function classNameProvider()
	{
		return array(
			array('DB_IConnection_1', 'DB/IConnection.1.php'),
			array('ECash_Test', 'ECash/Test.php'),
		);
	}

	/**
	 * Tests the class name to file name conversion
	 *
	 * @dataProvider classNameProvider
	 * @param string $class
	 * @param string $file
	 */
	public function testClassNameToFile($class, $expected)
	{
		$file = AutoLoad_1::classToPath($class);
		$this->assertEquals($expected, $file);
	}
}

?>