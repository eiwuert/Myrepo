<?php

require_once 'OLPBlackboxTestSetup.php';

/**
 * Tests the clk decider
 * @author David Watkins <david.watkins@sellingsource.com>
 */
class OLPBlackbox_Enterprise_CLK_DeciderTest extends OLPBlackbox_Enterprise_Generic_DeciderTest
{
	/**
	 * setup decider to use for tests
	 * @return void
	 */
	protected function setUp()
	{
		$this->decider = new OLPBlackbox_Enterprise_CLK_Decider();
	}
	
	/**
	 * Tests that 1 disagreed gets marked as new
	 * @group previousCustomer
	 * @return void
	 */
	public function testOneDisagreedNoCancelIsNew()
	{
		$history = $this->getMockHistory(array(
			'getCountDisagreed' => 1,
			));

		$result = $this->decider->getDecision($history);
		$this->assertEquals(
			OLPBlackbox_Enterprise_Generic_Decider::CUSTOMER_NEW,
			$result
		);
	}
	
	/**
	 * Tests that 2 disagreed gets marked as cancel/disagreed
	 * @group previousCustomer
	 * @return void
	 */
	public function testTwoDisagreedNoCancelledIsDisagreed()
	{
		$history = $this->getMockHistory(array(
			'getCountDisagreed' => 2,
			));

		$result = $this->decider->getDecision($history);
		$this->assertEquals(
			OLPBlackbox_Enterprise_Generic_Decider::CUSTOMER_DISAGREED,
			$result
		);
	}
	
	/**
	 * Tests that 1 confirmed_disagreed gets marked as new
	 * @group previousCustomer
	 * @return void
	 */
	public function testOneDisagreedNoCancelledIsNew()
	{
		$history = $this->getMockHistory(array(
			'getCountConfirmedDisagreed' => 1,
			));

		$result = $this->decider->getDecision($history);
		$this->assertEquals(
			OLPBlackbox_Enterprise_Generic_Decider::CUSTOMER_NEW,
			$result
		);
	}
	
	/**
	 * Tests that 2 confirmed_disagreed gets marked as cancel/disagreed
	 * @group previousCustomer
	 * @return void
	 */
	public function testTwoCancelledNoDisagreedIsDisagreed()
	{
		$history = $this->getMockHistory(array(
			'getCountConfirmedDisagreed' => 2,
			));

		$result = $this->decider->getDecision($history);
		$this->assertEquals(
			OLPBlackbox_Enterprise_Generic_Decider::CUSTOMER_DISAGREED,
			$result
		);
	}
	
	/**
	 * Tests one disagreed and one confirmed disagreed gets marked as cancel/disagreed
	 * @group previousCustomer
	 * @return void
	 */
	public function testOneCancelledOneDisagreedIsDisagreed()
	{
		$history = $this->getMockHistory(array(
			'getCountDisagreed' => 1,
			'getCountConfirmedDisagreed' => 1,
			));

		$result = $this->decider->getDecision($history);
		$this->assertEquals(
			OLPBlackbox_Enterprise_Generic_Decider::CUSTOMER_DISAGREED,
			$result
		);
	}
	
	/**
	 * Tests that react loans take precedence over disagreed
	 * @group previousCustomer
	 * @return void
	 */
	public function testReactTakesPrecedenceOverDisagreed()
	{
		$history = $this->getMockHistory(array(
			'getCountPaid' => 1,
			'getCountDisagreed' => 2,
		));

		$result = $this->decider->getDecision($history);
		$this->assertEquals(
			OLPBlackbox_Enterprise_Generic_Decider::CUSTOMER_REACT,
			$result
		);
	}
	
	/**
	 * Overwriting generic version
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
			OLPBlackbox_Enterprise_Generic_Decider::CUSTOMER_DISAGREED,
			$result
		);
	}
}

?>