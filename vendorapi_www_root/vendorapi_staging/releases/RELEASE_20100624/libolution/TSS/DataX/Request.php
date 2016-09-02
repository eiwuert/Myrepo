<?php

/**
 * Basic data request for DataX
 *
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
class TSS_DataX_Request implements TSS_DataX_IRequest
{
	/**
	 * License to use when requesting to
	 * datax
	 *
	 * @var string
	 */
	protected $license;

	/**
	 * Password to use when requesting to
	 * datax
	 *
	 * @var string
	 */
	protected $password;

	/**
	 * DataX Rule to request?
	 *
	 * @var string
	 */
	protected $call_name;

	/**
	 * Map for input data to DataX request data.
	 *
	 * @var array
	 */
	protected static $data_map = array(
		'NAMEFIRST' => 'name_first',
		'NAMELAST'  => 'name_last',
		'NAMEMIDDLE'=> 'name_middle',
		'STREET1'   => 'street',
		'CITY'      => 'city',
		'STATE'     => 'state',
		'ZIP'       => 'zip_code',
		'PHONEHOME' => 'phone_home',
		'PHONECELL' => 'phone_cell',
		'PHONEWORK' => 'phone_work',
		'EMAIL'     => 'email',
		'DOBYEAR'   => 'dob_y',
		'DOBMONTH'  => 'dob_m',
		'DOBDAY'    => 'dob_d',
		'SSN'       => 'ssn',
		'STREET2'   => 'unit',
		'BANKNAME'  => 'bank_name',
		'BANKACCTNUMBER' => 'bank_account',
		'BANKABA'   => 'bank_aba',
		'PAYPERIOD' => 'income_frequency',
		'IPADDRESS' => 'ip_address',
		'WORKNAME'  => 'employer_name',
		'SOURCE'    => 'client_url_root',
		'PROMOID'   => 'promo_id',
		'PUBLISHERSUBCODE' => 'promo_sub_code',
		'PUBLISHERID' => 'publisher_id',
		'INCOMETYPE' => 'income_source',
		'MONTHLYINCOME' => 'income_monthly',
		'LEADCOST'  => 'lead_cost',
		'DIRECTDEPOSIT' => 'income_direct_deposit',
		'PAYMENTMETHOD' => 'payment_method',
		'AMOUNTREQUESTED' => 'requested_amount',
		'DRIVERLICENSENUMBER' => 'legal_id_number',
		'DRIVERLICENSESTATE'  => 'legal_id_state',
		'APPLICATIONTYPE' => 'application_type'
	);


	/**
	 * Construct a basic datax request
	 *
	 * @param string $license
	 * @param string $password
	 * @param string $call_name
	 */
	public function __construct($license, $password, $call_name)
	{
		if (empty($license))
		{
			throw new InvalidArgumentException('empty license key passed');
		}

		if (empty($password))
		{
			throw new InvalidArgumentException('empty password passed');
		}

		if (empty($call_name))
		{
			throw new InvalidArgumentException('empty call type passed');
		}

		$this->license = $license;
		$this->password = $password;
		$this->call_name = $call_name;
	}

	/**
	 * Defined by TSS_DataX_IRequest
	 * 
	 * @return string
	 */
	public function getCallType()
	{
		return $this->call_name;
	}

	/**
	 * Defined by TSS_DataX_IRequest
	 * 
	 * @return string
	 */
	public function setCallType($calltype)
	{
		$this->call_name = $calltype;
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
		$root = $dom->appendChild($dom->createElement('DATAXINQUIRY'));

		$auth = $root->appendChild($dom->createElement('AUTHENTICATION'));
		$auth->appendChild($dom->createElement('LICENSEKEY', $this->license));
		$auth->appendChild($dom->createElement('PASSWORD', $this->password));

		$query = $root->appendChild($dom->createElement('QUERY'));

		if (isset($data['track_hash']))
		{
			$query->appendChild($dom->createElement('TRACKHASH', $data['track_hash']));
		}

		$query->appendChild($dom->createElement('TRACKID', $data['application_id']));
		$query->appendChild($dom->createElement('TYPE', $this->call_name));
		$query->appendChild($dom->createElement('TRACKKEY', $data['track_id']));
		
		$data_element = $query->appendChild($dom->createElement('DATA'));
		
		foreach (self::$data_map as $element_name => $array_key)
		{
			if (!empty($data[$array_key]) || $data[$array_key] === 0)
			{
				$data_element->appendChild($dom->createElement($element_name, $data[$array_key]));
			}
		}
		return trim($dom->saveXML());
	}
}
