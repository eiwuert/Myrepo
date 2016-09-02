<?php

  //mysql -hreader.ecashufc.ept.tss -uecash -pugd2vRjv

  //http://www.digitalsandwich.com/archives/63-PHPUnit-Database-Extension-DBUnit-Port.html
  //http://www.digitalsandwich.com/archives/64-Adding-Database-Tests-to-Existing-PHPUnit-Test-Cases.html
  //http://andrewm.tss/projects/blackbox/prev_customer/OldTest.phps

require_once 'db_setup.php';
require_once 'mysqli.1.php';
require_once 'qualify.2.php';
require_once 'qualify.3.php';

class qualifyTest extends PHPUnit_Framework_TestCase
{
	protected $qualify;
	protected $property = 'UFC';
	protected $holiday_iterator;

	protected function setUp()
	{
        $this->setupSession();
		$db_tester = $this->getDatabaseTester();
        $db_tester->onSetUp();

		//get a years worth of holidays
		$this->holiday_iterator = new Date_BankHolidays_1();
		$holiday_array = $this->holiday_iterator->getHolidayArray();
		
		$this->qualify = new Qualify_3($this->property, $holiday_array, new stdClass(), $this->getECash());
	}

	public function testMonthlyNetWeekly()
	{
		$monthly = $this->qualify->Calculate_Monthly_Net('WEEKLY', 1000);
		$this->assertEquals(231,$monthly);
	}

	public function testMonthlyNetBiWeekly()
	{
		$monthly = $this->qualify->Calculate_Monthly_Net('BI_WEEKLY', 1000);
		$this->assertEquals(462,$monthly);
	}

	public function testMonthlyNetTwiceMonthly()
	{
		$monthly = $this->qualify->Calculate_Monthly_Net('TWICE_MONTHLY', 1000);
		$this->assertEquals(500,$monthly);
	}

	public function testMonthlyNetMonthly()
	{
		$monthly = $this->qualify->Calculate_Monthly_Net('MONTHLY', 1000);
		$this->assertEquals(1000,$monthly);
	}
/*
	//Calculate_React_Loan_Amount($monthly_net, $direct_deposit, $react_app_id = 0, $frequency_name = NULL) //mantis:9786 - added $frequency_name
	
	public function testReact500()
	{
		$amt = $this->qualify->Calculate_React_Loan_Amount(500, TRUE);

		$this->assertEquals(150,$amt);
	}
*/
	//Calculate_Loan_Amount($monthly_net, $direct_deposit)
	public function testLoan500()
	{
		$amt = $this->qualify->Calculate_Loan_Amount(500, TRUE);
		$this->assertEquals(150,$amt);
	}

	public function testLoan1000()
	{
		$amt = $this->qualify->Calculate_Loan_Amount(1000, TRUE);
		$this->assertEquals(150,$amt);
	}

	public function testLoan1500()
	{
		$amt = $this->qualify->Calculate_Loan_Amount(1500, TRUE);
		$this->assertEquals(200,$amt);
	}
	
	public function testLoan2000()
	{
		$amt = $this->qualify->Calculate_Loan_Amount(2000, TRUE);
		$this->assertEquals(300,$amt);
	}

	//Finance_Info($payoff_date, $fund_date, $loan_amount, $finance_charge = NULL)

	public function testFinance1()
	{
		$f_info = $this->qualify->Finance_Info(strtotime('2008-01-15'), strtotime('2008-01-01'), 300);
		$this->assertEquals(
			array('finance_charge' => '90',
				  'apr' => '782.14',
				  'total_payments' => '390'),
			$f_info);			
	}
	
	public function testFinance2()
	{
		$f_info = $this->qualify->Finance_Info(strtotime('2008-02-01'), strtotime('2008-01-01'), 100);
		$this->assertEquals(
			array('finance_charge' => '30',
				  'apr' => '353.23',
				  'total_payments' => '130'),
			$f_info);			
	}

	//Qualify_Person($pay_dates, $pay_span, $pay, $direct_deposit, $job_length, $loan_amount = NULL, $react_loan = FALSE, $react_app_id = NULL, $preact = FALSE)

	private function formatDates(array $dates)
	{
		foreach($dates as $key => $timestamp)
		{
			$dates[$key] = date('Y-m-d', $timestamp);
		}
		return $dates;
	}
	
	public function testQualify1()
	{
		$pay_span = 'weekly';
		$model_name = Date_PayDateModel_1::WEEKLY_ON_DAY;
		$direct_deposit = TRUE;
		$dow = 'tuesday';
		$pay = 2000;
		$job_length = NULL; //unused
		$start_date = strtotime('2008-01-01');
		$fund_date = strtotime('2008-02-22');
		
		$model = Date_PayDateModel_1::getModel($model_name, $dow);
		$calc = new Date_PayDateCalculator_1($model, new Date_Normalizer_1($this->holiday_iterator, $direct_deposit), $start_date);		
		$pay_dates = $this->formatDates($calc->getPayDateArray());		
		
		$q_info = $this->qualify->Qualify_Person($pay_dates, $pay_span, $pay, $direct_deposit, $job_length, NULL, NULL, NULL, NULL, $fund_date);
		$this->assertEquals(
			array('finance_charge' => '45',
				  'apr' => '995.45',
				  'total_payments' => '195',
				  'fund_date' => '2008-02-22',
				  'payoff_date' => '2008-03-04',
				  'fund_amount' => '150',
				  'net_pay' => '462'),
			$q_info);			
	}
	
	/**
	 * Gets a connection to the ECash test database
	 *
	 * @return MySQLi_1
	 */
	protected function getECash()
	{
		return new MySQLi_1(TEST_DB_HOST, TEST_DB_USER, TEST_DB_PASS, TEST_DB);
	}

	/**
	 * Sets up the session
	 *
	 */
	protected function setupSession()
	{
		// for session use... gah!
		unset($GLOBALS['_SESSION']);
		$GLOBALS['_SESSION'] = array();
            
		$_SESSION['config'] = new stdClass();
		$_SESSION['config']->use_new_process = TRUE;
		$_SESSION['config']->ecash_prop_list = array('ca', 'ufc', 'pcl', 'd1', 'ucl');
		$_SESSION['config']->ecash3_prop_list = array('ca', 'ufc', 'pcl', 'd1', 'ucl');
	}

	/**
	 * Gets a database tester for the given schema
	 *
	 * @param string $schema
	 * @param PHPUnit_Extensions_Database_DataSet_IDataSet $dataset
	 * @param PHPUnit_Extensions_Database_Operation_DatabaseOperation $setup
	 * @param PHPUnit_Extensions_Database_Operation_DatabaseOperation $teardown
	 * @return PHPUnit_Extensions_Database_DefaultTester
	 */
	protected function getDatabaseTester()
	{
		$pdo = new PDO('mysql:host='.TEST_DB_HOST.';dbname='.TEST_DB, TEST_DB_USER, TEST_DB_PASS);
		$connection = new PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection(
			$pdo, 
			TEST_DB
            );

		$test = new PHPUnit_Extensions_Database_DefaultTester($connection);
		$test->setSetUpOperation(PHPUnit_Extensions_Database_Operation_Factory::CLEAN_INSERT());
		$test->setTearDownOperation(PHPUnit_Extensions_Database_Operation_Factory::NONE());
		$test->setDataSet(new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet(dirname(__FILE__) . '/qualify-rules.xml'));

		return $test;
	}
}


?>
