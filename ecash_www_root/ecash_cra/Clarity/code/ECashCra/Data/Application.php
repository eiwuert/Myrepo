<?php

/**
 * ECashCra Application Data Class for Clarity Reporting
 *
 * @package ECashCra
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */
class ECashCra_Data_Application
{

	/**
	 * @var varchar(2)
	 */
	protected $VoidStatus;

	/**
	 * @var varchar(2)
	 */
	protected $AccountType;

	/**
	 * @var varchar(2)
	 */
	protected $PortfolioType;

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
	protected $FirstDate;
    
	/**
	 * @var string YYYY-MM-DD
	 */
	protected $LastDate;
    
	/**
	 * @var string YYYY-MM-DD loan start date
	 */
	protected $PastDate;
    
	/**
	 * @var string YYYY-MM-DD next payment date
	 */
	protected $CloseDate;
	
	/**
	 * @var real 
	 */
	protected $PaymentAmt;
    
	/**
	 * @var real total balance
	 */
	protected $Balance;
    
	/**
	 * @var real
	 */
	protected $Principal;
    
	/**
	 * @var real
	 */
	protected $SchedPayment;
    
	/**
	 * @var real
	 */
	protected $PastDue;
    
	/**
	 * @var bool string
	 */
	protected $FirstPaymentBad;
    
	/**
	 * @var int or varchar(1) payment frequency
	 */
	protected $Frequency;
    
	/**
	 * @var int, total number of payments
	 */
	protected $Duration;
    
	/**
	 * @var varchar(1) clarity status
	 */
	protected $Rating;
    
	/**
	 * @var ssn
	 */
	protected $SSN;
    
	/**
	 * @var first name
	 */
	protected $NameFirst;
    
	/**
	 * @var last name
	 */
	protected $NameLast;
    
	/**
	 * @var date of birth
	 */
	protected $DOB;
    
	/**
	 * @var street address primary
	 */
	protected $Street1;
    
	/**
	 * @var street address secondary
	 */
	protected $Street2;
    
	/**
	 * @var city
	 */
	protected $City;
    
	/**
	 * @var state
	 */
	protected $Street;
    
	/**
	 * @var zip
	 */
	protected $Zip;
    
	/**
	 * @var ssn
	 */
	protected $PhoneHome;
	
	/**
	 * @var BankABA
	 */
	protected $BankABA;
	
	/**
	 * @var Bank Account
	 */
	protected $BankAcct;

	/**
	 * @var varchar track_id
	 */
	protected $AppID;

	/**
	 * @var int parent application_id for refi loans
	 */
	//protected $RollOverRef;
	
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
		$this->VoidStatus = $db_row['VoidStatus'];
		$this->AccountType = $db_row['AccountType'];
		$this->PortfolioType = $db_row['PortfolioType'];
		$this->LoanID = $db_row['LoanID'];
        
		$this->LoanDate = $db_row['LoanDate'];
		$this->FirstDate = $db_row['FirstDate'];
		$this->LastDate = $db_row['LastDate'];
		$this->PastDate = $db_row['PastDate'];
		$this->CloseDate = $db_row['CloseDate'];
        
		$this->PaymentAmt = $db_row['PaymentAmt'];
		$this->Balance = $db_row['Balance'];
		$this->Principal = $db_row['Principal'];
		$this->SchedPayment = $db_row['SchedPayment'];
		$this->PastDue = $db_row['PastDue'];
		$this->FirstPaymentBad = $db_row['FirstPaymentBad'];
        
		//$this->Frequency = $this->CLAssignMap($db_row['Frequency']);
		$this->Frequency = $db_row['Frequency'];
		$this->Duration = $db_row['Duration'];
		$this->Rating = $db_row['Rating'];
		$this->History = $db_row['History'];
        
