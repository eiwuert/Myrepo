<?php

/**
 * Tests for the loan API abstract
 * 
 * @package ECash_Loan
 * @author Adam Englander <adam.englander@sellingsurce.com>
 * @author Jum Wu <jim.wu@sellingsource.com>
 */
class ECash_Service_Loan_APITest extends PHPUnit_Framework_TestCase
{
	const COMPANY_ID = 1;
	
	/**
	 * @var ECash_Factory
	 */
	protected $ecash_factory;

	/**
	 * @var ECash_Service_Loan_IECashAPIFactory
	 */
	protected $ecash_api_factory;
	
	/**
	 * Implementation object
	 *
	 * @var ECash_Service_Loan_API
	 */
	protected $loan_api;
	
	private $customer_login = '';
	private $application_id = NULL;
	private $old_password = '';
	private $new_password = '';
	private $old_encrypted_password = '';
	private $new_encrypted_password = '';
		
	/**
	 * Set up the mocked API and implementation for tests
	 * @return void
	 */
	public function setUp()
	{
		$this->customer_login = 'login';
		$this->application_id = 123456;
		$this->old_password = 'old_password';
		$this->new_password = 'new_password';
		$this->old_encrypted_password = crypt_3::Encrypt($this->old_password);
		$this->new_encrypted_password = crypt_3::Encrypt($this->new_password);

		
		$ecash_api_methods = array(
			"Get_Status_Date", "Get_Date_Funded", "Get_Date_Fund_Estimated", "Get_Payoff_Amount",
			"Get_Active_Paid_Out_Date", "Get_Paid_Out_Date", "Has_Pending_Transactions",
			"Is_Regulatory_Flag", "Get_Last_Payment_Date", "Get_Last_Payment_Amount",
			"getFutureCurrentDueDate", "getFutureCurrentDueAmount", "getFutureCurrentDuePrincipalAmount",
			"getFutureCurrentDueServiceChargeAmount", "Get_Current_Due_Date",
			"Get_Current_Due_Amount",  "Get_Current_Due_Principal_Amount", "Get_Current_Due_Service_Charge_Amount",
			"Get_2_Tier_Phone", "Has_Paydown", "Get_Loan_Amount",
		);
	
		$this->ecash_api = $this->getMock(
			"eCash_API_2",
			$ecash_api_methods,
			array(),
			"",
			FALSE);
			
		$this->ecash_api_factory = $this->getMock('ECash_Service_Loan_IECashAPIFactory');
		$this->ecash_api_factory->expects($this->any())
			->method("createECashApi")
			->will($this->returnValue($this->ecash_api));
			
		$this->ecash_factory = $this->getMock('ECash_Factory', array('getReferenceList', 'getModel', 'getWebServiceFactory'));
		
		$this->setUpLoanApi(TRUE);
	}
	
	protected function setUpLoanApi($use_web_services)
	{
		$this->loan_api = new ECash_Service_Loan_API(
			$this->ecash_factory,
			$this->ecash_api_factory,
			$this->getMock('ECash_Service_Loan_ICustomerLoanProvider'),
			self::COMPANY_ID,
			$use_web_services);
	}

	/**
	 * Reset the variables set up in setUp
	 * @return void
	 */
	public function tearDown()
	{
		$this->loan_api = NULL;
		$this->ecash_api = NULL;
		$this->ecash_api_factory = NULL;
	}
	
	public function testChangeCustomerPasswordUpdatesECashDBOnly()
	{
		$this->setUpLoanApi(FALSE);
		$customer = $this->getMock('ECash_Models_Customer', array('save', 'loadBy'));
		$customer->expects($this->once())
			->method('save');
		$customer->expects($this->once())
			->method('loadBy')
			->with(array(
				'company_id' => self::COMPANY_ID,
				'login' => $this->customer_login,
				'password' => $this->old_encrypted_password
			))
			->will($this->returnValue(TRUE));
		$this->ecash_factory->expects($this->once())
			->method('getModel')
			->with('Customer')
			->will($this->returnValue($customer));
		
		$this->ecash_factory->expects($this->never())->method('getWebServiceFactory');
		
		$result = $this->loan_api->changeCustomerPassword($this->application_id, $this->customer_login, $this->old_password, $this->new_password);
		$this->assertEquals("success", $result);
		$this->assertEquals($this->new_encrypted_password, $customer->password, "Model was not updated properly.");
	}
	
