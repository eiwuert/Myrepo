<?php

/**
 * Basic data request for FactorTrust
 *
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
require_once('FactorTrustStoreLookup.php');
class FactorTrust_UW_Request implements FactorTrust_UW_IRequest
{
	/**
	 * Username to use when requesting to
	 * FactorTrust
	 *
	 * @var string
	 */
	protected $username;

	/**
	 * Password to use when requesting to
	 * FactorTrust
	 *
	 * @var string
	 */
	protected $password;

	/**
	 * Merchant ID to use when requesting to
	 * FactorTrust
	 *
	 * @var string
	 */
	protected $merchant;

	/**
	 * Store ID to use when requesting to
	 * FactorTrust
	 *
	 * @var string
	 */
	protected $store;    

	/**
	 * FactorTrust Rule to request?
	 *
	 * @var string
	 */
	protected $call_name;

	/**
	 * Map for input data to FactorTrust request data.
	 *
	 * @var array
	 */
	protected static $data_map = array(
		'ApplicationID'      => 'track_id',
		'MobileNumber'       => 'phone_cell',
		'FirstName'          => 'name_first',
		'LastName'           => 'name_last',
		'EmailAddress'       => 'email',
		'IPAddress'          => 'ip_address',
		'DLNumber'           => 'legal_id_number',
		'DLNumberIssueState' => 'legal_id_state',
		'DateOfBirth'        => 'dob',
		'Address1'           => 'streetphone_work',
		'Address2'           => 'unit',
		'City'               => 'city',
		'State'              => 'state',
		'Zip'                => 'zip_code',
		'HomePhone'          => 'phone_home',
		'SSN'                => 'ssn',
		'AccountABA'         => 'bank_aba',
		'AccountDDA'         => 'bank_account',
		'EmployerName'       => 'employer_name',
		'MonthlyIncome'      => 'income_monthly',
		'RequestedLoanAmount' => 'requested_amount',
		'LeadSource'         => 'application_type'
	);
    
	protected static $static_map = array(
		'Country'            => 'US',
		'AlternateZip'       => '',
		'SSNIssueState'      => '',
		'PayrollGarnishment' => '',
		'HasBankruptcy'      => '',
		'LeadType'           => '',
		'BlackBox'           => ''
    );
    
	protected static $function_map = array(
		'AccountType'        => array('FTAssignMap', 'bank_account_type'),
		'PayrollType'        => array('FTAssignMap', 'income_direct_deposit'),
		'PayrollFrequency'   => array('FTAssignMap', 'income_frequency'),
		'MonthsAtAddress'    => array('MonthsFromDate', 'residence_start_date'),
		'LengthOfEmployment' => array('MonthsFromDate', 'date_hire'),
		'RequestedEffectiveDate' => array('DateToday'),
		'RequestedDueDate'   => array('DateToday')
    );

	/**
	 * Construct a basic FactorTrust request
	 *
	 * @param string $license
	 * @param string $password
	 * @param string $call_name
	 */
	public function __construct($store_id)
	{
		if (empty($store_id))
		{
			throw new InvalidArgumentException('empty factor trust store id passed');
		}
        //use the store/inquiry to lookup the merchant, username, and password
        $store = new FactorTrustStore($store_id);

		$this->username = $store->username;
		$this->password = $store->password;
		$this->merchant = $store->merchant;
		$this->store = $store_id;
	}

	/**
	 * Defined by FactorTrust_UW_IRequest
	 * 
	 * @return string
	 */
	public function getCallType()
	{
		return $this->store;
	}

	/**
	 * Defined by FactorTrust_UW_IRequest
	 * 
	 * @return string
	 */
	public function setCallType($val)
	{
        $this->store = $val;
		return;
	}

	/**
	 * Transform the data we have into an xml packed
	 *
	 * @param array $data
	 * @return string;
	 */
	public function transformData(array $data)
	{
		$dom = new DOMDocument();
		$root = $dom->appendChild($dom->createElement('Application'));

		$auth = $root->appendChild($dom->createElement('LoginInfo'));
		$auth->appendChild($dom->createElement('Username', $this->username));
		$auth->appendChild($dom->createElement('Password', $this->password));
		$auth->appendChild($dom->createElement('Channelidentifier', ''));
		$auth->appendChild($dom->createElement('MerchantIdentifier', $this->merchant));
		$auth->appendChild($dom->createElement('StoreIdentifier', $this->store));

		$query = $root->appendChild($dom->createElement('ApplicationInfo'));

		foreach (self::$data_map as $element_name => $array_key)
		{
			if (!empty($data[$array_key]) || $data[$array_key] === 0)
			{
				$query->appendChild($dom->createElement($element_name, $data[$array_key]));
			}
		}
		foreach (self::$static_map as $element_name => $array_key)
		{
			$query->appendChild($dom->createElement($element_name, $array_key));
		}

		foreach (self::$function_map as $element_name => $array_key)
		{
            if (count($array_key) == 1) {
                $value = call_user_func(array($this,$array_key[0]));
            } else {
                $value = call_user_func(array($this,$array_key[0]),$data[$array_key[1]]);
            }
			$query->appendChild($dom->createElement($element_name, $value));
		}
		return trim($dom->saveXML());
	}

	protected function GenerateApID($ssn)
    {
        return($ssn.time());
    }
    
	protected function FTAssignMap($key)
    {
        $map = array (
            'CHECKING'      => 'C',
            'SAVINGS'       => 'S',
            'DEBIT'         => 'D',
            'NO'            => 'P',
            'YES'           => 'D',
            'WEEKLY'        => 'W',
            'BI-WEEKLY'     => 'B',
            'SEMI-MONTHLY'  => 'S',
            'TWICE-MONTHLY' => 'S',
            'BI_WEEKLY'     => 'B',
            'SEMI_MONTHLY'  => 'S',
            'TWICE_MONTHLY' => 'S',
            'MONTHLY'       => 'M'
        );
        
        if ($map[strtoupper($key)]) return $map[strtoupper($key)];
        else error_log ('Key '.$key.' not found in: '.__METHOD__.' || '.__FILE__);
    }
    
	protected function MonthsFromDate($timestamp)
    {
        return (floor(floatval(time() - $timestamp) / (60 * 60 * 24 * 365 / 12)));
    }
    
	protected function DateToday()
    {
        date('m/d/Y');
    }
    
	protected function DateTomorrow()
    {
        date('m/d/Y',time() + (24 * 60 * 60));
    }
}
