<?php
class Tests_ApplicationHelper
{
	protected $application;
	
	protected $payment;
	
	public function __construct()
	{
		$this->prepareData();
	}
	
	public function getApplication()
	{
		return $this->application;
	}
	
	public function getPayment()
	{
		return $this->payment;
	}
	
	public function getBaseXml()
	{
		return "<?xml version=\"1.0\" encoding=\"utf8\"?>\n".
			"<CRAINQUIRY>".
				"<QUERY>".
					"<TYPE>TEST</TYPE>".
					"<EXTERNALID>10001</EXTERNALID>".
					"<DATA/>".
				"</QUERY>".
			"</CRAINQUIRY>\n";
	}
	
	public function getUpdateXml()
	{
		return "<?xml version=\"1.0\" encoding=\"utf8\"?>\n".
			"<CRAINQUIRY>".
				"<QUERY>".
					"<TYPE>TEST</TYPE>".
					"<EXTERNALID>10001</EXTERNALID>".
					"<DATA>".
						"<NAMEFIRST>Mike</NAMEFIRST>".
						"<NAMEMIDDLE>J</NAMEMIDDLE>".
						"<NAMELAST>Lively</NAMELAST>".
						"<STREET1>123 Test St</STREET1>".
						"<STREET2>Apt 180</STREET2>".
						"<CITY>Las Vegas</CITY>".
						"<STATE>NV</STATE>".
						"<ZIP>89119</ZIP>".
						"<PHONEHOME>5555555555</PHONEHOME>".
						"<PHONECELL>5555555556</PHONECELL>".
						"<PHONEWORK>5555555557</PHONEWORK>".
						"<PHONEEXT>123</PHONEEXT>".
						"<EMAIL>rebel75cell@gmail.com</EMAIL>".
						"<IPADDRESS>123.123.123.123</IPADDRESS>".
						"<DOB>1980-01-01</DOB>".
						"<SSN>123121234</SSN>".
						"<DRIVERLICENSENUMBER>123456789</DRIVERLICENSENUMBER>".
						"<DRIVERLICENSESTATE>NV</DRIVERLICENSESTATE>".
						"<WORKNAME>The Selling Source</WORKNAME>".
						"<WORKSTREET1>325 E. Warm Springs Road</WORKSTREET1>".
						"<WORKSTREET2>Suite 200</WORKSTREET2>".
						"<WORKCITY>Las Vegas</WORKCITY>".
						"<WORKSTATE>NV</WORKSTATE>".
						"<WORKZIP>89119</WORKZIP>".
						"<BANKNAME>First Bank</BANKNAME>".
						"<BANKACCTNUMBER>123456</BANKACCTNUMBER>".
						"<BANKABA>123456789</BANKABA>".
						"<PAYPERIOD>weekly</PAYPERIOD>".
					"</DATA>".
				"</QUERY>".
			"</CRAINQUIRY>\n";
	}
	
	protected function prepareData()
	{
		$this->application = new ECashCra_Data_Application(array(
			'application_id' => 10001,
			'fund_date' => '2008-03-20',
			'fund_amount' => 300,
			'date_first_payment' => '2008-04-01',
			'fee_amount' => 90
		));
		
		$this->application->setEmployer(new ECashCra_Data_Employer(array(
			'employer_name' => 'The Selling Source',
			'employer_street1' => '325 E. Warm Springs Road',
			'employer_street2' => 'Suite 200',
			'employer_city' => 'Las Vegas',
			'employer_state' => 'NV',
			'employer_zip' => '89119',
			'phone_work' => '5555555557',
			'phone_ext' => '123',
			'pay_period' => 'weekly',
		)));
		
		$this->application->setPersonal(new ECashCra_Data_Personal(array(
			'name_first' => 'Mike',
			'name_middle' => 'J',
			'name_last' => 'Lively',
			'street1' => '123 Test St',
			'street2' => 'Apt 180',
			'city' => 'Las Vegas',
			'state' => 'NV',
			'zip' => '89119',
			'phone_home' => '5555555555',
			'phone_cell' => '5555555556',
			'email' => 'rebel75cell@gmail.com',
			'ip_address' => '123.123.123.123',
			'dob' => '1980-01-01',
			'ssn' => '123121234',
			'driver_license_number' => '123456789',
			'driver_license_state' => 'NV',
			'bank_name' => 'First Bank',
			'bank_acct_number' => '123456',
			'bank_aba' => '123456789',
		)));
		
		$this->payment = new ECashCra_Data_Payment(array(
			'payment_id' => '2002',
			'payment_type' => 'DEBIT',
			'payment_method' => 'ACH',
			'payment_date' => '2008-03-21',
			'payment_amount' => '90',
			'payment_return_code' => 'NSF'
		));
		
		$this->payment->setApplication($this->application);
	}
}
?>
