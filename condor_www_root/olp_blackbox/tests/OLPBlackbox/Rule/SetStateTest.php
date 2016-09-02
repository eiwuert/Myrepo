<?php

require_once('OLPBlackboxTestSetup.php');

/**
 * Tests the class {@see OLPBlackbox_Rule_SetState}.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Rule_SetStateTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Blackbox Data for the test.
	 *
	 * @var OLPBlackbox_Data
	 */
	protected $data = NULL;
	
	/**
	 * State data for the test.
	 *
	 * @var OLPBlackbox_CampaignStateData
	 */
	protected $state_data = NULL;
	
	/**
	 * Set up this test to run.
	 *
	 * @return void
	 */
	public function setUp()
	{
		$this->data_obj = new OLPBlackbox_Data();
		$init = array('name' => 'ca', 'campaign_name' => 'ca1');
		$this->state_data = new OLPBlackbox_CampaignStateData($init);
	}
	
	/**
	 * Test trying to set a flag in state data which does not exist.
	 *
	 * @return void
	 */
	public function testBadStateSet()
	{
		$rule = $this->getMock('OLPBlackbox_Rule_SetState',
			array('onInvalid'),
			array('asdfasdf', 'k3239k')
		);
		
		$rule->expects($this->once())->method('onInvalid');
		
		$rule->isValid($this->data_obj, $this->state_data);
		$this->assertEquals(NULL, $this->state_data->asdfasdf);
	}
	
	/**
	 * Test setting a flag in the state data legitimately.
	 *
	 * @return void
	 */
	public function testGoodStateSet()
	{
		$rule = $this->getMock('OLPBlackbox_Rule_SetState',
			array('onValid'),
			array('partner_weekly_vetting_lead')
		);
		$rule->expects($this->once())->method('onValid');
		
		$this->assertTrue($rule->isValid($this->data_obj, $this->state_data));
		$this->assertEquals(TRUE, $this->state_data->partner_weekly_vetting_lead);
	}
}

?>
