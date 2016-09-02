<?php
/**
 * Defines the OLPBlackbox_Enterprise_Impact_Rule_WinnerVerifiedStatusTest class.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com> 
 */

require_once('OLPBlackboxTestSetup.php');

/**
 * Class to test the OLPBlackbox_Enterprise_Impact_Rule_WinnerVerifiedStatus class.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com> 
 */
class OLPBlackbox_Enterprise_Impact_Rule_WinnerVerifiedStatusTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests the main functionality of the Rule (which is event logging)
	 * 
	 * @return void
	 */
	public function testWinnerVerifiedStatus()
	{
		$data = new OLPBlackbox_Data();
		$data->phone_home = '123451234';
		$data->phone_work = '123451234';
		$data->paydates = array(time(), strtotime('+1 week'));

		$init = array('target_name' => 'ca', 'name' => 'ca');
		$state_data = new OLPBlackbox_TargetStateData($init);

		$rule = $this->getMock('OLPBlackbox_Enterprise_Impact_Rule_WinnerVerifiedStatus',
						array('logEvent')
		);
		$rule->expects($this->at(0))
			->method('logEvent')
			->with($this->equalTo('VERIFY_SAME_WH'),
					$this->equalTo('VERIFY'),
					$this->equalTo($state_data->name));

		$rule->isValid($data, $state_data);
	}
}
?>
