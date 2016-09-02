<?php

/**
 * Tests the abstract criteria class functionality.
 *
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class VendorAPI_PreviousCustomer_Criteria_AbstractTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var VendorAPI_PreviousCustomer_Criteria_Abstract
	 */
	protected $criteria;

	/**
	 * Test Fixture
	 * @return NULL
	 */
	public function setUp()
	{
		$this->markTestSkipped("Broken test");
		$this->criteria = $this->getMockForAbstractClass('VendorAPI_PreviousCustomer_Criteria_Abstract', array(new VendorAPI_PreviousCustomer_CustomerHistoryStatusMap()));
	}

	/**
	 * Tests that data is pulled from a concrete implementation (getCriteriaMapping()) when getAppServiceObject is called.
	 * @return NULL
	 */
	public function testGetAppServiceObjectPullsDataFromConcrete()
	{
		$app_data = array(
			'bank_aba' => 123456789,
			'bank_account' => 9876543231,
			'dob' => '1980-01-01',
			'ssn' => '111223333'
		);
		
		$this->criteria->expects($this->any())
			->method('getCriteriaMapping')
			->will($this->returnValue(array('ssn' => 'ssn', 'dob' => 'dateOfBirth')));
		
		$expected_result = array(
			(object)array(
				'searchCriteria' => '111223333',
				'field' => 'ssn',
				'strategy' => 'is'
			),
			(object)array(
				'searchCriteria' => '1980-01-01',
				'field' => 'dateOfBirth',
				'strategy' => 'is'
			),
		);

		$this->assertEquals($expected_result, $this->criteria->getAppServiceObject($app_data));
	}

	/**
	 * Tests that getAppServiceObject returns an empty value when criteria is not properly set up.
	 * This is important to ensure we don't run full table scans on the app service when pulling criteria.
	 * @return NULL
	 */
	public function testGetAppServiceObjectReturnsEmptyWhenCriteriaNotSetUp()
	{
		$app_data = array(
			'bank_aba' => 123456789,
			'bank_account' => 9876543231,
			'dob' => '1980-01-01',
			'ssn' => '111223333'
		);

		$this->criteria->expects($this->any())
			->method('getCriteriaMapping')
			->will($this->returnValue(array()));

		$expected_result = array();

		$this->assertEquals($expected_result, $this->criteria->getAppServiceObject($app_data));
	}

	/**
	 * Ensures that the app service does not attemp to check criteria that does not have enough data specified.
	 * @return NULL
	 */
	public function testGetAppServiceObjectChecksForCriteriaValidity()
	{
		$app_data = array(
			'bank_aba' => 123456789,
			'bank_account' => 9876543231,
			'ssn' => '111223333'
		);

		$this->criteria->expects($this->any())
			->method('getCriteriaMapping')
			->will($this->returnValue(array('ssn' => 'ssn', 'dob' => 'dateOfBirth')));

		$expected_result = array();

		$this->assertEquals($expected_result, $this->criteria->getAppServiceObject($app_data));
	}

	/**
	 * Tests that statuses can be properly ignored in post processing.
	 * @return NULL
	 */
	public function testPostProcessingIgnoresProperStatuses()
	{
		$this->criteria->expects($this->any())
			->method('getIgnoredStatuses')
			->will($this->returnValue(array('paid', 'settled')));

		$apps = array(
			(object)array(
				'do_not_loan_in_company' => true,
				'do_not_loan_other_company' => true,
				'do_not_loan_override' => true,
				'regulatory_flag' => true,
				'application_status' => 'paid::customer::*root'
			),
			(object)array(
				'do_not_loan_in_company' => false,
				'do_not_loan_other_company' => false,
				'do_not_loan_override' => false,
				'regulatory_flag' => false,
				'application_status' => 'settled::customer::*root'
			)
		);

		$expected_result = array(
		);

		$result = $this->criteria->postProcessResults($apps);

		$this->assertEquals($expected_result, $result);
	}

	/**
	 * Tests that post processing will allow for NOT checking DNL.
	 * @return NULL
	 */
	public function testPostProcessingWillRemoveFlag()
	{
		$this->criteria->expects($this->any())
			->method('overrideDoNotLoanLookup')
			->will($this->returnValue(TRUE));

		$apps = array(
			(object)array(
				'do_not_loan_in_company' => true,
				'do_not_loan_other_company' => true,
				'do_not_loan_override' => true,
				'regulatory_flag' => true,
			)
		);

		$expected_result = array(
			(object)array(
				'do_not_loan_in_company' => false,
				'do_not_loan_other_company' => false,
				'do_not_loan_override' => false,
				'regulatory_flag' => false,
			)
		);

		$result = $this->criteria->postProcessResults($apps);

		$this->assertEquals($expected_result, $result);
	}
}
?>
