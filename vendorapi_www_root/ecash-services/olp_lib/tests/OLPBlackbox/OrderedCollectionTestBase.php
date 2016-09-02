<?php

/**
 * Base test class for the OLPBlackbox_OrderedCollectionTest class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
abstract class OLPBlackbox_OrderedCollectionTestBase extends PHPUnit_Framework_TestCase
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
	 * Blackbox data for tests
	 *
	 * @var OLPBlackbox_Data
	 */
	protected $blackbox_data;

	/**
	 * Sets up the tests OLPBlackbox_TargetCollection object.
	 *
	 * @return void
	 */
	public function setUp()
	{
		$this->blackbox_data = new Blackbox_Data();
		$this->state_data = new Blackbox_StateData();
		$this->target_collection = $this->getCollection();
	}
	
	/**
	 * Returns the correct collection for the test
	 * 
	 * @param string $name
	 * @return OLPBlackbox_TargetCollection
	 */
	abstract protected function getCollection($name = 'test');

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
		$valid = $this->target_collection->isValid($this->blackbox_data, $this->state_data);
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
		$this->target_collection->addTarget($this->getCampaign(array('isValid' => $campaign_valid)));
		$valid = $this->target_collection->isValid($this->blackbox_data, $this->state_data);
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
		$this->target_collection->addTarget($this->getCampaign(array('isValid' => $campaign_one_valid), 'test'));
		$this->target_collection->addTarget($this->getCampaign(array('isValid' => $campaign_two_valid), 'test2'));

		$valid = $this->target_collection->isValid($this->blackbox_data, $this->state_data);
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
	public function testPickTargetTwoTargets($campaign_one_valid, $campaign_two_valid, $campaign_one_name, $campaign_two_name,  $expected_campaign_name, $unexpected_valid)
	{
		$this->target_collection->addTarget($this->getCampaign(array('isValid' => $campaign_one_valid), $campaign_one_name));
		$this->target_collection->addTarget($this->getCampaign(array('isValid' => $campaign_two_valid), $campaign_two_name));

		$this->target_collection->isValid($this->blackbox_data, $this->state_data);
		$winner = $this->target_collection->pickTarget($this->blackbox_data);

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
		$campaign3 = $this->getCampaign(array('isValid' => NULL), 'test3', 3);
		$campaign3->expects($this->never())->method('isValid');

		$this->target_collection->addTarget($this->getCampaign(array('isValid' => FALSE), 'test', 1));
		$this->target_collection->addTarget($this->getCampaign(array('isValid' => TRUE), 'test2', 2));
		$this->target_collection->addTarget($campaign3);

		$valid = $this->target_collection->isValid($this->blackbox_data, $this->state_data);
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
		// Forces Blackbox_RuleCollection's isValid function to return TRUE
		$rules = $this->getMock('Blackbox_RuleCollection', array('isValid'));
		$rules->expects($this->any())
			->method('isValid')
			->will($this->returnValue(FALSE));

		$this->target_collection->addTarget(new OLPBlackbox_Campaign('test', 0, 10));
		$this->target_collection->setRules($rules);

		$valid = $this->target_collection->isValid($this->blackbox_data, $this->state_data);

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

		$this->target_collection->addTarget($this->getCampaign(array('isValid' => TRUE)));
		$this->target_collection->setRules($rules);

		$valid = $this->target_collection->isValid($this->blackbox_data, $this->state_data);

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
		// The rules will return TRUE (valid)
		$rule = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$rule->expects($this->any())
			->method('isValid')
			->will($this->returnValue(TRUE));

		$this->target_collection->setRules($rule);
		$this->target_collection->addTarget($this->getCampaign(array('isValid' => FALSE)));

		$valid = $this->target_collection->isValid($this->blackbox_data, $this->state_data);
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
		$campaign1 = $this->getCampaign(array('isValid' => TRUE, 'pickTarget' => FALSE), 'test1');
		$campaign2 = $this->getCampaign(array('isValid' => TRUE), 'test2');

		$this->target_collection->addTarget($campaign1);
		$this->target_collection->addTarget($campaign2);

		$this->assertTrue($this->target_collection->isValid($this->blackbox_data, $this->state_data));

		$winner = $this->target_collection->pickTarget($this->blackbox_data);
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
		$tier_campaign = new OLPBlackbox_Campaign('tier1', 0, 0);
		$tier_campaign->setRules(new Blackbox_RuleCollection()); // Bogus
		$target_collection = new Blackbox_TargetCollection();
		$tier_campaign->setTarget($target_collection);
		
		$target_collection->addTarget($this->getCampaign(array('isValid' => TRUE), 'test1'));
		$target_collection->addTarget($this->getCampaign(array('isValid' => TRUE), 'test2'));

		$this->target_collection->addTarget($tier_campaign);

		// First pick
		$this->assertTrue($this->target_collection->isValid($this->blackbox_data, $this->state_data));
		$winner = $this->target_collection->pickTarget($this->blackbox_data);
		$this->assertNotEquals(FALSE, $winner);
		$this->assertEquals('test1', $winner->getCampaign()->getStateData()->campaign_name);
		
		// Second pick - here it shouldn't pick test1 again
		$this->assertTrue($this->target_collection->isValid($this->blackbox_data, $this->state_data));
		$winner = $this->target_collection->pickTarget($this->blackbox_data);
		$this->assertNotEquals(FALSE, $winner);
		$this->assertEquals('test2', $winner->getCampaign()->getStateData()->campaign_name);
	}

	/**
	 * Data provider for testWakeupDefault test
	 * @return void
	 */
	public function dataProviderSleepWakeup()
	{
		return array(
			array(
				array(
					'valid' => NULL,
					'state_data' => new Blackbox_StateData(),
					'pick_target_rules_result' => NULL,
					'previous_target' => 0,
					'children' => array('test' => array('I slept with the campaign'))
				),
				'test'
			)
		);
	}
	
	/**
	 * Test the return of the sleep method
	 *
	 * @dataProvider dataProviderSleepWakeup
	 * @param array $sleep_data
	 * @param string $campaign_name
	 * @return void
	 */
	public function testSleep(array $sleep_data, $campaign_name)
	{
		$picker_sleep_return = 'I slept with the picker';
		$picker = $this->getMock('OLPBlackbox_IPicker');
		$picker->expects($this->any())
			->method('sleep')
			->will($this->returnValue($picker_sleep_return));
		$this->target_collection->setPicker($picker);

		$campaign_sleep_return = $sleep_data['children'][$campaign_name][0];
		$campaign = $this->getCampaign(array('sleep' => $campaign_sleep_return, 'isValid' => TRUE), $campaign_name);

		$this->target_collection->addTarget($campaign);
		
		$sleep_data = $this->target_collection->sleep();
		
		$this->assertType('array', $sleep_data);
		$this->assertArrayHasKey('pick_target_rules_result', $sleep_data);
		$this->assertAttributeEquals($sleep_data['pick_target_rules_result'], 'pick_target_rules_result', $this->target_collection);
		$this->assertArrayHasKey('state_data', $sleep_data);
		$this->assertAttributeEquals($sleep_data['state_data'], 'state_data', $this->target_collection);
		$this->assertArrayHasKey('picker', $sleep_data);
		$this->assertEquals($picker_sleep_return, $sleep_data['picker']);
		$this->assertArrayHasKey('children', $sleep_data);
		$this->assertType('array', $sleep_data['children']);
		$this->assertArrayHasKey($campaign_name, $sleep_data['children']);
		$this->assertEquals($campaign_sleep_return, $sleep_data['children'][$campaign_name]);
		
		return $sleep_data;
	}
	
	/**
	 * Test the wakeup method for a default sleep 
	 *
	 * @dataProvider dataProviderSleepWakeup
	 * @param array $sleep_data
	 * @param string $campaign_name
	 * @return void
	 */
	public function testWakeupDefault(array $sleep_data, $campaign_name)
	{
		$campaign = $this->getMock(
			'OLPBlackbox_Campaign',
			array('wakeup', 'isValid', 'pickTarget'),
			array($campaign_name)
		);

		$campaign->expects($this->once())
			->method('isValid')
			->will($this->returnValue(TRUE));

		$campaign->expects($this->once())
			->method('wakeup')
			->with($this->equalTo($sleep_data['children'][$campaign_name]));

		$campaign->expects($this->once())
			->method('pickTarget')
			->will($this->returnValue($campaign));

		$coll_rules = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$coll_rules->expects($this->any())->method('isValid')->will($this->returnValue(TRUE));
		
		$coll_pick_rules = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$coll_pick_rules->expects($this->once())->method('isValid')->will($this->returnValue(TRUE));
		
		// Campaign mock just needs to implement OLPBlackbox_IRestorable
		$this->target_collection->addTarget($campaign);
		$this->target_collection->setRules($coll_rules);
		$this->target_collection->setPickTargetRules($coll_pick_rules);

		$this->target_collection->wakeup($sleep_data);
		
		$valid = $this->target_collection->isValid($this->blackbox_data, $this->state_data);
		$this->assertTrue($valid);

		$winner = $this->target_collection->pickTarget($this->blackbox_data);
		
		$this->assertEquals($campaign, $winner);
	}
	
	/**
	 * Returns a mocked OLPBlackbox_Campaign
	 *
	 * @param array $functions Functions to mock with expected values
	 * @param string $name Campaign name
	 * @param int $weight Weight for the campaign
	 * @param string $target_name Optional target name to use; will use campaign's name otherwise
	 * @return OLPBlackbox_Campaign
	 */
	protected function getCampaign($functions = array('isValid' => FALSE), $name = 'test', $weight = 10, $target_name =  NULL)
	{
		$mock = $this->getMock(
			'OLPBlackbox_Campaign',
			array_keys($functions),
			array(
				$name,
				0,
				$weight,
				new OLPBlackbox_Target((empty($target_name)) ? $name : $target_name, 0)
			)
		);
		
		foreach ($functions as $function => $return_value)
		{
			// only setup functions that have defined return values
			if ($return_value !== NULL)
			{
				$mock->expects($this->any())->method($function)->will($this->returnValue($return_value));
			}
		}
		
		return $mock;
	}
}
?>
