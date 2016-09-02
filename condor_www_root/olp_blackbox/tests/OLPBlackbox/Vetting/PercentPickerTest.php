<?php

require_once('OLPBlackboxTestSetup.php');

/**
 * Tests the functionality of the OLPBlackbox_Vetting_PercentPicker class.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Vetting_PercentPickerTest extends OLPBlackbox_Vetting_PickerBaseTest
{
	/**
	 * Test the repick functionality of a OLPBlackbox_Vetting_PercentPicker.
	 *
	 * The OLPBlackbox_Vetting_PercentPicker only picks once and it picks the
	 * same target on subsequent runs even if there are other (valid) targets
	 * to choose from.
	 * 
	 * @return void
	 */
	public function testRepick()
	{
		// we want to save a snapshot
		$config = OLPBlackbox_Config::getInstance();
		$old_config_snapshot = $config->allowSnapshot;
		unset($config->allowSnapshot);
		$config->allowSnapshot = TRUE;
		
		$data = new OLPBlackbox_Data();
		$state_data = new OLPBlackbox_StateData();
		
		// mock up a couple of objects to pick from
		$campaign1 = $this->getValidCampaign('ca1', 3, 5);
		$campaign2 = $this->getValidCampaign('ca2', 5, 1);
		
		$collection = new OLPBlackbox_TargetCollection('vet');
		$collection->addTarget($campaign1);
		$collection->addTarget($campaign2);
		
		$collection->setPicker(new OLPBlackbox_Vetting_PercentPicker());
		
		// ca2 should be picked because it's number of leads is lower
		$collection->isValid($data, $state_data);
		$winner = $collection->pickTarget($data);
		$this->assertTrue($winner instanceof Blackbox_IWinner);
		$this->assertEquals('ca2', $winner->getStateData()->campaign_name);
		
		// despite the fact that ca1 is valid, the next call should return FALSE.
		$this->assertFalse($collection->pickTarget($data));
		
		// restore allowSnapshot option
		unset($config->allowSnapshot);
		$config->allowSnapshot = $old_config_snapshot;
	}
}

?>
