<?php

class VendorAPI_Actions_GetStatus extends VendorAPI_Actions_Base
{
	protected $application_factory;

	public function __construct(VendorAPI_IDriver $driver,
		VendorAPI_IApplicationFactory $application_factory
	)
	{
		parent::__construct($driver);
		$this->application_factory = $application_factory;

	}

	public function execute($application_id, $state_object = NULL)
	{
		$this->call_context->setApplicationId($application_id);

		if ($state_object == NULL)
		{
			$state = $this->getStateObjectByApplicationID($application_id);
		}
		else
		{
			$state = $this->getStateObject($state_object);
		}

		$persistor = new VendorAPI_StateObjectPersistor($state);
		$application = $this->application_factory->getApplication($application_id, $persistor, $state);
		return new VendorAPI_Response(
			$state,
			VendorAPI_Response::SUCCESS,
			array(
				'status_id' => $application->getApplicationStatusId(),
				'status' => $application->getApplicationStatus()
			)
		);
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
