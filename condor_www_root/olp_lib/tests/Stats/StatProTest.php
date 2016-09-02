<?php
require('olp_lib_setup.php');

/** Extended class for Stats_StatPro to allow mocked Stats_StatPro_Client_1
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Stats_StatPro_Mocked extends Stats_StatPro
{
	/** Initialize libolution statpro client.
	 *
	 * @param string $mode The mode statpro is running under.
	 * @param int $property_id Account stats will hit to.
	 * @param Stats_StatPro_Client_1 $mocked_client Mocked statpro client.
	 */
	protected function __construct($mode, $property_id, $mocked_client = NULL)
	{
		if ($mocked_client)
		{
			$this->mode = self::getInternalMode($mode);
			$this->statpro_key = self::getKey($mode, $property_id);
			
			$property_data = self::getPropertyData($property_id);
			$this->statpro_user = $property_data['statpro_user'];
			$this->statpro_pass = $property_data['statpro_pass'];
			
			$this->statpro = $mocked_client;
		}
		else
		{
			parent::__construct($mode, $property_id);
		}
	}
	
	/** Grab an instance of a statpro client. There should only be one
	 * statpro client per database connection.
	 *
	 * @param string $mode The mode statpro is running under.
	 * @param int $property_id Account stats will hit to.
	 * @param Stats_StatPro_Client_1 $mocked_client Mocked statpro client.
	 * @return Stats_StatPro_OLP
	 */
	public static function getInstance($mode, $property_id = NULL, $mocked_client = NULL)
	{
		return new self($mode, $property_id, $mocked_client);
	}
	
}

/** Test case for Stats_StatPro.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Stats_StatProTest extends PHPUnit_Framework_TestCase
{
	protected $statpro; /**< @var Stats_StatPro_Mocked */
	
	protected $mode; /**< @var string */
	protected $property_id; /**< @var int */
	protected $space_def; /** @var array */
	
	/** Sets up mode and property id.
	 *
	 * @return void
	 */
	public function setUp()
	{
		$this->mode = 'LOCAL';
		$this->property_id = NULL; // Defaults to user: catch
		$this->space_def = array(
			'page_id' => '100',
			'promo_id' => '10000',
			'promo_sub_code' => 'cruisecontrol',
		);
		
		$this->statpro = NULL;
	}
	
	/** Destroys the mocked client.
	 *
	 * @return void
	 */
	public function tearDown()
	{
		if ($this->statpro)
		{
			unset($this->statpro);
		}
	}

	/** Initialize client with correctly mocked object.
	 *
	 * @param int $stats_hit How many stats will be hit.
	 * @param int $batches_begin How many batches will begin.
	 * @param int $batches_flush How many batches will flush.
	 * @param int $spaces_created How many space keys will be created.
	 * @param int $tracks_created How many track keys will be created.
	 * @return void
	 */
	protected function setupInstance($stats_hit = 0, $batches_begin = 0, $batches_flush = 0, $spaces_created = 0, $tracks_created = 0)
	{
		$mocked_client = $this->getMock(
			'Stats_StatPro_Client_1',
			array(
				'recordEvent',
				'beginBatch',
				'endBatch',
				'getSpaceKey',
				'createTrackKey',
			),
			array(
				'spc_catch_test', '', ''
			)
		);
		
		$mocked_client->expects($this->exactly($stats_hit))->method('recordEvent');
		$mocked_client->expects($this->exactly($batches_begin))->method('beginBatch');
		$mocked_client->expects($this->exactly($batches_flush))->method('endBatch');
		$mocked_client->expects($this->exactly($spaces_created))->method('getSpaceKey')->will($this->returnValue(str_repeat('A', 27)));
		$mocked_client->expects($this->exactly($tracks_created))->method('createTrackKey')->will($this->returnValue(str_repeat('A', 27)));
		
		$this->statpro = Stats_StatPro_Mocked::getInstance($this->mode, $this->property_id, $mocked_client);
	}
	
	/** Tests that the getInstance function returns an instance of Stats_StatPro.
	 *
	 * @return void
	 */
	public function testGetInstance()
	{
		$this->assertType('Stats_StatPro', Stats_StatPro::getInstance($this->mode, $this->property_id));
	}
	
	/** Tests that the same resulting client will be same database.
	 *
	 * @return void
	 */
	public function testSameClientInstances()
	{
		$this->assertEquals(Stats_StatPro::getInstance($this->mode, 31631), Stats_StatPro::getInstance($this->mode, 3018));
	}
	
	/** Tests that normal stats get hit.
	 *
	 * @return void
	 */
	public function testHitSingleStat()
	{
		$this->setupInstance(1, 0, 0, 1, 1);
		
		$this->statpro->createTrackKey();
		$this->statpro->createSpaceKey($this->space_def);
		$this->statpro->hitStat('test_basic_stat');
	}
	
	/** Test that batches work as expected.
	 *
	 * @return void
	 */
	public function testHitBatchedStats()
	{
		$this->setupInstance(2, 1, 1, 1, 1);
		
		$this->statpro->enableBatch();
		$this->statpro->createTrackKey();
		$this->statpro->createSpaceKey($this->space_def);
		
		$this->statpro->hitStat('test_batched_stat_a');
		$this->statpro->hitStat('test_batched_stat_b');
		
		$this->statpro->flushBatch();
	}
	
	/** Test that queued stats not process are dropped.
	 *
	 * @return void
	 */
	public function testLostBatchedStats()
	{
		$this->setupInstance(2, 1, 0, 1, 1);
		
		$this->statpro->enableBatch();
		$this->statpro->createTrackKey();
		$this->statpro->createSpaceKey($this->space_def);
		
		$this->statpro->hitStat('test_batched_lost_stat_a');
		$this->statpro->hitStat('test_batched_lost_stat_b');
		
		// Not calling flushBatch()
	}
	
	/** Test you can hit multiple queues.
	 *
	 * @return void
	 */
	public function testMultipleQueuedStats()
	{
		$this->setupInstance(5, 2, 2, 1, 2);
		
		$this->statpro->enableBatch();
		$this->statpro->createTrackKey();
		$this->statpro->createSpaceKey($this->space_def);
		$this->statpro->hitStat('test_multiple_batch1_stat_a');
		$this->statpro->hitStat('test_multiple_batch1_stat_b');
		$this->statpro->flushBatch();
		
		$this->statpro->enableBatch();
		$this->statpro->createTrackKey();
		$this->statpro->createSpaceKey($this->space_def);
		$this->statpro->hitStat('test_multiple_batch2_stat_a');
		$this->statpro->hitStat('test_multiple_batch2_stat_b');
		$this->statpro->flushBatch();
		
		$this->statpro->hitStat('test_multiple_hit_stat');
	}
}

?>
