<?php
/**
 * Preview a document
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_Actions_PreviewDocument extends VendorAPI_Actions_Base
{
	/**
	 * @var VendorAPI_IApplicationFactory
	 */
	protected $app_factory;

	/**
	 * @var VendorAPI_ITokenProvider
	 */
	protected $provider;

	/**
	 * @var VendorAPI_IDocument
	 */
	protected $document;

	protected $application;

	/**
	 * @param VendorAPI_IApplicationFactory $app_factory
	 * @param VendorAPI_IDocument $document
	 */
	public function __construct(
		VendorAPI_IDriver $driver,
		VendorAPI_IApplicationFactory $app_factory,
		VendorAPI_ITokenProvider $provider,
		VendorAPI_IDocument $document
	)
	{
		parent::__construct($driver);
		$this->app_factory = $app_factory;
		$this->provider = $provider;
		$this->document = $document;
	}

	/**
	 * Generates a document preview of the given template for the given application
	 *
	 * @param int $application_id
	 * @param string $template
	 * @param string $serialized_state
	 * @return VendorAPI_Response
	 */
	public function execute($application_id, $template, array $data = NULL, $serialized_state = NULL)
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

		// CSO (and soon AMG?) can change the loan amount on a preview
		$loan_amount = isset($data['loan_amount'])
			? $data['loan_amount']
			: NULL;

		$persistor = new VendorAPI_StateObjectPersistor($state);
		$this->application = $this->app_factory->getApplication($application_id, $persistor, $state);

		$this->setPayDateModel($data);
		$this->setReferences($data);

		$tokens = $this->provider->getTokens($this->application, TRUE, $loan_amount);
		$document = $this->document->previewDocument($template, $tokens, $this->getCallContext());
		unset($tokens['Time'], $tokens['Today']);
		$hash = $this->document->previewDocument($template, $tokens, $this->getCallContext());

		
		// If we were provided any data we need to get a fresh application and
		// state so we don't save the preview data to the application
		if (!empty($data))
		{
			$state = $this->getStateObject($application_id, $serialized_state);
			$this->application = $this->getApplication($application_id, $state);
		}

		// record the preview hash so that we can compare it later
		$this->application->recordDocumentPreview($hash, $this->getCallContext());
		$this->saveState($state);

		return new VendorAPI_Response(
			$state,
			VendorAPI_Response::SUCCESS,
			array('document' => $document->getDocumentContent())
		);
	}

	/**
	 * Generates a document preview of the given template for the given application, and then sends it to the email
	 *
	 * @param int $application_id
	 * @param string $template
	 * @param string $serialized_state
	 * @return VendorAPI_Response
	 */
	public function send($application_id, $template, $email_ary, array $data = NULL, $serialized_state = NULL)
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
		$this->application = $this->app_factory->getApplication($application_id, $persistor, $state);

		$document = $this->document->create($template, $this->application, $this->provider, $this->getCallContext());
		$result = $this->document->sendDocument($document->getDocumentId(),$email_ary);
		
		// If we were provided any data we need to get a fresh application and
		// state so we don't save the preview data to the application
		if (!empty($data))
		{
			$state = $this->getStateObject($application_id, $serialized_state);
			$this->application = $this->getApplication($application_id, $state);
		}
		$this->application->addDocument($document, $this->getCallContext());
		$this->saveState($state);

		return new VendorAPI_Response(
			$state,
			VendorAPI_Response::SUCCESS,
			array('document' => $document->getDocumentContent())
		);
	}

	/**
	 * Get a VendorAPI_IApplication using the supplied data
	 * @param integer $application_id
	 * @param VendorAPI_StateObject $serialized_state
	 * @return VendorAPI_IApplication
	 */
	protected function getApplication($application_id, $state)
	{
		$persistor = new VendorAPI_StateObjectPersistor($state);
		return $this->app_factory->getApplication($application_id, $persistor, $state);
	}

	/**
	 * Get a VendorAPI_StateObject using the supplied data
	 * @param integer $application_id
	 * @param string $serialized_state
	 * @return VendorAPI_StateObject
	 */
	protected function getStateObject($application_id, $serialized_state = NULL)
	{
		if ($serialized_state == NULL)
		{
			$state = $this->getStateObjectByApplicationID($application_id);
		}
		else
		{
			$state = parent::getStateObject($serialized_state);
		}
		return $state;
	}

	/**
	 * setApplicationObject
	 *
	 * Sets Items in Application object based on key.
	 * Item value will be used for col name translations
	 *
	 * @param array $items
	 * @param array $data
	 * @return void
	 */
	protected function setApplicationObject($items, $data)
	{
		$app_data = array();
		foreach ($items as $from_item => $to_item)
		{
			if (!empty($data[$to_item]))
			{
				$app_data[$from_item] = $data[$to_item];
			}
		}

		$this->application->setApplicationData($app_data);
	}

	/**
	 * Set Relationship Information
	 *
	 * @param array $data
	 * @return void
	 */
	protected function setReferences($data)
	{
		for ($i = 1; isset($data['ref_0'.$i.'_name_full']); $i++)
		{
			$this->application->addPersonalReference(
				$this->getCallContext(),
				$data['ref_0'.$i.'_name_full'],
				$data['ref_0'.$i.'_phone_home'],
				$data['ref_0'.$i.'_relationship']);
		}
	}

	/**
	 * Set Pay Date Information
	 *
	 * @param array $data
	 * @return void
	 */
	protected function setPayDateModel($data)
	{
		$items = array(
			'income_frequency' 	=> 'income_frequency',
			'paydate_model'		=> 'paydate_model',
			'day_of_week'		=> 'day_of_week',
			'last_paydate'		=> 'last_paydate',
			'day_of_month_1'    => 'day_of_month_1',
			'day_of_month_2'	=> 'day_of_month_2',
			'week_1'			=> 'week_1',
			'week_2'			=> 'week_2'
		);

		$this->setApplicationObject($items, $data);

//		$this->application->setApplicationData(array('date_first_payment' => NULL));
		$this->application->calculateQualifyInfo(TRUE);
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
