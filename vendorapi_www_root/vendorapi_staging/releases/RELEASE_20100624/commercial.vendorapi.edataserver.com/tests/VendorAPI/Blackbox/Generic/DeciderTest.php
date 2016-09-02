<?php

/**
 * Tests the generic decider
 *
 * @group previousCustomer
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_Blackbox_Generic_DeciderTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var OLPBlackbox_Enterprise_Generic_Decider
	 */
	protected $decider;

	/**
	 * setup decider to use for tests
	 * @return void
	 */
	protected function setUp()
	{
		$this->decider = new VendorAPI_Blackbox_Generic_Decider(1, '-20 days', 1, '-20 days', '-20 days', '');
	}

	protected function tearDown()
	{
		$this->decider = NULL;
	}

	/**
	 * @param array $calls
	 * @return OLPBlackbox_Enterprise_CustomerHistory
	 */
	protected function getMockHistory(array $calls, $status = NULL, $date = NULL)
	{
		// force all calls to return a known value
		$all = array(
			'getCountBad' => 0,
			'getCountActive' => 0,
			'getCountPaid' => 0,
			'getCountDisagreed' => 0,
			'getCountConfirmedDisagreed' => 0,
			'getActiveCompanies' => array(),
			'getPendingCompanies' => array(),
			'getIsDoNotLoan' => FALSE
		);

		// all the methods we need to mock...
		$methods = array_keys($all);
		$methods[] = 'getNewestLoanDateInStatus';

		$history = $this->getMock(
			'ECash_CustomerHistory',
			$methods
		);

		foreach (array_merge($all, $calls) as $method=>$return)
		{
			$history->expects($this->any())
				->method($method)
				->will($this->returnValue($return));
		}

		if ($status)
		{
			$history->expects($this->any())
				->method('getNewestLoanDateInStatus')
				->will($this->returnValue($date));
		}

		return $history;
	}

	/**
	 * Tests that a denied app within the denied threshold returns denied
	 * @group previousCustomer
	 * @return void
	 */
	public function testDeniedUnderThresholdIsDenied()
	{
		$date = strtotime('-10 days');
		$history = $this->getMockHistory(
			array(),
			VendorAPI_Blackbox_Generic_Decision::CUSTOMER_DENIED,
			$date
		);

		$decision = $this->decider->getDecision($history);
		$this->assertEquals(
			VendorAPI_Blackbox_Generic_Decision::CUSTOMER_DENIED,
			$decision->getDecision()
		);
	}

	/**
	 * Tests that a denied app doesn't return denied when using a null
	 * denied threshold
	 * @group previousCustomer
	 * @return void
	 */
	public function testNullDeniedThresholdSkipsDenied()
	{
		$date = strtotime('-10 days');
		$history = $this->getMockHistory(
			array(),
			VendorAPI_Blackbox_Generic_Decision::CUSTOMER_DENIED,
			$date
		);

		// Create a Decider passing NULL as the threshold
		$decider = new VendorAPI_Blackbox_Generic_Decider(1, NULL, '', '', NULL, '');

		$decision = $decider->getDecision($history);
		$this->assertEquals(
			VendorAPI_Blackbox_Generic_Decision::CUSTOMER_NEW,
			$decision->getDecision()
		);
	}

	/**
	 * Tests that a denied app over the denied threshold returns new
	 * @group previousCustomer
	 * @return void
	 */
	public function testDeniedOverThresholdIsNew()
	{
		$date = strtotime('-50 days');
		$history = $this->getMockHistory(
			array(),
			VendorAPI_Blackbox_Generic_Decision::CUSTOMER_DENIED,
			$date
		);

		$decision = $this->decider->getDecision($history);
		$this->assertEquals(
			VendorAPI_Blackbox_Generic_Decision::CUSTOMER_NEW,
			$decision->getDecision()
		);
	}

	/**
	 * Tests that a bad application returns bad
	 * @group previousCustomer
	 * @return void
	 */
	public function testBadIsBad()
	{
		$history = $this->getMockHistory(array(
			'getCountBad' => 1
		));

		$decision = $this->decider->getDecision($history);
		$this->assertEquals(
			VendorAPI_Blackbox_Generic_Decision::CUSTOMER_BAD,
			$decision->getDecision()
		);
	}

	/**
	 * Tests that one active application returns underactive
	 * @group previousCustomer
	 * @return void
	 */
	public function testOneActiveIsUnderactive()
	{
		$history = $this->getMockHistory(array(
			'getCountActive' => 1
		));

		$decision = $this->decider->getDecision($history);
		$this->assertEquals(
			VendorAPI_Blackbox_Generic_Decision::CUSTOMER_UNDERACTIVE,
			$decision->getDecision()
		);
	}

	/**
	 * Tests that two active applications returns overactive
	 * @group previousCustomer
	 * @return void
	 */
	public function testTwoActiveIsOveractive()
	{
		$history = $this->getMockHistory(array(
			'getCountActive' => 2
		));

		$decision = $this->decider->getDecision($history);
		$this->assertEquals(
			VendorAPI_Blackbox_Generic_Decision::CUSTOMER_OVERACTIVE,
			$decision->getDecision()
		);
	}

	/**
	 * Tests that one paid app returns new/react
	 * @group previousCustomer
	 * @return void
	 */
	public function testOnePaidIsReact()
	{
		$history = $this->getMockHistory(array(
			'getCountPaid' => 1
		));

		$decision = $this->decider->getDecision($history);
		$this->assertEquals(
			VendorAPI_Blackbox_Generic_Decision::CUSTOMER_REACT,
			$decision->getDecision()
		);
	}

	/**
	 * Tests that multiple disagreed apps becomes disagreed....
	 * @group previousCustomer
	 * @return void
	 */
	public function testDisagreedUnderCountThresholdIsNew()
	{
		$history = new ECash_CustomerHistory();
		$history->addLoan('ca', ECash_CustomerHistory::STATUS_DISAGREED, 1, strtotime('-10 days'));

		$decision = $this->decider->getDecision($history);
		$this->assertEquals(
			VendorAPI_Blackbox_Generic_Decision::CUSTOMER_NEW,
			$decision->getDecision()
		);
	}

	/**
	 * Tests that multiple disagreed apps becomes disagreed....
	 * @group previousCustomer
	 * @return void
	 */
	public function testDisagreedOverTimeThresholdIsNew()
	{
		$history = new ECash_CustomerHistory();
		$history->addLoan('ca', ECash_CustomerHistory::STATUS_DISAGREED, 1, strtotime('-40 days'));
		$history->addLoan('ca', ECash_CustomerHistory::STATUS_DISAGREED, 2, strtotime('-40 days'));

		$decision = $this->decider->getDecision($history);
		$this->assertEquals(
			VendorAPI_Blackbox_Generic_Decision::CUSTOMER_NEW,
			$decision->getDecision()
		);
	}

	/**
	 * Tests that multiple disagreed apps becomes disagreed....
	 * @group previousCustomer
	 * @return void
	 */
	public function testDisagreedUnderThresholdIsDisagreed()
	{
		$history = new ECash_CustomerHistory();
		$history->addLoan('ca', ECash_CustomerHistory::STATUS_DISAGREED, 1, strtotime('-10 days'));
		$history->addLoan('ca', ECash_CustomerHistory::STATUS_DISAGREED, 2, strtotime('-10 days'));

		$decision = $this->decider->getDecision($history);
		$this->assertEquals(
			VendorAPI_Blackbox_Generic_Decision::CUSTOMER_DISAGREED,
			$decision->getDecision()
		);
	}

	public function testWithdrawnOverThresholdIsNew()
	{
		$history = new ECash_CustomerHistory();
		$history->addLoan('ca', ECash_CustomerHistory::STATUS_WITHDRAWN, 1, strtotime('-40 days'));

		$decision = $this->decider->getDecision($history);
		$this->assertEquals(
			VendorAPI_Blackbox_Generic_Decision::CUSTOMER_NEW,
			$decision->getDecision()
		);
	}

	public function testWithdrawnUnderThresholdIsWithdrawn()
	{
		$history = new ECash_CustomerHistory();
		$history->addLoan('ca', ECash_CustomerHistory::STATUS_WITHDRAWN, 1, strtotime('-10 days'));

		$decision = $this->decider->getDecision($history);
		$this->assertEquals(
			VendorAPI_Blackbox_Generic_Decision::CUSTOMER_WITHDRAWN,
			$decision->getDecision()
		);
	}

	/**
	 * Tests that bad loans take precedence over denied
	 * @group previousCustomer
	 * @return void
	 */
	public function testBadTakesPrecedenceOverDenied()
	{
		$date = strtotime('-10 days');

		$history = $this->getMockHistory(
			array('getCountBad' => 2),
			VendorAPI_Blackbox_Generic_Decision::CUSTOMER_BAD,
			$date
		);

		$decision = $this->decider->getDecision($history);
		$this->assertEquals(
			VendorAPI_Blackbox_Generic_Decision::CUSTOMER_BAD,
			$decision->getDecision()
		);
	}

	/**
	 * Tests that denied loans take precedence over active
	 * @group previousCustomer
	 * @return void
	 */
	public function testDeniedTakesPrecedenceOverActive()
	{
		$date = strtotime('-10 days');

		$history = $this->getMockHistory(
			array(
				'getCountActive' => 2,
				'getCountDenied' => 1,
			),
			VendorAPI_Blackbox_Generic_Decision::CUSTOMER_DENIED,
			$date
		);

		$decision = $this->decider->getDecision($history);
		$this->assertEquals(
			VendorAPI_Blackbox_Generic_Decision::CUSTOMER_DENIED,
			$decision->getDecision()
		);
	}

	/**
	 * Tests that overactive loans take precedence over react
	 * @group previousCustomer
	 * @return void
	 */
	public function testOveractiveTakesPrecedenceOverReact()
	{
		$history = $this->getMockHistory(array(
			'getCountActive' => 2,
			'getCountPaid' => 1,
		));

		$decision = $this->decider->getDecision($history);
		$this->assertEquals(
			VendorAPI_Blackbox_Generic_Decision::CUSTOMER_OVERACTIVE,
			$decision->getDecision()
		);
	}

	/**
	 * Tests that underactive loans take precedence over reacts
	 * @group previousCustomer
	 * @return void
	 */
	public function testUnderactiveTakesPrecedenceOverReact()
	{
		$history = $this->getMockHistory(array(
			'getCountActive' => 1,
			'getCountPaid' => 1,
		));

		$decision = $this->decider->getDecision($history);
		$this->assertEquals(
			VendorAPI_Blackbox_Generic_Decision::CUSTOMER_UNDERACTIVE,
			$decision->getDecision()
		);
	}

	/**
	 * Tests that react loans take precedence over nothing
	 * @group previousCustomer
	 * @return void
	 */
	public function testReactTakesPrecedenceOverNew()
	{
		$history = $this->getMockHistory(array(
			'getCountPaid' => 1,
		));

		$decision = $this->decider->getDecision($history);
		$this->assertEquals(
			VendorAPI_Blackbox_Generic_Decision::CUSTOMER_REACT,
			$decision->getDecision()
		);
	}

	public function testActiveWithReactCompanyIsOverative()
	{
		$history = $this->getMockHistory(array(
			'getActiveCompanies' => array('ca')
		));

		$decider = new VendorAPI_Blackbox_Generic_Decider(1, null, 0, null, null, 'ca');
		$decision = $decider->getDecision($history);

		$this->assertEquals(
			VendorAPI_Blackbox_Generic_Decision::CUSTOMER_OVERACTIVE,
			$decision->getDecision()
		);
	}

	public function testPendingWithReactCompanyIsOverative()
	{
		$history = $this->getMockHistory(array(
			'getPendingCompanies' => array('ca')
		));

		$decider = new VendorAPI_Blackbox_Generic_Decider(1, null, 0, null, null, 'ca');
		$decision = $decider->getDecision($history);

		$this->assertEquals(
			VendorAPI_Blackbox_Generic_Decision::CUSTOMER_OVERACTIVE,
			$decision->getDecision()
		);
	}

	public function testDNLIsDoNotLoan()
	{
		$history = $this->getMockHistory(array(
			'getIsDoNotLoan' => TRUE
		));

		$decider = new VendorAPI_Blackbox_Generic_Decider(1, null, 0, null, null, 'ca');
		$decision = $decider->getDecision($history);

		$this->assertEquals(
			VendorAPI_Blackbox_Generic_Decision::CUSTOMER_DONOTLOAN,
			$decision->getDecision()
		);
	}

	/**
	 * Tests various decisions for validity.
	 *
	 * @dataProvider isValidDataProvider
	 * @param string $decision_string The decision constant to construct the decision with.
	 * @param bool $is_valid The validity of the decision.
	 * @return void
	 */
	public function testValid($decision_string, $is_valid)
	{
		$decision = new VendorAPI_Blackbox_Generic_Decision($decision_string);
		return $this->assertEquals($is_valid, $decision->isValid());
	}

	public static function isValidDataProvider()
	{
		return array(
			array(VendorAPI_Blackbox_Generic_Decision::CUSTOMER_UNDERACTIVE, TRUE),
			array(VendorAPI_Blackbox_Generic_Decision::CUSTOMER_REACT, TRUE),
			array(VendorAPI_Blackbox_Generic_Decision::CUSTOMER_NEW, TRUE),
			array(VendorAPI_Blackbox_Generic_Decision::CUSTOMER_OVERACTIVE, FALSE),
			array(VendorAPI_Blackbox_Generic_Decision::CUSTOMER_BAD, FALSE),
			array(VendorAPI_Blackbox_Generic_Decision::CUSTOMER_DENIED, FALSE),
			array(VendorAPI_Blackbox_Generic_Decision::CUSTOMER_DISAGREED, FALSE),
		);
	}
}

?>
