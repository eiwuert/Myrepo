<?php
/**
 *
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 *
 */
require_once "crypt.3.php";

class VendorAPI_Actions_ResetPassword extends VendorAPI_Actions_Base
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

	public function execute($application_id, $password)
	{
		$result = array();
		
		if ($this->setCredentials($application_id, $password))
		{
			$status = VendorAPI_Response::SUCCESS;
			$result['application_id'] = $application_id; 
		}
		else
		{
			$status = VendorAPI_Response::ERROR;
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
	public function getCustomer($application_id)
	{
		if (!isset($this->customer))
		{
			$factory = $this->driver->getFactory();
			
			$application = $factory->getModel('Application');
	
			if (!$application->loadBy(array("application_id" => $application_id)))
			{
				return false;
			}
			else
			{
				$this->application = $application;
			}

			$customer = $factory->getModel('ApplicantAccount');

			if (!$customer->loadBy(array("applicant_account_id" => $application->customer_id)))
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
	public function setCredentials($application_id, $password)
	{
		if (!$customer = $this->getCustomer($application_id))
		{
			return false;
		}
		$customer->password = crypt_3::Encrypt($password);

		return $customer->save();		
	}
}
