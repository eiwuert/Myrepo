<?php

/**
 * Basic data request for Clarity
 *
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
require_once('ClarityStoreLookup.php');
class Clarity_UW_Request implements Clarity_UW_IRequest
{
	/**
	 * Username to use when requesting to
	 * Clarity
	 *
	 * @var string
	 */
	protected $username;

	/**
	 * Password to use when requesting to
	 * Clarity
	 *
	 * @var string
	 */
	protected $password;

	/**
	 * Merchant ID to use when requesting to
	 * Clarity
	 *
	 * @var string
	 */
	protected $merchant;

	/**
	 * Store ID to use when requesting to
	 * Clarity
	 *
	 * @var string
	 */
	protected $store;    

	/**
	 * Clarity Rule to request?
	 *
	 * @var string
	 */
	protected $call_name;

	/**
	 * Map for input data to Clarity request data.
	 *
	 * @var array
	 */
	protected static $data_map = array(
		'last-name'                 => 'name_last',
		'first-name'                => 'name_first',
		'middle-initial'            => 'name_middle',
		'street-address-1'          => 'streetphone_work',
		'street-address-2'          => 'unit',
		'city'                      => 'city',
		'state'                     => 'state',
		'zip-code'                  => 'zip_code',
		'social-security-number'    => 'ssn',
		'drivers-license-number'     => 'legal_id_number',
		'drivers-license-state'      => 'legal_id_state',
		'bank-routing-number'       => 'bank_aba',
		'bank-account-number'       => 'bank_account',
		'email-address'             => 'email-address',
		'home-phone'                => 'phone_home',
		'cell-phone'                => 'phone_cell',
		'work-phone'                => 'phone_work',
		'employer-name'             => 'employer_name',
		'net-monthly-income'        => 'income_monthly',
		'pass-through-1'            => 'application_id',
		'pass-through-2'            => 'track_id',
		'pass-through-3'            => 'ip_address',
		'pass-through-4'            => 'requested_amount',
		'pass-through-5'            => 'application_type'
	);
    
	protected static $static_map = array(
		'inquiry-purpose-type'      => 'AR',
		'inquiry-tradeline-type'    => 'C1'
    );
    
	protected static $function_map = array(
		'months-at-address'         => array('MonthsFromDate', 'residence_start_date'),
		'date-of-birth'             => array('CLformatDate', 'dob'),
		'months-at-current-employer'=> array('MonthsFromDate', 'date_hire'),
		'date-of-next-payday'       => array('CLformatDate', 'date_first_payment'),
		'pay-frequency'             => array('CLAssignMap', 'income_frequency'),
		'bank-account-type'         => array('CLAssignMap', 'bank_account_type'),
		'paycheck-direct-deposit'   => array('ClAssignMap', 'income_direct_deposit')
    );

	/**
	 * Construct a basic Clarity request
	 *
	 * @param string $license
	 * @param string $password
	 * @param string $call_name
	 */
	public function __construct($store_id, $inquiry)
	{
//error_log(__METHOD__.' || '.__FILE__);
//error_log('call type: '.$inquiry);
//error_log(print_r($store_name,true));
		if (empty($store_id))
		{
			throw new InvalidArgumentException('empty clarity store id passed');
		}
		if (empty($inquiry))
		{
			throw new InvalidArgumentException('empty clarity inquiry passed');
		}
        //use the store/inquiry to lookup the merchant, username, and password
        $store = new ClarityStore($store_id);
//error_log(print_r($store,true));

        $this->call_name = $inquiry;
		$this->username = $store->username;
		$this->password = $store->password;
		$this->merchant = $store->merchant;
		$this->group = $store->group;
		$this->store = $store->store;
	}

	/**
	 * Defined by Clarity_UW_IRequest
	 * 
	 * @return string
	 */
	public function getCallType()
	{
		return $this->call_name;
	}

	/**
	 * Defined by Clarity_UW_IRequest
	 * 
	 * @return string
	 */
	public function setCallType($val)
	{
//error_log(__METHOD__.' || '.__FILE__);
//error_log('call type: '.$val);
        $this->call_name = $val;
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
		$root = $dom->appendChild($dom->createElement('inquiry'));

		$root->appendChild($dom->createElement('username', $this->username));
		$root->appendChild($dom->createElement('password', $this->password));
		$root->appendChild($dom->createElement('account-id', $this->merchant));
		$root->appendChild($dom->createElement('location-id', $this->store));
		$root->appendChild($dom->createElement('group-id', $this->group));
		$root->appendChild($dom->createElement('control-file-name', $this->call_name));

		foreach (self::$data_map as $element_name => $array_key)
		{
			if (!empty($data[$array_key]) || $data[$array_key] === 0)
			{
				$root->appendChild($dom->createElement($element_name, $data[$array_key]));
			}
		}
		foreach (self::$static_map as $element_name => $array_key)
		{
			$root->appendChild($dom->createElement($element_name, $array_key));
		}

		foreach (self::$function_map as $element_name => $array_key)
		{
            if (count($array_key) == 1) {
                $value = call_user_func(array($this,$array_key[0]));
            } else {
                $value = call_user_func(array($this,$array_key[0]),$data[$array_key[1]]);
            }
			$root->appendChild($dom->createElement($element_name, $value));
		}
		return trim($dom->saveXML());
	}
    
	protected function CLAssignMap($key)
    {
        $map = array (
            'CHECKING'      => 'Checking',
            'SAVINGS'       => 'Savings',
            'DEBIT'         => 'Debit Card',
            'NO'            => '0',
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
        
        if ($map[strtoupper($key)]) return $map[strtoupper($key)];
        else error_log ('Key '.$key.' not found in: '.__METHOD__.' || '.__FILE__);
    }
    
	protected function MonthsFromDate($timestamp)
    {
        return (floor(floatval(time() - $timestamp) / (60 * 60 * 24 * 365 / 12)));
    }
    
	protected function DateToday()
    {
        return date('Y-M-D');
    }
    
	protected function DateTomorrow()
    {
        return date('Y-M-D',time() + (24 * 60 * 60));
    }
    
	protected function CLformatDate($date_str)
    {
        return date('Y-M-D',strtotime($date_str));
    }
}
