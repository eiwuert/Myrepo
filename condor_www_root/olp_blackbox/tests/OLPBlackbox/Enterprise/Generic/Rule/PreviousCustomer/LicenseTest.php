<?php

require_once 'OLPBlackboxTestSetup.php';

class OLPBlackbox_Enterprise_Generic_Rule_PreviousCustomer_LicenseTest extends PHPUnit_Framework_TestCase
{
	protected $provider_ecash;
	protected $provider_olp;
	protected $decider;
	protected $rule;
	protected $blackbox_data;
	protected $state;

	/**
	 * Performs a bunch of setup
	 * @return void
	 */
	public function setUp()
	{
		$this->provider_ecash = $this->getMock(
			'OLPBlackbox_Enterprise_ICustomerHistoryProvider',
			array('getHistoryBy', 'excludeApplication')
		);

		$this->provider_olp = $this->getMock(
			'OLPBlackbox_Enterprise_ICustomerHistoryProvider',
			array('getHistoryBy', 'excludeApplication')
		);

		$this->decider = new OLPBlackbox_Enterprise_Generic_Decider();

		$this->rule = new OLPBlackbox_Enterprise_Generic_Rule_PreviousCustomer_License(
			$this->provider_olp,
			$this->provider_ecash,
			$this->decider
		);

		$this->blackbox_data = new OLPBlackbox_Data();

		$this->state = new OLPBlackbox_Enterprise_TargetStateData(
			array('customer_history' => new OLPBlackbox_Enterprise_CustomerHistory())
		);
	}

	/**
	 * Tests that the provider is called with the license in data
	 * @group previousCustomer
	 * @return void
	 */
	public function testUsesLicenseFromData()
	{
		$this->provider_ecash->expects($this->once())
			->method('getHistoryBy')
			->with($this->equalTo(array('legal_id_number' => 123456789)), $this->anything());

		$this->blackbox_data->state_id_number = 123456789;
		$this->rule->isValid($this->blackbox_data, $this->state);
	}

	/**
	 * Tests that the rule skips when the license is missing
	 * @group previousCustomer
	 * @return void
	 */
	public function testSkipsWithoutLicense()
	{
		$rule = $this->getMock(
			'OLPBlackbox_Enterprise_Generic_Rule_PreviousCustomer_License',
			array('onSkip'),
			array($this->provider_ecash, $this->provider_olp, $this->decider)
		);

		$rule->expects($this->once())
			->method('onSkip');

		$rule->isValid($this->blackbox_data, $this->state);
	}
}

?>