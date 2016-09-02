<?php

/**
 * Tests basic functionality on the Blackbox_Models_WriteableModel class.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class Blackbox_Models_WriteableModelTest extends PHPUnit_Extensions_Database_TestCase
{
	/**
	 * Tests what happens when you pass an array as a where value. (such as 
	 * 'column_name' => array(1, 2, 3);
	 *
	 * @dataProvider multipleValueWhereArgumentProvider
	 * @param array $where
	 * @param array $expected
	 */
	public function testMultipleValueWhereArgument(array $where, array $expected)
	{
		$loader = $this->freshWritableModel();
		
		$models = $loader->loadAllBy($where);
		
		$this->assertTrue((bool)count($models));
		
		$results = array();
		
		foreach ($models as $model)
		{
			$results[$model->target_tag_id] = $model->tag;
		}
		
		ksort($expected);
		ksort($results);
		
		$this->assertEquals($expected, $results, 'Query fetched the wrong objects');
	}
	
	/**
	 * Provide values for {@see testMultipleValueWhereArgument}
	 *
	 * @return array
	 */
	public static function multipleValueWhereArgumentProvider()
	{
		return array(
			array(array('tag' => array('first', 'second')), array('1' => 'first', '2' => 'second')),
			array(array('target_tag_id' => '1', 'tag' => array('first', 'second')), array('1' => 'first')),
		);
	}
	
	// -------------------------------------------------------------------------
	
	/**
	 * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection 
	 * @see PHPUnit_Extensions_Database_TestCase::getConnection()
	 */
	protected function getConnection()
	{
		return $this->createDefaultDBConnection(
			TEST_DB_PDO(TEST_BLACKBOX), 
			TEST_GET_DB_INFO(TEST_BLACKBOX)->name
		);
	}
	
	/**
	 * @return PHPUnit_Extensions_Database_DataSet_IDataSet 
	 * @see PHPUnit_Extensions_Database_TestCase::getDataSet()
	 */
	protected function getDataSet()
	{
		return $this->createXMLDataSet(
			dirname(__FILE__) . '/_fixtures/WriteableModel.xml'
		);
	}
	
	// -------------------------------------------------------------------------
	
	/**
	 * Fixture method.
	 *
	 * @return MockTargetTagModel
	 */
	protected function freshWritableModel()
	{
		return new MockTargetTagModel(TEST_DB_CONNECTOR(TEST_BLACKBOX));
	}
}

// -----------------------------------------------------------------------------

/**
 * Class which imitates a Blackbox_Model for a target tag which has JUST the basic
 * functionality needed for running {@see Blackbox_Models_WriteableModelTest}.
 * 
 * Necessary because of the way PHPUnit's mock system mucks up __get/__set and
 * the way the Blackbox_Models_WriteableModelTest needs to work.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class MockTargetTagModel extends Blackbox_Models_WriteableModel
{
	/**
	 * Implemented to fill requirements for Blackbox_Models_WriteableModel.
	 * @return array
	 */
	public function getColumns()
	{
		return array($this->getPrimaryKey(), 'tag');
	}
	
	/**
	 * Implemented to fill requirements for Blackbox_Models_WriteableModel.
	 * @return string
	 */
	public function getAutoincrement()
	{
		return $this->getPrimaryKey();
	}
	
	/**
	 * Implemented to fill requirements for Blackbox_Models_WriteableModel.
	 * @return string
	 */
	public function getPrimaryKey()
	{
		return 'target_tag_id';
	}
	
	/**
	 * Implemented to fill requirements for Blackbox_Models_WriteableModel.
	 * @return string
	 */
	public function getTableName()
	{
		return 'target_tag';
	}
}
?>