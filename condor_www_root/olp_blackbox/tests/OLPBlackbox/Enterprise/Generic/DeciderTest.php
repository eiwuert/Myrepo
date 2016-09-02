<?php

require_once 'OLPBlackboxTestSetup.php';

/**
 * Tests the generic decider
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Generic_DeciderTest extends PHPUnit_Framework_TestCase
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
		$this->decider = new OLPBlackbox_Enterprise_Generic_Decider();
	}

	/**
	 * @param array $calls
	 * @return OLPBlackbox_Enterprise_CustomerHistory
	 */
	protected function getMockHistory(array $calls)
	{
		// force all calls to return a known value
		$all = array(
			'getCountBad' => 0,
			'getLastDeniedDate' => NULL,
			'getCountActive' => 0,
			'getCountPaid' => 0,
			'getCountDisagreed' => 0,
			'getCountConfirmedDisagreed' => 0,
		);

		$history = $this->getMock(
			'OLPBlackbox_Enterprise_CustomerHistory',
			array_keys($all)
		);

		$calls = array_merge($all, $calls);

		foreach ($calls as $method=>$return)
		{
			$history->expects($this->any())
				->method($method)
				->will($this->returnValue($return));
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
		$history = $this->getMockHistory(array(
			'getLastDeniedDate' => $date
		));

		$result = $this->decider->getDecision($history);
		$this->assertEquals(
			OLPBlackbox_Enterprise_Generic_Decider::CUSTOMER_DENIED,
			$result
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
		$history = $this->getMockHistory(array(
			'getLastDeniedDate' => $date
		));

		// Create a Decider passing NULL as the threshold
		$decider = new OLPBlackbox_Enterprise_Generic_Decider(1, NULL);

		$result = $decider->getDecision($history);
		$this->assertEquals(
			OLPBlackbox_Enterprise_Generic_Decider::CUSTOMER_NEW,
			$result
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
		$history = $this->getMockHistory(array(
			'getLastDeniedDate' => $date
		));

		$result = $this->decider->getDecision($history);
		$this->assertEquals(
			OLPBlackbox_Enterprise_Generic_Decider::CUSTOMER_NEW,
			$result
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

		$result = $this->decider->getDecision($history);
		$this->assertEquals(
			OLPBlackbox_Enterprise_Generic_Decider::CUSTOMER_BAD,
			$result
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

		$result = $this->decider->getDecision($history);
		$this->assertEquals(
			OLPBlackbox_Enterprise_Generic_Decider::CUSTOMER_UNDERACTIVE,
			$result
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

		$result = $this->decider->getDecision($history);
		$this->assertEquals(
			OLPBlackbox_Enterprise_Generic_Decider::CUSTOMER_OVERACTIVE,
			$result
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
			'getCountPaid' => 2
		));

		$result = $this->decider->getDecision($history);
		$this->assertEquals(
			OLPBlackbox_Enterprise_Generic_Decider::CUSTOMER_REACT,
			$result
		);
	}
	
	/**
	 * Tests that multiple disagreed apps are ignored for generic decider
	 * @group previousCustomer
	 * @return void
	 */
	public function testMultipleDisagreed()
	{
		$history = $this->getMockHistory(array(
			'getCountDisagreed' => 2
		));

		$result = $this->decider->getDecision($history);
		$this->assertEquals(
			OLPBlackbox_Enterprise_Generic_Decider::CUSTOMER_NEW,
			$result
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

		$history = $this->getMockHistory(array(
			'getLastDeniedDate' => $date,
			'getCountBad' => 2,
		));

		$result = $this->decider->getDecision($history);
		$this->assertEquals(
			OLPBlackbox_Enterprise_Generic_Decider::CUSTOMER_BAD,
			$result
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

		$history = $this->getMockHistory(array(
			'getLastDeniedDate' => $date,
			'getCountActive' => 2,
		));

		$result = $this->decider->getDecision($history);
		$this->assertEquals(
			OLPBlackbox_Enterprise_Generic_Decider::CUSTOMER_DENIED,
			$result
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

		$result = $this->decider->getDecision($history);
		$this->assertEquals(
			OLPBlackbox_Enterprise_Generic_Decider::CUSTOMER_OVERACTIVE,
			$result
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

		$result = $this->decider->getDecision($history);
		$this->assertEquals(
			OLPBlackbox_Enterprise_Generic_Decider::CUSTOMER_UNDERACTIVE,
			$result
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

		$result = $this->decider->getDecision($history);
		$this->assertEquals(
			OLPBlackbox_Enterprise_Generic_Decider::CUSTOMER_REACT,
			$result
		);
	}

	/**
	 * Tests that an underactive customer is considered valid
	 * @group previousCustomer
	 * @return void
	 */
	public function testUnderactiveIsValid()
	{
		$valid = $this->decider->isValid(OLPBlackbox_Enterprise_Generic_Decider::CUSTOMER_UNDERACTIVE);
		$this->assertTrue($valid);
	}

	/**
	 * Tests that a new customer is considered valid
	 * @group previousCustomer
	 * @return void
	 */
	public function testReactIsValid()
	{
		$valid = $this->decider->isValid(OLPBlackbox_Enterprise_Generic_Decider::CUSTOMER_REACT);
		$this->assertTrue($valid);
	}

	/**
	 * Tests that a new customer is considered valid
	 * @group previousCustomer
	 * @return void
	 */
	public function testNewIsValid()
	{
		$valid = $this->decider->isValid(OLPBlackbox_Enterprise_Generic_Decider::CUSTOMER_NEW);
		$this->assertTrue($valid);
	}

	/**
	 * Tests that an overactive customer is considered invalid
	 * @group previousCustomer
	 * @return void
	 */
	public function testOveractiveIsNotValid()
	{
		$valid = $this->decider->isValid(OLPBlackbox_Enterprise_Generic_Decider::CUSTOMER_OVERACTIVE);
		$this->assertFalse($valid);
	}

	/**
	 * Tests that a bad customer is considered invalid
	 * @group previousCustomer
	 * @return void
	 */
	public function testBadIsNotValid()
	{
		$valid = $this->decider->isValid(OLPBlackbox_Enterprise_Generic_Decider::CUSTOMER_BAD);
		$this->assertFalse($valid);
	}

	/**
	 * Tests that a denied customer is considered invalid
	 * @group previousCustomer
	 * @return void
	 */
	public function testDeniedIsNotValid()
	{
		$valid = $this->decider->isValid(OLPBlackbox_Enterprise_Generic_Decider::CUSTOMER_DENIED);
		$this->assertFalse($valid);
	}
	
	/**
	 * Tests that a customer who has disagreed more then once is considered invalid
	 * @group previousCustomer
	 * @return void
	 */
	public function testDisagreedIsNotValid()
	{
		$valid = $this->decider->isValid(OLPBlackbox_Enterprise_Generic_Decider::CUSTOMER_DISAGREED);
		$this->assertFalse($valid);
	}
}

?>
