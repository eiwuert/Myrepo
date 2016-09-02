<?php
/**
 *
 * @author Bryan Campbell <bryan.campbell@dataxltd.com>
 *
 */

require_once("crypt.3.php");

class VendorAPI_Actions_Login extends VendorAPI_Actions_Base implements VendorAPI_ICustomer
{
	protected $application_factory;
	
	/**
	 * ECash_Models_Customer
	 */
	
	protected $customer;

	public function __construct(VendorAPI_IDriver $driver,
		VendorAPI_IApplicationFactory $application_factory
	)
	{
		parent::__construct($driver);
		$this->application_factory = $application_factory;
	}

	public function execute($username, $password)
	{
		$result = array();
		
		if ($this->credentialsValid($username, $password))
		{
			$status = VendorAPI_Response::SUCCESS;
			$result['application_ids'] = $this->getApplicationIds($username); 
		}
		else
		{
			$status = VendorAPI_Response::ERROR;
			if ($ap_ids = $this->getApplicationIds($username)){
				$loginLock = new VendorAPI_Actions_LoginLock($this->driver, $this->driver->getFactory(), $this->application_factory);
				$loginLock->execute(max($ap_ids),'set');
			}
		}
		
		return new VendorAPI_Response(new VendorAPI_StateObject(), $status, $result);
	}
	
	/**
	 * Finds customer. Returns a customer model on success and
	 * false on failure.
	 *
	 * @param string $username
	 * @return ECash_Models_Customer
	 */
	public function getCustomer($username)
	{
		if (!isset($this->customer))
		{
			$factory = $this->driver->getFactory();
			
			$customer = $factory->getModel('ApplicantAccount');
			if (!$customer->loadBy(array("login" => $username)))
			{
				return false;
			}
			else
			{
				$this->customer = $customer;
			}
		}
		
		return $this->customer;
	}
	
	/**
	 * Authenticates the customer. Returns true and success and
	 * false on failure.
	 *
	 * @param string $username
	 * @param string $password
	 * @return bool
	 */
	public function credentialsValid($username, $password)
	{
		if (!$customer = $this->getCustomer($username))
		{
			return false;
		}
		
		if (trim($customer->password) != trim(crypt_3::Encrypt(trim($password))))
		{
			return false;			
		}

		return true;		
	}
	
	/**
	 * Finds active applications under the customer. Returns an array of application_ids on success and
	 * false on failure.
	 *
	 * @param string $username
	 * @return array
	 */
	public function getApplicationIds($username)
	{
		$application_ids = array();
		if ($customer = $this->getCustomer($username))
		{
			$factory = $this->driver->getFactory();
			
			if (method_exists($factory, "getMasterDb"))
			{
				$db = $factory->getMasterDb();
			}
			else
			{
				$db = $factory->getDB();
			}
			
			$application = $factory->getModel('Application', $db);
			
			$applications = $application->loadAllBy(array("applicant_account_id" => $customer->applicant_account_id));
			
			foreach($applications as $app)
			{
				$application_ids[] = $app->application_id;
			}
		}
		
		return $application_ids;
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
