<?php
/**
 * Defines the OLPBlackbox_Enterprise_Agean_Rule_WinnerVerifiedStatusTest class.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com> 
 */

require_once('OLPBlackboxTestSetup.php');

/**
 * Test the functionality of an Agean Verify rule object.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com> 
 */
class OLPBlackbox_Enterprise_Agean_Rule_WinnerVerifiedStatusTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Determine if this test can run since it depends on bfw code.
	 *
	 * @return void
	 */
	public function setUp()
	{
		if (!class_exists('Authentication'))
		{
			$this->markTestIncomplete('Authentication class not available.');
		}
	}
	/**
	 * Test the event logging functionality of this rule.
	 *
	 * @return void
	 */
	public function testWinnerVerifiedStatus()
	{
		// fake application data
		$data = new OLPBlackbox_Data();
		$data->phone_home = '3419098939';
		$data->phone_work = '3419098939';
		$data->income_type = 'BENEFITS';
		$data->paydates = array(strtotime('-1 week'),
								strtotime('now'),
								strtotime('+2 day'));
		$init = array('name' => 'ca', 'target_name' => 'ca');
		$state_data = new OLPBlackbox_TargetStateData($init);

		$rule = $this->getMock('OLPBlackbox_Enterprise_Agean_Rule_WinnerVerifiedStatus', array('logEvent'));

		$this->assertTrue($rule->isValid($data, $state_data));
	}
}
?>
