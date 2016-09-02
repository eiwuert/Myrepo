<?php

/**
 * Returns transations for a given application
 *
 * @author Randy Klepetko andy.klepetko@clearlakeholdings.com
 */
class VendorAPI_Actions_LoginLock extends VendorAPI_Actions_Base
{
	protected $application_factory;

	protected $factory;
	
	protected $login_lock;

	public function __construct(
		VendorAPI_IDriver $driver,
		ECash_Factory $factory,
		VendorAPI_IApplicationFactory $application_factory)
	{
		parent::__construct($driver);
		$this->factory = $factory;
		$this->application_factory = $application_factory;
		$this->login_lock = $this->factory->getModel('ApplicationLoginLock');
	}

	/**
	 * Execute the action
	 *
	 * @param Integer $application_id
	 * @param string $action
	 * @return VendorAPI_Response
	 */
	public function execute($application_id, $action, $serialized_state = NULL)
	{
		/*
		if ($serialized_state == NULL)
		{
			$state = $this->getStateObjectByApplicationID($application_id);
		}
		else
		{
			$state = $this->getStateObject($serialized_state);
		}

		$persistor = new VendorAPI_StateObjectPersistor($state);
		*/
		switch ($action) {
			case 'check':
				$rtn = $this->checkLoginLock($application_id);
				if ($rtn == "pass") $this->clearLoginLock($application_id);
				break;
			case 'set':
				$rtn = $this->setLoginLock($application_id);
				break;
			case 'clear':
				$rtn = $this->clearLoginLock($application_id);
				break;
			default:
				$rtn = null;
		}	
		
		if ($rtn)
		{
			$status = VendorAPI_Response::SUCCESS;
		}
		else
		{
			$status = VendorAPI_Response::ERROR;
		}
		
		return new VendorAPI_Response(new VendorAPI_StateObject(), $status, array($rtn));
		//return new VendorAPI_Response($state, $status, array($rtn));
	}

	/**
	 * Checks the login lock setting of an application by field, returns true if it is locked
	 */
	public function checkLoginLock($application_id)
	{
		$field_id = 8 ;//'login_lock';
			
		$application_fields = $this->factory->getModel('ApplicationField');
		$rows = $application_fields->loadAllBy(array("table_row_id" => $application_id, "application_field_attribute_id" => $field_id));
		if (!$rows || (count($rows) == 0)){
			return "pass";
		} else {
			return "locked";
		}
	}

	/**
	 * Clears the login lock record of an application, doesn't touch the application field
	 */
	public function clearLoginLock($application_id)
	{
		$loaded = $this->login_lock->loadBy(array('application_id' => $application_id));
		if ($loaded) {
			$this->login_lock->counter = 0;
			$this->login_lock->save();
		}
		return true;
	}
	/**
	 * Sets the login lock record of an application, and increments it.
	 * If it is more than the value found in the business rule, set the application field
	 */
	public function setLoginLock($application_id)
	{
		$application_id = (int)$application_id;
		$application = ECash::getApplicationByID($application_id);

		$company_model = $this->factory->getModel('Company');
		$company_model->loadBy(array('company_id' => $application->getCompanyId()));

		if (method_exists($factory, "getMasterDb"))
		{
			$db = $this->factory->getMasterDb();
		}
		else
		{
			$db = $this->factory->getDB();
		}
			
		$business_rules = new ECash_BusinessRules($db);
		$settings = $business_rules->Get_Rule_Set_Component_Parm_Values($company_model->name_short, 'login_lock');
		$rate = $settings['max_attempt'];

		$loaded = $this->login_lock->loadBy(array('application_id' => $application_id));
		if (!$loaded)
		{
			$this->login_lock->date_created = date('Y-m-d H:i:s');
			$this->login_lock->application_id = $application_id;
			$this->login_lock->counter = 0;
		}

		$this->login_lock->counter++;
		$this->login_lock->save();

		if ($this->login_lock->counter >= $rate)
		{
			$olp_agent = $this->factory->getModel('Agent');
			$olp_agent->loadBy(array('login' => 'olp'));
			$olp_agent_id = $olp_agent->agent_id;
			$application->getContactFlags()->set($olp_agent_id, 'login_lock', 'application_id');
		}
		
		return true;
	}

	/**
	 * Returns Application Factory.
	 *
	 * @return VendorAPI_IApplicationFactory
	 */
	protected function getApplicationFactory()
	{
		return $this->application_factory;
		
	}}

?>
