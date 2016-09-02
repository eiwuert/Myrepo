<?php
require_once 'qualify.2.php';
require_once 'pay_date_calc.3.php';

/**
 * Tests the VendorAPI_LegacyQualify class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class VendorAPI_LegacyQualifyTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests that the qualifyPerson() function calls what's needed as well as converts data as it should.
	 *
	 * @return void
	 */
	public function testQualifyPerson()
	{		
		$data = array(
			'paydate_model' => 'dw',
			'income_direct_deposit' => TRUE,
			'day_of_week' => 'mon',
			'day_of_month_1' => '',
			'day_of_month_2' => '',
			'week_1' => '',
			'week_2' => '',
			'income_frequency' => 'weekly',
			'last_paydate' => '2009-01-29'
		);
		
		$paydate_info = new stdClass();
		$paydate_info->paydate_model = $data['paydate_model'];
		$paydate_info->income_frequency = $data['income_frequency'];
		$paydate_info->income_direct_deposit = $data['income_direct_deposit'];
		$paydate_info->last_paydate = $data['last_paydate'];
		$paydate_info->day_of_week = $data['day_of_week'];
		$paydate_info->day_of_month_1 = $data['day_of_month_1'];
		$paydate_info->day_of_month_2 = $data['day_of_month_2'];
		$paydate_info->week_1 = $data['week_1'];
		$paydate_info->week_2 = $data['week_2'];
		
		$qualify2 = $this->getMock('Qualify_2', array(), array(), '', FALSE);
		$qualify2->expects($this->once())
			->method('Qualify_Person');
		
		$pay_date_calc = $this->createPayDateCalcMock($this->once());

		$qualify = new VendorAPI_LegacyQualify($qualify2, $pay_date_calc);
		$qualify->qualifyApplication($data);
	}
	
	/**
	 * Tests that we call the Calculate_Loan_Amount function on Qualify_2 if it's not a react.
	 * 
	 * @return void
	 */
	public function testGetLoanAmountNonReact()
	{
		$data = array('income_monthly' => 1500, 'income_direct_deposit' => TRUE);
		
		$pay_date_calc = $this->createPayDateCalcMock();
		$qualify2 = $this->getMock('Qualify_2', array(), array(), '', FALSE);
		$qualify2->expects($this->once())
			->method('Calculate_Loan_Amount')
			->with($data['income_monthly'], $data['income_direct_deposit']);
		$qualify2->expects($this->never())
			->method('Calculate_React_Loan_Amount');
		
		$qualify = $this->getMock(
			'VendorAPI_LegacyQualify',
			array('isReact'),
			array($qualify2, $pay_date_calc)
		);
		$qualify->expects($this->any())
			->method('isReact')
			->will($this->returnValue(FALSE));
			
		$qualify->qualifyApplication($data);
	}
	
	/**
	 * Tests that we call the Calculate_React_Loan_Amount function on Qualify_2 if it is a react.
	 * 
	 * @return void
	 */
	public function testGetLoanAmountReact()
	{
		$data = array(
			'income_monthly' => 1500,
			'income_direct_deposit' => TRUE,
			'react_application_id' => 123456,
			'income_frequency' => 'weekly'
		);
		
		$pay_date_calc = $this->createPayDateCalcMock();
		$qualify2 = $this->getMock('Qualify_2', array(), array(), '', FALSE);
		$qualify2->expects($this->once())
			->method('Calculate_React_Loan_Amount')
			->with($data['income_monthly'], $data['income_direct_deposit']);
		$qualify2->expects($this->never())
			->method('Calculate_Loan_Amount');
		
		$qualify = $this->getMock(
			'VendorAPI_LegacyQualify',
			array('isReact'),
			array($qualify2, $pay_date_calc)
		);
		$qualify->expects($this->any())
			->method('isReact')
			->will($this->returnValue(TRUE));
			
		$qualify->qualifyApplication($data);
	}
	
	/**
	 * Data provider for testIsReact().
	 *
	 * @return array
	 */
	public static function isReactDataProvider()
	{
		return array(
			array(TRUE, TRUE),
			array(FALSE, FALSE)
		);
	}
	
	/**
	 * Tests the isReact function.
	 *
	 * @dataProvider isReactDataProvider
	 * @param bool $react
	 * @param bool $expected
	 * @return void
	 */
	public function testIsReact($react, $expected)
	{
		$data = array(
			'is_react' => $react
		);
		
		$paydate_info = new stdClass();
		$paydate_info->paydate_model = $data['paydate_model'];
		$paydate_info->income_direct_deposit = $data['income_direct_deposit'];
		$paydate_info->day_of_week = $data['day_of_week'];
		$paydate_info->day_of_month_1 = $data['day_of_month_1'];
		$paydate_info->day_of_month_2 = $data['day_of_month_2'];
		$paydate_info->week_1 = $data['week_1'];
		$paydate_info->week_2 = $data['week_2'];
		$paydate_info->last_paydate = $data['last_paydate'];
		
		$qualify2 = $this->getMock('Qualify_2', array(), array(), '', FALSE);
		$qualify2->expects($this->once())
			->method('Qualify_Person')
			->with(
				$this->anything(),
				$this->anything(),
				$this->anything(),
				$this->anything(),
				$this->anything(),
				$this->anything(),
				$expected
			);
		
		$pay_date_calc = $this->createPayDateCalcMock();
		
		$qualify = new VendorAPI_LegacyQualify($qualify2, $pay_date_calc);
		$qualify->qualifyApplication($data);
	}
	
	/**
	 * Data provider for testEcashIsReact().
	 *
	 * @return array
	 */
	public static function isEcashReactDataProvider()
	{
		return array(
			array('ecashapp_react', TRUE),
			array('online_confirmation', FALSE)
		);
	}
	
	/**
	 * Tests the isEcashReact function.
	 *
	 * @dataProvider isEcashReactDataProvider
	 * @param bool $react
	 * @return void
	 */
	public function testEcashIsReact($react, $expected)
	{
		$data = array(
			'olp_process' => $react
		);
		
		$paydate_info = new stdClass();
		$paydate_info->paydate_model = $data['paydate_model'];
		$paydate_info->income_direct_deposit = $data['income_direct_deposit'];
		$paydate_info->day_of_week = $data['day_of_week'];
		$paydate_info->day_of_month_1 = $data['day_of_month_1'];
		$paydate_info->day_of_month_2 = $data['day_of_month_2'];
		$paydate_info->week_1 = $data['week_1'];
		$paydate_info->week_2 = $data['week_2'];
		$paydate_info->last_paydate = $data['last_paydate'];
		
		$qualify2 = $this->getMock('Qualify_2', array(), array(), '', FALSE);
		$qualify2->expects($this->once())
			->method('Qualify_Person')
			->with(
				$this->anything(),
				$this->anything(),
				$this->anything(),
				$this->anything(),
				$this->anything(),
				$this->anything(),
				$expected
			);
		
		$pay_date_calc = $this->createPayDateCalcMock();
		
		$qualify = new VendorAPI_LegacyQualify($qualify2, $pay_date_calc);
		$qualify->qualifyApplication($data);
	}

	private function createPayDateCalcMock($constraint = NULL)
	{
		if (empty($constraint))
		{
			$constraint = $this->any();
		}
		$data = array('2010-01-01', '2010-01-15', '2010-02-01', '2010-02-15');

		$pdc = $this->getMock('Pay_Date_Calc_3');
		$pdc->expects($constraint)
			->method('Calculate_Pay_Dates')
			->will($this->returnValue($data));

		return $pdc;
	}
}
