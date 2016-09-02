<?php
require_once ('test_setup.php');

class ECash_ConfigTest extends PHPUnit_Framework_TestCase
{

	/**
	 * @var ECash_Config
	 */
	protected $config;

	protected function setUp()
	{
		$this->config = new ECash_ConfigTest_Override(new ECash_ConfigTest_Base());
	}

	protected function tearDown()
	{
		$this->config = NULL;
	}

	/**
	 * @expectedException ECash_ConfigTest_CheckForClassCalledException
	 */
	public function testInitCalledOnConstruct()
	{
		new ECash_ConfigTest_InitCall();
	}

	/**
	 * This test is not permanent. The code being tested SHOULD be throwing
	 * an exception. When this behavior is changed this test can be changed
	 * as well.
	 */
	public function testGetNonExistantValue()
	{
		$this->assertNull($this->config->bad_value);
	}

	public function testGetBaseValue()
	{
		$this->assertEquals('test2', $this->config->val2);
	}

	public function testGetOverriddenValue()
	{
		$this->assertEquals('test4', $this->config->val1);
	}

	public function testGetOverriddenValueNotInBase()
	{
		$this->assertEquals('test5', $this->config->val4);
	}

	public function testNonExistValueIsSet()
	{
		$this->assertFalse(isset($this->config->bad_value));
	}

	public function testBaseValueIsSet()
	{
		$this->assertTrue(isset($this->config->val2));
	}

	public function testOverriddenValueIsSet()
	{
		$this->assertTrue(isset($this->config->val1));
	}

	public function testOverriddenValueNotInBaseIsSet()
	{
		$this->assertTrue(isset($this->config->val4));
	}
	
	public function testValidGetDbConfig()
	{
		$this->assertEquals(new ECash_ConfigTest_DBConfigMock(), $this->config->getDbConfig('val3'));
	}
	
	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testGetDbConfigNonExistantValue()
	{
		$this->config->getDbConfig('bad_value');
	}
		
	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testGetDbConfigNonDBConfigValue()
	{
		$this->config->getDbConfig('val1');
	}
}

class ECash_ConfigTest_CheckForClassCalledException extends Exception
{
}

class ECash_ConfigTest_InitCall extends ECash_Config
{

	protected function init()
	{
		throw new ECash_ConfigTest_CheckForClassCalledException();
	}
}

class ECash_ConfigTest_Base extends ECash_Config
{

	protected function init()
	{
		$this->configVariables['val1'] = 'test';
		$this->configVariables['val2'] = 'test2';
		$this->configVariables['val3'] = new ECash_ConfigTest_DBConfigMock();
	}
}

class ECash_ConfigTest_Override extends ECash_Config
{

	protected function init()
	{
		$this->configVariables['val1'] = 'test4';
		$this->configVariables['val4'] = 'test5';
	}
}

class ECash_ConfigTest_DBConfigMock implements DB_IDatabaseConfig_1
{

	public function getConnection()
	{
	}
}

?>