	public function testChangeCustomerPasswordUpdatesECashAndAppServiceDB()
	{
		$customer = $this->getMock('ECash_Models_Customer', array('save', 'loadBy'));
		$customer->expects($this->once())
			->method('save');
		$customer->expects($this->once())
			->method('loadBy')
			->with(array(
				'company_id' => self::COMPANY_ID,
				'login' => $this->customer_login,
				'password' => $this->old_encrypted_password
			))
			->will($this->returnValue(TRUE));
		$this->ecash_factory->expects($this->once())
			->method('getModel')
			->with('Customer')
			->will($this->returnValue($customer));
		
		$app_service = $this->mockWebService();
		$app_service->expects($this->once())
			->method('updateApplicantAccount')
			->with($this->customer_login, $this->old_encrypted_password, $this->new_encrypted_password)
			->will($this->returnValue(TRUE));
				
		$result = $this->loan_api->changeCustomerPassword($this->application_id, $this->customer_login, $this->old_password, $this->new_password);
		$this->assertEquals("success", $result);
		$this->assertEquals($this->new_encrypted_password, $customer->password, "Model was not updated properly.");
	}
	
	public function testChangeCustomerPasswordIsAtomicWhenECashFails()
	{
		$customer = $this->getMock('ECash_Models_Customer', array('save', 'loadBy'));
		$customer->expects($this->once())
			->method('save')
			->will($this->throwException(new Exception('Unable to write to ECash')));
		
		$customer->expects($this->once())
			->method('loadBy')
			->with(array(
				'company_id' => self::COMPANY_ID,
				'login' => $this->customer_login,
				'password' => $this->old_encrypted_password
			))
			->will($this->returnValue(TRUE));
		$this->ecash_factory->expects($this->once())
			->method('getModel')
			->with('Customer')
			->will($this->returnValue($customer));
		
		$app_service = $this->mockWebService();
		$app_service->expects($this->exactly(2))
			->method('updateApplicantAccount')
			->will($this->returnValue(TRUE));
		
		// SUT
		$result = $this->loan_api->changeCustomerPassword($this->application_id, $this->customer_login, $this->old_password, $this->new_password);
		
		$this->assertEquals("unable_to_save", $result);
	}

	public function testChangeCustomerPasswordIsAtomicWhenAppServiceFails()
	{
		$app_service_exception = 'App Service Exception!';
		
		$app_service = $this->mockWebService();
		$app_service->expects($this->once())
			->method('updateApplicantAccount')
			->will($this->throwException(new Exception($app_service_exception)));
		
		// SUT
		$result = $this->loan_api->changeCustomerPassword($this->application_id, $this->customer_login, $this->old_password, $this->new_password);
		
		$this->assertEquals("unable_to_save", $result);
	}
	
	protected function mockWebService() {
		$webservices_factory = $this->getMock('WebServices_Factory', array('getWebService'));
		$this->ecash_factory->expects($this->once())
			->method('getWebServiceFactory')
			->will($this->returnValue($webservices_factory));
		
		$app_client = $this->getMock('ECash_WebService_AppClient', array('updateApplicantAccount', 'getApplicantAccountInfo'));
		$webservices_factory->expects($this->once())
			->method('getWebService')
			->with('application')
			->will($this->returnValue($app_client));
				
		return $app_client;
	}
	
