<?php

/**
 * ECashCra Application Data Class for FactorTrust Reporting
 *
 * @package ECashCra
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */
class ECashCra_Data_Application
{
        const PRODUCTTYPE = 'PDL';

	/**
	 * @var int
	 */
	protected $Type;
	
	/**
	 * @var ssn
	 */
	protected $SSN;
    
	/**
	 * @var string YYYY-MM-DD
	 */
	protected $TranDate;

	/**
	 * @var varchar krack_key
	 */
	protected $AppID;

	/**
	 * @var int application_id
	 */
	protected $LoanID;
    
	/**
	 * @var string YYYY-MM-DD loan start date
	 */
	protected $LoanDate;
    
	/**
	 * @var string YYYY-MM-DD next payment date
	 */
	protected $DueDate;
	
	/**
	 * @var real 
	 */
	protected $PaymentAmt;
    
	/**
	 * @var real total balance
	 */
	protected $Balance;
     
	/**
	 * @var varchar returned ach code
	 */
	protected $ReturnCode;
    
	/**
	 * @var int parent application_id for refi loans
	 */
	protected $RollOverRef;
    
	/**
	 * @var int numbr of parent/grandparent/... for refi loans
	 */
	protected $RollOverNumber;
	
	/**
	 * @var BankABA
	 */
	protected $BankABA;
	
	/**
	 * @var Bank Account
	 */
	protected $BankAcct;

	
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
		$this->Type = $db_row['Type'];
		$this->SSN = $db_row['SSN'];
		$this->TranDate = $db_row['TranDate'];
		$this->AppID = $db_row['AppID'];
		$this->LoanID = $db_row['LoanID'];
		$this->LoanDate = $db_row['LoanDate'];
		$this->DueDate = $db_row['DueDate'];
		$this->PaymentAmt = $db_row['PaymentAmt'];
		$this->Balance = $db_row['Balance'];
                $this->ReturnCode = $db_row['ReturnCode'];
		$this->RollOverRef = $db_row['RollOverRef'];
		$this->RollOverNumber = $db_row['RollOverNumber'];
		$this->BankABA = $db_row['BankABA'];
		$this->BankAcct = $db_row['BankAcct'];
	}

	/**
	 * Returns the factor trust update type.
	 * 
	 * @return string Type
	 */
	public function getType()
	{
		return $this->Type;
	}

    /**
     * Returns the SSN.
     *
     * @return string SSN
     */
    public function getSSN()
    {
        return $this->SSN;
    }

	/**
	 * Returns the transaction date
	 *  translates from YYYY-MM-DD to MM-DD-YYYY.
	 *
	 * @return MM-DD-YYYY
	 */
	public function getTranDate()
	{
                $return = empty($this->TranDate) ? date('m-d-Y', strtotime("-1 day", time())) : date('m-d-Y',strtotime($this->TranDate));
		return $return;
	}

	/**
	 * Returns the the factor trust app_id (our track_key).
	 *
	 * @return int
	 */
	public function getAppID()
	{
		return $this->AppID;
	}

	/**
	 * Returns the application id as the factor trust loan id.
	 *
	 * @return int
	 */
	public function getLoanID()
	{
		return $this->LoanID;
	}

    /**
	 * Returns the loan actual funded date
	 *  translates from YYYY-MM-DD to MM-DD-YYYY.
	 *
	 * @return MM-DD-YYYY
	 */
	public function getLoanDate()
	{
                $return = empty($this->LoanDate) ? NULL : date('m-d-Y',strtotime($this->LoanDate));
		return $return;
	}

    /**
	 * Returns the next payment date
	 *  translates from YYYY-MM-DD to MM-DD-YYYY.
	 *
	 * @return MM-DD-YYYY
	 */
	public function getDueDate()
	{
                $return = empty($this->DueDate) ? NULL : date('m-d-Y',strtotime($this->DueDate));
		return $return;
	}

	/**
	 * Returns the payment amount.
	 *
	 * @return real
	 */
	public function getPaymentAmt()
	{
		return $this->PaymentAmt;
	}

	/**
	 * Returns the total loan balance.
	 *
	 * @return real
	 */
	public function getBalance()
	{
		return $this->Balance;
	}

	/**
	 * Returns any refi-parent application_id.
	 *
	 * @return int
	 */
	public function getRollOverRef()
	{
		return $this->RollOverRef;
    }

	/**
	 * Returns the count of consecutive refis.
	 *
	 * @return int
	 */
	public function getRollOverNumber()
	{
		return $this->RollOverNumber;
	}

	/**
	 * Returns the count of consecutive refis.
	 *
	 * @return int
	 */
	public function getReturnCode()
	{
		return $this->ReturnCode;
	}

	/**
	 * Returns bank aba number.
	 *
	 * @return string of numbers
	 */
	public function getBankABA()
	{
		return $this->BankABA;
	}

	/**
	 * Returns bank account number.
	 *
	 * @return string of numbers
	 */
	public function getBankAcct()
	{
		return $this->BankAcct;
	}
        
        public function getProductType()
	{
		return self::PRODUCTTYPE;
	}
}

?>
