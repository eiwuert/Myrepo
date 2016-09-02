<?php
/**
 * Defines the WinnerVerifiedStatusTest class.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */

require_once('OLPBlackboxTestSetup.php');

/**
 * Class for testing the WinnerVerifiedStatus class, which implements IRule.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Generic_Rule_WinnerVerifiedStatusTest extends PHPUnit_Framework_TestCase
{

	/**
	 * Set up the Rule Test.
	 *
	 * @return void
	 */
	public function setUp()
	{
		if (!class_exists('Authentication'))
		{
			$this->markTestSkipped('Authentication class not available.');
		}
	}
	/**
	 * Tests the basic functionality of the OLPBlackbox_Enterprise_Generic_Rule_WinnerVerifiedStatus class.
	 *
	 * @return void
	 */
	public function testWinnerVerifiedStatus()
	{
		$data = new OLPBlackbox_Data();
		$data->income_monthly_net = 3000;
		$data->paydates = array(strtotime('-1 week'),
								strtotime('now'),
								strtotime('+2 day'));

		$init = array('name' => 'ca', 'target_name' => 'ca');
		$state_data = new OLPBlackbox_TargetStateData($init);

		// mock up the rule
		$rule = $this->getMock('OLPBlackbox_Enterprise_Generic_Rule_WinnerVerifiedStatus',
				array('logEvent', 'fraudCheckOLP', 'hitEvent', 'hitStat'));

		// mock the fraudCheckOLP method to return 0 ('no violations')
		$rule->expects($this->any())
			->method('fraudCheckOLP')
			->will($this->returnValue(0));

		// set up the expected values for the side effect logging
		$rule->expects($this->at(0))
			->method('logEvent')
			->with($this->equalTo('VERIFY_MIN_INCOME'),
					$this->equalTo('VERIFIED'),
					$this->equalTo($state_data->name));

		$rule->expects($this->at(1))
			->method('logEvent')
			->with($this->equalTo('VERIFY_PAYDATES'),
					$this->equalTo('VERIFY'),
					$this->equalTo($state_data->name));

		$rule->isValid($data, $state_data);
	}
}
?>
