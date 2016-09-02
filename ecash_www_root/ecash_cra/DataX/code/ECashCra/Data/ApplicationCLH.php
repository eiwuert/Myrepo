<?php

/**
 * ECashCra ApplicationCLH Data Class
 *
 * @package ECashCra
 */
class ECashCra_Data_ApplicationCLH
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

	protected $name_first;
	protected $name_last;
	protected $street;
	protected $unit;
	protected $city;
	protected $state;
	protected $zip_code;
	protected $phone_home;
	protected $phone_cell;
	protected $phone_work;
	protected $phone_work_ext;
	protected $email;
	protected $dob;
	protected $ssn;
	protected $legal_id_number;
	protected $legal_id_state;
	protected $employer_name;
	protected $work_address_1;
	protected $work_address_2;
	protected $work_city;
	protected $work_state;
	protected $work_zip_code;
	protected $bank_name;
	protected $bank_aba;
	protected $bank_account;
	protected $income_frequency;
	protected $income_direct_deposit;
	protected $income_monthly;
	protected $apr;
	protected $payment_frequency;
	protected $payment_amount;
	
	protected $canceldate;
	protected $paidoffdate;
	protected $chargeoffdate;
	protected $balance;
	protected $recoverydate;
	protected $recoveryamount;
	
	//payment
	protected $transaction_register_id;
	protected $type;
	protected $method;
	protected $paymentdate;
	protected $paymentamount;
	protected $returncode;
	
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
		//$this->application_id = $db_row['EXTERNALID'];
		$this->application_id = $db_row['application_id'];
		
		$this->name_first = $db_row['NAMEFIRST'];
		$this->name_last = $db_row['NAMELAST'];
		$this->street = $db_row['STREET1'];
		$this->unit = $db_row['STREET2'];
		$this->city = $db_row['CITY'];
		$this->state = $db_row['STATE'];
		$this->zip_code = $db_row['ZIP'];
		$this->phone_home = $db_row['PHONEHOME'];
		$this->phone_cell = $db_row['PHONECELL'];
		$this->phone_work = $db_row['PHONEWORK'];
		$this->phone_work_ext = $db_row['PHONEEXT'];
		$this->email = $db_row['EMAIL'];
		$this->dob = $db_row['DOB'];
		$this->ssn = $db_row['SSN'];
		$this->legal_id_number = $db_row['DRIVERLICENSENUMBER'];
		$this->legal_id_state = $db_row['DRIVERLICENSESTATE'];
		$this->employer_name = $db_row['WORKNAME'];
		$this->work_address_1 = $db_row['WORKSTREET1'];
		$this->work_address_2 = $db_row['WORKSTREET2'];
		$this->work_city = $db_row['WORKCITY'];
		$this->work_state = $db_row['WORKSTATE'];
		$this->work_zip_code = $db_row['WORKZIP'];
		$this->bank_name = $db_row['BANKNAME'];
		$this->bank_aba = $db_row['BANKABA'];
		$this->bank_account = $db_row['BANKACCTNUMBER'];
		$this->income_frequency = $db_row['PAYPERIOD'];
		$this->income_direct_deposit = $db_row['DIRECTDEPOSIT'];
		$this->income_monthly = $db_row['MONTHLYINCOME'];
		
		//update
		/*
		$this->fund_date = $db_row['FUNDDATE'];
		$this->fund_amount = $db_row['FUNDAMOUNT'];
		$this->date_first_payment = $db_row['DUEDATE'];
		$this->fee_amount = $db_row['FUNDFEE'];
		$this->status_chain = $db_row['application_status_name'];
		*/
		$this->fund_date = $db_row['fund_date'];
		$this->fund_amount = $db_row['fund_amount'];
		$this->date_first_payment = $db_row['date_first_payment'];
		$this->fee_amount = $db_row['fee_amount'];
		$this->status_chain = $db_row['application_status_name'];
		$this->apr = $db_row['APR'];
		$this->payment_frequency = $db_row['PAYMENTFREQUENCY'];
		$this->payment_amount = $db_row['PAYMENTAMOUNT'];

		$this->canceldate = $db_row['CANCELLEDDATE'];
		$this->paidoffdate = $db_row['PAIDOFFDATE'];
		$this->chargeoffdate = $db_row['CHARGEDOFFDATE'];
		$this->balance = $db_row['balance'];
		$this->recoverydate = $db_row['RECOVEREDCHARGEDOFFDATE'];
		$this->recoveryamount = $db_row['RECOVEREDCHARGEDOFFAMOUNT'];
		
		//payment
		$this->transaction_register_id = $db_row['transaction_register_id'];
		$this->type = $db_row['TYPE'];
		$this->method = $db_row['METHOD'];
		$this->paymentdate = $db_row['PAYMENTDATE'];
		$this->paymentamount = $db_row['AMOUNT'];
		$this->returncode = $db_row['RETURNCODE'];
	}

	public function getNameFirst()
	{
		return $this->name_first;
	}

	public function getNameLast()
	{
		return $this->name_last;
	}

	public function getStreet1()
	{
		return $this->street;
	}

	public function getStreet2()
	{
		return $this->unit;
	}

	public function getCity()
	{
		return $this->city;
	}

	public function getState()
	{
		return $this->state;
	}

	public function getZip()
	{
		return $this->zip_code;
	}

	public function getPhoneHome()
	{
		return $this->phone_home;
	}

	public function getPhoneCell()
	{
		return $this->phone_cell;
	}

	public function getPhoneWork()
	{
		return $this->phone_work;
	}

	public function getPhoneExt()
	{
		return $this->phone_work_ext;
	}

	public function getEmail()
	{
		return $this->email;
	}

	public function getDOB()
	{
		return $this->dob;
	}

	public function getSSN()
	{
		return $this->ssn;
	}

	public function getDriverLicenseNumber()
	{
		return $this->legal_id_number;
	}

	public function getDriverLicenseState()
	{
		return $this->legal_id_state;
	}

	public function getWorkName()
	{
		return $this->employer_name;
	}

	public function getWorkStreet1()
	{
	 	return $this->work_address_1;
	}

	public function getWorkStreet2()
	{
		return $this->work_address_2;
	}

	public function getWorkCity()
	{
		return $this->work_city;
	}

	public function getWorkState()
	{
		return $this->work_state;
	}

	public function getWorkZip()
	{
		return $this->work_zip_code;
	}

	public function getBankName()
	{
		return $this->bank_name;
	}

	public function getBankAba()
	{
		return $this->bank_aba;
	}

	public function getBankAcctNumber()
	{
		return $this->bank_account;
	}

	public function getPayPeriod()
	{
		return $this->income_frequency;
	}

	public function getDirectDeposit()
	{
		return $this->income_direct_deposit;
	}

	public function getMonthlyIncome()
	{
		return $this->income_monthly;
	}

	//Update
	//fund_update, active
	public function getChannel()
	{
		return 'INTERNET';
	}

	public function getTradeLineType()
	{
		return 'O';
	}

	public function getTradeLineTypeCode()
	{
		return '15';
	}

	public function getAPR()
	{
		return $this->apr;
	}

	public function getPaymentFrequency()
	{
		return $this->payment_frequency;
	}

	public function getPaymentAmount()
	{
		return $this->payment_amount;
	}
	
	//cancel
	public function getCancelDate()
	{
		return $this->canceldate;
	}
	
	//paidoffdate
	public function getPaidOffDate()
	{
		return $this->paidoffdate;
	}
	
	//chargeoffdate
	public function getChargeOffDate()
	{
		return $this->chargeoffdate;
	}
	
	//balance
	public function getBalance()
	{
		return $this->balance;
	}
	
	//recoverydate
	public function getRecoveryDate()
	{
		return $this->recoverydate;
	}
	
	//recoveryamount
	public function getRecoveryAmount()
	{
		return $this->recoveryamount;
	}
	
	//payment
	public function getPaymentId()
	{
		return $this->transaction_register_id;
	}
	
	public function getType()
	{
		return $this->type;
	}
	
	public function getMethod()
	{
		return $this->method;
	}
	
	public function getPaymentDate()
	{
		return $this->paymentdate;
	}
	
	public function getPayment_Amount()
	{
		return $this->paymentamount;
	}
	
	public function getReturnCode()
	{
		return $this->returncode;
	}
	////////////////////////////////////////////////////////////////////

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
