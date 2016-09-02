<?php

require_once('OLPBlackboxTestSetup.php');

/**
 * Tests the functionality of the OLPBlackbox_Vetting_OrderedPicker class.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Vetting_OrderedPickerTest extends OLPBlackbox_Vetting_PickerBaseTest
{
	public function testRepick()
	{
		$data = new OLPBlackbox_Data();
		$state_data = new OLPBlackbox_StateData();
		
		$campaign1 = $this->getValidCampaign('ca1', 2, 5);
		$campaign2 = $this->getValidCampaign('ca2', 4, 2);
		
		$collection = new OLPBlackbox_TargetCollection('vet');
		$collection->addTarget($campaign1);
		$collection->addTarget($campaign2);
		
		$collection->setPicker(new OLPBlackbox_Vetting_OrderedPicker());
		
		$collection->isValid($data, $state_data);
		
		// ca1 should be picked, despite it being less weighty and 
		// having more leads than ca2
		$winner = $collection->pickTarget($data);
		$this->assertTrue($winner instanceof Blackbox_IWinner);
		$this->assertEquals('ca1', $winner->getStateData()->campaign_name);
		
		// despite the fact that ca2 is valid, the next call should return FALSE.
		$this->assertFalse($collection->pickTarget($data));
	}
}

?>
