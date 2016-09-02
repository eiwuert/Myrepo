<?php
/**
 * Unit test for OLPEcash_CFE_Rules class.
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */

require_once('olp_lib_setup.php');

// Clients are required to have these constants declared so the test must declare them
define('ECASH_COMMON_DIR','/virtualhosts/ecash_common/');
define('LIB_DIR','/virtualhosts/lib/');

/**
 * Tests the OLPEcash_CFE_Rules class.
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class OLPECash_CFE_RulesTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Mark the test skipped if the classes we need are not declared.
	 *
	 * @return void
	 */
	public function setUp()
	{
		if (!class_exists('OLPECash_CFE_Rules') 
			|| !class_exists('DBInfo_Enterprise') 
			|| !class_exists('OLPECash_LoanType') 
		)
		{
			$this->markTestIncomplete('required classes not found.');
		}
	}
	
	/**
	 * Data provider for {@see testQualifiesForAmount}.
	 * 
	 * @return array multidimentional array of values to test with.
	 */
	public static function qualifiesProvider()
	{
		return array(
			array('ifs',0.00, FALSE, "California Payday Loan", 0, 200, 50),
			array('ifs',1500.00, FALSE, "Delaware Title Loan", 150, 200, 50),
			array('cbnk',1500.00, FALSE, "Payday Loan", 150, 200, 50),
			array('cbnk',1500.00, TRUE, "Payday Loan", 150, 250, 50),
			array('clk',1500.00, TRUE, "Payday Loan", 150, 250, 50),
		);
	}
	
	/**
	 * Gets rule set object for mocking getRuleSet
	 *
	 * @param string $loan_type_name Name of the loan.  Required for the Agean calculator to determine in the loan is title loan
	 * @return stdClass
	 */
	public static function getRuleSet($loan_type_name)
	{
		$rule_set = new stdClass(); 
		$rule_set->loan_type_name = $loan_type_name;
		$rule_set->business_rules = 
			array(
				"arrangements_met_discount" => "10",
				"automated_email" => "Off",
				"bankruptcy_notified" => "30",
				"cancelation_delay" => "3",
				"debit_frequency" => array(
					"weekly" => "every other pay period",
					"bi_weekly" => "every pay period",
					"twice_monthly" => "every pay period",
					"monthly" => "every pay period"
				),
				"failed_pmnt_next_attempt_date" => array(
					1 => "immediate",
					2 => "next pay day"
				),
				"grace_period" => "10",
				"gp_before_next_payment" => "7",
				"max_ach_fee_chrg_per_loan" => "1",
				"max_num_arr_payments" => array(
					100 => "2",
					200 => "4",
					300 => "4",
					400 => "4",
					500 => "5",
					600 => "7",
				),
				"max_num_arr_payment_failed" => array(
					100 => "2",
					200 => "4",
					300 => "4",
					400 => "4",
					500 => "5",
					600 => "7",
				),
				"max_contact_attempts" => "6",
				"max_svc_charge_failures" => "2",
				"minimum_loan_amount" => array(
					'min_react' => "250",
					'min_non_react' => '200'
				),
				"loan_cap" => array(
					0 => "150",
					1 => "200",
					2 => "250",
					3 => "300",
					4 => "350",
					5 => "400"
				),
				"max_react_loan_amount" => array(
					800 => "300",
					1200 => "400",
					1700 => "500",
					2000 => "500",
				),
				"max_svc_charge_only_pmts" => "4",
				"new_loan_amount" => array(
					1200 => "150",
					1700 => "200",
					2000 => "250",
					50000 => "300"
				),
				"past_due_status" => "1",
				"pending_period" => array(
					"payment_fee_ach_fail" => "3",
					"credit_card_fees" => "1",
					"credit_card_princ" => "1",
					"moneygram_fees" => "2",
					"moneygram_princ" => "2",
					"money_order_fees" => "13",
					"money_order_princ" => "13",
					"quickcheck" => "60",
					"assess_fee_ach_fail" => "0",
					"writeoff_fee_ach_fail" => "0",
					"bad_data_payment_debt_fee" => "3",
					"bad_data_payment_debt_pri" => "3",
					"cancel_principal" => "3",
					"full_balance" => "3",
					"h_fatal_cashline_return" => "1",
					"h_nfatal_cashline_return" => "1",
					"loan_disbursement" => "3",
					"paydown" => "3",
					"payment_arranged_fees" => "3",
					"payment_arranged_princ" => "3",
					"payment_service_chg" => "3",
					"payout_fees" => "3",
					"payout_principal" => "3",
					"personal_check_fees" => "3",
					"personal_check_princ" => "3",
					"refund_3rd_party_fees" => "3",
					"refund_3rd_party_princ" => "3",
					"refund_fees" => "3",
					"refund_princ" => "3",
					"repayment_principal" => "3",
				),
				"principal_payment_amount" => "50",
				"loan_amount_increment" => "50",
				"react_amount_increase" => "50",
				"return_transaction_fee" => "30",
				"svc_charge_percentage" => "30",
				"loan_percentage" => "25",
			);
		return $rule_set;
	}
	
	/**
	 * Gets an array user rules for Agean title loans
	 *
	 * @return array
	 */
	public static function getUserRulesArray()
	{
		//Build user rules array of auto_loan data
		$user_rules = array(
			'vehicle_make' => 'mazda',
			'vehicle_vin' => '01237101231231',
			'vehicle_year' => '2005',
			'vehicle_model' => 'tribute',
		// ?? I dunno what "vehicle_type" must include
			'vehicle_type' => '2cylfwd',
		// again, whatever.
			'vehicle_series' => 'le');
		return $user_rules;
	}
	
	/**
	 * Tests the basic functionality of OLPEcash_CFE_Rules class.
	 *
	 * @param string $property_short Enterprise property_short
	 * @param float $monthly_net the monthly net amount to use in calculations 
	 * @param bool $is_react Is app for react?
	 * @param string $loan_type_name Loan Type Name used by Agean calculator
	 * @param double $max_fund Maxmimum funcd amount expected
	 * @param double $min_fund Minimum fund amount expected
	 * @param double $inc_fund Fund increment amount expected
	 * 
	 * @dataProvider qualifiesProvider 
	 * 
	 * @return void
	 */
	public function testQualifiesForAmount(
						$property_short,
						$monthly_net,
						$is_react,
						$loan_type_name,
						$max_fund,
						$min_fund,
						$inc_fund
					)
	{
			
		// mock the rule to run
		$rule = $this->getMock(
			'OLPEcash_CFE_Rules',
			array('getRuleSet'),
			array($property_short,'LIVE',FALSE)
		);
		
		
		$rule->expects($this->any())
			->method('getRuleSet')
			->will($this->returnValue(self::getRuleSet($loan_type_name)));

		// run tests
		$this->assertEquals(
			$max_fund,
			$rule->getMaxFundAmount($monthly_net,$is_react));
		$this->assertEquals(
			$min_fund,
			$rule->getMinFundAmount($is_react));
		$this->assertEquals(
			$inc_fund,
			$rule->getFundAmountIncrement());
	}
}
?>
