<?php

require_once('OLPBlackboxTestSetup.php');

/**
 * Contains base functionality for Vetting picker classes.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
abstract class OLPBlackbox_Vetting_PickerBaseTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Returns a campaign object that will return a winner one time only.
	 *
	 * @param string $name Name of the campaign.
	 * @param int $weight Campaign weight.
	 * @param int $leads Number of leads this campaign pretends it's gotten.
	 * 
	 * @return Blackbox_IWinner
	 */
	public function getValidCampaign($name, $weight, $leads)
	{
		// the campaign and target will not run without a rule.
		// mock onValid to prevent stats hits/event hits
		$mock_rule = $this->getMock(
			'OLPBlackbox_DebugRule',
			array('isValid', 'canRun', 'onValid')
		);
		$mock_rule->expects($this->any())
			->method('isValid')
			->will($this->returnValue(TRUE));
		$mock_rule->expects($this->any())
			->method('canRun')
			->will($this->returnValue(TRUE));
		$mock_rule->expects($this->any())
			->method('onValid')
			->will($this->returnValue(NULL));
		
		// mock target to feed to the campaign
		$target = $this->getMock('OLPBlackbox_Target',
			array('runRule', 'canRun'),
			array($name)
		);
		$target->setRules($mock_rule);
		$target->expects($this->any())
			->method('runRule')
			->will($this->returnValue(TRUE));
		$target->expects($this->any())
			->method('canRun')
			->will($this->returnValue(TRUE));
		
		// mock the actual campaign, add rules and set the leads to it should
		// pretend it has.
		$campaign = $this->getMock('OLPBlackbox_Campaign',
			array('getCurrentLeads'),
			array($name, $weight, $target)
		);
		$campaign->setRules($mock_rule);
		$campaign->expects($this->any())
			->method('getCurrentLeads')
			->will($this->returnValue($leads));
									
		return $campaign;
	}	
}

?>
