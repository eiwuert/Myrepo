<?php
require_once('OLPBlackboxTestSetup.php');

/**
 * PHPUnit test class for the OLPBlackbox_OrderedCollectionTest class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_OrderedCollectionTest extends PHPUnit_Framework_TestCase
{
	/**
	 * The OLPBlackbox_TargetCollection object used in tests.
	 *
	 * @var OLPBlackbox_OrderedCollection
	 */
	protected $target_collection;

	/**
	 * State data to pass around to tests.
	 *
	 * @var Blackbox_StateData
	 */
	protected $state_data;

	/**
	 * Sets up the tests OLPBlackbox_TargetCollection object.
	 *
	 * @return void
	 */
	public function setUp()
	{
		$this->state_data = new Blackbox_StateData();
		$this->target_collection = new OLPBlackbox_OrderedCollection('test');
	}

	/**
	 * Destroys the target collection at the end of each test.
	 *
	 * @return void
	 */
	public function tearDown()
	{
		unset($this->target_collection);
	}

	/**
	 * Tests that the default isValid function return FALSE.
	 *
	 * @return void
	 */
	public function testIsValidDefault()
	{
		$data = new OLPBlackbox_Data();

		$valid = $this->target_collection->isValid($data, $this->state_data);
		$this->assertFalse($valid);
	}

	/**
	 * The data provider for the isValidOneTarget tests.
	 *
	 * @return array
	 */
	public static function isValidOneTargetDataProvider()
	{
		return array(
			array(    // Target returns TRUE, expect TRUE in return
				TRUE, // $campaign_valid
				TRUE  // $expected_valid
			),
			array(FALSE, FALSE) // Target returns FALSE, expect FALSE in return
		);
	}

	/**
	 * Tests that when we only have one campaign and it's valid, isValid returns TRUE.
	 *
	 * @param bool $campaign_valid whether the campaign is valid
	 * @param bool $expected_valid what we expect to get back from isValid
	 * @dataProvider isValidOneTargetDataProvider
	 * @return void
	 */
	public function testIsValidOneTarget($campaign_valid, $expected_valid)
	{
		$data = new OLPBlackbox_Data();

		$campaign = $this->getMock(
			'OLPBlackbox_Campaign',
			array('isValid'),
			array('test', 0, 10)
		);
		$campaign->expects($this->any())->method('isValid')->will($this->returnValue($campaign_valid));

		$this->target_collection->addTarget($campaign);
		$valid = $this->target_collection->isValid($data, $this->state_data);
		$this->assertSame($valid, $expected_valid);
	}

	/**
	 * Data provider for the isValidTwoTarget tests.
	 *
	 * @return array
	 */
	public static function isValidTwoTargetsDataProvider()
	{
		return array(
			array(    // both targets return valid = TRUE
				TRUE, // $campaign_one_valid
				TRUE, // $campaign_two_valid
				TRUE  // $expected_valid
			),
			array(FALSE, FALSE, FALSE), // both targets return valid = FALSE
			array(TRUE, FALSE, TRUE),   // first target returns valid = TRUE
			array(FALSE, TRUE, TRUE),   // second target returns valid = TRUE
		);
	}

	/**
	 * Tests that when we have two campaigns and the second is valid, isValid returns TRUE.
	 *
	 * @param bool $campaign_one_valid whether the first campaign is valid
	 * @param bool $campaign_two_valid whether the second campaign is valid
	 * @param bool $expected_valid     what we expect to get back from isValid
	 * @dataProvider isValidTwoTargetsDataProvider
	 * @return void
	 */
	public function testIsValidTwoTargets($campaign_one_valid, $campaign_two_valid, $expected_valid)
	{
		$data = new OLPBlackbox_Data();

		$campaign = $this->getMock(
			'OLPBlackbox_Campaign',
			array('isValid'),
			array('test', 0, 10)
		);
		$campaign->expects($this->any())->method('isValid')->will($this->returnValue($campaign_one_valid));

		$campaign2 = $this->getMock(
			'OLPBlackbox_Campaign',
			array('isValid'),
			array('test2', 0, 10)
		);
		$campaign2->expects($this->any())->method('isValid')->will($this->returnValue($campaign_two_valid));

		$this->target_collection->addTarget($campaign);
		$this->target_collection->addTarget($campaign2);

		$valid = $this->target_collection->isValid($data, $this->state_data);
		$this->assertSame($valid, $expected_valid);
	}

	/**
	 * The data provider for the pickTargetTwoTargets test.
	 *
	 * @return array
	 */
	public static function pickTargetTwoTargetsDataProvider()
	{
		return array(
			array(
				TRUE,    // $campaign_one_valid
				TRUE,    // $campaign_two_valid
				'test',  // $campaign_one_name
				'test2', // $campaign_two_name
				'test',  // $expected_campaign_name
				FALSE    // $unexpected_valid
			),
			array(FALSE, TRUE, 'test', 'test2', 'test2', FALSE)
		);
	}

	/**
	 * Test pickTarget with two targets in the OrderedCollection.
	 *
	 * @param bool   $campaign_one_valid     what the first campaign returns from isValid
	 * @param bool   $campaign_two_valid     what the second campaign returns from isValid
	 * @param string $campaign_one_name      the first campaign's name
	 * @param string $campaign_two_name      the second campaign's name
	 * @param string $expected_campaign_name the expected winner's campaign name
	 * @param bool   $unexpected_valid       what we don't expect from the collection's isValid function
	 * @dataProvider pickTargetTwoTargetsDataProvider
	 * @return void
	 */
	public function testPickTargetTwoTargets(
		$campaign_one_valid,
		$campaign_two_valid,
		$campaign_one_name,
		$campaign_two_name,
		$expected_campaign_name,
		$unexpected_valid)
	{
		$data = new OLPBlackbox_Data();

		$target = new OLPBlackbox_Target('test', 0);
		$campaign = $this->getMock(
			'OLPBlackbox_Campaign',
			array('isValid'),
			array($campaign_one_name, 0, 10, $target)
		);
		$campaign->expects($this->any())->method('isValid')->will($this->returnValue($campaign_one_valid));

		$target2 = new OLPBlackbox_Target('test2', 0);
		$campaign2 = $this->getMock(
			'OLPBlackbox_Campaign',
			array('isValid'),
			array($campaign_two_name, 0, 10, $target2)
		);
		$campaign2->expects($this->any())->method('isValid')->will($this->returnValue($campaign_two_valid));

		$this->target_collection->addTarget($campaign);
		$this->target_collection->addTarget($campaign2);

		$this->target_collection->isValid($data, $this->state_data);
		$winner = $this->target_collection->pickTarget($data);

		$this->assertNotEquals($unexpected_valid, $winner);
		$this->assertEquals(
			$expected_campaign_name,
			$winner->getCampaign()->getTarget()->getStateData()->target_name
		);
	}

	/**
	 * Test isValid with three targets in the OrderedCollection and verifies that the third
	 * target's isValid never gets reached.
	 *
	 * @return void
	 */
	public function testIsValidThreeTargets()
	{
		$data = new OLPBlackbox_Data();

		$target = new OLPBlackbox_Target('test', 0);
		$campaign = $this->getMock(
			'OLPBlackbox_Campaign',
			array('isValid'),
			array('test', 0, 10, $target)
		);
		$campaign->expects($this->once())->method('isValid')->will($this->returnValue(FALSE));

		$target2 = new OLPBlackbox_Target('test2', 0);
		$campaign2 = $this->getMock(
			'OLPBlackbox_Campaign',
			array('isValid'),
			array('test2', 0, 10, $target2)
		);
		$campaign2->expects($this->once())->method('isValid')->will($this->returnValue(TRUE));

		$target3 = new OLPBlackbox_Target('test3', 0);
		$campaign3 = $this->getMock(
			'OLPBlackbox_Campaign',
			array('isValid'),
			array('test3', 0, 10, $target3)
		);
		$campaign3->expects($this->never())->method('isValid');

		$this->target_collection->addTarget($campaign);
		$this->target_collection->addTarget($campaign2);
		$this->target_collection->addTarget($campaign3);

		$valid = $this->target_collection->isValid($data, $this->state_data);
		$this->assertTrue($valid);
	}

	/**
	 * Runs a test on isValid expecting that the rule collection passed into the target collection
	 * returns as invalid.
	 *
	 * @return void
	 */
	public function testIsValidFailOnRules()
	{
		$data = new OLPBlackbox_Data();
		$campaign = new OLPBlackbox_Campaign('test', 0, 10);

		// Forces Blackbox_RuleCollection's isValid function to return TRUE
		$rules = $this->getMock('Blackbox_RuleCollection', array('isValid'));
		$rules->expects($this->any())
			->method('isValid')
			->will($this->returnValue(FALSE));

		$this->target_collection->addTarget($campaign);
		$this->target_collection->setRules($rules);

		$valid = $this->target_collection->isValid($data, $this->state_data);

		$this->assertFalse($valid);
	}

	/**
	 * Runs a test on isValid expecting that the rule collection passed into the target collection
	 * returns as invalid.
	 *
	 * @return void
	 */
	public function testIsValidPassOnRules()
	{
		// Forces Blackbox_RuleCollection's isValid function to return TRUE
		$rules = $this->getMock('Blackbox_RuleCollection', array('isValid'));
		$rules->expects($this->any())
			->method('isValid')
			->will($this->returnValue(TRUE));

		// Force the campaign isValid object to return TRUE
		$campaign = $this->getMock(
			'OLPBlackbox_Campaign',
			array('isValid'),
			array('test', 0, 10)
		);
		$campaign->expects($this->any())
			->method('isValid')
			->will($this->returnValue(TRUE));

		$data = new OLPBlackbox_Data();

		$this->target_collection->addTarget($campaign);
		$this->target_collection->setRules($rules);

		$valid = $this->target_collection->isValid($data, $this->state_data);

		$this->assertTrue($valid);
	}

	/**
	 * There was a bug where if you had rules setup and they passed, it wouldn't run
	 * the target rules because $valid became TRUE.
	 *
	 * @return void
	 */
	public function testIsValidPassOnRuleRunTargetRules()
	{
		$data = new OLPBlackbox_Data();

		// The rules will return TRUE (valid)
		$rule = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$rule->expects($this->any())
			->method('isValid')
			->will($this->returnValue(TRUE));

		/**
		 * Force the campaign isValid object to return FALSE. Before the fix for this, this would
		 * actually cause it to return TRUE, because the target rules would never have been run.
		 */
		$campaign = $this->getMock(
			'OLPBlackbox_Campaign',
			array('isValid'),
			array('test', 0, 10)
		);
		$campaign->expects($this->any())
			->method('isValid')
			->will($this->returnValue(FALSE));

		$this->target_collection->setRules($rule);
		$this->target_collection->addTarget($campaign);

		$valid = $this->target_collection->isValid($data, $this->state_data);
		$this->assertFalse($valid);
	}

	/**
	 * Tests that when we run pickTarget and get a FALSE back from a target, that we
	 * attempt to keep picking targets.
	 *
	 * @return void
	 */
	public function testPickTargetOnFail()
	{
		$data = new OLPBlackbox_Data();
		$state_data = new Blackbox_StateData();

		$target1 = new OLPBlackbox_Target('test1', 0);
		$campaign1 = $this->getMock(
			'OLPBlackbox_Campaign',
			array('isValid', 'pickTarget'),
			array('test1', 0, 10, $target1)
		);
		$campaign1->expects($this->any())
			->method('isValid')
			->will($this->returnValue(TRUE));
		$campaign1->expects($this->any())
			->method('pickTarget')
			->will($this->returnValue(FALSE));

		$target2 = new OLPBlackbox_Target('test2', 0);
		$campaign2 = $this->getMock(
			'OLPBlackbox_Campaign',
			array('isValid'),
			array('test2', 0, 10, $target2)
		);
		$campaign2->expects($this->any())
			->method('isValid')
			->will($this->returnValue(TRUE));

		$this->target_collection->addTarget($campaign1);
		$this->target_collection->addTarget($campaign2);

		$this->assertTrue($this->target_collection->isValid($data, $state_data));

		$winner = $this->target_collection->pickTarget($data);
		$this->assertNotEquals(FALSE, $winner);
		$this->assertEquals('test2', $winner->getCampaign()->getStateData()->campaign_name);
	}
	
	/**
	 * Tests that we get back a target in the same collection when pickTarget is called more than
	 * once.
	 * 
	 * When an OrderedCollection has a set of TargetCollections, it needs to keep picking the same
	 * target as long as it's valid. There was a bug [#10605] that was causing it to move on to the next
	 * target (TargetCollection in this case) on the second call. This would mean that it would pick a
	 * target in Tier 2, then if that post failed, on the next Blackbox call, it would move on to
	 * Tier 3. See OrderedCollection:pickTarget and isValid for more information.
	 * 
	 * @return void
	 */
	public function testPickTargetTwice()
	{
		$data = new OLPBlackbox_Data();
		$state_data = new Blackbox_StateData();

		$tier_campaign = new OLPBlackbox_Campaign('tier1', 0, 0);
		$tier_campaign->setRules(new Blackbox_RuleCollection()); // Bogus
		$target_collection = new Blackbox_TargetCollection();
		$tier_campaign->setTarget($target_collection);
		
		$target1 = new OLPBlackbox_Target('test1', 0);
		$campaign1 = $this->getMock(
			'OLPBlackbox_Campaign',
			array('isValid'),
			array('test1', 0, 10, $target1)
		);
		$campaign1->expects($this->any())
			->method('isValid')
			->will($this->returnValue(TRUE));

		$target2 = new OLPBlackbox_Target('test2', 0);
		$campaign2 = $this->getMock(
			'OLPBlackbox_Campaign',
			array('isValid'),
			array('test2', 0, 10, $target2)
		);
		$campaign2->expects($this->any())
			->method('isValid')
			->will($this->returnValue(TRUE));
			
		$target_collection->addTarget($campaign1);
		$target_collection->addTarget($campaign2);

		$this->target_collection->addTarget($tier_campaign);

		// First pick
		$this->assertTrue($this->target_collection->isValid($data, $state_data));
		$winner = $this->target_collection->pickTarget($data);
		$this->assertNotEquals(FALSE, $winner);
		$this->assertEquals('test1', $winner->getCampaign()->getStateData()->campaign_name);
		
		// Second pick - here it shouldn't pick test1 again
		$this->assertTrue($this->target_collection->isValid($data, $state_data));
		$winner = $this->target_collection->pickTarget($data);
		$this->assertNotEquals(FALSE, $winner);
		$this->assertEquals('test2', $winner->getCampaign()->getStateData()->campaign_name);
	}
}
?>
