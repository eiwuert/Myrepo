<?php

/**
 * ECashCra Payment Data Class
 *
 * @package ECashCra
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class ECashCra_Data_Personal
{
	/**
	 * @var string
	 */
	protected $name_first;
	
	/**
	 * @var string
	 */
	protected $name_middle;
	
	/**
	 * @var string
	 */
	protected $name_last;
	
	/**
	 * @var string
	 */
	protected $street1;
	
	/**
	 * @var string
	 */
	protected $street2;
	
	/**
	 * @var string
	 */
	protected $city;
	
	/**
	 * @var string
	 */
	protected $state;
	
	/**
	 * @var string
	 */
	protected $zip;
	
	/**
	 * @var string
	 */
	protected $phone_home;
	
	/**
	 * @var string
	 */
	protected $phone_cell;
	
	/**
	 * @var string
	 */
	protected $email;
	
	/**
	 * @var string
	 */
	protected $ip_address;
	
	/**
	 * @var string
	 */
	protected $dob;
	
	/**
	 * @var string
	 */
	protected $ssn;
	
	/**
	 * @var string
	 */
	protected $driver_license_number;
	
	/**
	 * @var string
	 */
	protected $driver_license_state;
	
	/**
	 * @var string
	 */
	protected $bank_name;
	
	/**
	 * @var string
	 */
	protected $bank_acct_number;
	
	/**
	 * @var string
	 */
	protected $bank_aba;
	
	/**
	 * Creates a new employer object.
	 * 
	 * The parameter passed in should be an associative array with the 
	 * following keys:
	 * 
	 * <ul>
	 * <li>name_first</li>
	 * <li>name_middle</li>
	 * <li>name_last</li>
	 * <li>street1</li>
	 * <li>street2</li>
	 * <li>city</li>
	 * <li>state</li>
	 * <li>zip</li>
	 * <li>phone_home</li>
	 * <li>phone_cell</li>
	 * <li>email</li>
	 * <li>ip_address</li>
	 * <li>dob</li>
	 * <li>ssn</li>
	 * <li>driver_license_number</li>
	 * <li>driver_license_state</li>
	 * <li>bank_name</li>
	 * <li>bank_acct_number</li>
	 * <li>bank_aba</li>
	 * </ul>
	 *
	 * @param array $db_row
	 */
	public function __construct(array $db_row)
	{
		$this->name_first = $db_row['name_first'];
		$this->name_middle = $db_row['name_middle'];
		$this->name_last = $db_row['name_last'];
		$this->street1 = $db_row['street1'];
		$this->street2 = $db_row['street2'];
		$this->city = $db_row['city'];
		$this->state = $db_row['state'];
		$this->zip = $db_row['zip'];
		$this->phone_home = $db_row['phone_home'];
		$this->phone_cell = $db_row['phone_cell'];
		$this->email = $db_row['email'];
		$this->ip_address = $db_row['ip_address'];
		$this->dob = $db_row['dob'];
		$this->ssn = $db_row['ssn'];
		$this->driver_license_number = $db_row['driver_license_number'];
		$this->driver_license_state = $db_row['driver_license_state'];
		$this->bank_name = $db_row['bank_name'];
		$this->bank_acct_number = $db_row['bank_acct_number'];
		$this->bank_aba = $db_row['bank_aba'];
	}

	/**
	 * Returns the first name.
	 *
	 * @return string
	 */
	public function getNameFirst()
	{
		return $this->name_first;
	}
	
	/**
	 * Returns the middle name.
	 *
	 * @return string
	 */
	public function getNameMiddle()
	{
		return $this->name_middle;
	}
	
	/**
	 * Returns the last name.
	 *
	 * @return string
	 */
	public function getNameLast()
	{
		return $this->name_last;
	}
	
	/**
	 * Returns the street 1.
	 *
	 * @return string
	 */
	public function getStreet1()
	{
		return $this->street1;
	}
	
	/**
	 * Returns the street 2.
	 *
	 * @return string
	 */
	public function getStreet2()
	{
		return $this->street2;
	}
	
	/**
	 * Returns the city.
	 *
	 * @return string
	 */
	public function getCity()
	{
		return $this->city;
	}
	
	/**
	 * Returns the state.
	 *
	 * @return string
	 */
	public function getState()
	{
		return $this->state;
	}
	
	/**
	 * Returns the zip.
	 *
	 * @return string
	 */
	public function getZip()
	{
		return $this->zip;
	}
	
	/**
	 * Returns the phone home.
	 *
	 * @return string
	 */
	public function getPhoneHome()
	{
		return $this->phone_home;
	}
	
	/**
	 * Returns the phone cell.
	 *
	 * @return string
	 */
	public function getPhoneCell()
	{
		return $this->phone_cell;
	}
	
	/**
	 * Returns the email.
	 *
	 * @return string
	 */
	public function getEmail()
	{
		return $this->email;
	}
	
	/**
	 * Returns the ip address.
	 *
	 * @return string
	 */
	public function getIPAddress()
	{
		return $this->ip_address;
	}
	
	/**
	 * Returns the dob.
	 *
	 * @return string
	 */
	public function getDOB()
	{
		return $this->dob;
		
	}
	
	/**
	 * Returns the ssn.
	 *
	 * @return string
	 */
	public function getSSN()
	{
		return $this->ssn;
	}
	
	/**
	 * Returns the driver license number.
	 *
	 * @return string
	 */
	public function getDriverLicenseNumber()
	{
		return $this->driver_license_number;
	}
	
	/**
	 * Returns the driver license state.
	 *
	 * @return string
	 */
	public function getDriverLicenseState()
	{
		return $this->driver_license_state;
	}
	
	/**
	 * Returns the bank name.
	 *
	 * @return string
	 */
	public function getBankName()
	{
		return $this->bank_name;
	}
	
	/**
	 * Returns the bank account number.
	 *
	 * @return string
	 */
	public function getBankAcctNumber()
	{
		return $this->bank_acct_number;
	}
	
	/**
	 * Returns the bank aba.
	 *
	 * @return string
	 */
	public function getBankAba()
	{
		return $this->bank_aba;
	}
}

?>