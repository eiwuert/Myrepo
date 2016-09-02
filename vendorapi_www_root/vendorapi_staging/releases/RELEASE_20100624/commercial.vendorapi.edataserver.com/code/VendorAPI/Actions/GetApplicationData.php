<?php

/**
 * An action to get application data by application id, designed for the eCash/
 * partner weekly separation Java project.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class VendorAPI_Actions_GetApplicationData extends VendorAPI_Actions_Base
{
	/**
	 * @var VendorAPI_IApplicationFactory
	 */
	protected $application_factory;

	function __construct(VendorAPI_IDriver $driver,
		VendorAPI_IApplicationFactory $application_factory)
	{
		parent::__construct($driver);
		$this->application_factory = $application_factory;
	}

	public function execute($application_id, $serialized_state = NULL)
	{
		$this->call_context->setApplicationId($application_id);

		if ($serialized_state == NULL)
		{
			$state_object = $this->getStateObjectByApplicationID($application_id);
		}
		else
		{
			$state_object = $this->getStateObject($serialized_state);
		}

		$persistor = new VendorAPI_StateObjectPersistor($state_object);
		/* @var $application VendorAPI_IApplication */
		$application = $this->application_factory->getApplication($application_id, $persistor, $state_object);

		$result = $application->getData();
		$result['date_modified'] = date('Y-m-d H:i:s O', $result['date_modified']);
		$result['date_created'] = date('Y-m-d H:i:s O', $result['date_created']);
		$result['date_application_status_set'] = date('Y-m-d H:i:s O', $result['date_application_status_set']);
		$result['date_next_contact'] = date('Y-m-d', $result['date_next_contact']);
		$result['date_fund_estimated'] = date('Y-m-d', $result['date_fund_estimated']);
		$result['date_fund_actual'] = is_numeric($result['date_fund_actual'])?date('Y-m-d', $result['date_fund_actual']):'';
		$result['date_first_payment'] = date('Y-m-d', $result['date_first_payment']);
		$result['last_four_ssn'] = substr($result['ssn'], -4);
		$result['last_four_bank_account'] = substr($result['bank_account'], -4);
		$result['promo_id'] = (string)$result['promo_id'];
		

		return new VendorAPI_Response($state_object, VendorAPI_Response::SUCCESS, $result);
	}

	protected function getApplicationFactory()
	{
		return $this->application_factory;
	}
}

?>
