<?php
/**
 * Tests the TrackKeyGenertor.
 */
class Stats_StatPro_TrackKeyGeneratorTest extends PHPUnit_Framework_TestCase
{
	private $generator;
	
	public function setUp()
	{
		$this->generator = new Stats_StatPro_TrackKeyGenerator();
	}
	
	/**
	 * Test that generate creates a valid track key
	 */
	public function testGenerateTrackKey()
	{
		self::assertTrue(preg_match('/^[a-z0-9,-]{27}$/i', $this->generator->generate()) == 1);
	}
}
