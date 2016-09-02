<?php

/**
 * ECashCra Application Data Class
 *
 * @package ECashCra
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class ECashCra_Data_Application
{
	/**
	 * @var int
	 */
	protected $application_id;
	
	/**
	 * @var string YYYY-MM-DD
	 */
	protected $fund_date;
	
	/**
	 * @var int
	 */
	protected $fund_amount;
	
	/**
	 * @var int
	 */
	protected $fee_amount;
	
	/**
	 * @var string YYYY-MM-DD
	 */
	protected $date_first_payment;
	
	/**
	 * @var ECashCra_Data_Personal
	 */
	protected $personal;
	
	/**
	 * @var ECashCra_Data_Employer
	 */
	protected $employer;

	protected $status_chain;	

	
	/**
	 * Creates a new application object.
	 * 
	 * The parameter passed in should be an associative array with the 
	 * following keys:
	 * 
	 * <ul>
	 * <li>application_id</li>
	 * <li>fund_date</li>
	 * <li>fund_amount</li>
	 * <li>date_first_payment</li>
	 * <li>fee_amount</li>
	 * </ul>
	 *
	 * @param array $db_row
	 */
	public function __construct(array $db_row)
	{
		$this->application_id = $db_row['application_id'];
		$this->fund_date = $db_row['fund_date'];
		$this->fund_amount = $db_row['fund_amount'];
		$this->date_first_payment = $db_row['date_first_payment'];
		$this->fee_amount = $db_row['fee_amount'];
		$this->status_chain = $db_row['application_status_name'];
	}
	
	/**
	 * Sets the personal data.
	 *
	 * @param ECashCra_Data_Personal $personal
	 * @return null
	 */
	public function setPersonal(ECashCra_Data_Personal $personal)
	{
		$this->personal = $personal;
	}
	
	/**
	 * Sets the employer data.
	 *
	 * @param ECashCra_Data_Employer $employer
	 * @return null
	 */
	public function setEmployer(ECashCra_Data_Employer $employer)
	{
		$this->employer = $employer;
	}
	
	/**
	 * Returns the application id.
	 * 
	 * @return int
	 */
	public function getApplicationId()
	{
		return $this->application_id;
	}
	
        /**
         * Returns the status chain.
         *
         * @return string status
         */
        public function getStatusChain()
        {
                return $this->status_chain;
        }

	/**
	 * Returns the personal data.
	 *
	 * @return ECashCra_Data_Personal
	 */
	public function getPersonal()
	{
		return $this->personal;
	}
	
	/**
	 * Returns the employer data.
	 *
	 * @return ECashCra_Data_Employer
	 */
	public function getEmployer()
	{
		return $this->employer;
	}
	
	/**
	 * Returns the fund data.
	 *
	 * @return string YYYY-MM-DD
	 */
	public function getFundDate()
	{
		return $this->fund_date;
	}
	
	/**
	 * Returns the fund amount.
	 *
	 * @return int
	 */
	public function getFundAmount()
	{
		return $this->fund_amount;
	}
	
	/**
	 * Returns the first service charge amount.
	 *
	 * @return int
	 */
	public function getFirstServiceChargeAmount()
	{
		return $this->fee_amount;
	}
	
	/**
	 * Returns the first payment date.
	 *
	 * @return string YYYY-MM-DD
	 */
	public function getFirstPaymentDate()
	{
		return $this->date_first_payment;
	}
}

?>
