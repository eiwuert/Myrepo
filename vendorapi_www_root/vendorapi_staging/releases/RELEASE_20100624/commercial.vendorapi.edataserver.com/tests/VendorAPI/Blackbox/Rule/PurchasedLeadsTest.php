<?php

/**
 * Tests the PurchasedLeads rule
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_PurchasedLeadsTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var VendorAPI_Blackbox_Rule_PurchasedLeads
	 */
	protected $rule;

	/**
	 * @var VendorAPI_PurchasedLeadStore_Memcache
	 */
	protected $store;

	/**
	 * @var Blackbox_Data
	 */
	protected $bbx_data;

	/**
	 * @var Blackbox_IStateData
	 */
	protected $bbx_state_data;

	/**
	 * @var ECash_CustomerHistory
	 */
	protected $child_history;

	/**
	 * Creates the purchased lead object for the fixture.
	 * @return NULL
	 */
	public function setUp()
	{
		$this->bbx_data = new VendorAPI_Blackbox_Data();
		$this->bbx_data->application_id = 1;
		$this->bbx_data->ssn = '123456789';

		$this->bbx_state_data = new VendorAPI_Blackbox_StateData();

		$this->child_history = $this->getMock('ECash_CustomerHistory');

		$customer_history = $this->getMock('ECash_CustomerHistory');
		$customer_history->expects($this->any())
			->method('getCompanyHistory')
			->will($this->returnValue($this->child_history));

		$this->bbx_state_data->customer_history = $customer_history;

		$this->store = $this->getMock('VendorAPI_PurchasedLeadStore_Memcache', array(), array("prefix", new Memcache));

		$this->rule = new VendorAPI_Blackbox_Rule_PurchasedLeads(
			new VendorAPI_Blackbox_EventLog(new VendorAPI_StateObject(), 0, ''),
			$this->store,
			'30 days',
			2,
			'c1',
			'15 days',
			1
		);
	}

	/**
	 * Tests that run rule actually obtains an ssn lock
	 * @return NULL
	 */
	public function testRunRuleObtainsSsnLock()
	{
		$this->store->expects($this->once())
			->method('lockSsn')
			->with('123456789');

		$this->store->expects($this->once())
			->method('unlockSsn')
			->with('123456789');

		$this->runRule();
	}

	/**
	 * Tests that the history is retrieved for the given ssn.
	 * @return NULL
	 */
	public function testHistoryIsRetreived()
	{
		$this->store->expects($this->once())
			->method('getApplications')
			->with('123456789');

		$this->runRule();
	}

	/**
	 * Tests that customer history is set for results from getApplications
	 * @return NULL
	 */
	public function testHistoryIsAddedToCustomerHistory()
	{
		$this->store->expects($this->any())
			->method('getApplications')
			->will($this->returnValue(array(
				'2' => array('application_id' => 2, 'ssn' => '123456789', 'company' => 'company', 'date' => strtotime('2009-01-01 00:00:00')),
				'3' => array('application_id' => 3, 'ssn' => '123456789', 'company' => 'company', 'date' => strtotime('2009-01-01 00:00:00')),
			)));

		$this->bbx_state_data->customer_history->expects($this->at(0))
			->method('addLoan')
			->with('company', 'pending', 2, strtotime('2009-01-01 00:00:00'), strtotime('2009-01-01 00:00:00'));
		$this->bbx_state_data->customer_history->expects($this->at(1))
			->method('addLoan')
			->with('company', 'pending', 3, strtotime('2009-01-01 00:00:00'), strtotime('2009-01-01 00:00:00'));

		$this->runRule();
	}

	/**
	 * Tests that purchasedLeadCount is properly checked.
	 * @return NULL
	 */
	public function testPurchasedLeadCountChecked()
	{
		$this->bbx_state_data->customer_history->expects($this->once())
			->method('getPurchasedLeadCount')
			->with('30 days')
			->will($this->returnValue(1));

		$this->assertTrue($this->runRule());
	}

	/**
	 * Tests that a threshold can cause the rule to fail.
	 * @return NULL
	 */
	public function testRuleChecksCountThresholdAndFails()
	{
		$this->bbx_state_data->customer_history->expects($this->once())
			->method('getPurchasedLeadCount')
			->will($this->returnValue(3));

		$this->assertFalse($this->runRule());
	}

	/**
	 * Tests that an app is added as a purchased lead if it passes this check. 
	 * @return NULL
	 */
	public function testLeadMarkedAsPurchasedOnPass()
	{
		$this->bbx_state_data->customer_history->expects($this->once())
			->method('getPurchasedLeadCount')
			->with('30 days')
			->will($this->returnValue(1));

		$this->store->expects($this->once())
			->method('addApplication')
			->with('123456789', 'c1', 1, $this->equalTo(time(), 1));

		$this->assertTrue($this->runRule());
	}

	/**
	 * Tests that an app is NOT added as a purchased lead if it fails this check. 
	 * @return NULL
	 */
	public function testLeadNotMarkedAsPurchasedOnFail()
	{
		$this->bbx_state_data->customer_history->expects($this->once())
			->method('getPurchasedLeadCount')
			->with('30 days')
			->will($this->returnValue(10));

		$this->store->expects($this->never())
			->method('addApplication');

		$this->assertFalse($this->runRule());
	}

	/**
	 * Tests that a company threshold can cause the rule to fail.
	 * @return NULL
	 */
	public function testRuleChecksCompanyCountThresholdAndFails()
	{
		$this->child_history->expects($this->once())
			->method('getPurchasedLeadCount')
			->will($this->returnValue(1));

		$this->assertFalse($this->runRule());
	}

	/**
	 * Tests that an app is added as a purchased lead if it passes this check. 
	 * @return NULL
	 */
	public function testLeadMarkedAsPurchasedOnCompanyPass()
	{
		$this->child_history->expects($this->once())
			->method('getPurchasedLeadCount')
			->with('15 days')
			->will($this->returnValue(0));

		$this->store->expects($this->once())
			->method('addApplication')
			->with('123456789', 'c1', 1, $this->equalTo(time(), 1));

		$this->assertTrue($this->runRule());
	}

	/**
	 * Utility function to run the rule with the appropriate data and to return the results.
	 * @return bool
	 */
	protected function runRule()
	{
		return $this->rule->isValid($this->bbx_data, $this->bbx_state_data);
	}
}

?>