	/**
	 * Test the getLoanData method
	 * @return void
	 */
	public function testGetLoanData()
	{
		$status_date = "2009-01-01 01:02";
		$status_date_expected = "2009-01-01T01:02:00";
		$this->ecash_api
			->expects($this->exactly(4))
			->method("Get_Status_Date")
			->will($this->returnValue($status_date));

		$funded_date = "01/02/2009";
		$funded_date_expected = "2009-01-02T00:00:00";
		$this->ecash_api
			->expects($this->once())
			->method("Get_Date_Funded")
			->will($this->returnValue($funded_date));

		$this->ecash_api
			->expects($this->exactly(2))
			->method("Get_Date_Fund_Estimated")
			->will($this->returnValue($funded_date));

		$fund_amount = 123.45;
		$this->ecash_api
			->expects($this->exactly(1))
			->method("Get_Loan_Amount")
			->will($this->returnValue($fund_amount));
			
		$payoff_amount = 10; 
		$this->ecash_api
			->expects($this->once())
			->method("Get_Payoff_Amount")
			->will($this->returnValue($payoff_amount));

		$active_paid_out_date = FALSE; 
		$this->ecash_api
			->expects($this->once())
			->method("Get_Active_Paid_Out_Date")
			->will($this->returnValue($active_paid_out_date));

		$paid_out_date = "2009-01-03"; 
		$expected_paid_out_date = "2009-01-03T00:00:00";
		$this->ecash_api
			->expects($this->once())
			->method("Get_Paid_Out_Date")
			->will($this->returnValue($paid_out_date));

		$has_pending_transactions = TRUE; 
		$this->ecash_api
			->expects($this->once())
			->method("Has_Pending_Transactions")
			->will($this->returnValue($has_pending_transactions));

		$is_regulatory_flag = TRUE; 
		$this->ecash_api
			->expects($this->once())
			->method("Is_Regulatory_Flag")
			->will($this->returnValue($is_regulatory_flag));
		
		$has_paydown = FALSE;
		$this->ecash_api
			->expects($this->once())
			->method("Has_Paydown")
			->will($this->returnValue($has_paydown));

		$tier_2_collections_phone = "123456789";
		$this->ecash_api
			->expects($this->once())
			->method("Get_2_Tier_Phone")
			->will($this->returnValue($tier_2_collections_phone));
			
		
		// react date is 7 days after last payment because of holiday (Jan 1) and Jan 2/3
		// being Sat/Sun/  So, 7 days is 4 business days
		$react_date = "2010-01-07";
		$expected_react_date = "2010-01-07T00:00:00";
		$this->ecash_api
			->expects($this->once())
			->method("Get_Last_Payment_Date")
			->will($this->returnValue("2009-12-31"));
		
			
		$response = $this->loan_api->getLoanData(1);
		
		$this->assertEquals($status_date_expected, $response["date_received"]);
		$this->assertEquals($status_date_expected, $response["date_confirmed"]);
		$this->assertEquals($status_date_expected, $response["date_approved"]);
		$this->assertEquals($funded_date_expected, $response["date_funded"]);
		$this->assertEquals($funded_date_expected, $response["date_fund_estimated"]);
		$this->assertEquals($status_date_expected, $response["date_withdrawn"]);
		$this->assertEquals($payoff_amount, $response["payoff_amount"]);
		$this->assertEquals($active_paid_out_date, $response["has_active_paid_out_date"]);
		$this->assertEquals($expected_paid_out_date, $response["paid_out_date"]);
		$this->assertEquals($has_pending_transactions, $response["has_pending_transactions"]);
		$this->assertEquals($is_regulatory_flag, $response["is_regulatory_flag"]);
		$this->assertEquals($has_paydown, $response["has_paydown"]);
		$this->assertEquals($fund_amount, $response["fund_amount"]);

		$this->assertEquals($tier_2_collections_phone, $response["tier_2_collections_phone"]);
		$this->assertEquals($expected_react_date, $response["react_date"]);

		// Date allowed to pay down should be at least two days from now
		$matches = array();
		$this->assertTrue((bool)preg_match('/^([0-9]{4}-[0-9]{2}-[0-9]{2})T([0-9]{2}:[0-9]{2}:[0-9]{2})$/i',
					$response["date_allowed_to_paydown"], 
					$matches),
			$response["date_allowed_to_paydown"] . " was the wrong format for date allowed to paydown"
		);

		// Done to provide Java flow in customer service with a way to find "two business days
		// in the future"
		$two_days_future = strtotime("+2 days");
		$two_days_future = strtotime(date('Y-m-d'));
		$this->assertTrue(strtotime($matches[1]) >= $two_days_future,
				$response["date_allowed_to_paydown"] . " was not more than two days from now");
	}

	/**
	 * Test the getLastPayment method
	 * @return void 
	 */
	public function testGetLastPayment()
	{
		$payment_date = "2009-01-01 01:02";
		$payment_date_expected = "2009-01-01T01:02:00";
		$this->ecash_api
			->expects($this->once())
			->method("Get_Last_Payment_Date")
			->will($this->returnValue($payment_date));

		$payment_amount = 10; 
		$this->ecash_api
			->expects($this->once())
			->method("Get_Last_Payment_Amount")
			->will($this->returnValue($payment_amount));

		
		$response = $this->loan_api->getLastPayment(1);
		
		$this->assertEquals($payment_date_expected, $response["date"]);
		$this->assertEquals($payment_amount, $response["amount"]);
	}

