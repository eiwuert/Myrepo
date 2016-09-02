<?php

class ECash_CompositeProviderTest extends PHPUnit_Framework_TestCase
{
	protected $_provider;
	protected $_composite;

	public function setUp()
	{
		$this->_provider = $this->getMock(
			'ECash_ICustomerHistoryProvider',
			array(
				'excludeApplication',
				'setCompany',
				'getHistoryBy',
				'runDoNotLoan',
			)
		);

		$this->_composite = new ECash_CompositeProvider();
		$this->_composite->addProvider($this->_provider);
	}

	protected function tearDown()
	{
		$this->_provider = NULL;
		$this->_composite = NULL;
	}

	public function testCompositeCallsExcludeApplication()
	{
		$app_id = 100;

		$this->_provider->expects($this->once())
			->method('excludeApplication')
			->with($app_id);

		$this->_composite->excludeApplication($app_id);
	}

	public function testCompositeCallsSetCompany()
	{
		$company = 'company';

		$this->_provider->expects($this->once())
			->method('setCompany')
			->with($company);

		$this->_composite->setCompany($company);
	}

	public function testCompositeCallsGetHistoryByWithCorrectConditions()
	{
		$conditions = array('ssn' => 123456789);
		$history = new ECash_CustomerHistory();

		$this->_provider->expects($this->once())
			->method('getHistoryBy')
			->with($conditions, $this->anything());

		$this->_composite->getHistoryBy($conditions, $history);
	}

	public function testGetHistoryByReturnsHistory()
	{
		$conditions = array('ssn' => 123456789);
		$history1 = new ECash_CustomerHistory();

		$history2 = new ECash_CustomerHistory();
		$history2->addLoan('test', 'denied', 100);

		$this->_provider->expects($this->once())
			->method('getHistoryBy')
			->will($this->returnValue($history2));

		$actual = $this->_composite->getHistoryBy($conditions, $history1);
		$this->assertEquals($history2, $actual);
	}

	public function testHistoryReturnedByProviderIsPassedToNextProvider()
	{
		$conditions = array('ssn' => 123456789);
		$history1 = new ECash_CustomerHistory();

		$history2 = new ECash_CustomerHistory();
		$history2->addLoan('test', 'denied', 100);

		$this->_provider->expects($this->once())
			->method('getHistoryBy')
			->will($this->returnValue($history2));

		// ensure that the history argument is the history returned from the first provider
		$provider2 = $this->getMock('ECash_ICustomerHistoryProvider');
		$provider2->expects($this->once())
			->method('getHistoryBy')
			->with($this->anything(), $history2);
		$this->_composite->addProvider($provider2);

		$this->_composite->getHistoryBy($conditions, $history1);
	}

	public function testCompositeCallsRunDoNotLoan()
	{
		$this->_provider->expects($this->once())
			->method('runDoNotLoan');

		$history = new ECash_CustomerHistory();
		$this->_composite->runDoNotLoan('12345789', $history);
	}
}

?>