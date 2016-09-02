<?php

require_once(LIB_DIR . "Payment_Card.class.php");

class SuitePayRequest
{
	//const LIVE_URL = 'https://secure.suitepay.com/api/v2/card/sale';
	const LIVE_URL = 'https://api.suitepay.com/api/v2/card/sale';
	const QA_URL = 'https://qa.suitepay.com/api/v2/card/sale2';
	//const QA_URL = 'https://ZNMx8eVZsBL:kHJz0DFjXBkV927x8eED18k9HZpjhWfH@qa.suitepay.com/api/v2/card/sale2';
	//const QA_URL = 'sandbox.suitepay.com';
	const DEVELOPER_ID = '053bce59548ac01d1b52dfd3ab688ff6a7646bd3';
	const PUBLIC_KEY = 'kHJz0DFjXBkV927x8eED18k9HZpjhWfH';
	//const USER_LOGIN = 'alex.lyakhov@clearlakeholdings.com';
	const USER_LOGIN = 'ZNMx8eVZsBL';
	//const USER_LOGIN = 'jared.kleinman@clearlakeholdings.com';
	const MID = '99';
	const COUNTRY = 'US';

	protected $crypter;

	private $amount;
	//private $cardfullname;
	private $mid;
	private $creditcard;
	private $month;
	private $year;
	private $bcountry;
	private $bzip;
	private $cvv;

	private $json_tr_array = array();
	private $json_array = array();
	private $json_obj;

	public function __construct($data)
	{
		$this->crypter = new Payment_Card();

		$this->setField("amount", $data['amount']);

		$this->setField("mid", self::MID);

		//$cardfullname = $this->crypter->decrypt($data['cardholder_name']);
		//$this->setField("cardfullname", $cardfullname);

		$creditcard = $this->crypter->decrypt($data['card_number']);
		$this->setField("creditcard", $creditcard);

		$month = date('m',strtotime($data['expiration_date']));
		$this->setField("month", $month);

		$year = date('y',strtotime($data['expiration_date']));
		$this->setField("year", $year);

		$this->setField("bcountry", self::COUNTRY);

		$this->setField("bzip", $data["zip_code"]);
		
		$this->setField("cvv", "076");
	}

	public function setField($name, $value)
	{
		$this->{$name} = $value;
		$this->json_tr_array[$name] = $value;
	}

	public function setJsonArray()
	{
		$this->json_array["transaction_data"] = $this->json_tr_array;
		$this->json_array["developerid"] = self::DEVELOPER_ID ;
		$this->json_array["public_key"] = self::PUBLIC_KEY;
		$this->json_array["user_login"] = self::USER_LOGIN;
	}

	public function getField($name)
	{
		return $this->{$name};
	}

	public function getJsonArray()
	{
		return $this->json_array;
	}

	public function getJsonObject()
	{
		return $this->json_obj;
	}

	public function process()
	{
		$this->setJsonArray();
		$this->json_obj = json_encode($this->json_array);
		//var_dump($this->json_obj);

		$opt = array(
		CURLOPT_URL => self::QA_URL,
		CURLOPT_POST => 1,
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_POSTFIELDS => $this->json_obj,
		CURLOPT_SSL_VERIFYPEER => FALSE,
		);
		$curl = curl_init();
		curl_setopt_array($curl, $opt);
		$response = curl_exec($curl);
		curl_close($curl);

		//if ($response === FALSE)
		//	echo 'Curl error: ' . curl_error($curl);
		//else
		//	var_dump($response);

		//var_dump($response);
		//$response = json_decode($response);
		//var_dump($response);
	}
}

?>
