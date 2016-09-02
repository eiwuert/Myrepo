<?php
/**
 * OLPBlackbox_Rule_DuplicateLead test case.
 * 
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class OLPBlackbox_Rule_DuplicateLeadTest extends PHPUnit_Framework_TestCase
{
	
	/**
	 * Test that when "do_datax_rework" is true in the blackbox config,
	 * the rule is skipped and isValid returns true
	 *
	 * @return void
	 */
	public function testReworkSkipsRule()
	{
		$config = new OLPBlackbox_Config();
		$config->do_datax_rework = TRUE;
		$rule = $this->getMock("OLPBlackbox_Rule_DuplicateLead", array("runRule", "onSkip", "getConfig"));
		$rule->expects($this->any())->method("getConfig")->will($this->returnValue($config));
		$rule->expects($this->never())->method("runRule");
		$rule->expects($this->once())->method("onSkip");
		$rule->isValid(new Blackbox_Data(),new Blackbox_StateData());
	}

	/**
	 * Test the basic functionality of the rule.  When there are no matching keys 
	 *
	 * @return void
	 */
	public function testReadWriteReadWrite()
	{	
		$rule_value = array("data_keys" => array("key1", "key2", "key3"));
		
		$memcache = new MemcacheSurrogate();
		
		$state_data = new OLPBlackbox_CampaignStateData(array("campaign_name" => "camp"));

		$blackbox_data = $this->getMock("Blackbox_Data", array("__get"));
		$blackbox_data->expects($this->any())
			->method("__get")
			->will($this->returnValue("Hello World"));

		$rule = $this->getMock("OLPBlackbox_Rule_DuplicateLead", array("onSkip", "getMemcache"));
		$rule->setRuleValue($rule_value);
		$rule->expects($this->never())->method("onSkip");
		$rule->expects($this->any())
			->method("getMemcache")
			->will($this->returnValue($memcache));

		// No memcache entries.  Stating fresh
		$this->assertEquals(count($memcache->getAll()), 0);
		// Rule should pas ass keys do not exist
		$this->assertTrue($rule->isValid($blackbox_data, $state_data));
		// Should have created as many entries as there are data keys
		$this->assertEquals(count($memcache->getAll()), count($rule_value["data_keys"]));
		// should find the keys now and fail
		$this->assertFalse($rule->isValid($blackbox_data, $state_data));
		// Count of keys should still be the same
		$this->assertEquals(count($memcache->getAll()), count($rule_value["data_keys"]));
		
	}
}

/**
 * Surrogate class for test
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class MemcacheSurrogate extends Cache_Memcache
{
	/**
	 * Data
	 *
	 * @var array
	 */
	private $key_data = array();

	/**
	 * Set a key
	 *
	 * @param string $key
	 * @param string $value
	 * @param int $timeout
	 * @return bool
	 */
	public function set($key, $value, $timeout)
	{
		$this->key_data[$key] = $value;
		return TRUE;
	}

	/**
	 * Get a key
	 *
	 * @param string $key
	 * @return string
	 */
	public function get($key)
	{
		return (isset($this->key_data[$key])) ? $this->key_data[$key] : FALSE;
	}

	/**
	 * Get all keys
	 *
	 * @return array
	 */
	public function getAll()
	{
		return $this->key_data;
	}
}