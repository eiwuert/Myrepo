<?php
/** Test class for test.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class PHPUnitDBModelsModelFactoryWritableModelTestClass extends DB_Models_ReferenceModel_1 implements DB_Models_IReferenceable_1
{
	public function getColumns()
	{
		return array('id', 'name');
	}
	
	public function getTableName()
	{
		return NULL;
	}
	
	public function getPrimaryKey()
	{
		return array('id');
	}
	
	public function getAutoIncrement()
	{
		return 'id';
	}
	
	public function getColumnID()
	{
		return 'id';
	}
	
	public function getColumnName()
	{
		return 'name';
	}
	
	public function getReferencedModel(DB_Models_ModelFactory_1 $factory)
	{
		return new DB_Models_Decorator_ReferencedWritableModel_1($this);
	}
}

/** Test class for test.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class PHPUnitDBModelsModelFactoryTestClass extends DB_Models_ModelFactory_1
{
	public function getModel($model_name)
	{
		return new PHPUnitDBModelsModelFactoryWritableModelTestClass();
	}
}

/** Tests the Model Factory abstract class.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class DB_Models_ModelFactoryTest extends PHPUnit_Framework_TestCase
{
	public static function dataProviderGetReferenceTable()
	{
		return array(
			array(
				'object1',
				NULL,
				'object1',
				NULL,
				TRUE,
				'Same name returns same object.',
			),
			
			array(
				'object1',
				NULL,
				'object2',
				NULL,
				FALSE,
				'Different object names return different objects.',
			),
			
			array(
				'object1',
				NULL,
				'object1',
				array(),
				TRUE,
				'Same name with a NULL where and an empty where returns same object.',
			),
			
			array(
				'object1',
				NULL,
				'object1',
				array('id' => 1),
				FALSE,
				'Same name with a NULL where and a filled where returns different objects.',
			),
			
			array(
				'object1',
				array(),
				'object1',
				array('id' => 1),
				FALSE,
				'Same name with an empty where and a filled where returns different objects.',
			),
			
			array(
				'object1',
				array('id' => 1),
				'object1',
				array('id' => 1),
				TRUE,
				'Same name with the same where returns same object.',
			),
			
			array(
				'object1',
				array('id' => 1),
				'object1',
				array('id' => 2),
				FALSE,
				'Same name with different wheres returns different objects.',
			),
			
			array(
				'object1',
				array('id' => 1, 'name' => 'sam'),
				'object1',
				array('id' => 1, 'name' => 'sam'),
				TRUE,
				'Same name with the same complex where returns same object.',
			),
			
			array(
				'object1',
				array('id' => 1, 'name' => 'sam'),
				'object1',
				array('id' => 1, 'name' => 'jane'),
				FALSE,
				'Same name with different complex wheres returns different objects.',
			),
			
			array(
				'object1',
				NULL,
				'object2',
				array(),
				FALSE,
				'Different names with a NULL where and an empty where returns different objects.',
			),
			
			array(
				'object1',
				NULL,
				'object2',
				array('id' => 1),
				FALSE,
				'Different names with a NULL where and a filled where returns different objects.',
			),
			
			array(
				'object1',
				array(),
				'object2',
				array('id' => 1),
				FALSE,
				'Different names with an empty where and a filled where returns different objects.',
			),
			
			array(
				'object1',
				array('id' => 1),
				'object2',
				array('id' => 1),
				FALSE,
				'Different names with the same where returns different objects.',
			),
			
			array(
				'object1',
				array('id' => 1),
				'object2',
				array('id' => 2),
				FALSE,
				'Different names with different wheres returns different objects.',
			),
			
			array(
				'object1',
				array('id' => 1, 'name' => 'sam'),
				'object2',
				array('id' => 1, 'name' => 'sam'),
				FALSE,
				'Different names with the same complex where returns different objects.',
			),
			
			array(
				'object1',
				array('id' => 1, 'name' => 'sam'),
				'object2',
				array('id' => 1, 'name' => 'jane'),
				FALSE,
				'Different names with different complex wheres returns different objects.',
			),
		);
	}
	
	/** Asserts that objects returned via getReferenceTable() are the same or not.
	 *
	 * @dataProvider dataProviderGetReferenceTable
	 *
	 * @param string $object1_name
	 * @param array $object1_where
	 * @param string $object2_name
	 * @param array $object2_where
	 * @param bool $equals
	 * @param string $message
	 * @return void
	 */
	public function testGetReferenceTable($object1_name, $object1_where, $object2_name, $object2_where, $equals, $message)
	{
		$factory = new PHPUnitDBModelsModelFactoryTestClass();
		
		$object1 = $factory->getReferenceTable($object1_name, FALSE, $object1_where);
		$object2 = $factory->getReferenceTable($object2_name, FALSE, $object2_where);
		
		// Get the object hash to assert that they are equal or not
		$objects_equal = spl_object_hash($object1) === spl_object_hash($object2);
		
		$this->assertEquals($equals, $objects_equal, $message);
	}
	
	/** Verify that getting a referenced model will return a referenced writable model.
	 *
	 * @return void
	 */
	public function testGetReferencedModel()
	{
		$factory = new PHPUnitDBModelsModelFactoryTestClass();
		
		$model = $factory->getReferencedModel('object1');
		
		$this->assertTrue($model instanceof DB_Models_Decorator_ReferencedWritableModel_1);
	}
}

?>
