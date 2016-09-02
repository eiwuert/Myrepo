<?php

/**
 * Tests the functionality of the Previous Customer History Loader
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class VendorAPI_PreviousCustomer_HistoryLoaderTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var VendorAPI_PreviousCustomer_HistoryLoader
	 */
	protected $loader;

	/**
	 * @var ECash_WebService_AppClient
	 */
	protected $app_service;

	/**
	 * @var ECash_WebService_CriteriaContainer
	 */
	protected $container;

	/**
	 * Test Fixture
	 * @return NULL
	 */
	public function setUp()
	{
		$this->app_service = $this->getMock('WebServices_Client_AppClient', array('getPreviousCustomerApps'), array(), '', FALSE);
		$this->container = $this->getMock('VendorAPI_PreviousCustomer_CriteriaContainer');
		$this->loader = new VendorAPI_PreviousCustomer_HistoryLoader($this->app_service, new VendorAPI_PreviousCustomer_CustomerHistoryStatusMap(), $this->container, FALSE);
	}

	/**
	 * Ensure that the history object will be properly loaded.
	 * @return NULL
	 */
	public function testLoadHistoryObject()
	{
		$history = $this->getMock('ECash_CustomerHistory');
		$app_service_criteria = array(array((object)array('id' => 1)));
		$app_service_response = array(
			(object)array(
				'application_id' => 1234,
				'date_application_status_set' => '2009-11-06T20:00:43.707-08:00',
				'date_created' => '2009-11-07T20:00:43.707-08:00',
				'company' => 'pcl',
				'application_status' => 'active::servicing::customer::*root',
				'olp_process' => 'online_confirmation',
			),
			(object)array(
				'application_id' => 1235,
				'date_application_status_set' => '2009-10-06T20:00:43.707-08:00',
				'date_created' => '2009-10-07T20:00:43.707-08:00',
				'company' => 'ucl',
				'application_status' => 'active::servicing::customer::*root',
				'olp_process' => 'online_confirmation',
			)
		);

		$this->container->expects($this->once())
			->method('getAppServiceObject')
			->with(array('key' => 'value'))
			->will($this->returnValue($app_service_criteria));

		$this->container->expects($this->once())
			->method('postProcessResults')
			->will($this->returnValue($app_service_response));

		$this->app_service->expects($this->once())
			->method('getPreviousCustomerApps')
			->with($this->identicalTo($app_service_criteria))
			->will($this->returnValue($app_service_response));

		$history->expects($this->at(0))
			->method('addLoan')
			->with('pcl', 'active', 1234, 1257566443, 1257652843);

		$history->expects($this->at(1))
			->method('addLoan')
			->with('ucl', 'active', 1235, 1254888043, 1254974443);

		$this->loader->loadHistoryObject($history, array('key' => 'value'));
	}

	/**
	 * Tests that expirable apps are not loaded into the history object as loans.
	 * @return NULL
	 */
	public function testExpirableAppsAreNotLoaded()
	{
		$this->loader = new VendorAPI_PreviousCustomer_HistoryLoader($this->app_service, new VendorAPI_PreviousCustomer_CustomerHistoryStatusMap(), $this->container, TRUE);

		$history = $this->getMock('ECash_CustomerHistory');
		$app_service_criteria = array(array((object)array('id' => 1)));
		$app_service_response = array(
			(object)array(
				'application_id' => 1234,
				'date_application_status_set' => '2009-11-06T20:00:43.707-08:00',
				'date_created' => '2009-11-07T20:00:43.707-08:00',
				'company' => 'pcl',
				'application_status' => 'active::servicing::customer::*root',
				'olp_process' => 'online_confirmation',
			),
			(object)array(
				'application_id' => 1235,
				'date_application_status_set' => '2009-10-06T20:00:43.707-08:00',
				'date_created' => '2009-10-07T20:00:43.707-08:00',
				'company' => 'ucl',
				'application_status' => 'pending::prospect::*root',
				'olp_process' => 'online_confirmation',
			)
		);

		$this->container->expects($this->once())
			->method('getAppServiceObject')
			->with(array('key' => 'value'))
			->will($this->returnValue(array()));

		$this->container->expects($this->once())
			->method('postProcessResults')
			->will($this->returnValue($app_service_response));

		$this->app_service->expects($this->once())
			->method('getPreviousCustomerApps')
			->will($this->returnValue($app_service_response));

		$history->expects($this->at(0))
			->method('addLoan')
			->with('pcl', 'active', 1234, 1257566443, 1257652843);

		$history->expects($this->at(1))
			->method('setExpirable')
			->with('ucl', 1235, 'ECASH', 'pending::prospect::*root');

		$this->loader->loadHistoryObject($history, array('key' => 'value'));
	}

	/**
	 * Tests that the loader can correcty skip adding a given app id to the history.
	 * @return NULL
	 */
	public function testLoaderExcludesAppIdWhenPassed()
	{
		$history = $this->getMock('ECash_CustomerHistory');
		$app_service_criteria = array(array((object)array('id' => 1)));
		$app_service_response = array(
			(object)array(
				'application_id' => 1234,
				'date_application_status_set' => '2009-11-06T20:00:43.707-08:00',
				'date_created' => '2009-11-07T20:00:43.707-08:00',
				'company' => 'pcl',
				'application_status' => 'active::servicing::customer::*root',
				'olp_process' => 'online_confirmation',
			),
			(object)array(
				'application_id' => 1235,
				'date_application_status_set' => '2009-10-06T20:00:43.707-08:00',
				'date_created' => '2009-10-07T20:00:43.707-08:00',
				'company' => 'ucl',
				'application_status' => 'active::servicing::customer::*root',
				'olp_process' => 'online_confirmation',
			)
		);

		$this->container->expects($this->once())
			->method('getAppServiceObject')
			->with(array('key' => 'value'))
			->will($this->returnValue($app_service_criteria));

		$this->container->expects($this->once())
			->method('postProcessResults')
			->will($this->returnValue($app_service_response));

		$this->app_service->expects($this->once())
			->method('getPreviousCustomerApps')
			->with($this->identicalTo($app_service_criteria))
			->will($this->returnValue($app_service_response));

		$history->expects($this->at(0))
			->method('addLoan')
			->with('ucl', 'active', 1235, 1254888043, 1254974443);

		$this->loader->loadHistoryObject($history, array('key' => 'value'), 1234);
	}


	/**
	 * Tests that the loader can handle DNL data coming back from the app service.
	 * @return NULL
	 */
	public function testLoaderSetsDNL()
	{
		$history = $this->getMock('ECash_CustomerHistory');
		$app_service_criteria = array(array((object)array('id' => 1)));
		$app_service_response = array(
			(object)array(
				'application_id' => 1234,
				'date_application_status_set' => '2009-11-06T20:00:43.707-08:00',
				'date_created' => '2009-11-07T20:00:43.707-08:00',
				'company' => 'pcl',
				'application_status' => 'active::servicing::customer::*root',
				'olp_process' => 'online_confirmation',
				'do_not_loan_in_company' => TRUE,
				'do_not_loan_other_company' => TRUE,
				'do_not_loan_override' => TRUE,
				'regulatory_flag' => TRUE,
			),
			(object)array(
				'application_id' => 1235,
				'date_application_status_set' => '2009-10-06T20:00:43.707-08:00',
				'date_created' => '2009-10-07T20:00:43.707-08:00',
				'company' => 'ucl',
				'application_status' => 'active::servicing::customer::*root',
				'olp_process' => 'online_confirmation',
				'do_not_loan_in_company' => FALSE,
				'do_not_loan_other_company' => FALSE,
				'do_not_loan_override' => FALSE,
				'regulatory_flag' => FALSE,
			)
		);

		$this->container->expects($this->once())
			->method('getAppServiceObject')
			->with(array('key' => 'value'))
			->will($this->returnValue($app_service_criteria));

		$this->container->expects($this->once())
			->method('postProcessResults')
			->will($this->returnValue($app_service_response));

		$this->app_service->expects($this->once())
			->method('getPreviousCustomerApps')
			->with($this->identicalTo($app_service_criteria))
			->will($this->returnValue($app_service_response));

		$history->expects($this->at(0))
			->method('addLoan')
			->with('pcl', 'active', 1234, 1257566443, 1257652843);

		$history->expects($this->at(1))
			->method('setDoNotLoan')
			->with('pcl');

		$history->expects($this->at(2))
			->method('setDoNotLoanOtherCompany')
			->with('pcl');

		$history->expects($this->at(3))
			->method('setDoNotLoanOverride')
			->with('pcl');

		$history->expects($this->at(4))
			->method('addLoan')
			->with('ucl', 'active', 1235, 1254888043, 1254974443);

		$this->loader->loadHistoryObject($history, array('key' => 'value'));
	}

}


?>
