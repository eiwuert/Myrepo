<?php

/**
 * ECashCra Payment Data Class
 *
 * @package ECashCra
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class ECashCra_Data_Payment
{
	/**
	 * @var int
	 */
	protected $id;
	
	/**
	 * @var string
	 */
	protected $type;
	
	/**
	 * @var string
	 */
	protected $method;
	
	/**
	 * @var string - YYYY-MM-DD
	 */
	protected $date;
	
	/**
	 * @var float
	 */
	protected $amount;
	
	/**
	 * @var ECashCra_Data_Application
	 */
	protected $application;
	
	/**
	 * @var string
	 */
	protected $return_code;
	
	/**
	 * Creates a new payment object.
	 * 
	 * The parameter passed in should be an associative array with the 
	 * following keys:
	 * 
	 * <ul>
	 * <li>id</li>
	 * <li>type</li>
	 * <li>method</li>
	 * <li>date</li>
	 * <li>amount</li>
	 * <li>return_code</li>
	 * </ul>
	 *
	 * @param array $db_row
	 */
	public function __construct(array $db_row)
	{
		$this->id = $db_row['payment_id'];
		$this->type = $db_row['payment_type'];
		$this->method = $db_row['payment_method'];
		$this->date = $db_row['payment_date'];
		$this->amount = $db_row['payment_amount'];
		$this->return_code = $db_row['payment_return_code'];
	}
	
	/**
	 * Sets the application for this payment
	 *
	 * @param ECashCra_Data_Application $application
	 * @return null
	 */
	public function setApplication(ECashCra_Data_Application $application)
	{
		$this->application = $application;
	}
	
	/**
	 * Returns the payment id.
	 *
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}
	
	/**
	 * Returns the payment type.
	 *
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}
	
	/**
	 * Returns the payment method.
	 *
	 * @return string
	 */
	public function getMethod()
	{
		return $this->method;
	}
	
	/**
	 * Returns the payment date
	 *
	 * @return string YYYY-MM-DD
	 */
	public function getDate()
	{
		return $this->date;
	}
	
	/**
	 * Returns the payment amount.
	 *
	 * @return float
	 */
	public function getAmount()
	{
		return $this->amount;
	}

	/**
	 * Returns the return code
	 *
	 * @return string
	 */
	public function getReturnCode()
	{
		return $this->return_code;
	}
	
	/**
	 * Returns the payment's application
	 *
	 * @return ECashCra_Data_Application
	 */
	public function getApplication()
	{
		return $this->application;
	}
}

?>