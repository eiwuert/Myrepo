<?php
/**
 * The SignDocument action creates and signs a document in Condor.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class VendorAPI_Actions_SignDocument extends VendorAPI_Actions_Base
{
	/**
	 * Application Factory
	 *
	 * @var VendorAPI_IApplicationFactory
	 */
	protected $app_factory;

	/**
	 * Document object
	 *
	 * @var VendorAPI_IDocument
	 */
	protected $document;

	/**
	 *
	 * @var VendorAPI_ITokenProvider
	 */
	protected $provider;

	/**
	 *
	 * @var VendorAPI_IDriver
	 */
	protected $driver;

	/**
	 * Constructor
	 *
	 * @param VendorAPI_IApplicationFactory $app_factory
	 * @param VendorAPI_IDocument           $document
	 * @param VendorAPI_ITokenProvider      $provider
	 * @param VendorAPI_IDriver             $driver
	 */
	public function __construct(
		VendorAPI_IApplicationFactory $app_factory,
		VendorAPI_IDocument $document,
		VendorAPI_ITokenProvider $provider,
		VendorAPI_IDriver $driver
	)
	{
		$this->app_factory = $app_factory;
		$this->document = $document;
		$this->provider = $provider;
		$this->driver = $driver;
	}


	/**
	 * Executes the action.
	 *
	 * @param int    $application_id   an integer with the application_id
	 * @param string $template         a string with the name of the template being created/signed
	 * @param string $update_now       Update the db now
	 * @param string $serialized_state a string with a serialized state object

	 * @return VendorAPI_Response
	 */
	public function execute($application_id, $template, $update_now, $state_object = NULL)
	{
		$this->call_context->setApplicationId($application_id);

		$error = FALSE;
		try
		{
			if ($state_object == NULL)
			{
				$state = $this->getStateObjectByApplicationID($application_id);
			}
			else
			{
				$state = $this->getStateObject($state_object);
			}

			$state->application_id = $application_id;
			$persistor = new VendorAPI_StateObjectPersistor($state);
			$app = $this->app_factory->getApplication($application_id, $persistor, $state);
			$docdata = $this->document->create($template, $app, $this->provider, $this->getCallContext());
			if (!$this->document->signDocument($app, $docdata, $this->getCallContext()))
			{
				$error = 'Sign failed';
			}
			else
			{
				$app->addDocument($docdata, $this->getCallContext());
				if ($update_now)
				{
					$app = $this->getDAOApplication();
					if(!$app->save($state))
					{
						$this->saveState($state); // who knows maybe it'll work!
						throw new Exception('Application was not saved');
					}
				}
				else
				{
					$this->saveState($state);
				}

			}
		}
		catch (Exception $e)
		{
			$error = $e->getMessage();
		}
		$result = array();
		if ($docdata instanceof VendorAPI_DocumentData)
		{
			$result['archive_id'] = $docdata->getDocumentId();
			if (is_numeric($result['archive_id']))
			{
				$result['signed'] = TRUE;
			}
		}

		return new VendorAPI_Response(
			$state,
			(empty($error) ? VendorAPI_Response::SUCCESS : VendorAPI_Response::ERROR),
			$result,
			empty($error) ? null : $error
		);
	}

	/**
	 *
	 * @return unknown_type
	 */
	public function getDAOApplication()
	{
		return new ECash_VendorAPI_DAO_Application($this->driver);
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
