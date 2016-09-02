<?php
require_once('OLPBlackboxTestSetup.php');

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
	 * Tests that we get back exceptions if we call isValid before target or rules were set.
	 *
	 * @expectedException Blackbox_Exception
	 * @return void
	 */
	public function testIsValidExceptions()
	{
		try
		{
			$this->campaign->isValid($this->bb_data, $this->state_data);
		}
		catch (Blackbox_Exception $e)
		{
			$target = $this->getMock('Blackbox_Target', array('isValid'));
			$target->expects($this->never())->method('isValid');

			$this->campaign->setTarget($target);
			$this->campaign->isValid($this->bb_data, $this->state_data);
		}

		$this->fail('Failed to throw exception on missing target.');
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
	 * Tests that we get exceptions with bad constructor data.
	 *
	 * @param mixed $param1 what we want to pass to the first constructor parameter
	 * @param mixed $param2 what we want to pass to the second constructor parameter
	 * @param mixed $param3 what we want to pass to the third constructor parameter
	 * @expectedException InvalidArgumentException
	 * @dataProvider constructorExceptionsDataProvider
	 * @return void
	 */
	public function testConstructorExceptions($param1, $param2, $param3)
	{
		new OLPBlackbox_Campaign($param1, $param2, $param3);
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
}
?>
