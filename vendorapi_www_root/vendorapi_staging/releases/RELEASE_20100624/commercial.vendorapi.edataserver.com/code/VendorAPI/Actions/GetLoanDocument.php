<?php
/**
 *
 *
 * This call is so we get the loan document... like the name says =)
 * @author Bryan Campbell <bryan.campbell@dataxltd.com>
 */
class VendorAPI_Actions_GetLoanDocument extends VendorAPI_Actions_Base
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
	 * Validation object for the action
	 *
	 * @var VendorAPI_Actions_Validators_ViewDocument
	 */
	protected $validator;

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
		Validation_Validator_1 $validator,
		VendorAPI_IDriver $driver
	)
	{
		$this->application_factory = $app_factory;
		$this->document = $document;
		$this->validator = $validator;
		$this->driver = $driver;
	}

	public function execute($application_id, $state = NULL)
	{
		$this->call_context->setApplicationId($application_id);

		if ($state == NULL)
		{
			$this->state = $this->getStateObjectByApplicationID($application_id);
		}
		else
		{
			$this->state = $this->getStateObject($state);
		}

		$persistor = new VendorAPI_StateObjectPersistor($this->state);

		$this->application = $this->application_factory->getApplication($this->state->application_id, $persistor, $this->state);

		$errors = array();
		$result = array();

		$result['template'] = $template = $this->application->getLoanDocumentTemplate();

		try
		{
			$result['archive_id'] = $archive_id = $this->document->findDocument($application_id, $template);
		}
		catch (Exception $e)
		{
			$errors[] = $e->getMessage();
		}

		if (empty($errors))
		{
			if ($this->validator->validate(array('application_id' => $application_id, 'archive_id' => $archive_id)))
			{
				try
				{
					$document = array_shift($this->driver->getDocumentClient()->findDocumentByArchiveId($archive_id));
					if ($document->application_id != $application_id)
					{
						$errors[] = "Document not owned by $application_id";
					} else {
						$document = $this->document->getByArchiveId($archive_id);
					}
				}
				catch (VendorAPI_DocumentNotFoundException $e)
				{
					// Typically, we wouldn't expect someone to be requesting documents we can't retrieve
					$this->driver->getLog()->write($e->getMessage());
					$errors[] = "Could not find document.";
				}
			}
		}

		if ($document instanceof VendorAPI_DocumentData)
		{
			$result['document'] = $document->getContents();
		}
		else
		{
			$result['document'] = "";
		}

		$arr = $this->getValidationErrors($this->validator);
		if (!is_array($arr))
		{
			$arr = array();
		}

		$errors = array_merge($errors, $arr);

		$this->success = count($errors) ? VendorAPI_Response::ERROR : VendorAPI_Response::SUCCESS;

		return new VendorAPI_Response(
			$this->state,
			$this->success,
			$result,
			$errors
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
