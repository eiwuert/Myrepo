<?php
/**
 * PHPUnit test class for the OLPBlackbox_Campaign class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_CampaignTest extends PHPUnit_Framework_TestCase
{
	/**
	 * The OLPBlackbox_Campaign object to use in these tests.
	 *
	 * @var OLPBlackbox_Campaign
	 */
	protected $campaign;

	/**
	 * Generic data object for us to pass around.
	 *
	 * @var Blackbox_Data
	 */
	protected $bb_data;

	/**
	 * State data to pass around.
	 *
	 * @var Blackbox_StateData
	 */
	protected $state_data;

	/**
	 * Instantiates a new OLPBlackbox_Campaign for each test.
	 *
	 * @return void
	 */
	public function setUp()
	{
		$this->bb_data = new Blackbox_Data();
		$this->state_data = new Blackbox_StateData();
		$this->campaign = new OLPBlackbox_Campaign('test', 0, 100);
	}

	/**
	 * Unsets the campaign class variable.
	 *
	 * @return void
	 */
	public function tearDown()
	{
		unset($this->bb_data);
		unset($this->campaign);
	}

	/**
	 * Tests that the isValid function returns TRUE when the rules and the target isValid functions
	 * return TRUE.
	 *
	 * @return void
	 */
	public function testIsValidPass()
	{
		// Forces Blackbox_Target's isValid function to return TRUE
		$target = $this->getMock('Blackbox_Target', array('isValid'));
		$target->expects($this->any())->method('isValid')->will($this->returnValue(TRUE));

		// Forces Blackbox_Rule's isValid function to return TRUE
		$rule = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$rule->expects($this->any())->method('isValid')->will($this->returnValue(TRUE));

		$this->campaign->setTarget($target);
		$this->campaign->setRules($rule);

		$valid = $this->campaign->isValid($this->bb_data, $this->state_data);

		$this->assertTrue($valid);
	}

	/**
	 * Tests that the isValid function returns FALSE when the the target isValid function
	 * return FALSE.
	 *
	 * @return void
	 */
	public function testIsValidFailOnTarget()
	{
		// Forces Blackbox_Target's isValid function to return FALSE
		$target = $this->getMock('Blackbox_Target', array('isValid'));
		$target->expects($this->once())
			->method('isValid')
			->will($this->returnValue(FALSE));

		// Forces Blackbox_Rule's isValid function to return TRUE
		$rule = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$rule->expects($this->once())
			->method('isValid')
			->will($this->returnValue(TRUE));

		$this->campaign->setTarget($target);
		$this->campaign->setRules($rule);

		$valid = $this->campaign->isValid($this->bb_data, $this->state_data);

		$this->assertFalse($valid);
	}

	/**
	 * Tests that the isValid function returns FALSE when the the rules isValid function
	 * return FALSE.
	 *
	 * @return void
	 */
	public function testIsValidFailOnRules()
	{
		// We don't expect the target's isValid function to be called if the rules return FALSE
		$target = $this->getMock('Blackbox_Target', array('isValid'));
		$target->expects($this->never())->method('isValid');

		// Forces Blackbox_Rule's isValid function to return FALSE
		$rule = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$rule->expects($this->once())
			->method('isValid')
			->will($this->returnValue(FALSE));

		$this->campaign->setTarget($target);
		$this->campaign->setRules($rule);

		$valid = $this->campaign->isValid($this->bb_data, $this->state_data);

		$this->assertFalse($valid);
	}

	/**
	 * Tests that we get False if we call isValid before target or rules were set.
	 *
	 * @return void
	 */
	public function testIsValidExceptions()
	{
		$this->assertFalse($this->campaign->isValid($this->bb_data, $this->state_data));
	}

	/**
	 * Tests that the pickTarget function returns FALSE if the target's pickTarget function returns
	 * FALSE.
	 *
	 * @return void
	 */
	public function testPickTargetFail()
	{
		// We don't expect the target's isValid function to be called if the rules return FALSE
		$target = $this->getMock('Blackbox_Target', array('pickTarget'));
		$target->expects($this->any())->method('pickTarget')->will($this->returnValue(FALSE));

		$this->campaign->setTarget($target);

		$winner = $this->campaign->pickTarget($this->bb_data);
		$this->assertFalse($winner);
	}

	/**
	 * Tests that when we get the winner from the target, that the campaign still
	 * returns an OLPBlackbox_Winner with the campaign inside.
	 *
	 * @return void
	 */
	public function testPickTargetPass()
	{
		$target = new OLPBlackbox_Target('test', 0);
		$this->campaign->setTarget($target);

		$winner = $this->campaign->pickTarget($this->bb_data);
		$this->assertType('OLPBlackbox_Winner', $winner);
		// Below runs getTarget instead of getCampaign in case it starts returning something wrong
		$this->assertType('OLPBlackbox_Campaign', $winner->getTarget());
	}

	/**
	 * Data provider for the testConstructorExceptions test.
	 *
	 * @return array
	 */
	public static function constructorExceptionsDataProvider()
	{
		return array(
			array(1, 0, '2'),
			array('test', 0, '2'),
			//array('test', 0, 2,)
		);
	}

	/**
	 * Test that target_collections wrapped in a Campaign return the leaf nodes, not the
	 * collection itself.
	 *
	 * Found a problem where if we have campaign wrappers around target collections,
	 * the target collection gets returned instead of the target leaf. This verifies that we don't
	 * run into this problem again!
	 *
	 * Expects pickTarget to return Campaign 2.
	 *
	 * Root
	 *    \
	 *     Target Collection 1
	 *    /
	 * Campaign 1 (Target Collection 2)
	 *  \                             \
	 *   Campaign 2 (Target 1)         Campaign 3 (Target 2)
	 *
	 * @return void
	 */
	public function testTwoLevelCampaignsAndCollections()
	{
		$data = new OLPBlackbox_Data();
		$rules = $this->getMock('Blackbox_StandardRule', array('runRule', 'isValid'));
		$rules->expects($this->any())->method('isValid')->will($this->returnValue(TRUE));

		$target_collection_1 = new OLPBlackbox_TargetCollection('test_collection1');
		$target_collection_2 = new OLPBlackbox_TargetCollection('test_collection2');

		$target_1 = new OLPBlackbox_Target('target1', 0);
		$target_1->setRules($rules);
		$target_2 = new OLPBlackbox_Target('target2', 0);
		$target_2->setRules($rules);

		$campaign_1 = new OLPBlackbox_Campaign('campaign1', 0, 100, $target_collection_2, $rules);
		$campaign_2 = new OLPBlackbox_Campaign('campaign2', 0, 100, $target_1, $rules);
		$campaign_3 = new OLPBlackbox_Campaign('campaign3', 0, 100, $target_2, $rules);

		$target_collection_1->addTarget($campaign_1);
		$target_collection_2->addTarget($campaign_2);
		$target_collection_2->addTarget($campaign_3);
		
		$tc_campaign = new OLPBlackbox_Campaign('tc_campaign', 0, 100, $target_collection_1);

		$valid = $target_collection_1->isValid($data, $this->state_data);
		$this->assertTrue($valid);

		$winner = $target_collection_1->pickTarget($data);
		$this->assertEquals(
			$campaign_2->getStateData()->campaign_name,
			$winner->getCampaign()->getStateData()->campaign_name
		);
	}

	/**
	 * Test that target_collections wrapped in a Campaign return the leaf nodes, not the
	 * collection itself.
	 *
	 * This test implements a three tier collection tree. Needs to return Campaign 4.
	 *
	 * Root
	 *    \
	 *     Target Collection 1
	 *                        \
	 *                        Campaign 1 (Target Collection 2)
	 *                        /                             \
	 *   Campaign 2 (Target Collection 3)        Campaign 3 (Target 1)
	 *  /                       \
	 * Campaign 4 (Target 2)     Campaign 5 (Target 3)
	 *
	 * @return void
	 */
	public function testThreeLevelCampaignsAndCollections()
	{
		$data = new OLPBlackbox_Data();
		$rules = $this->getMock('Blackbox_StandardRule', array('runRule', 'isValid'));
		$rules->expects($this->any())->method('isValid')->will($this->returnValue(TRUE));

		$target_collection_1 = new OLPBlackbox_TargetCollection('test_collection1');
		$target_collection_2 = new OLPBlackbox_TargetCollection('test_collection2');
		$target_collection_3 = new OLPBlackbox_TargetCollection('test_collection3');

		$target_1 = new OLPBlackbox_Target('target1', 0);
		$target_1->setRules($rules);
		$target_2 = new OLPBlackbox_Target('target2', 0);
		$target_2->setRules($rules);
		$target_3 = new OLPBlackbox_Target('target3', 0);
		$target_3->setRules($rules);

		$campaign_1 = new OLPBlackbox_Campaign('campaign1', 0, 100, $target_collection_2, $rules);
		$campaign_2 = new OLPBlackbox_Campaign('campaign2', 0, 100, $target_collection_3, $rules);
		$campaign_3 = new OLPBlackbox_Campaign('campaign3', 0, 100, $target_1, $rules);
		$campaign_4 = new OLPBlackbox_Campaign('campaign4', 0, 100, $target_2, $rules);
		$campaign_5 = new OLPBlackbox_Campaign('campaign5', 0, 100, $target_3, $rules);

		$target_collection_1->addTarget($campaign_1);
		$target_collection_2->addTarget($campaign_2);
		$target_collection_2->addTarget($campaign_3);
		$target_collection_3->addTarget($campaign_4);
		$target_collection_3->addTarget($campaign_5);

		$valid = $target_collection_1->isValid($data, $this->state_data);
		$this->assertTrue($valid);

		$winner = $target_collection_1->pickTarget($data);
		$this->assertEquals(
			$campaign_4->getStateData()->campaign_name,
			$winner->getCampaign()->getStateData()->campaign_name
		);
	}
	
	/**
	 * Tests that a Blackbox_Exception is thrown when setInvalid is called on a Campaign object.
	 * 
	 * setInvalid() throws an exception, because we don't want people to accidently set the campaign invalid
	 * when they really meant to set the target invalid.
	 *
	 * @expectedException Blackbox_Exception
	 * @return void
	 */
	public function testSetInvalid()
	{
		$this->campaign->setInvalid();
	}

	/**
	 * Data provider for testRexceptionPropagation
	 * array((string)ExceptionName, (bool)propagates)
	 *
	 * @return array
	 */
	public function exceptionPropagationProvider()
	{
		return array(
			array('OLPBlackbox_ReworkException', TRUE),
			array('OLPBlackbox_FailException', TRUE),
			array('Blackbox_Exception', FALSE),
			array('Exception', FALSE),
		);
	}

 	/**
	 * Tests that when a rule throws a propagating exception that the campaign does not
	 * prevent that exception from propagating 
	 *
	 * @return void
	 * @dataProvider exceptionPropagationProvider
	 */
	public function testExceptionPropagation($exception, $propagates)
	{
		$rule = $this->getMock('Blackbox_IRule');
		$rule->expects($this->any())
			->method('canRun')
			->will($this->returnValue(TRUE));
		$rule->expects($this->any())
			->method('isValid')
			->will($this->throwException(new $exception('Exception')));

		$target = new OLPBlackbox_Target('test', 0);
		$this->campaign->setTarget($target);
		$this->campaign->setPickTargetRules($rule);

		// If an exception is expected to propagate, let the test case know its name
		if ($propagates)
		{
			$this->setExpectedException($exception);
		}

		$winner = $this->campaign->pickTarget($this->bb_data);

		// If an exception should not propagate, it should return False
		if (!$propagates)
		{
			$this->assertFalse($winner);
		}
		
	}

	/**
	 * Test the wakeup function on a target that was deemed invalid
	 *
	 * @return void
	 */
	public function testWakeupInvalidTarget()
	{
		$bbx_data = $this->bb_data;
		$state_data = $this->state_data;
		
		$target_wakeup_data = array('I slept with the target');
				
		// This portion will test restoring a default rule that has not been validated or picked
		
		// IsValid will not run
		$rules = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$rules->expects($this->never())->method('isValid')->will($this->returnValue(FALSE));

		// Pick Target will not run
		$rules2 = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$rules2->expects($this->never())->method('isValid')->will($this->returnValue(FALSE));

		$target = $this->getMock(
			'OLPBlackbox_Target',
			array('isValid', 'pickTarget', 'wakeup'),
			array('Target', 0)
		);
		$target->expects($this->once())
			->method('wakeup')
			->with($this->equalTo($target_wakeup_data));
		$target->expects($this->never())
			->method('isValid');
		$target->expects($this->never())
			->method('pickTarget');

		$this->campaign->setTarget($target);
		$this->campaign->setRules($rules);
		$this->campaign->setPickTargetRules($rules2);

		$wakeup_data = array(
			'valid' => FALSE,
			'pick_target_rules_result' => NULL,
			'state_data' => $state_data,
			'target' => $target_wakeup_data
		);

		$this->campaign->wakeup($wakeup_data);
		$valid = $this->campaign->isValid($bbx_data, $state_data);
		$this->assertType('bool', $valid);
		$this->assertEquals($wakeup_data['valid'], $valid);
		$winner = $this->campaign->pickTarget($bbx_data);
		$this->assertType('bool', $winner);
		$this->assertEquals(FALSE, $winner);

	}
	
	/**
	 * Test the wakeup function on a target that was deemed valid but failed pick target rules
	 *
	 * @return void
	 */
	public function testWakeupFailedPickTargetRules()
	{
		$bbx_data = $this->bb_data;
		$state_data = $this->state_data;
		
		$target_wakeup_data = array('I slept with the target');
				
		// IsValid will not run since it has been loaded to true
		$rules = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$rules->expects($this->never())->method('isValid');

		// Pick Target will run because valid will be set to TRUE
		$rules2 = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$rules2->expects($this->once())->method('isValid')->will($this->returnValue(TRUE));

		$target = $this->getMock(
			'OLPBlackbox_Target',
			array('isValid', 'pickTarget', 'wakeup'),
			array('Target', 0)
		);
		$target->expects($this->once())
			->method('isValid')
			->will($this->returnValue(TRUE));
		$target->expects($this->once())
			->method('wakeup')
			->with($this->equalTo($target_wakeup_data));
		$target->expects($this->once())
			->method('pickTarget')
			->will($this->returnValue(TRUE));

		$this->campaign->setTarget($target);
		$this->campaign->setRules($rules);
		$this->campaign->setPickTargetRules($rules2);

		$wakeup_data = array(
			'valid' => TRUE,
			'pick_target_rules_result' => NULL,
			'state_data' => $state_data,
			'target' => $target_wakeup_data
		);

		$this->campaign->wakeup($wakeup_data);
		$valid = $this->campaign->isValid($bbx_data, $state_data);
		$this->assertType('bool', $valid);
		$this->assertEquals($wakeup_data['valid'], $valid);
		$winner = $this->campaign->pickTarget($bbx_data);
		
		// Since the valid and pick_target_rules_result were set to true, the target should
		// wrap itself in a winner object and return that object
		$this->assertType('Blackbox_IWinner', $winner);
		$this->assertEquals($this->campaign, $winner->getTarget());
		
	}
	
	/**
	 * Test the wakeup function on a target that was deemed valid and passed pick target rules
	 *
	 * @return void
	 */
	public function testWakeupValidAndPassed()
	{
		$bbx_data = $this->bb_data;
		$state_data = $this->state_data;
		$target_wakeup_data = array('I slept with the target');
		
		// IsValid will not run since it has been loaded to true
		$rules = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$rules->expects($this->never())->method('isValid');

		// Pick Target will not run because pick_target_rules_result will be set
		$rules2 = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$rules2->expects($this->never())->method('isValid');

		$target = $this->getMock(
			'OLPBlackbox_Target',
			array('isValid', 'pickTarget', 'wakeup'),
			array('Target', 0)
		);
		$target->expects($this->once())
			->method('isValid')
			->will($this->returnValue(TRUE));
		$target->expects($this->once())
			->method('wakeup')
			->with($this->equalTo($target_wakeup_data));
		$target->expects($this->once())
			->method('pickTarget')
			->will($this->returnValue(TRUE));

		$this->campaign->setTarget($target);
		$this->campaign->setRules($rules);
		$this->campaign->setPickTargetRules($rules2);

		$wakeup_data = array(
			'valid' => TRUE,
			'pick_target_rules_result' => TRUE,
			'state_data' => $state_data,
			'target' => $target_wakeup_data
		);

		$this->campaign->wakeup($wakeup_data);
		$valid = $this->campaign->isValid($bbx_data, $state_data);
		$this->assertType('bool', $valid);
		$this->assertEquals($wakeup_data['valid'], $valid);
		$winner = $this->campaign->pickTarget($bbx_data);

		// Since the valid and pick_target_rules_result were set to true, the target should
		// wrap itself in a winner object and return that object
		$this->assertType('Blackbox_IWinner', $winner);
		$this->assertEquals($this->campaign, $winner->getTarget());

	}
}
?>
