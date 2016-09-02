<?php

require_once 'OLPBlackboxTestSetup.php';

/**
 * Tests the customer history
 *
 * I didn't shoot for full coverage because the class is dead simple.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_Enterprise_CustomerHistoryTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var OLPBlackbox_Enterprise_CustomerHistory
	 */
	protected $history;

	public function setUp()
	{
		$this->history = new OLPBlackbox_Enterprise_CustomerHistory();
	}

	/**
	 * Tests that the last denied date works
	 * @group previousCustomer
	 * @return void
	 */
	public function testGetLastDeniedDate()
	{
		$date = time();

		$this->history->addLoan('ca', OLPBlackbox_Enterprise_CustomerHistory::STATUS_DENIED, 0, $date);
		$this->assertEquals($date, $this->history->getLastDeniedDate());
	}

	/**
	 * Tests that getCountDenied works
	 * @group previousCustomer
	 * @return void
	 */
	public function testGetCountDenied()
	{
		$this->history->addLoan('ca', OLPBlackbox_Enterprise_CustomerHistory::STATUS_DENIED, 0, time());

		$count = $this->history->getCountDenied();
		$this->assertEquals(1, $count);
	}

	/**
	 * Tests that when one bad loan is added, getCountBad returns... 1
	 * @group previousCustomer
	 * @return void
	 */
	public function testGetCountBad()
	{
		$this->history->addLoan('ca', OLPBlackbox_Enterprise_CustomerHistory::STATUS_BAD, 0);

		$count = $this->history->getCountBad();
		$this->assertEquals(1, $count);
	}

	/**
	 * Tests that when one active loan is added, getCountActive returns... 1
	 * @group previousCustomer
	 * @return void
	 */
	public function testGetCountActive()
	{
		$this->history->addLoan('ca', OLPBlackbox_Enterprise_CustomerHistory::STATUS_ACTIVE, 0);

		$count = $this->history->getCountActive();
		$this->assertEquals(1, $count);
	}

	/**
	 * Tests that when one active loan is added, getCountActive returns... 1
	 * @group previousCustomer
	 * @return void
	 */
	public function testGetCountPending()
	{
		$this->history->addLoan('ca', OLPBlackbox_Enterprise_CustomerHistory::STATUS_PENDING, 0);

		$count = $this->history->getCountPending();
		$this->assertEquals(1, $count);
	}

	/**
	 * Tests that when one paid loan is added, getCountPaid returns... 1
	 * @group previousCustomer
	 * @return void
	 */
	public function testGetCountPaid()
	{
		$this->history->addLoan(
			'ca',
			OLPBlackbox_Enterprise_CustomerHistory::STATUS_PAID,
			0
		);

		$count = $this->history->getCountPaid();
		$this->assertEquals(1, $count);
	}

	/**
	 * Tests that when one active loan is added, getCountActive returns... 1
	 * @group previousCustomer
	 * @return void
	 */
	public function testGetActiveCompanies()
	{
		$this->history->addLoan('ca', OLPBlackbox_Enterprise_CustomerHistory::STATUS_ACTIVE, 0);

		$c = $this->history->getActiveCompanies();
		$this->assertEquals(array('ca' => 'ca'), $c);
	}

	/**
	 * Tests that when one active loan is added, getCountActive returns... 1
	 * @group previousCustomer
	 * @return void
	 */
	public function testGetPaidCompanies()
	{
		$this->history->addLoan('ca', OLPBlackbox_Enterprise_CustomerHistory::STATUS_PAID, 0);

		$c = $this->history->getPaidCompanies();
		$this->assertEquals(array('ca' => 'ca'), $c);
	}

	/**
	 * Tests that isReact is FALSE without any history
	 * @group previousCustomer
	 * @return void
	 */
	public function testIsNotReactWithNoHistory()
	{
		$this->assertFalse($this->history->getIsReact('pcl'));
	}

	/**
	 * Provides status that aren't paid
	 */
	public static function unpaidStatusProvider()
	{
		return array(
			array(OLPBlackbox_Enterprise_CustomerHistory::STATUS_BAD),
			array(OLPBlackbox_Enterprise_CustomerHistory::STATUS_DENIED),
			array(OLPBlackbox_Enterprise_CustomerHistory::STATUS_ACTIVE),
			array(OLPBlackbox_Enterprise_CustomerHistory::STATUS_PENDING),
		);
	}

	/**
	 * Tests that when isReact is using the proper status
	 * @group previousCustomer
	 * @dataProvider unpaidStatusProvider
	 * @return void
	 */
	public function testIsNotReactWithoutPaidApp($status)
	{
		$this->history->addLoan('ca', $status, 0);
		$this->assertFalse($this->history->getIsReact('pcl'));
	}

	/**
	 * Tests that when a paid app is added, isReact is true
	 * @group previousCustomer
	 * @return void
	 */
	public function testIsReactWithPaidApp()
	{
		$this->history->addLoan('ca', OLPBlackbox_Enterprise_CustomerHistory::STATUS_PAID, 0);
		$this->assertTrue($this->history->getIsReact('ca'));
	}

	/**
	 * Tests that when isReact works only for the given company
	 * @group previousCustomer
	 * @return void
	 */
	public function testIsNotReactWithoutCompanyHistory()
	{
		$this->history->addLoan('ca', OLPBlackbox_Enterprise_CustomerHistory::STATUS_PAID, 0);
		$this->assertFalse($this->history->getIsReact('pcl'));
	}
}

?>