<?php
/**
 * The ViewDocument action allows clients to view documents in condor based on the application and archive ID.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class VendorAPI_Actions_ViewDocument extends VendorAPI_Actions_Base
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
		$this->app_factory = $app_factory;
		$this->document = $document;
		$this->validator = $validator;
		$this->driver = $driver;
	}

	/**
	 * Executes the action.
	 *
	 * @param int    $application_id   an integer with the application_id
	 * @param int    $archive_id       an integer with the archive_id
	 * @param string $serialized_state a string with a serialized state object
	 * @return VendorAPI_Response
	 */
	public function execute($application_id, $archive_id, $serialized_state)
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

		if ($this->validator->validate(array('application_id' => $application_id, 'archive_id' => $archive_id)))
		{
			try
			{
				$document = array_shift($this->driver->getDocumentClient()->findDocumentByArchiveId($archive_id));

				if ($document->application_id != $application_id)
				{
					$this->error_msg = "Document not owned by $application_id";
				} else {
					$document = $this->document->getByArchiveId($archive_id);
				}
			}
			catch (VendorAPI_DocumentNotFoundException $e)
			{
				// Typically, we wouldn't expect someone to be requesting documents we can't retrieve
				$this->driver->getLog()->write($e->getMessage());
				$this->error_msg = "Could not find document.";
			}
		}

		$errors = $this->getErrors();
		$this->success = count($errors) ? VendorAPI_Response::ERROR : VendorAPI_Response::SUCCESS;
		return new VendorAPI_Response(
			$state,
			$this->success,
			$document instanceof VendorAPI_DocumentData ? array('document' => $document->getContents()) : array(),
			$errors
		);
	}

	/**
	 * Return an array of errors
	 * @return Array
	 */
	protected function getErrors()
	{
		$arr = $this->getValidationErrors($this->validator);
		if (!is_array($arr))
		{
			$arr = array();
		}
		if (!empty($this->error_msg))
		{
			$arr[] = $this->error_msg;
		}
		return $arr;
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
