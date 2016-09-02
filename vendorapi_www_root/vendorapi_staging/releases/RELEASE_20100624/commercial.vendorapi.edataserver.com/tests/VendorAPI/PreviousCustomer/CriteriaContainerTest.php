<?php

/**
 * Tests the functionality of the Previous Customer Criteria Container
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class VendorAPI_PreviousCustomer_CriteriaContainerTest extends PHPUnit_Framework_TestCase
{
	/**
	 *
	 * @var VendorAPI_PreviousCustomer_CriteriaContainer
	 */
	protected $container;

	/**
	 *
	 * @var array
	 */
	protected $criteria;

	/**
	 * Test Fixture
	 * @return NULL
	 */
	public function setUp()
	{
		$this->criteria = array(
			$this->getMock('VendorAPI_PreviousCustomer_ICriterion'),
			$this->getMock('VendorAPI_PreviousCustomer_ICriterion')
		);
		$this->container = new VendorAPI_PreviousCustomer_CriteriaContainer($this->criteria);
	}

	/**
	 * Tests that getCriteria() return the criteria
	 * @return NULL
	 */
	public function testGetCriteria()
	{
		$this->assertEquals($this->criteria, $this->container->getCriteria());
	}

	/**
	 * Ensure that nothing being passed to the class results in an empty array
	 * @return NULL
	 */
	public function testPassingNothingToClassIsEquivelantToEmptyArray()
	{
		$container = new VendorAPI_PreviousCustomer_CriteriaContainer();

		$this->assertEquals(array(), $container->getCriteria());
	}

	/**
	 * Test that getAppServiceObject returns data that can be used in the call to the app service function.
	 * @return NULL
	 */
	public function testGetAppServiceObject()
	{
		$this->criteria[0]->expects($this->once())
			->method('getAppServiceObject')
			->with(array('key' => 'value'))
			->will($this->returnValue($this->getCriterionObject('ssn', 123456789)));

		$this->criteria[1]->expects($this->once())
			->method('getAppServiceObject')
			->with(array('key' => 'value'))
			->will($this->returnValue($this->getCriterionObject('dob', '1980-01-01')));

		$this->assertEquals(
			array(
				$this->getCriterionObject('ssn', 123456789, 0),
				$this->getCriterionObject('dob', '1980-01-01', 1)
			),
			$this->container->getAppServiceObject(array('key' => 'value'))
		);
	}

	/**
	 * Tests that getAppServiceObject will do nothing to criterion that doesn't have any data.
	 * We are depending on validation to actually fail the app in these cases.
	 * @return NULL
	 */
	public function testGetAppServiceObjectIgnoresCriterionWithNoData()
	{
		$this->criteria[0]->expects($this->once())
			->method('getAppServiceObject')
			->with(array('key' => 'value'))
			->will($this->returnValue(NULL));

		$this->criteria[1]->expects($this->once())
			->method('getAppServiceObject')
			->with(array('key' => 'value'))
			->will($this->returnValue($this->getCriterionObject('dob', '1980-01-01')));

		$this->assertEquals(
			array(
				$this->getCriterionObject('dob', '1980-01-01', 1)
			),
			$this->container->getAppServiceObject(array('key' => 'value'))
		);
	}

	/**
	 * Ensures that post process results will delegate out property to the internal criterion objects.
	 * @return NULL
	 */
	public function testPostProcessResults()
	{
		$result = array();
		$result[0] = new stdClass;
		$result[0]->label = 0;
		$result[0]->results = array(array('id' => 1));

		$result[1] = new stdClass;
		$result[1]->label = 1;
		$result[1]->results = array(array('id' => 2));

		$this->criteria[0]->expects($this->once())
			->method('postProcessResults')
			->with(array(array('id' => 1)))
			->will($this->returnValue(array(array('id' => 10))));

		$this->criteria[1]->expects($this->once())
			->method('postProcessResults')
			->with(array(array('id' => 2)))
			->will($this->returnValue(array(array('id' => 20))));

		$this->assertEquals(
			array(array('id' => 10), array('id' => 20)),
			$this->container->postProcessResults($result)
		);
	}

	/**
	 * Ensure that we don't bother calling post processing when we have no results.
	 * @return NULL
	 */
	public function testPostProcessResultsNotCalledOnEmptyResults()
	{
		$result = array();
		$result[0] = new stdClass;
		$result[0]->label = 0;

		$result[1] = new stdClass;
		$result[1]->label = 1;

		$this->criteria[0]->expects($this->never())
			->method('postProcessResults');

		$this->criteria[1]->expects($this->never())
			->method('postProcessResults');

		$this->assertEquals(
			array(),
			$this->container->postProcessResults($result)
		);
	}

	/**
	 * Tests post Process Results handling single values (not just arrays) properly.
	 * @return NULL
	 */
	public function testPostProcessResultsNotCalledCorrectlyOnSingleResults()
	{
		$result = array();
		$result[0] = new stdClass;
		$result[0]->label = 0;
		$result[0]->results = (object)array('id' => 1);

		$result[1] = new stdClass;
		$result[1]->label = 1;
		$result[1]->results = (object)array('id' => 2);

		$this->criteria[0]->expects($this->once())
			->method('postProcessResults')
			->with(array((object)array('id' => 1)))
			->will($this->returnValue(array()));

		$this->criteria[1]->expects($this->once())
			->method('postProcessResults')
			->with(array((object)array('id' => 2)))
			->will($this->returnValue(array()));

		$this->assertEquals(
			array(),
			$this->container->postProcessResults($result)
		);
	}

	/**
	 * Utility function to ease the pain of creating a criterion object
	 * @param string $criteria
	 * @param string $field
	 * @param string $label
	 * @return <array
	 */
	protected function getCriterionObject($criteria, $field, $label = NULL)
	{
		if (isset($label))
		{
			$criterion = new stdClass;
			$criterion->label = $label;
			$criterion->criteria = array((object)array('searchCriteria' => $criteria, 'field' => $field, 'strategy' => 'is'));

			return $criterion;
		}
		else
		{
			return array((object)array('searchCriteria' => $criteria, 'field' => $field, 'strategy' => 'is'));
		}
	}
}

?>
