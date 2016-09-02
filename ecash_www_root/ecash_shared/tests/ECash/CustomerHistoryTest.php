<?php

// PHPUnit apparently still includes the bootstrap file at different times
// depending upon whether it's specified on the command line (includes it
// AFTER executing the dataProvider, which breaks this test) or the
// configuration file... this allows us to run this test individually
require_once 'bootstrap.php';

/** Tests the customer history.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class ECash_CustomerHistoryTest extends PHPUnit_Framework_TestCase
{
	/** Populates CustomerHistory.
	 *
	 * @param array $loans
	 * @return ECash_CustomerHistory
	 */
	protected function getCustomerHistory(array $loans, array $expired_loans = NULL)
	{
		$history = new ECash_CustomerHistory();
		
		foreach ($loans AS $loan)
		{
			if (!isset($loan[0], $loan[1], $loan[2]))
			{
				throw new InvalidArgumentException("Loan to insert into customer history is invalid. Needs to be in format: Company | Status | Application ID [ | Date ]\nPassed in loan:\n" . var_export($loan, TRUE));
			}
			
			// Company | Status | Application ID | Date
			$history->addLoan(
				$loan[0], $loan[1], $loan[2], isset($loan[3]) ? $loan[3] : NULL
			);
		}
		
		if (is_array($expired_loans))
		{
			foreach ($expired_loans AS $loan)
			{
				if (!isset($loan[0], $loan[1], $loan[2], $loan[3]))
				{
					throw new InvalidArgumentException('Loan to set expirable is invalid: ' . var_export($loan, TRUE));
				}
				
				// Company | Application ID | Provider | Status
				$history->setExpirable(
					$loan[0], $loan[1], $loan[2], $loan[3]
				);
			}
		}
		
		return $history;
	}
	
	/** Provider for testGetCompanyHistory().
	 *
	 * @return array
	 */
	public static function dataProviderGetCompanyHistory()
	{
		$loans = array(
			array('test1', ECash_CustomerHistory::STATUS_BAD, 1),
			array('test1', ECash_CustomerHistory::STATUS_BAD, 2),
			array('test2', ECash_CustomerHistory::STATUS_BAD, 3),
			array('test2', ECash_CustomerHistory::STATUS_BAD, 4),
			array('test3', ECash_CustomerHistory::STATUS_BAD, 4),
		);
		
		$data = array(
			array($loans, 'test1', 2, 'Normal loan insertion process.'),
			array($loans, 'test2', 1, 'AppID 4 is overwritten to test3 from test2.'),
			array($loans, 'test3', 1, 'AppID 4 is set to test3.'),
			array($loans, 'testnone', 0, 'Empty company.'),
		);
		
		return $data;
	}
	
	/** Tests getCompanyHistory().
	 *
	 * @dataProvider dataProviderGetCompanyHistory
	 *
	 * @param array $loans
	 * @param string $company
	 * @param int $expected_loans_found
	 * @param string $message
	 * @return void
	 */
	public function testGetCompanyHistory(array $loans, $company, $expected_loans_found, $message)
	{
		$history = $this->getCustomerHistory($loans);
		
		$company_history = $history->getCompanyHistory($company);
		$loans_found = $company_history->getCountBad();
		
		$this->assertEquals($expected_loans_found, $loans_found, $message);
	}
	
	/** Provider for testAddLoan().
	 *
	 * @return array
	 */
	public static function dataProviderAddLoan()
	{
		return array(
			array(
				array(
					array('test1', ECash_CustomerHistory::STATUS_BAD, 1),
					array('test1', ECash_CustomerHistory::STATUS_BAD, 1),
				),
				1,
				'AppID 1 is inserted twice for the same company.',
			),
		);
	}
	
	/** Tests addLoan().
	 *
	 * @dataProvider dataProviderAddLoan
	 *
	 * @param array $loans
	 * @param int $expected_loans_found
	 * @param string $message
	 * @return void
	 */
	public function testAddLoan(array $loans, $expected_loans_found, $message)
	{
		$history = $this->getCustomerHistory($loans);
		$loans_found = $history->getCountBad();
		
		$this->assertEquals($expected_loans_found, $loans_found, $message);
	}
	
	/** Provider for testExpirable().
	 *
	 * @return array
	 */
	public static function dataProviderExpirable()
	{
		$loans = array(
			array('test1', 1, 'phpunit', ECash_CustomerHistory::STATUS_BAD),
			array('test1', 2, 'phpunit', ECash_CustomerHistory::STATUS_BAD),
			array('test2', 2, 'phpunit', ECash_CustomerHistory::STATUS_BAD),
		);
		
		$data = array(
			array($loans, 1, TRUE),
			array($loans, 2, TRUE),
			array($loans, 3, FALSE),
		);
		
		return $data;
	}
	
	/** Tests setExpirable() and getExpirable().
	 *
	 * @dataProvider dataProviderExpirable
	 *
	 * @param array $expired_loans
	 * @param int $application_id
	 * @param bool $expected_result
	 * @return void
	 */
	public function testExpirable(array $expired_loans, $application_id, $expected_result)
	{
		$history = $this->getCustomerHistory(array(), $expired_loans);
		$result = $history->getExpirable($application_id);
		
		$this->assertEquals($expected_result, $result);
	}
	
	/** Provider for testDoNotLoan().
	 *
	 * @return array
	 */
	public static function dataProviderGetDoNotLoanCompanies()
	{
		return array(
			array(
				array(),
				array(),
				array(),
				'No DNL, no DNLO, nothing returned.',
			),
			array(
				array('test1'),
				array(),
				array('test1' => 'test1'),
				'test1 is the only DNL company.',
			),
			array(
				array('test1'),
				array('test2'),
				array('test1' => 'test1'),
				'test1 is DNL, while test2 is DNLO even without being in the DNL list.',
			),
			array(
				array('test1','test2'),
				array('test2'),
				array('test1' => 'test1'),
				'test1 and test2 are DNL, but test2 is in the DNLO list.',
			),
			array(
				array('test1','test2'),
				array('test2','test1'),
				array(),
				'test1 and test2 are DNL, and both are in the DNLO list.',
			),
		);
	}
	
	/** Tests getDoNotLoanCompanies().
	 *
	 * @dataProvider dataProviderGetDoNotLoanCompanies
	 *
	 * @param array $dnl_companies
	 * @param array $dnlo_companies
	 * @param array $expected_companies
	 * @param string $message
	 * @return void
	 */
	public function testDoNotLoan(array $dnl_companies, array $dnlo_companies, array $expected_companies, $message)
	{
		$history = $this->getCustomerHistory(array());
		
		foreach ($dnl_companies AS $company)
		{
			$history->setDoNotLoan($company);
		}
		
		foreach ($dnlo_companies AS $company)
		{
			$history->setDoNotLoanOverride($company);
		}
		
		$companies = $history->getDoNotLoanCompanies();
		
		$this->assertEquals($expected_companies, $companies, $message);
	}
	
	/** Provider for testGetDoNotLoan() and testGetDoNotLoanOverride().
	 *
	 * @return array
	 */
	public static function dataProviderGetDoNotLoan()
	{
		return array(
			array(
				array(),
				array(),
				'Nothing created, noting returned.',
			),
			array(
				array('test1'),
				array('test1' => 'test1'),
				'test1 is in the list.',
			),
			array(
				array('test1', 'test2'),
				array('test1' => 'test1', 'test2' => 'test2'),
				'test1 and test2 are in the list.',
			),
			array(
				array('test1', 'test1'),
				array('test1' => 'test1'),
				'Cannot insert same company twice.',
			),
		);
	}
	
	/** Tests getDoNotLoan().
	 *
	 * @dataProvider dataProviderGetDoNotLoan
	 *
	 * @param array $dnl_companies
	 * @param array $expected_result
	 * @param string $message
	 * @return void
	 */
	public function testGetDoNotLoan(array $dnl_companies, array $expected_result, $message)
	{
		$history = $this->getCustomerHistory(array());
		
		foreach ($dnl_companies AS $company)
		{
			$history->setDoNotLoan($company);
		}
		
		$result = $history->getDoNotLoan();
		
		$this->assertEquals($expected_result, $result, $message);
		
		// And test when we do getCompanyHistory()
		$history2 = $history->getCompanyHistory('test1');
		$result = $history2->getDoNotLoan();
		$this->assertEquals($expected_result, $result, $message);
	}
	
	/** Tests getDoNotLoan().
	 *
	 * @dataProvider dataProviderGetDoNotLoan
	 *
	 * @param array $dnlo_companies
	 * @param array $expected_result
	 * @param string $message
	 * @return void
	 */
	public function testGetDoNotLoanOverride(array $dnlo_companies, array $expected_result, $message)
	{
		$history = $this->getCustomerHistory(array());
		
		foreach ($dnlo_companies AS $company)
		{
			$history->setDoNotLoanOverride($company);
		}
		
		$result = $history->getDoNotLoanOverride();
		
		$this->assertEquals($expected_result, $result, $message);
		
		// And test when we do getCompanyHistory()
		$history2 = $history->getCompanyHistory('test1');
		$result = $history2->getDoNotLoanOverride();
		$this->assertEquals($expected_result, $result, $message);
	}
	
	/** Provider for testResults().
	 *
	 * @return array
	 */
	public static function dataProviderResults()
	{
		return array(
			array(
				array(
					'SSN' => 'PASSED',
					'DOB' => 'FAILED',
					'JOHN' => 'SMITH',
				),
			),
			array(array()),
		);
	}
	
	/** Tests setResult() and getResults().
	 *
	 * @dataProvider dataProviderResults
	 *
	 * @param array $results
	 * @return void
	 */
	public function testResults(array $results)
	{
		$history = $this->getCustomerHistory(array());
		
		foreach ($results as $name => $value)
		{
			$history->setResult($name, $value);
		}
		
		$output_results = $history->getResults();
		
		$this->assertEquals($results, $output_results);
		
		// And test when we do getCompanyHistory()
		$history2 = $history->getCompanyHistory('test1');
		$output_results = $history2->getResults();
		$this->assertEquals($results, $output_results);
	}
	
	/** Provider for testGetNewestLoanDateInStatus().
	 *
	 * @return array
	 */
	public static function dataProviderGetNewestLoanDateInStatus()
	{
		$loans = array(
			array('test1', ECash_CustomerHistory::STATUS_BAD, 1, 1000),
			array('test1', ECash_CustomerHistory::STATUS_BAD, 2, 1100),
			array('test2', ECash_CustomerHistory::STATUS_BAD, 3, 1200),
			array('test1', ECash_CustomerHistory::STATUS_DENIED, 4, 1300),
			array('test2', ECash_CustomerHistory::STATUS_PAID, 5, 1400),
		);
		
		return array(
			array($loans, ECash_CustomerHistory::STATUS_BAD, 1200, 1100, 'Bad reduced away test2 larger value.'),
			array($loans, ECash_CustomerHistory::STATUS_DENIED, 1300, 1300, 'Denied is static through reduce.'),
			array($loans, ECash_CustomerHistory::STATUS_ACTIVE, NULL, NULL, 'There are no active loans.'),
			array($loans, ECash_CustomerHistory::STATUS_PAID, 1400, NULL, 'Paid is reduced away.'),
		);
	}
	
	/** Tests getNewestLoanDateInStatus().
	 *
	 * @dataProvider dataProviderGetNewestLoanDateInStatus
	 *
	 * @param array $loans
	 * @param string $loan_status
	 * @param int $expected_last_date
	 * @param int $expected_last_date_reduced
	 * @param string $message
	 * @return void
	 */
	public function testGetNewestLoanDateInStatus(array $loans, $loan_status, $expected_last_date, $expected_last_date_reduced, $message)
	{
		$history = $this->getCustomerHistory($loans);
		$last_date = $history->getNewestLoanDateInStatus($loan_status);
		$this->assertEquals($expected_last_date, $last_date, $loan_status);
		
		// And reduced to test1
		$history2 = $history->getCompanyHistory('test1');
		$last_date = $history2->getNewestLoanDateInStatus($loan_status);
		$this->assertEquals($expected_last_date_reduced, $last_date, $message);
	}
	
	/** Provider for testGetCounts().
	 *
	 * @return array
	 */
	public static function dataProviderGetCounts()
	{
		$loans = array(
			array('test1', ECash_CustomerHistory::STATUS_BAD, 1, 1000),
			array('test1', ECash_CustomerHistory::STATUS_BAD, 2, 1100),
			array('test2', ECash_CustomerHistory::STATUS_BAD, 3, 1200),
			array('test1', ECash_CustomerHistory::STATUS_DENIED, 4, 1300),
			array('test2', ECash_CustomerHistory::STATUS_PAID, 5, 1400),
		);
		
		return array(
			array(
				array(),
				NULL,
				0, 0, 0, 0, 0, 0, 0, 0,
				'No loans.',
			),
			array(
				array(
					array('test1', ECash_CustomerHistory::STATUS_BAD, 1),
				),
				NULL,
				0, 1, 0, 0, 0, 0, 0, 0,
				'One bad loan.',
			),
			array(
				array(
					array('test1', ECash_CustomerHistory::STATUS_DENIED, 1),
					array('test1', ECash_CustomerHistory::STATUS_BAD, 2),
					array('test1', ECash_CustomerHistory::STATUS_PENDING, 3),
					array('test1', ECash_CustomerHistory::STATUS_ACTIVE, 4),
					array('test1', ECash_CustomerHistory::STATUS_PAID, 5),
					array('test1', ECash_CustomerHistory::STATUS_DISAGREED, 6),
					array('test1', ECash_CustomerHistory::STATUS_CONFIRMED_DISAGREED, 7),
					array('test1', ECash_CustomerHistory::STATUS_WITHDRAWN, 8),
				),
				NULL,
				1, 1, 1, 1, 1, 1, 1, 0,
				'One of all loan types.',
			),
			array(
				array(
					array('test1', ECash_CustomerHistory::STATUS_DENIED, 1, 1000),
					array('test1', ECash_CustomerHistory::STATUS_BAD, 2, 1100),
					array('test1', ECash_CustomerHistory::STATUS_PENDING, 3, 1200),
					array('test1', ECash_CustomerHistory::STATUS_ACTIVE, 4, 1300),
					array('test1', ECash_CustomerHistory::STATUS_PAID, 5, 1400),
					array('test1', ECash_CustomerHistory::STATUS_DISAGREED, 6, 1500),
					array('test1', ECash_CustomerHistory::STATUS_CONFIRMED_DISAGREED, 7, 1600),
					array('test1', ECash_CustomerHistory::STATUS_WITHDRAWN, 8, 1700),
				),
				1400,
				0, 0, 0, 0, 1, 1, 1, 0,
				'One of all loan types, but filter away loans older than 1400.',
			),
			array(
				array(
					array('test1', ECash_CustomerHistory::STATUS_DENIED, 1, 1000),
					array('test1', ECash_CustomerHistory::STATUS_BAD, 2, 1100),
					array('test1', ECash_CustomerHistory::STATUS_PENDING, 3, 1200),
					array('test1', ECash_CustomerHistory::STATUS_ACTIVE, 4, 1300),
					array('test1', ECash_CustomerHistory::STATUS_PAID, 5, 1400),
					array('test1', ECash_CustomerHistory::STATUS_DISAGREED, 6, 1500),
					array('test1', ECash_CustomerHistory::STATUS_CONFIRMED_DISAGREED, 7, 1600),
					array('test1', ECash_CustomerHistory::STATUS_WITHDRAWN, 8, 1700),
				),
				1401,
				0, 0, 0, 0, 0, 1, 1, 0,
				'One of all loan types, but filter away loans older than 1401.',
			),
			array(
				array(
					array('test1', ECash_CustomerHistory::STATUS_DENIED, 1, 1000),
					array('test1', ECash_CustomerHistory::STATUS_DENIED, 2, 1100),
					array('test1', ECash_CustomerHistory::STATUS_DENIED, 3, 1200),
					array('test1', ECash_CustomerHistory::STATUS_DENIED, 4, 1300),
					array('test1', ECash_CustomerHistory::STATUS_DENIED, 5, 1400),
					array('test1', ECash_CustomerHistory::STATUS_DENIED, 6, 1500),
					array('test1', ECash_CustomerHistory::STATUS_DENIED, 7, 1600),
					array('test1', ECash_CustomerHistory::STATUS_DENIED, 8, 1700),
				),
				NULL,
				8, 0, 0, 0, 0, 0, 0, 0,
				'Lots of denied loans.',
			),
			array(
				array(
					array('test1', ECash_CustomerHistory::STATUS_DENIED, 1, 1000),
					array('test1', ECash_CustomerHistory::STATUS_DENIED, 2, 1100),
					array('test1', ECash_CustomerHistory::STATUS_DENIED, 3, 1200),
					array('test1', ECash_CustomerHistory::STATUS_DENIED, 4, 1300),
					array('test1', ECash_CustomerHistory::STATUS_DENIED, 5, 1400),
					array('test1', ECash_CustomerHistory::STATUS_DENIED, 6, 1500),
					array('test1', ECash_CustomerHistory::STATUS_DENIED, 7, 1600),
					array('test1', ECash_CustomerHistory::STATUS_DENIED, 8, 1700),
				),
				1400,
				4, 0, 0, 0, 0, 0, 0, 0,
				'Lots of denied loans, but filtered away older then 1400.',
			),
			array(
				array(
					array('test1', ECash_CustomerHistory::STATUS_DENIED, 1, 1000),
					array('test1', ECash_CustomerHistory::STATUS_DENIED, 2, 1100),
					array('test1', ECash_CustomerHistory::STATUS_DENIED, 3, 1200),
					array('test1', ECash_CustomerHistory::STATUS_DENIED, 4, 1300),
					array('test1', ECash_CustomerHistory::STATUS_DENIED, 5, 1400),
					array('test1', ECash_CustomerHistory::STATUS_DENIED, 6, 1500),
					array('test1', ECash_CustomerHistory::STATUS_DENIED, 7, 1600),
					array('test1', ECash_CustomerHistory::STATUS_DENIED, 8, 1700),
				),
				1401,
				3, 0, 0, 0, 0, 0, 0, 0,
				'Lots of denied loans, but filtered away older then 1401.',
			),
		);
	}
	
	/** Tests getCount***().
	 *
	 * @dataProvider dataProviderGetCounts
	 *
	 * @param array $loans
	 * @return void
	 */
	public function testGetCounts(array $loans, $filter_date, $expected_denied, $expected_bad, $expected_pending, $expected_active, $expected_paid, $expected_disagreed, $expected_confirmeddisagreed, $expected_settled, $message)
	{
		$history = $this->getCustomerHistory($loans);
		
		$this->assertEquals($expected_denied, $history->getCountDenied($filter_date), "Denied: {$message}");
		$this->assertEquals($expected_bad, $history->getCountBad($filter_date), "Bad: {$message}");
		$this->assertEquals($expected_pending, $history->getCountPending($filter_date), "Pending: {$message}");
		$this->assertEquals($expected_active, $history->getCountActive($filter_date), "Active: {$message}");
		$this->assertEquals($expected_paid, $history->getCountPaid($filter_date), "Paid: {$message}");
		$this->assertEquals($expected_disagreed, $history->getCountDisagreed($filter_date), "Disagreed: {$message}");
		$this->assertEquals($expected_confirmeddisagreed, $history->getCountConfirmedDisagreed($filter_date), "Confirmed Disagreed: {$message}");
		$this->assertEquals($expected_settled, $history->getCountSettled($filter_date), "Settled: {$message}");
	}
	
	/** Provider for testGetActiveCompanies().
	 *
	 * @return array
	 */
	public static function dataProviderGetActiveCompanies()
	{
		return array(
			array(
				array(),
				array(),
				array(),
				array(),
				'No loans, no companies.',
			),
			array(
				array(
					array('test1', ECash_CustomerHistory::STATUS_BAD, 1),
					array('test1', ECash_CustomerHistory::STATUS_PENDING, 2),
				),
				array(),
				array('test1' => 'test1'),
				array(),
				'Only a pending company.',
			),
			array(
				array(
					array('test1', ECash_CustomerHistory::STATUS_ACTIVE, 1),
					array('test1', ECash_CustomerHistory::STATUS_ACTIVE, 2),
				),
				array('test1' => 'test1'),
				array(),
				array(),
				'test1 has only active loans.',
			),
			array(
				array(
					array('test1', ECash_CustomerHistory::STATUS_PAID, 1),
					array('test1', ECash_CustomerHistory::STATUS_ACTIVE, 1),
				),
				array('test1' => 'test1'),
				array(),
				array('test1' => 'test1'), /// FIXME: This is most likely a bug, as we overwrote the status for an application_id
				'AppID 1 is overwritten to be active.',
			),
			array(
				array(
					array('test1', ECash_CustomerHistory::STATUS_ACTIVE, 1),
					array('test1', ECash_CustomerHistory::STATUS_PENDING, 1),
				),
				array('test1' => 'test1'), /// FIXME: This is most likely a bug, as we overwrote the status for an application_id
				array('test1' => 'test1'),
				array(),
				'AppID 1 is overwritten to be pending.',
			),
			array(
				array(
					array('test1', ECash_CustomerHistory::STATUS_PENDING, 1),
					array('test2', ECash_CustomerHistory::STATUS_PENDING, 2),
				),
				array(),
				array('test1' => 'test1', 'test2' => 'test2'),
				array(),
				'Two companies with pending applications.',
			),
		);
	}
	
	/** Tests getActiveCompanies().
	 *
	 * @dataProvider dataProviderGetActiveCompanies
	 *
	 * @param array $loans
	 * @param int $expected_loans_found
	 * @param string $message
	 * @return void
	 */
	public function testGetActiveCompanies(array $loans, $expected_active_companies, $expected_pending_companies, $expected_paid_companies, $message)
	{
		$history = $this->getCustomerHistory($loans);
		
		$this->assertEquals($expected_active_companies, $history->getActiveCompanies(), "Active: {$message}");
		$this->assertEquals($expected_pending_companies, $history->getPendingCompanies(), "Active: {$message}");
		$this->assertEquals($expected_paid_companies, $history->getPaidCompanies(), "Active: {$message}");
	}
	
	/** Provider for testGetActiveCompaniesReduced().
	 *
	 * @return array
	 */
	public static function dataProviderGetActiveCompaniesReduced()
	{
		return array(
			array(
				array(),
				array(),
				array(),
				array(),
				'No loans, no companies.',
			),
			array(
				array(
					array('test1', ECash_CustomerHistory::STATUS_BAD, 1),
					array('test1', ECash_CustomerHistory::STATUS_PENDING, 2),
				),
				array(),
				array('test1' => 'test1'),
				array(),
				'Only a pending company.',
			),
			array(
				array(
					array('test1', ECash_CustomerHistory::STATUS_ACTIVE, 1),
					array('test1', ECash_CustomerHistory::STATUS_ACTIVE, 2),
				),
				array('test1' => 'test1'),
				array(),
				array(),
				'test1 has only active loans.',
			),
			array(
				array(
					array('test1', ECash_CustomerHistory::STATUS_PENDING, 1),
					array('test2', ECash_CustomerHistory::STATUS_PENDING, 2),
				),
				array(),
				array('test1' => 'test1'),
				array(),
				'Two companies with pending applications.',
			),
		);
	}
	
	/** Tests getActiveCompanies() combined with getCompanyHistory().
	 *
	 * @dataProvider dataProviderGetActiveCompaniesReduced
	 *
	 * @param array $loans
	 * @param int $expected_loans_found
	 * @param string $message
	 * @return void
	 */
	public function testGetActiveCompaniesReduced(array $loans, $expected_active_companies, $expected_pending_companies, $expected_paid_companies, $message)
	{
		$history = $this->getCustomerHistory($loans);
		$history2 = $history->getCompanyHistory('test1');
		
		$this->assertEquals($expected_active_companies, $history2->getActiveCompanies(), "Active: {$message}");
		$this->assertEquals($expected_pending_companies, $history2->getPendingCompanies(), "Active: {$message}");
		$this->assertEquals($expected_paid_companies, $history2->getPaidCompanies(), "Active: {$message}");
	}
	
	/** Provider for testGetIsReact().
	 *
	 * @return array
	 */
	public static function dataProviderGetIsReact()
	{
		return array(
			array(
				array(),
				'test1',
				FALSE,
				FALSE,
				'No loans.',
			),
			array(
				array(
					array('test1', ECash_CustomerHistory::STATUS_PAID, 1),
				),
				'test1',
				TRUE,
				TRUE,
				'test1 has a paid application.',
			),
			array(
				array(
					array('test1', ECash_CustomerHistory::STATUS_PAID, 1),
					array('test1', ECash_CustomerHistory::STATUS_BAD, 2),
				),
				'test1',
				TRUE,
				TRUE,
				'test1 has a paid application, even though it also has a bad, is still a react.',
			),
			array(
				array(
					array('test2', ECash_CustomerHistory::STATUS_PAID, 1),
				),
				'test2',
				TRUE,
				FALSE,
				'test2 has a paid application.',
			),
			array(
				array(
					array('test1', ECash_CustomerHistory::STATUS_PAID, 1),
					array('test2', ECash_CustomerHistory::STATUS_PAID, 1),
				),
				'test2',
				TRUE,
				FALSE,
				'test2 has a paid application, which is paid in two companies.',
			),
			array(
				array(
					array('test1', ECash_CustomerHistory::STATUS_PAID, 1, 75),
					array('test2', ECash_CustomerHistory::STATUS_PAID, 1, 50),
				),
				'test2',
				FALSE,
				FALSE,
				'Company [test2] should not be considered as having a react.',
			),
			array(
				array(
					array('test1', ECash_CustomerHistory::STATUS_PAID, 1, 75),
					array('test2', ECash_CustomerHistory::STATUS_PAID, 1, 50),
				),
				'test1',
				TRUE,
				TRUE,
				'Company [test1] should be considered as having a react.',
			),
		);
	}
	
	/** Tests getIsReact().
	 *
	 * @dataProvider dataProviderGetIsReact
	 *
	 * @param array $loans
	 * @param string $company
	 * @param bool $expected_result
	 * @param bool $expected_reduced_result
	 * @param string $message
	 * @return void
	 */
	public function testGetIsReact(array $loans, $company, $expected_result, $expected_reduced_result, $message)
	{
		$history = $this->getCustomerHistory($loans);
		$result = $history->getIsReact($company);
		$this->assertEquals($expected_result, $result, $message);
		
		// And after reducing to test1
		$history2 = $history->getCompanyHistory('test1');
		$result = $history2->getIsReact($company);
		$this->assertEquals($expected_reduced_result, $result, "Reduced to test1: {$message}");
	}
	
	/** Provider for testGetIsDoNotLoan().
	 *
	 * @return array
	 */
	public static function dataProviderGetIsDoNotLoan()
	{
		return array(
			array(
				array(),
				array(),
				'test1',
				FALSE,
				FALSE,
				'No companies are in the DNL list.',
			),
			array(
				array('test1'),
				array(),
				'test1',
				TRUE,
				TRUE,
				'test1 is on the DNL list.',
			),
			array(
				array('test1', 'test2'),
				array(),
				'test1',
				TRUE,
				TRUE,
				'test1 is on the DNL list, along with test2.',
			),
			array(
				array('test1'),
				array(),
				'test2',
				FALSE,
				FALSE,
				'test2 is not on the DNL list.',
			),
			array(
				array('test1', 'test2'),
				array(),
				'test2',
				TRUE,
				TRUE, // Even through you reduce away apps, DNL stays around
				'test2 is on the DNL list, along with test1.',
			),
			array(
				array('test1'),
				array('test1'),
				'test1',
				TRUE,
				TRUE,
				'test1 is on both DNL and DNLO lists.',
			),
			array(
				array(''),
				array('test1'),
				'test1',
				FALSE,
				FALSE,
				'test1 is on the DNLO lists.',
			),
		);
	}
	
	/** Tests getIsDoNotLoan().
	 *
	 * @dataProvider dataProviderGetIsDoNotLoan
	 *
	 * @param array $dnl_companies
	 * @param array $expected_result
	 * @param string $message
	 * @return void
	 */
	public function testGetIsDoNotLoan(array $dnl_companies, array $dnlo_companies, $company, $expected_result, $expected_reduced_result, $message)
	{
		$history = $this->getCustomerHistory(array());
		
		foreach ($dnl_companies AS $dnl_company)
		{
			$history->setDoNotLoan($dnl_company);
		}
		
		foreach ($dnlo_companies AS $dnlo_company)
		{
			$history->setDoNotLoanOverride($dnlo_company);
		}
		
		$result = $history->getIsDoNotLoan($company);
		$this->assertEquals($expected_result, $result, $message);
		
		// And test when we do getCompanyHistory()
		$history2 = $history->getCompanyHistory('test1');
		$result = $history2->getIsDoNotLoan($company);
		$this->assertEquals($expected_reduced_result, $result, "Reduced: {$message}");
	}
	
	/** Provider for testGetReactID().
	 *
	 * @return array
	 */
	public static function dataProviderGetReactID()
	{
		return array(
			array(
				array(),
				'test1',
				FALSE,
				FALSE,
				'No loans.',
			),
			array(
				array(
					array('test1', ECash_CustomerHistory::STATUS_PAID, 1),
				),
				'test1',
				1,
				1,
				'test1 has a paid application.',
			),
			array(
				array(
					array('test1', ECash_CustomerHistory::STATUS_PAID, 1),
					array('test1', ECash_CustomerHistory::STATUS_BAD, 2),
				),
				'test1',
				1,
				1,
				'test1 has a paid application, even though it also has a bad, is still a react.',
			),
			array(
				array(
					array('test2', ECash_CustomerHistory::STATUS_PAID, 1),
				),
				'test2',
				1,
				FALSE,
				'test2 has a paid application.',
			),
			array(
				array(
					array('test1', ECash_CustomerHistory::STATUS_PAID, 1),
					array('test2', ECash_CustomerHistory::STATUS_PAID, 1),
				),
				'test2',
				1,
				FALSE,
				'test2 has a paid application, which is paid in two companies.',
			),
			array(
				array(
					array('test1', ECash_CustomerHistory::STATUS_PAID, 1),
					array('test1', ECash_CustomerHistory::STATUS_PAID, 2),
				),
				'test1',
				2,
				2,
				'test1 has two paid applications, but AppID 2 is larger.',
			),
			array(
				array(
					array('test1', ECash_CustomerHistory::STATUS_PAID, 2),
					array('test1', ECash_CustomerHistory::STATUS_PAID, 1),
				),
				'test1',
				2,
				2,
				'Testing order of insertion, should still find 2.',
			),
			array(
				array(
					array('test1', ECash_CustomerHistory::STATUS_PAID, 1, 75),
					array('test2', ECash_CustomerHistory::STATUS_PAID, 1, 50),
				),
				'test2',
				FALSE,
				FALSE,
				'Company [test2] should not be considered as having a react.',
			),
			array(
				array(
					array('test1', ECash_CustomerHistory::STATUS_PAID, 1, 75),
					array('test2', ECash_CustomerHistory::STATUS_PAID, 1, 50),
				),
				'test1',
				1,
				1,
				'Company [test1] should be considered as having a react.',
			),
		);
	}
	
	/** Tests getReactID().
	 *
	 * @dataProvider dataProviderGetReactID
	 *
	 * @param array $loans
	 * @param array $expected_result
	 * @param string $message
	 * @return void
	 */
	public function testGetReactID(array $loans, $company, $expected_result, $expected_reduced_result, $message)
	{
		$history = $this->getCustomerHistory($loans);
		$result = $history->getReactID($company);
		$this->assertEquals($expected_result, $result, $message);
		
		// And test when we do getCompanyHistory()
		$history2 = $history->getCompanyHistory('test1');
		$result = $history2->getReactID($company);
		$this->assertEquals($expected_reduced_result, $result, "Reduced: {$message}");
	}
	
	/** Provider for testGetExpirableApplications().
	 *
	 * @return array
	 */
	public static function dataProviderGetExpirableApplications()
	{
		return array(
			array(
				array(),
				array(),
				'Nothing expirable, nothing returned.',
			),
			array(
				array(
					array('test1', 1, 'phpunit', ECash_CustomerHistory::STATUS_BAD),
				),
				array(
					1 => array(
						array(
							'company' => 'test1',
							'application_id' => 1,
							'provider' => 'phpunit',
							'status' => 'bad',
						),
					),
				),
				'Simple one application test.',
			),
			array(
				array(
					array('test1', 1, 'phpunit', ECash_CustomerHistory::STATUS_BAD),
					array('test1', 1, 'phpunit', ECash_CustomerHistory::STATUS_BAD),
				),
				array(
					1 => array(
						array(
							'company' => 'test1',
							'application_id' => 1,
							'provider' => 'phpunit',
							'status' => ECash_CustomerHistory::STATUS_BAD,
						),
						array(
							'company' => 'test1',
							'application_id' => 1,
							'provider' => 'phpunit',
							'status' => ECash_CustomerHistory::STATUS_BAD,
						),
					),
				),
				'Same application inserted twice.',
			),
			array(
				array(
					array('test1', 1, 'phpunit', ECash_CustomerHistory::STATUS_BAD),
					array('test2', 2, 'phpunit', ECash_CustomerHistory::STATUS_PENDING),
				),
				array(
					1 => array(
						array(
							'company' => 'test1',
							'application_id' => 1,
							'provider' => 'phpunit',
							'status' => ECash_CustomerHistory::STATUS_BAD,
						),
					),
					2 => array(
						array(
							'company' => 'test2',
							'application_id' => 2,
							'provider' => 'phpunit',
							'status' => ECash_CustomerHistory::STATUS_PENDING,
						),
					),
				),
				'Two applications.',
			),
		);
	}
	
	/** Tests getExpirableApplications().
	 *
	 * @dataProvider dataProviderGetExpirableApplications
	 *
	 * @param array $expired_loans
	 * @param array $expected_result
	 * @param string $message
	 * @return void
	 */
	public function testGetExpirableApplications(array $expired_loans, $expected_result, $message)
	{
		$history = $this->getCustomerHistory(array(), $expired_loans);
		$result = $history->getExpirableApplications();
		$this->assertEquals($expected_result, $result, $message);
	}
	
	/** Provider for testToString().
	 *
	 * @return array
	 */
	public static function dataProviderToString()
	{
		return array(
			array(
				array(),
				'',
				'No loans.',
			),
			array(
				array(
					array('test1', ECash_CustomerHistory::STATUS_BAD, 1, strtotime('1968-12-24 11:00:00')),
				),
				'1 is BAD with TEST1 at 1968-12-24 11:00:00 AM.',
				'One loan.',
			),
			array(
				array(
					array('test1', ECash_CustomerHistory::STATUS_ACTIVE, 1, strtotime('2000-01-01 00:00:00')),
					array('test2', ECash_CustomerHistory::STATUS_PAID, 2, strtotime('1972-02-29 23:59:59')),
				),
				"1 is ACTIVE with TEST1 at 2000-01-01 12:00:00 AM.\n2 is PAID with TEST2 at 1972-02-29 11:59:59 PM.",
				'Two loans.',
			),
		);
	}
	
	/** Tests __toString().
	 *
	 * @dataProvider dataProviderToString
	 *
	 * @param array $loans
	 * @param int $expected_result
	 * @param string $message
	 * @return void
	 */
	public function testToString(array $loans, $expected_result, $message)
	{
		$history = $this->getCustomerHistory($loans);
		$result = (string)$history;
		$this->assertEquals($expected_result, $result, $message);
	}
	
	/** Provider for testSaveToDatabase().
	 *
	 * @return array
	 */
	public static function dataProviderSaveToDatabase()
	{
		return array(
			array(
				array(),
				1,
				'test1',
			),
		);
	}
	
	/** Tests saveToDatabase().
	 *
	 * @dataProvider dataProviderSaveToDatabase
	 *
	 * @param array $loans
	 * @param int $application_id
	 * @param string $property_short
	 * @return void
	 */
	public function testSaveToDatabase(array $loans, $application_id, $property_short)
	{
		$history = $this->getCustomerHistory($loans);
		$history->saveToDatabase($application_id, $property_short);
	}

	/**
	 * Test purchased loan counts when no purchase dates are specified.
	 * @return NULL
	 */
	public function testPurchasedLoanCountNoPurchasesPassed()
	{
		$history = new ECash_CustomerHistory(array(
			array('company' => 'c1', 'status' => 'pending', 'application_id' => 1, 'date' => strtotime('-5 days')),
			array('company' => 'c1', 'status' => 'pending', 'application_id' => 2, 'date' => strtotime('-10 days')),
		));

		$history->addLoan('c1', 'pending', 3, strtotime('-5 days'));

		$this->assertEquals(0, $history->getPurchasedLeadCount('6 days'));
	}

	/**
	 * Test purchased loan counts.
	 * @return NULL
	 */
	public function testPurchasedLoanCount()
	{
		$history = new ECash_CustomerHistory(array(
			array('company' => 'c1', 'status' => 'pending', 'application_id' => 1, 'date' => strtotime('-5 days'), 'purchase_date' => strtotime('-5 days')),
			array('company' => 'c1', 'status' => 'pending', 'application_id' => 2, 'date' => strtotime('-10 days'), 'purchase_date' => strtotime('-10 days')),
		));

		$history->addLoan('c1', 'pending', 3, strtotime('-4 days'), strtotime('-4 days'));

		$this->assertEquals(2, $history->getPurchasedLeadCount('6 days'));
		$this->assertEquals(3, $history->getPurchasedLeadCount('20 days'));
	}

	/**
	 * Test purchased loan counts after a customer history is reduced to a single company.
	 * @return NULL
	 */
	public function testPurchasedLoanCountCompanyReduction()
	{
		$history = new ECash_CustomerHistory(array(
			array('company' => 'c1', 'status' => 'pending', 'application_id' => 1, 'date' => strtotime('-5 days'), 'purchase_date' => strtotime('-5 days')),
			array('company' => 'c2', 'status' => 'pending', 'application_id' => 2, 'date' => strtotime('-10 days'), 'purchase_date' => strtotime('-10 days')),
		));

		$history->addLoan('c1', 'pending', 3, strtotime('-4 days'), strtotime('-4 days'));

		$chistory = $history->getCompanyHistory('c1');

		$this->assertEquals(2, $chistory->getPurchasedLeadCount('20 days'));
	}
}

?>
