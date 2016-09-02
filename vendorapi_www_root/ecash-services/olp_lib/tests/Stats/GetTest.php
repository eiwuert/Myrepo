<?php
/**
 * Test case for Stats_Get.
 *
 * @group stats
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class Stats_GetTest extends PHPUnit_Framework_TestCase
{
	const DB_MODE = 'RC';
	const STAT = 'prequal';
	const SCHEMA = 'stat';
	const DATE_FORMAT = 'Y-m-d H:i:s';
	
	/**
	 * Result of count from String test
	 *
	 * @var array
	 */
	protected $string_count;

	/**
	 * Result of count from Array test
	 *
	 * @var array
	 */
	protected $array_count;
	
	/**
	 * Test with a string for stat
	 *
	 * @return void
	 */
	public function testStatString()
	{
		$stats = new Stats_Get(self::DB_MODE);
		$count = $stats->countEvents(self::STAT,
					self::SCHEMA,
					date(self::DATE_FORMAT,time()-6000),
					date(self::DATE_FORMAT,time())
		);
		
		$this->string_count = $count;
		$this->assertNotNull($count);
		$this->assertTrue(is_array($count));
	}

	/**
	 * Test with an array for stat
	 *
	 * @return void
	 */
	public function testStatArray()
	{
		$stats = new Stats_Get(self::DB_MODE);
		$count = $stats->countEvents(array(self::STAT),
					self::SCHEMA,
					date(self::DATE_FORMAT,time()-6000),
					date(self::DATE_FORMAT,time())
		);
		$this->array_count = $count;
		$this->assertNotNull($count);
		$this->assertTrue(is_array($count));
	}

	/**
	 * Test that result of string stat and array stat is the same 
	 *
	 * @return void
	 */
	public function testStringEqualsArray()
	{
		if (!empty($this->string_count) && !empty($this->array_count))
		{
			$this->assertEquals($this->string_count,$this->array_count);
		}
	}

}

?>
