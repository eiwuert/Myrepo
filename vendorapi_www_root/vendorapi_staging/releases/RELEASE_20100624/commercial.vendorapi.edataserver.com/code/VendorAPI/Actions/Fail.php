<?php

/**
 * Processes Application Failures. Should be passed the serialized state object as the first parameter
 *
 *
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class VendorAPI_Actions_Fail extends VendorAPI_Actions_Base
{
	/**
	 * @var VendorAPI_IApplicationFactory
	 */
	protected $app_factory;
	
	/**
	 * @param VendorAPI_IDriver $driver
	 * @param VendorAPI_IApplicationFactory $app_factory
	 */
	public function __construct(VendorAPI_IDriver $driver, VendorAPI_IApplicationFactory $app_factory)
	{
		parent::__construct($driver);
		$this->app_factory = $app_factory;
	}

	/**
	 * Executes the Fail Action
	 *
	 * @param string $state - serialized state object
	 * @return VendorAPI_Response
	 */
	public function execute(array $data = NULL, $state = NULL)
	{
		if (isset($data['ecash_application_id']))
		{
			$state = $this->getStateObjectByApplicationID($data['ecash_application_id']);
		}
		else
		{
			$state = $this->app_factory->createStateObject($this->getCallContext());
		}

		// ecash reacts that fail a rule on the OLP side send
		// all of the application data so we can save the full app
		if (is_array($data) && empty($data['ecash_application_id']))
		{
			$state_persistor = new VendorAPI_StateObjectPersistor($state);
			$persistor = new VendorAPI_TemporaryPersistor($state_persistor);
			$app = $this->app_factory->createApplication(
				$persistor,
				$state,
				$this->getCallContext(),
				$data
			);

			$app->updateStatus('denied::applicant::*root', $this->getCallContext()->getApiAgentId());
			$this->saveToAppService($state, $app, $this->driver->getAppClient(), $data, $persistor);
			$app->save($state_persistor, TRUE);
			$this->saveState($state);
		}
		// applications that fail a rule on the confirmation/agree page need to be failed
		elseif ($state->isPart('application'))
		{
			$persistor = new VendorAPI_StateObjectPersistor($state);
			$app = $this->app_factory->getApplication(
				$state->application_id,
				$persistor,
				$state
			);

			$app->updateStatus('denied::applicant::*root', $this->getCallContext()->getApiAgentId());
			$this->saveState($state);
		}

file_put_contents('/tmp/faillog', print_r($state,true));		
		return new VendorAPI_Response($state, VendorAPI_Response::SUCCESS);
	}
	
	/**
	 * Returns Application Factory.
	 *
	 * @return VendorAPI_IApplicationFactory
	 */
	protected function getApplicationFactory()
	{
		return $this->app_factory;
		
	}

}

?>
