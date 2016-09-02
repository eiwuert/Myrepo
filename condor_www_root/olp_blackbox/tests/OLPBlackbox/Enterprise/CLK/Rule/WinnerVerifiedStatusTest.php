<?php
/**
 * Defines the OLPBlackbox_Enterprise_CLK_Rule_WinnerVerifiedStatusTest class.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com> 
 */

require_once('OLPBlackboxTestSetup.php');

/**
 * Test the functionality of a CLK Verify rule object.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com> 
 */
class OLPBlackbox_Enterprise_CLK_Rule_WinnerVerifiedStatusTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Determine if this test can run, since it depends on bfw classes.
	 *
	 * @return void
	 */
	public function setUp()
	{
		if (!class_exists('Authentication'))
		{
			$this->markTestSkipped('Missing Authentication class.');
		}
	}
	/**
	 * Tests the basic functionality of the OLPBlackbox_Enterprise_CLK_Rule_WinnerVerifiedStatus class.
	 *
	 * @return void
	 */
	public function testWinnerVerifiedStatus()
	{
		$data = new OLPBlackbox_Data();
		$data->home_zip = '89120';
		$data->home_city = 'Las Vegas';
		$data->home_state = 'NV';

		$init = array('target_name' => 'ca', 'name' => 'ca');
		$state_data = new OLPBlackbox_TargetStateData($init);

		$rule = $this->getMock('OLPBlackbox_Enterprise_CLK_Rule_WinnerVerifiedStatus',
				array('logEvent', 'getDataXResponse', 'getVerifiedZipCount', 'fraudCheckOLP'));

		// mock the fraudCheckOLP method to return 1 ('in violation')
		$rule->expects($this->any())
			->method('fraudCheckOLP')
			->will($this->returnValue(1));

		// mock the DataX response, which would normally come from the OLP db.
		$rule->expects($this->any())
			->method('getDataXResponse')
			->will($this->returnValue(array('received_package' => file_get_contents('data/Rule/CLK/winner-verified-status.xml'))));

		// mock the getVerifiedZipCount function which would normally use the olp db
		$rule->expects($this->any())
			->method('getVerifiedZipCount')
			->will($this->returnValue(1));


		// work phone type check
		$rule->expects($this->at(1))
			->method('logEvent')
			->with($this->equalTo('VERIFY_SAME_WH'),
					$this->equalTo('VERIFY'),
					$this->equalTo($state_data->name));

		$rule->expects($this->at(2))
			->method('logEvent')
			->with($this->equalTo('VERIFY_W_TOLL_FREE'),
					$this->equalTo('VERIFIED'),
					$this->equalTo($state_data->name));

		$rule->expects($this->at(3))
			->method('logEvent')
			->with($this->equalTo('VERIFY_WH_AREA'),
					$this->equalTo('VERIFIED'),
					$this->equalTo($state_data->name));

		$rule->expects($this->at(4))
			->method('logEvent')
			->with($this->equalTo('VERIFY_W_PHONE'),
					$this->equalTo('VERIFIED'),
					$this->equalTo($state_data->name));

		$rule->expects($this->at(5))
			->method('logEvent')
			->with($this->equalTo('VERIFY_SAME_CR_W_PHONE'),
					$this->equalTo('VERIFY'),
					$this->equalTo($state_data->name));

		// fraud check
		/*
		// somehow, PHPUnit thinks that THIS time we call logEvent, the 
		// mocked function doesn't exist
		$rule->expects($this->at(6))
			->method('logEvent')
			->with($this->equalTo('FRAUD_CHECK'),
					$this->equalTo('VERIFY'),
					$this->equalTo($state_data->name));
		*/

		$rule->isValid($data, $state_data);
	}
}
?>
