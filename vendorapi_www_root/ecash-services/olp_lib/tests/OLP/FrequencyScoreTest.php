<?php

/**
 * Tests the OLP_FrequencyScore class.
 *
 * @author Tym Feindel <timothy.feindel@sellingsource.com>
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_FrequencyScoreTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var OLP_FrequencyScore
	 */
	protected $freq_object;
	
	/**
	 * Set up the db connection for this test.
	 *
	 * @return void
	 */
	public function setUp()
	{
		$db = $this->getDatabase();
		$memcache = new Cache_FakeMemcache();
		
		$this->freq_object = new OLP_FrequencyScore($db, $memcache);
	}
	
	/**
	 * Clean up database,
	 *
	 * @return void
	 */
	public function tearDown()
	{
		$query = "TRUNCATE TABLE vendor_decline_freq";
		
		$db = $this->getDatabase();
		$db->exec($query);
	}
	
	/**
	 * Gets the database.
	 *
	 * @return DB_IConnection
	 */
	protected function getDatabase()
	{
		return TEST_DB_CONNECTOR(TEST_OLP);
	}
	
	/**
	 * Asserts that our email has this memscore.
	 *
	 * @param string $email
	 * @param int $expect_memscore
	 * @param string $message
	 * @return void
	 */
	protected function assertMemScore($email, $expect_memscore, $message = NULL)
	{
		$memscore = $this->freq_object->getMemScore($email);
		$this->assertEquals($expect_memscore, $memscore, $message);
	}
	
	/**
	 * Test FrequencyScore.
	 *
	 * @return void
	 */
	public function testMemScore()
	{
		$email = 'test@test.tss';
		$property_short = 'abc';
		$application_id = mt_rand(10000, 99999);
		$hits = mt_rand(1, 5);
		
		$this->freq_object->removeMemScore($email);
		$this->assertMemScore($email, 0, 'Asserting that removeMemScore() reset test email to 0.');
		
		$this->freq_object->addPost($email);
		$this->assertMemScore($email, 1, 'Asserting that addPost() increments memcache score.');
		
		for ($x = 1; $x < $hits; $x++)
		{
			$this->freq_object->addPost($email);
		}
		
		$this->assertMemScore($email, $hits, 'Asserting that addPost() increments memcache score.');
		
		$original_rejects = $this->freq_object->getRejectsByHistory($email, '1 day');
		
		$this->freq_object->addAccept($email, $property_short, $application_id);
		$this->assertMemScore($email, 0, 'Asserting that after writing an accept the memcache score is zeroed.');
		
		$this->freq_object->addPost($email);
		$current_rejects = $this->freq_object->getRejectsByHistory($email, '1 day');
		$this->assertEquals($original_rejects + 1, $current_rejects, 'Asserting that getRejectsByHistory() returns the summed freq score(s) from db and memcache.');
	}
}

?>
