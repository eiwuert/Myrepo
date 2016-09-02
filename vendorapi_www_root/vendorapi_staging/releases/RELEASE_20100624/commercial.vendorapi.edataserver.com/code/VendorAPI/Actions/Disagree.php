<?php

class VendorAPI_Actions_Disagree extends VendorAPI_Actions_Base
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
		
		if ($state_object == NULL)
		{
			$state = $this->getStateObjectByApplicationID($application_id);
		}
		else
		{
			$state = $this->getStateObject($state_object);
		}
		
		$persistor = new VendorAPI_StateObjectPersistor($state);
		try
		{
			$application = $this->application_factory->getApplication($application_id, $persistor, $state);
		}
		catch (Exception $e)
		{		
			return new VendorAPI_Response(
				$state,
				VendorAPI_Response::ERROR,
				array(),
				"invalid_application"
				);
		}	
					
		
		if ( empty($application) )
		{
			return new VendorAPI_Response(
				$state,
				VendorAPI_Response::ERROR,
				array(),
				"Invalid Application, app id: " + $application_id
				);
		}
		else if ( $application->company_id != $this->driver->getCompanyId() )
		{
			return new VendorAPI_Response(
				$state,
				VendorAPI_Response::ERROR,
				array(),
				"Company Id on Application does not match company id passed in. App Company " + $application->company_id + " Passed Company: " + $this->driver->getCompanyId() 
				);
		}
		
		try
		{
			$application->updateStatus("disagree::prospect::*root", $this->getCallContext()->getApiAgentId());
			
			$this->saveState($state);
			
			return new VendorAPI_Response(
				$state,
				VendorAPI_Response::SUCCESS,
				array(
					'status_id' => $application->getApplicationStatusId(),
					'status' => $application->getApplicationStatus()
				)
			);
		}	
		catch (Exception $e)
		{	
			return new VendorAPI_Response(
				$state,
				VendorAPI_Response::ERROR,
				array(),
				$e->getMessage() . " " . $e.getTraceAsString() 
				);
		}
		
		
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
