<?php

/**
 * Basic data request for Tribal
 */
class TSS_Tribal_Request implements TSS_Tribal_IRequest
{
	/**
	 * License to use when requested
	 *
	 * @var string
	 */
	protected $license;

	/**
	 * Password to use when requested
	 *
	 * @var string
	 */
	protected $password;

	/**
	 * Tribal Rule to request
	 *
	 * @var string
	 */
	protected $call_name;

	/**
	 * Map for input data to Tribal request data.
	 *
	 * @var array
	 */
	protected static $data_map = array(
		'application_id' => 'application_id',
		'name_first' => 'name_first',
		'name_middle' => 'name_middle',
		'name_last' => 'name_last',
		'dob' => 'dob',
		'ssn_last4' => 'ssn_last4',
		'legal_id_type' => 'legal_id_type',
		'legal_id_state' => 'legal_id_state',
		'legal_id_number' => 'legal_id_number',
		'street' => 'street',
		'unit' => 'unit',
		'city' => 'city',
		'state' => 'state',
		'zip_code' => 'zip_code',
		'email' => 'email',
		'phone_home' => 'phone_home',
		'phone_cell' => 'phone_cell',
		'phone_work' => 'phone_work',
		'military' => 'military',
		'employer_name' => 'employer_name',
		'income_direct_deposit' => 'income_direct_deposit',
		'income_source' => 'income_source',
		'income_frequency' => 'income_frequency',
		'income_monthly' => 'income_monthly',
		'ip_address' => 'ip_address',
		'is_react' => 'is_react',
	);

	/**
	 * Construct a basic Tribal request
	 *
	 * @param string $license
	 * @param string $password
	 * @param string $call_name
	 */
	public function __construct($license, $password, $call_name)
	{
		/*
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
		*/

		$this->license = $license;
		$this->password = $password;
		$this->call_name = $call_name;
	}

	/**
	 * Defined by TSS_Tribal_IRequest
	 * 
	 * @return string
	 */
	public function getCallType()
	{
		return $this->call_name;
	}

	/**
	 * Defined by TSS_Tribal_IRequest
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
		$data['ssn_last4'] = substr($data['ssn'], 5);
		$data['legal_id_type'] = 'dl';

		$military = $data['military'];
		$income_direct_deposit = $data['income_direct_deposit'];
		$is_react = $data['is_react'];
		$data['dob'] = date("m/d/Y", strtotime($data['dob']));

		if (
			$military == '1'
			|| $military == 'yes'
			|| $military === TRUE
		)
		{
			$data['military'] = '1';
		}
		else
		{
			$data['military'] = '0';
		}
		
		if (
			$income_direct_deposit == '1'
			|| $income_direct_deposit == 'yes'
			|| $income_direct_deposit === TRUE
		)
		{
			$data['income_direct_deposit'] = '1';
		}
		else
		{
			$data['income_direct_deposit'] = '0';
		}
		
		if (
			$is_react == '1'
			|| $is_react == 'yes'
			|| $is_react === TRUE
		)
		{
			$data['is_react'] = '1';
		}
		else
		{
			$data['is_react'] = '0';
		}

		$db = ECash::getFactory()->getDB();
		$sql = "SELECT (max(authoritative_id)+1) AS next_authoritative_id from authoritative_ids";
		$result = $db->query($sql);
		$row = $result->fetch(PDO::FETCH_OBJ);
		$data['application_id'] = $row->next_authoritative_id;

		/*
		$dom = new DOMDocument();
		$request_element = $dom->appendChild($dom->createElement('request'));

		foreach (self::$data_map as $element_name => $array_key)
		{
			$request_element->appendChild($dom->createElement($element_name, $data[$array_key]));
		}

		return trim($dom->saveXML());
		*/

		$xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
<soap:Envelope
xmlns:soap=\"http://www.w3.org/2001/12/soap-envelope\"
soap:encodingStyle=\"http://www.w3.org/2001/12/soap-encoding\">
<soap:Body xmlns:m=\"https://vendorapi.loanservicingcompany.com/soap.php?wsdl\">
";
	 	foreach (self::$data_map as $element_name => $array_key)
		{
			$xml .= "<" . $element_name . ">" . $data[$array_key] . "</" . $element_name . ">";
		}

		$xml .= "
</soap:Body>
</soap:Envelope>";

		return  $xml;

		/*
		//for soapClient
		$xml_array = array();

		foreach (self::$data_map as $element_name => $array_key)
		{
			$xml_array[$array_key] = $data[$array_key];
		}

		return $xml_array;
		*/
	}
}
