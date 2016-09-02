<?php

/**
 * Vendor API funcational test case
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
abstract class FunctionalTestCase extends PHPUnit_Extensions_Database_TestCase
{
	protected $backupGlobals = FALSE;
	/**
	 * @return PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection
	 */
	public function getConnection()
	{
		return $this->createDefaultDBConnection(
			getTestPDODatabase(),
			$GLOBALS['db_name']
		);
	}

	/**
	 * Returns the directory that fixtures/expectations are stored relative to
	 * @return string
	 */
	protected function getDirectory()
	{
		$rc = new ReflectionClass($this);
		return dirname($rc->getFileName());
	}

	/**
	 * Loads and unserializes a state object fixture
	 * @param string $name
	 * @return VendorAPI_StateObject
	 */
	protected function getStateObject($name, $rel = '')
	{
		if ($rel && $rel{0} != '/') $rel = '/'.$rel;

		$file = $this->getDirectory().$rel."/_fixtures/{$name}";
		$state = file_get_contents($file);

		if ($state === FALSE
			|| !(unserialize($state) instanceof VendorAPI_StateObject))
		{
			throw new Exception('Invalid state object, '.$name);
		}

		return $state;
	}

	/**
	 * Loads an XML dataset fixture
	 * @param string $name
	 * @return PHPUnit_Extensions_Database_DataSet_XmlDataSet
	 */
	protected function getFixture($name)
	{
		$file = $this->getDirectory()."/_fixtures/{$name}.xml";
		return new PHPUnit_Extensions_Database_DataSet_XmlDataSet($file);
	}

	/**
	 * Loads and XML expectation
	 * @param string $name
	 * @return PHPUnit_Extensions_Database_DataSet_XmlDataSet
	 */
	protected function getExpectation($name)
	{
		$file = $this->getDirectory()."/_expectations/{$name}.xml";
		return new PHPUnit_Extensions_Database_DataSet_XmlDataSet($file);
	}

	/**
	 * Asserts an XML expectation
	 *
	 * This is a wrapper around assertDataSetsEqual that automatically
	 * creates a filtered dataset for only the columns present in the
	 * expectation.
	 *
	 * @param string $name
	 * @return void
	 */
	protected function assertExpectation($name)
	{
		$expected = $this->getExpectation($name);
		$dataset = $this->getConnection()
			->createDataSet($expected->getTableNames());

		$filter = array();
		foreach ($expected as $name=>$table)
		{
			/* @var $table PHPUnit_Extensions_Database_DataSet_ITable */
			$exclude = array_diff(
				$dataset->getTable($name)->getTableMetaData()->getColumns(),
				$table->getTableMetaData()->getColumns()
			);
			$filter[$name] = $exclude;
		}

		$filtered = new PHPUnit_Extensions_Database_DataSet_DataSetFilter(
			$dataset,
			$filter
		);

		$this->assertDataSetsEqual($expected, $filtered);
	}
}

?>
