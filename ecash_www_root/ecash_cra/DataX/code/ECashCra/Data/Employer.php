<?php

/**
 * ECashCra Employer Data Class
 *
 * @package ECashCra
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class ECashCra_Data_Employer
{
	/**
	 * @var string
	 */
	protected $name;
	
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
	protected $pay_period;
	
	/**
	 * @var string
	 */
	protected $phone_work;
	
	/**
	 * @var string
	 */
	protected $phone_ext;
	
	/**
	 * Creates a new employer object.
	 * 
	 * The parameter passed in should be an associative array with the 
	 * following keys:
	 * 
	 * <ul>
	 * <li>name</li>
	 * <li>street1</li>
	 * <li>street2</li>
	 * <li>city</li>
	 * <li>state</li>
	 * <li>zip</li>
	 * <li>pay_period</li>
	 * <li>phone_work</li>
	 * <li>phone_ext</li>
	 * </ul>
	 *
	 * @param array $db_row
	 */
	public function __construct($db_row)
	{
		$this->name = $db_row['employer_name'];
		$this->street1 = '';//$db_row['employer_street1'];
		$this->street2 = '';//$db_row['employer_street2'];
		$this->city = $db_row['employer_city'];
		$this->state = $db_row['employer_state'];
		$this->zip = $db_row['employer_zip'];
		$this->pay_period = $db_row['pay_period'];
		$this->phone_work = $db_row['phone_work'];
		$this->phone_ext = $db_row['phone_ext'];
	}
	
	/**
	 * Returns the employer name.
	 *
	 * @return string
	 */
	public function getWorkName()
	{
		return $this->name;
	}
	
	/**
	 * Returns the employer street address.
	 *
	 * @return string
	 */
	public function getWorkStreet1()
	{
		return $this->street1;
	}
	
	/**
	 * Returns the employer suite / building number / etc.
	 *
	 * @return string
	 */
	public function getWorkStreet2()
	{
		return $this->street2;
	}
	
	/**
	 * Returns the employer city.
	 *
	 * @return string
	 */
	public function getWorkCity()
	{
		return $this->city;
	}
	
	/**
	 * Returns the employer state.
	 *
	 * @return string
	 */
	public function getWorkState()
	{
		return $this->state;
	}
	
	/**
	 * Returns the employer zip.
	 *
	 * @return string
	 */
	public function getWorkZip()
	{
		return $this->zip;
	}
	
	/**
	 * Returns the employee's pay period
	 *
	 * @return string
	 */
	public function getPayPeriod()
	{
		return $this->pay_period;
	}
	
	/**
	 * Returns the employer's phone number.
	 *
	 * @return string
	 */
	public function getPhoneWork()
	{
		return $this->phone_work;
	}
	
	/**
	 * Returns the employee's extension
	 *
	 * @return string
	 */
	public function getPhoneExt()
	{
		return $this->phone_ext;
	}
}

?>
