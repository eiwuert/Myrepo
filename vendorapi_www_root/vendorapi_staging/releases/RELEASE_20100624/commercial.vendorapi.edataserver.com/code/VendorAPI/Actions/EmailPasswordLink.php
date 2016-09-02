<?php
/**
 *
 */
class VendorAPI_Actions_EmailPasswordLink extends VendorAPI_Actions_Base
{
	/**
	 * Application Factory
	 *
	 * @var VendorAPI_IApplicationFactory
	 */
	protected $application_factory;

	/**
	 * VendorAPI Driver
	 *
	 * @var VendorAPI_IDriver
	 */
	protected $driver;

	/**
	 *
	 * @var VendorAPI_IApplication
	 */
	protected $application;

	/**
	 * @var VendorAPI_StateObject
	 */
	protected $state;

	/**
	 * Document object
	 *
	 * @var VendorAPI_IDocument
	 */
	protected $document;

	/**
	 * The success condition of the action.
	 *
	 * @var bool
	 */
	protected $success = VendorAPI_Response::ERROR;

	/**
	 * @var VendorAPI_ITokenProvider
	 */
	protected $provider;

	/**
	 * Validation errors to return in the response.
	 *
	 * @var array
	 */
	protected $validation_errors = array();

	/**
	 * Error string?
	 * @var String
	 */
	protected $error_msg;


	/**
	 * Constructor
	 *
	 * @param VendorAPI_IApplicationFactory             $app_factory an application factory
	 * @param VendorAPI_IDocument                       $document    a document object
	 * @param VendorAPI_Actions_Validators_ViewDocument $validator   a ViewDocument validator
	 * @param VendorAPI_IDriver                         $driver      a Driver object
	 */
	public function __construct(
		VendorAPI_IApplicationFactory $app_factory,
		VendorAPI_IDocument $document,
		VendorAPI_IDriver $driver,
		VendorAPI_ITokenProvider $provider
	)
	{
		$this->application_factory = $app_factory;
		$this->document = $document;
		$this->driver = $driver;
		$this->provider = $provider;
	}

	public function execute($ssn, $dob, $data = array(), $state = NULL)
	{
		$this->state = $this->getStateObject($state);
		$persistor = new VendorAPI_StateObjectPersistor($this->state);
		$this->application = $this->application_factory->getApplicationBySSNDOB($ssn, $dob, $persistor, $this->state);
		$application_id = $this->application->getApplicationId();
		$this->call_context->setApplicationId($application_id);

		$template = $this->application->getEmailPasswordLinkTemplate();

		$previewDocumentAction = new VendorAPI_Actions_PreviewDocument($this->driver, $this->application_factory, $this->provider, $this->document);
		$previewDocumentAction->setCallContext($this->call_context);
		$email_ary = array('email_primary' => $this->application->email,
				   'email_primary_name' => $this->application->name_first.' '.$this->application->name_last);

		return $previewDocumentAction->send($application_id, $template, $email_ary, $data);
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
