<?php

class VendorAPI_Actions_FindDocument extends VendorAPI_Actions_Base
{
	/**
	 *
	 * @var VendorAPI_IDocument
	 */
	protected $document;

	protected $application_factory;

	public function __construct(
		VendorAPI_IDriver $driver,
		VendorAPI_IApplicationFactory $application_factory,
		VendorAPI_IDocument $document)
	{
		parent::__construct($driver);
		$this->document = $document;
		$this->application_factory = $application_factory;
	}

	public function execute($application_id, $template, $state_object = NULL)
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

		$error = array();
		$result = array();

		try
		{
			$result['archive_id'] = $this->document->findDocument($application_id, $template);
		}
		catch (Exception $e)
		{
			$error[] = $e->getMessage();
		}

		return new VendorAPI_Response(
			$state,
			count($error) ? VendorAPI_Response::ERROR : VendorAPI_Response::SUCCESS,
			$result,
			$error
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