	/**
	 * Test getBalance method for an app with pending transactions
	 * @return void
	 */
	public function testGetBalanceHasPendingTx()
	{
		$current_due_date = "2009-01-01 01:01";
		$current_due_date_expected = "2009-01-01T01:01:00";
		$current_due_amount = 100;
		$next_due_date = "2009-01-02 01:02";
		$next_due_date_expected = "2009-01-02T01:02:00";
		$amount_due = 200;
		$principal_due = 150;
		$svc_chg_due = 50;
		$payoff = 500;
		
		$this->ecash_api
			->expects($this->once())
			->method("Has_Pending_Transactions")
			->will($this->returnValue(TRUE));

		$this->ecash_api
			->expects($this->once())
			->method("Get_Last_Payment_Date")
			->will($this->returnValue($current_due_date));

		$this->ecash_api
			->expects($this->once())
			->method("Get_Last_Payment_Amount")
			->will($this->returnValue($current_due_amount));

		$this->ecash_api
			->expects($this->once())
			->method("getFutureCurrentDueDate")
			->will($this->returnValue($next_due_date));

		$this->ecash_api
			->expects($this->once())
			->method("getFutureCurrentDueAmount")
			->will($this->returnValue($amount_due));

		$this->ecash_api
			->expects($this->once())
			->method("getFutureCurrentDuePrincipalAmount")
			->will($this->returnValue($principal_due));

		$this->ecash_api
			->expects($this->once())
			->method("getFutureCurrentDueServiceChargeAmount")
			->will($this->returnValue($svc_chg_due));

		$this->ecash_api
			->expects($this->once())
			->method("Get_Payoff_Amount")
			->will($this->returnValue($payoff));
		
		$this->ecash_api
			->expects($this->never())
			->method("Get_Current_Due_Principal_Amount");

		$this->ecash_api
			->expects($this->never())
			->method("Get_Current_Due_Service_Charge_Amount");
			
		$this->ecash_api
			->expects($this->never())
			->method("Get_Current_Due_Date");

		$response = $this->loan_api->getBalance(1);
		
		$this->assertEquals($current_due_date_expected, $response["current_due_date"]);
		$this->assertEquals($current_due_amount, $response["current_amount_due"]);
		$this->assertEquals($next_due_date_expected, $response["next_due_date"]);
		$this->assertEquals($amount_due, $response["amount_due"]);
		$this->assertEquals($principal_due, $response["principle_amount_due"]);
		$this->assertEquals($svc_chg_due, $response["service_charge_amount_due"]);
		$this->assertEquals($payoff, $response["payoff_amount"]);
	}
	
	/**
	 * Test getBalance method for an app with no pending transactions
	 * @return void
	 */
	public function testGetBalanceNoPendingTx()
	{
		// all "current due" stuff is null because there's no pending transaction
		$current_due_date_expected = NULL;
		$current_due_amount = NULL;
		$current_due_date = NULL;

		$next_due_date_expected = $current_due_date_expected;
		$amount_due = $current_due_amount;
		$principal_due = 150;
		$svc_chg_due = 50;
		$payoff = 500;
		
		$this->ecash_api
			->expects($this->once())
			->method("Has_Pending_Transactions")
			->will($this->returnValue(FALSE));

		$this->ecash_api
			->expects($this->once())
			->method("Get_Current_Due_Date")
			->will($this->returnValue($current_due_date));

		$this->ecash_api
			->expects($this->once())
			->method("Get_Current_Due_Amount")
			->will($this->returnValue($current_due_amount));

		$this->ecash_api
			->expects($this->never())
			->method("getFutureCurrentDueDate");

		$this->ecash_api
			->expects($this->never())
			->method("getFutureCurrentDueAmount");

		$this->ecash_api
			->expects($this->never())
			->method("getFutureCurrentDuePrincipalAmount");

		$this->ecash_api
			->expects($this->never())
			->method("getFutureCurrentDueServiceChargeAmount");

		$this->ecash_api
			->expects($this->once())
			->method("Get_Current_Due_Principal_Amount")
			->will($this->returnValue($principal_due));

		$this->ecash_api
			->expects($this->once())
			->method("Get_Current_Due_Service_Charge_Amount")
			->will($this->returnValue($svc_chg_due));

		$this->ecash_api
			->expects($this->once())
			->method("Get_Payoff_Amount")
			->will($this->returnValue($payoff));
		
		$response = $this->loan_api->getBalance(1);
		
		$this->assertEquals($current_due_date_expected, $response["current_due_date"]);
		$this->assertEquals($current_due_amount, $response["current_amount_due"]);
		$this->assertEquals($next_due_date_expected, $response["next_due_date"]);
		$this->assertEquals($amount_due, $response["amount_due"]);
		$this->assertEquals($principal_due, $response["principle_amount_due"]);
		$this->assertEquals($svc_chg_due, $response["service_charge_amount_due"]);
		$this->assertEquals($payoff, $response["payoff_amount"]);
	}
}

/**
 * Implementation class to use for unit tests
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class ECash_Service_Loan_APIImpl extends ECash_Service_Loan_API
{
	/**
	 * @var eCash_API_2
	 */
	protected $api;

	/**
	 * Constructor sets the API to be used in getEcashApi2()
	 *
	 * @param eCash_API_2 $api
	 */
	public function __construct(eCash_API_2 $api)
	{
		$this->api = $api;
	}

	/**
	 * Gets the injected API
	 *
	 * @param int $application_id
	 * @return eCash_API_2
	 */
	protected function getEcashApi2($application_id)
	{
		return $this->api;
	}
}
?>
