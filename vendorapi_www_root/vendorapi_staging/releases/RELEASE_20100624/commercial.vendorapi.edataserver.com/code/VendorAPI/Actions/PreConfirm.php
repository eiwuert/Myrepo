<?php 

/**
 * A PreConfirm Action
 *
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
class VendorAPI_Actions_PreConfirm extends VendorAPI_Actions_Base
{
	
	protected $application_factory;

	public function __construct(VendorAPI_IDriver $driver,
		VendorAPI_IApplicationFactory $application_factory
	)
	{
		parent::__construct($driver);
		$this->application_factory = $application_factory;

	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $application_id
	 * @param unknown_type $state_object
	 * @return unknown
	 */
	public function execute($application_id, $state_object = NULL)
	{
		if ($state_object == NULL)
		{
			$state_object = $this->getStateObjectByApplicationID($application_id);
		}
		else
		{
			$state_object = $this->getStateObject($state_object);
		}
		
		try 
		{
			$application = $this->getApplication($application_id);
			$fund_amount = $this->getFundAmount($application, $state_object);
			
			if (!is_numeric($fund_amount))
			{
				throw new RuntimeException("Invalid fund amount {$fund_amount}.");
			}
			$qualify = $this->driver->getQualify();
			// lollers
			if (method_exists($qualify, 'setBusinessRules'))
			{
				$loan_type_id = $this->getLoanTypeId($application, $state_object);
				$rules = $this->driver->getBusinessRules($loan_type_id);
				$rule_set_id = $this->driver->getRuleSetID();
				$qualify->setBusinessRules($rules);
			}
			return new VendorAPI_Response(
				$state_object,
				VendorAPI_Response::SUCCESS,
				array(
					'maximum_loan_amount' => $fund_amount,
					'fund_amounts'        => $qualify->getAmountIncrements($fund_amount, $this->isReact($application, $state_object)),
				)
			);
		}
		catch (Exception $e)
		{
			return new VendorAPI_Response(
				$state_object,
				VendorAPI_Response::ERROR,
				array(),
				$e->getMessage()
			);
		}
	}
	
	/**
	 * ....
	 * @param object $application
	 * @param object $state_object
	 * @return mixed
	 */
	protected function getLoanTypeId($application, $state_object)
	{
		if (!empty($application->loan_type_id))
		{
			return $application->loan_type_id;
		}
		elseif (!empty($state_object->application->loan_type_id))
		{
			return $state_object->application->loan_type_id;
		}
		return FALSE;
	}
	

	/**
	 * ....
	 * @param object $application
	 * @param object $state_object
	 * @return Boolean
	 */
	protected function isReact($application, $state_object)
	{
		return ($state_object->application->is_react == 'yes' || $application->is_react == 'yes');
	}
	
	/**
	 * Get an application model?
	 *
	 * @param unknown_type $id
	 * @return unknown
	 */
	protected function getApplication($id)
	{
		$application = $this->driver->getDataModelByTable('application');
		$application->loadBy(array('application_id' => $id));
		return $application;
	}
	
	/**
	 * Load the qualify info either from the state object or
	 * if it's in LDB, use the data thats in LDB.
	 *
	 * @param Object $application
	 * @param Object $state_object
	 * @return array
	 */
	protected function getFundAmount($application, $state_object)
	{
		$order = array('fund_actual', 'fund_requested', 'fund_qualified');
		foreach ($order as $field)
		{
			if (is_numeric($application->$field))
			{
				return $application->$field;
			}
			if ($state_object->isPart('application') && is_numeric($state_object->application->$field))
			{
				return $state_object->application->$field;
			}
		}
				
		return FALSE;
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
