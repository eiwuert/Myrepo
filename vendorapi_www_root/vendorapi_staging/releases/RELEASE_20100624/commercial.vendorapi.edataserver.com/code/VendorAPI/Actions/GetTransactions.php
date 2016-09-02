<?php

/**
 * Returns transations for a given application
 *
 * @author Randy Klepetko andy.klepetko@clearlakeholdings.com
 */
class VendorAPI_Actions_GetTransactions extends VendorAPI_Actions_Base
{
	/**
	 * Token provider
	 *
	 * @var VendorAPI_ITokenProvider
	 */
	protected $provider;

	/**
	 *
	 * @var VendorAPI_IApplicationFactory
	 */
	protected $application_factory;
	
	protected $schedule;

	protected $factory;

	/**
	 * construct
	 * @param VendorAPI_IDriver $driver
	 * @param VendorAPI_ITokenProvider $provider
	 */
	public function __construct(
		VendorAPI_IDriver $driver,
		ECash_Factory $factory,
		VendorAPI_IApplicationFactory $application_factory)
	{
		parent::__construct($driver);
		$this->provider = $provider;
		$this->factory = $factory;
		$this->application_factory = $application_factory;
	}

	/**
	 * Execute the action
	 *
	 * @param Integer $application_id
	 * @param array $data
	 * @param string $serialized_state
	 * @return VendorAPI_Response
	 */
	public function execute($application_id)
	{
		$this->call_context->setApplicationId($application_id);

		if ($serialized_state == NULL)
		{
			$state = $this->getStateObjectByApplicationID($application_id);
		}
		else
		{
			$state = $this->getStateObject($serialized_state);
		}

		$persistor = new VendorAPI_StateObjectPersistor($state);

		$schedule = $this->getSchedule($application_id);

		return new VendorAPI_Response(
			$state,
			VendorAPI_Response::SUCCESS,
			array(
				'transactions' => $schedule
			)
		);
	}

	/**
	 * Finds schedule. Returns an application schedule modle.
	 *
	 * @param string $username
	 * @return ECash_Models_Customer
	 */
	function getSchedule($application_id)
	{
		$application = $this->factory->getApplication($application_id,$this->driver->getCompanyID());

		if (!$application)
		{
			return false;
		}
		else
		{
			$this->application = $application;
		}
		$schedule = $application->getSchedule();
		$balance = $schedule->getBalanceInformation();
		$next_pay = $schedule->getScheduledPayments();

		return array($balance, $next_pay);
	}

	/**
	 * Returns Application Factory.
	 *
	 * @return VendorAPI_IApplicationFactory
	 */
	protected function getApplicationFactory()
	{
		return $this->application_factory;

	}
}

?>