		$this->SSN = $db_row['SSN'];
        $this->NameFirst = $db_row['NameFirst'];
        $this->NameLast = $db_row['NameLast'];
        $this->DOB = $db_row['DOB'];
        $this->Street1 = $db_row['Street1'];
        $this->Street2 = $db_row['Street2'];
        $this->City = $db_row['City'];
        $this->State = $db_row['State'];
        $this->Zip = $db_row['Zip'];
        $this->PhoneHome = $db_row['PhoneHome'];
		$this->BankABA = $db_row['BankABA'];
		$this->BankAcct = $db_row['BankAcct'];
		$this->AppID = $db_row['AppID'];
    }
    
    /*
     * Maps source data to a clarity result
     */
	protected function CLAssignMap($key)
    {
    	/*
        $map = array (
            'weekly'            => 'B',
            'twice_monthly'     => 'E',
            'bi_weekly'         => 'B',
            'monthly'           => 'M',
            'YES'           => '1',
            'WEEKLY'        => 'Weekly',
            'BI-WEEKLY'     => 'Biweekly',
            'SEMI-MONTHLY'  => 'Semimonthly',
            'TWICE-MONTHLY' => 'Semimonthly',
            'BI_WEEKLY'     => 'Biweekly',
            'SEMI_MONTHLY'  => 'Semimonthly',
            'TWICE_MONTHLY' => 'Semimonthly',
            'MONTHLY'       => 'Monthly'
        );
	*/

	$map = array (
	'weekly'            => 'B',
	'twice_monthly'     => 'E',
	'bi_weekly'         => 'B',
	'monthly'           => 'M',
	'YES'           => '1',
	'WEEKLY'        => 'B',
	'BI-WEEKLY'     => 'B',
	'SEMI-MONTHLY'  => 'E',
	'TWICE-MONTHLY' => 'E',
	'BI_WEEKLY'     => 'B',
	'SEMI_MONTHLY'  => 'E',
	'TWICE_MONTHLY' => 'E',
	'MONTHLY'       => 'M'
	);
        
        if ($map[strtoupper($key)]) return $map[strtoupper($key)];
        else error_log ('Key '.$key.' not found in: '.__METHOD__.' || '.__FILE__);
    }

	/**
	 * Returns the clarity void status.
	 * 
	 * @return varchar(2)
	 */
	public function getVoidStatus()
	{
		return $this->VoidStatus;
	}

	/**
	 * Returns the clarity account type.
	 * 
	 * @return varchar(2)
	 */
	public function getAccountType()
	{
		return $this->AccountType;
	}

	/**
	 * Returns the clarity portfolio type.
	 * 
	 * @return varchar(2)
	 */
	public function getPortfolioType()
	{
		return $this->PortfolioType;
	}

	/**
	 * Returns the application id as the clarity consumer-account-number.
	 *
	 * @return int
	 */
	public function getLoanID()
	{
		return $this->LoanID;
	}

    /**
	 * Returns the loan actual funded date
	 *
	 * @return YYYY-DD-MM
	 */
	public function getLoanDate()
	{
        if (($this->LoanDate) && ($this->LoanDate>0) && (strlen($this->LoanDate)>0)) return date('Y-m-d',strtotime($this->LoanDate));
		else return '';
	}

    /**
	 * Returns the first payment date
	 *
	 * @return YYYY-DD-MM
	 */
	public function getFirstDate()
	{
        if (($this->FirstDate) && ($this->FirstDate>0) && (strlen($this->FirstDate)>0)) return date('Y-m-d',strtotime($this->FirstDate));
		else return '';
	}
    
	/**
	 * Returns the last (this) payment date
	 *
	 * @return YYYY-DD-MM
	 */
	public function getLastDate()
	{
        if (($this->LastDate) && ($this->LastDate>0) && (strlen($this->LastDate)>0)) return date('Y-m-d',strtotime($this->LastDate));
		else return '';
	}

    /**
	 * Returns the deliquency or past-due since date
	 *
	 * @return YYYY-DD-MM
	 */
	public function getPastDate()
	{
        if (($this->PastDate) && ($this->PastDate>0) && (strlen($this->PastDate)>0)) return date('Y-m-d',strtotime($this->PastDate));
		else return '';
	}

    /**
	 * Returns the date the loan was closed
	 *
	 * @return YYYY-DD-MM
	 */
	public function getCloseDate()
	{
        if (($this->CloseDate) && ($this->CloseDate>0) && (strlen($this->CloseDate)>0)) return date('Y-m-d',strtotime($this->CloseDate));
		else return '';
	}

	/**
	 * Returns the payment amount.
	 *
	 * @return int
	 */
	public function getPaymentAmt()
	{
		return intval($this->PaymentAmt);
	}

	/**
	 * Returns the total loan balance.
	 *
	 * @return int
	 */
	public function getBalance()
	{
		return intval($this->Balance);
	}
    
	/**
	 * Returns the total loan balance.
	 *
	 * @return int
	 */
	public function getPrincipal()
	{
		return intval($this->Principal);
	}
    
    /**
     * Returns the amount of the next payment.
     *
     * @return int
     */
    public function getSchedPayment()
    {
        return intval($this->SchedPayment);
    }
    
    /**
     * Returns the amount that a loan is past due.
     *
     * @return int
     */
    public function getPastDue()
    {
        return intval($this->PastDue);
    }
    
    /**
     * Returns true if the first first payment returned/defaulted, true otherwise.
     *
     * @return string bool
     */
    public function getFirstPaymentBad()
    {
        //return intval($this->FirstPaymentBad);
	return $this->FirstPaymentBad;
    }

    
    /**
     * Returns the payment frequency model.
     *
     * @return char(1) or int
     */
    public function getFrequency()
    {
        return $this->Frequency;
    }

    
    /**
     * Returns the the number of payments left.
     *
     * @return int
     */
    public function getDuration()
    {
        return $this->Duration;
    }
    
    /**
     * Returns the application status/past due length as a clarity rating code.
     *
     * @return char(1)
     */
    public function getRating()
    {
        return $this->Rating;
    }
    
    /**
     * Returns the application status history as a clarity rating string.
     *
     * @return char(24)
     */
    public function getHistory()
    {
        return $this->History;
    }
    
    /**
     * Returns the SSN.
     *
     * @return int SSN
     */
    public function getSSN()
    {
        return $this->SSN;
    }
    
    /**
     * Returns the first name.
     *
     * @return string
     */
    public function getNameFirst()
    {
        return $this->NameFirst;
    }

    
    /**
     * Returns the last name.
     *
     * @return string
     */
    public function getNameLast()
    {
        return $this->NameLast;
    }

    /**
     * Returns the date of birth.
	 *
	 * @return YYYY-DD-MM
	 */
    public function getDOB()
    {
        if (($this->DOB) && ($this->DOB>0) && (strlen($this->DOB)>0)) return date('Y-m-d',strtotime($this->DOB));
		else return '';
    }

	/**
	 * Returns the primary street address.
	 *
	 * @return string
	 */
	public function getStreet1()
	{
		return $this->Street1;
	}

	/**
	 * Returns the secondary street address.
	 *
	 * @return string
	 */
	public function getStreet2()
	{
		return $this->Street2;
	}

	/**
	 * Returns the city.
	 *
	 * @return string
	 */
	public function getCity()
	{
		return $this->City;
    }

	/**
	 * Returns the state.
	 *
	 * @return string
	 */
	public function getState()
	{
		return $this->State;
	}

	/**
	 * Returns zip code.
	 *
	 * @return string of numbers
	 */
	public function getZip()
	{
		return $this->Zip;
	}

	/**
	 * Returns the phone number.
	 *
	 * @return string of numbers
	 */
	public function getPhoneHome()
	{
		return $this->PhoneHome;
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
	 * Returns the the clarity track id
	 *
	 * @return int
	 */
	public function getAppID()
	{
		return $this->AppID;
	}
}

?>
