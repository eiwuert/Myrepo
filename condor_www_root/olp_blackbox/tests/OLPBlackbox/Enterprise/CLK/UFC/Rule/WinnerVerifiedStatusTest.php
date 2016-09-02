<?php
/**
 * Defines the OLPBlackbox_Enterprise_CLK_UFC_Rule_WinnerVerifiedStatusTest class.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com> 
 */

require_once('OLPBlackboxTestSetup.php');

/**
 * Test the functionality of a UFC WinnerVerifiedStatus rule.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com> 
 */
class OLPBlackbox_Enterprise_CLK_UFC_Rule_WinnerVerifiedStatusTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Sets up the test class, skipping it's tests if olp/authentication can't be included.
	 * 
	 * @return void
	 */
	public function setUp()
	{
		if (!class_exists('Authentication')) 
		{
			$this->markTestSkipped('The Authentication class is not available.');
		}
	}

	/**
	 * Test the functionality of the OLPBlackbox_Enterprise_CLK_UFC_Rule_WinnerVerifiedStatus class.
	 *
	 * @return void
	 */
	public function testWinnerVerifiedStatus()
	{
		$data = new OLPBlackbox_Data();
		$data->phone_home = '123451234';
		$data->phone_work = '321544321';
		$data->income_type = 'EMPLOYMENT';

		$init = array('name' => 'ca', 'target_name' => 'ca');
		$state_data = new OLPBlackbox_TargetStateData($init);

		$rule = $this->getMock(
			'OLPBlackbox_Enterprise_CLK_UFC_Rule_WinnerVerifiedStatus',
			array('logEvent', 'getDataXResponse', 'fraudCheckOLP', 'canRun'));

		// mock the canRun rule so we can disregard $config->blackbox_mode
		$rule->expects($this->any())
			->method('canRun')
			->will($this->returnValue(TRUE));

		// mock the fraudCheckOLP method to return 0 ('no violations')
		$rule->expects($this->any())
			->method('fraudCheckOLP')
			->will($this->returnValue(0));

		// mock up datax response for testing purposes
		$rule->expects($this->any())
			->method('getDataXResponse')
			->will($this->returnValue(array('received_package' => file_get_contents('data/Rule/CLK/winner-verified-status.xml'))));

		// set up the expected log calls
		$rule->expects($this->at(1))
			->method('logEvent')
			->with($this->equalTo('VERIFY_SAME_WH'),
					$this->equalTo('PASS'),
					$this->equalTo($state_data->name));

		$rule->expects($this->at(3))
			->method('logEvent')
			->with($this->equalTo('CHECK_DATAX_REFERRAL'),
					$this->equalTo('FAIL'),
					$this->equalTo($state_data->name));

		$rule->isValid($data, $state_data);
	}
}
?>
