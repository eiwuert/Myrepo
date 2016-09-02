<?php

class VendorAPI_StateObjectPersistorTest extends PHPUnit_Framework_TestCase
{
	protected function setUp()
	{
		$this->_state = new VendorAPI_StateObject();
		$this->_persistor = new VendorAPI_StateObjectPersistor($this->_state);
	}

	protected function tearDown()
	{
		$this->_state = NULL;
		$this->_persistor = NULL;
	}

	public function testLoadAllByReturnsEmptyWhenNoMatchingRows()
	{
		$model = $this->getMock('PersistorTestModel1', array('loadBy', 'loadAllBy'));
		$model->expects($this->any())->method('loadAllBy')->will($this->returnValue(array()));

		$list = $this->_persistor->loadBy($model, array('name' => 'george'));
		$this->assertTrue(empty($list));

		$this->_state->createMultiPart('test');

		$list = $this->_persistor->loadBy($model, array('name' => 'george'));
		$this->assertTrue(empty($list));

		$this->_state->test->append(array('name' => 'bob', 'phone' => '1234567890'));

		$list = $this->_persistor->loadBy($model, array('name' => 'george'));
		$this->assertTrue(empty($list));
	}

	public function testLoadAllByLoadsFromModel()
	{
		$model = $this->getMock('PersistorTestModel1', array('loadBy', 'loadAllBy'));
		$model->expects($this->once())
			->method('loadAllBy')
			->with(array('name' => 'bob'))
			->will($this->returnValue(array()));

		$this->_persistor->loadAllBy($model, array('name' => 'bob'));
	}

	public function testLoadAllByUsesSinglePart()
	{
		$model = $this->getMock('PersistorTestModel1', array('loadBy', 'loadAllBy'));
		$model->expects($this->any())->method('loadAllBy')->will($this->returnValue(array()));
		$model->expects($this->any())->method('loadBy')->will($this->returnValue(TRUE));

		$this->_state->createPart('test');
		$this->_state->test->name = 'jill';
		$this->_state->test->phone = '7021234567';

		$list = $this->_persistor->loadAllBy($model, array('name' => 'jill'));
		$this->assertEquals(1, count($list));
		$this->assertEquals('jill', $list[0]->name);
		$this->assertEquals('7021234567', $list[0]->phone);
	}

	public function testLoadAllByLoadsPartialRowWithFullKey()
	{
		$model = $this->getMock('PersistorTestModel1', array('loadBy', 'loadAllBy'));
		$model->expects($this->any())->method('loadAllBy')->will($this->returnValue(array()));

		$this->_state->createMultiPart('test');
		$this->_state->test->append(array('test_id' => 1, 'phone' => '1234567890'));

		$model->expects($this->once())
			->method('loadBy')
			->with(array('test_id' => 1))
			->will($this->returnValue(TRUE));

		$this->_persistor->loadAllBy($model, array('phone' => '1234567890'));
	}

	public function testLoadAllByDoesNotLoadPartialRowWhenMissingAutoIncrement()
	{
		$model = $this->getMock('PersistorTestModel1', array('loadBy', 'loadAllBy'));
		$model->expects($this->any())->method('loadAllBy')->will($this->returnValue(array()));

		$this->_state->createMultiPart('test');
		$this->_state->test->append(array('name' => 'bob', 'phone' => '1234567890'));

		$model->expects($this->never())->method('loadBy');
		$this->_persistor->loadAllBy($model, array('phone' => '1234567890'));
	}

	public function testLoadAllByReusesModelInstances()
	{
		$model1 = new PersistorTestModel1(); $model1->test_id = 1; $model1->name = 'george';
		$list1 = $this->getMock('PersistorTestModel1', array('loadAllBy'));
		$list1->expects($this->any())->method('loadAllBy')->will($this->returnValue(array($model1)));

		$model2 = new PersistorTestModel1(); $model2->test_id = 1; $model2->name = 'george';
		$list2 = $this->getMock('PersistorTestModel1', array('loadAllBy'));
		$list2->expects($this->any())->method('loadAllBy')->will($this->returnValue(array($model2)));

		$result1 = $this->_persistor->loadAllBy($list1, array('name' => 'george'));
		$result2 = $this->_persistor->loadAllBy($list2, array('name' => 'george'));

		$this->assertSame($result1[0], $result2[0]);
	}

	public function testMatchingRowFromDatabaseIsReturned()
	{
		$model1 = new PersistorTestModel1();
		$model1->test_id = 1;
		$model1->name = 'george';
		$model1->phone = '7021234567';
		$model1->setDataSynched();

		$model = $this->getMock('PersistorTestModel1', array('loadAllBy'));
		$model->expects($this->any())
			->method('loadAllBy')
			->will($this->returnValue(array($model1)));

		$list = $this->_persistor->loadAllBy($model, array('name' => 'george'));

		$this->assertEquals(1, count($list));
		$this->assertEquals($model1, $list[0]);
	}

	public function testRowModifiedToNotMatchIsNotReturned()
	{
		$model1 = new PersistorTestModel1();
		$model1->test_id = 1;
		$model1->name = 'george';
		$model1->phone = '7021234567';
		$model1->setDataSynched();

		$model = $this->getMock('PersistorTestModel1', array('loadBy', 'loadAllBy'));
		$model->expects($this->any())
			->method('loadBy')
			->will($this->returnValue(TRUE));
		$model->expects($this->any())
			->method('loadAllBy')
			->will($this->returnValue(array($model1)));

		$this->_state->createMultiPart('test');
		$this->_state->test->append(array('test_id' => 1, 'name' => 'bob'));

		$this->_persistor->setVersion(0);
		$list = $this->_persistor->loadAllBy($model, array('name' => 'george'));

		$this->assertTrue(empty($list));
	}

	public function testMatchingRowInStateObjectIsReturned()
	{
		$model = $this->getMock('PersistorTestModel1', array('loadBy', 'loadAllBy'));
		$model->expects($this->any())
			->method('loadBy')
			->will($this->returnValue(TRUE));
		$model->expects($this->any())
			->method('loadAllBy')
			->will($this->returnValue(array()));

		$this->_state->createMultiPart('test');
		$this->_state->test->append(array('test_id' => 1, 'name' => 'george', 'phone' => '1234567890'));

		$this->_persistor->setVersion(0);
		$list = $this->_persistor->loadAllBy($model, array('name' => 'george'));

		$this->assertEquals(1, count($list));
		$this->assertType('PersistorTestModel1', $list[0]);
		$this->assertEquals(1, $list[0]->test_id);
		$this->assertEquals('george', $list[0]->name);
		$this->assertEquals('1234567890', $list[0]->phone);
	}

	public function testMatchingRowBelowCurrentVersionIsNotReturned()
	{
		$model = $this->getMock('PersistorTestModel1', array('loadBy', 'loadAllBy'));
		$model->expects($this->any())->method('loadBy')->will($this->returnValue(TRUE));
		$model->expects($this->any())->method('loadAllBy')->will($this->returnValue(array()));

		$this->_state->createMultiPart('test');
		$this->_state->test->append(array('test_id' => 1, 'name' => 'george', 'phone' => '1234567890'));

		$this->_persistor->setVersion(2);
		$list = $this->_persistor->loadAllBy($model, array('name' => 'george'));

		$this->assertTrue(empty($list));
	}

	public function testLoadByReturnsFalseWhenNoMatchingRows()
	{
		$model = $this->getMock('PersistorTestModel1', array('loadBy'));
		$model->expects($this->any())->method('loadBy')->will($this->returnValue(FALSE));

		$result = $this->_persistor->loadBy($model, array('name' => 'john'));
		$this->assertFalse($result);

		// now create an empty part
		$this->_state->createMultiPart('test');

		$result = $this->_persistor->loadBy($model, array('name' => 'john'));
		$this->assertFalse($result);

		// now add a non-matching row
		$this->_state->test->append(array('name' => 'bob', 'phone' => '1234567890'));

		$result = $this->_persistor->loadBy($model, array('name' => 'john'));
		$this->assertFalse($result);
	}

	public function testLoadByReusesModelInstances()
	{
		$model1 = $this->getMock('PersistorTestModel1', array('loadBy'));
		$model1->expects($this->any())->method('loadBy')->will($this->returnValue(TRUE));
		$model1->test_id = 1;
		$model1->name = 'george';

		$model2 = $this->getMock('PersistorTestModel1', array('loadBy'));
		$model2->expects($this->any())->method('loadBy')->will($this->returnValue(TRUE));
		$model2->test_id = 1;
		$model2->name = 'george';

		$result1 = $this->_persistor->loadBy($model1, array('name' => 'george'));
		$result2 = $this->_persistor->loadBy($model2, array('name' => 'george'));

		$this->assertType('DB_Models_WritableModel_1', $result1);
		$this->assertType('DB_Models_WritableModel_1', $result2);
		$this->assertSame($result1, $result2);
	}

	public function testLoadByReturnsSameModelInstanceThatWasSaved()
	{
		$model1 = new PersistorTestModel1();
		$model1->name = 'test';
		$model1->phone = '1234567890';
		$this->_persistor->save($model1);

		$model = $this->getMock('PersistorTestModel1', array('loadBy', 'loadAllBy'));
		$model->expects($this->never())->method('loadBy')->will($this->returnValue(FALSE));
		$model->expects($this->never())->method('loadAllBy')->will($this->returnValue(array()));

		$model2 = $this->_persistor->loadBy($model, array('name' => 'test'));
		$this->assertSame($model2, $model1);
	}

	public function testLoadByLoadsPartialRowFromDatabase()
	{
		$this->_state->createMultiPart('test');
		$this->_state->test->append(array('test_id' => 1, 'name' => 'bob'));

		$model = $this->getMock('PersistorTestModel1', array('loadBy', 'loadAllBy'));
		$model->expects($this->once())
			->method('loadBy')
			->with(array('test_id' => 1))
			->will($this->returnValue(TRUE));

		$this->_persistor->loadBy($model, array('name' => 'bob'));
	}

	public function testLoadByDoesNotLoadModelsWithMissingAutoIncrement()
	{
		$this->_state->createMultiPart('test');
		$this->_state->test->append(array('name' => 'bob', 'phone' => '1234567890'));

		$model = $this->getMock('PersistorTestModel1', array('loadBy'));
		$model->expects($this->never())->method('loadBy');

		$found = $this->_persistor->loadBy($model, array('name' => 'bob'));
		$this->assertFalse(empty($found));
	}

	public function testCannotSaveModelWithoutCompleteKey()
	{
		$model = new PersistorTestModel2();
		$model->date_created = time();

		$this->setExpectedException('Exception');
		$this->_persistor->save($model);
	}

	public function testSaveCreatesPartInStateObject()
	{
		$model = new PersistorTestModel1();
		$model->name = 'test';
		$model->phone = '1234567890';

		$this->assertFalse($this->_state->isMultiPart('test'));
		$this->_persistor->save($model);
		$this->assertTrue($this->_state->isMultiPart('test'));
	}

	public function testSaveAddsRowToStateObject()
	{
		$model = new PersistorTestModel1();
		$model->name = 'test';
		$model->phone = '1234567890';

		$this->_state->createMultiPart('test');

		$this->assertEquals(0, count($this->_state->test->getData()));
		$this->_persistor->save($model);
		$this->assertEquals(1, count($this->_state->test->getData()));
	}

	public function testModelIsNotAlteredAfterSave()
	{
		// this model converts date_created to/from a date string/timestamp, see below
		$model = new PersistorTestModel1();
		$model->name = 'bob';

		$this->_persistor->save($model);
		$this->assertFalse($model->isAltered());
	}

	public function testModelIsStoredAfterSave()
	{
		// this model converts date_created to/from a date string/timestamp, see below
		$model = new PersistorTestModel1();
		$model->name = 'bob';

		$this->_persistor->save($model);
		$this->assertTrue($model->isStored());
	}

	public function testSaveStoresPrimaryKey()
	{
		$model = new PersistorTestModel1();
		$model->test_id = 1;
		$model->name = 'bob';
		$model->phone = '1234567890';
		$model->setDataSynched();

		// modify just the name...
		$model->name = 'george';

		// pre-assertions
		$this->assertEquals(array('name' => 'george'), $model->getAlteredColumnData());
		$this->assertFalse($this->_state->isMultiPart('test'));

		$this->_persistor->save($model);

		$version = reset($this->_state->test[0]->getData());
		$this->assertEquals(array('test_id' => 1, 'name' => 'george'), $version);
	}

	public function testSaveFromLoadedModelUpdatesSameIndex()
	{
		$this->_state->createMultiPart('test');
		$this->_state->test->append(array('name' => 'bill', 'phone' => '1234567890'));
		$this->_state->updateVersion(TRUE);

		$model = $this->getMock('PersistorTestModel1', array('loadBy'));
		$model->expects($this->any())->method('loadBy')->will($this->returnValue(FALSE));
		$model1 = $this->_persistor->loadBy($model, array('name' => 'bill'));

		$model1->phone = '7021234567';
		$this->_persistor->save($model1);

		$expected = array(
			1 => array(
				0 => array('name' => 'bill', 'phone' => '1234567890'),
			),
			2 => array(
				0 => array('phone' => '7021234567'),
			)
		);

		$data = $this->_state->test[0]->orderedVersions();
		$this->assertEquals($expected, $data);
	}

	public function testSaveUpdatesSinglePart()
	{
		$this->_state->createPart('test');
		$this->_state->test->test_id = 1;
		$this->_state->test->name = 'bill';
		$this->_state->test->phone = '1234567890';

		$model = $this->getMock('PersistorTestModel1', array('loadBy'));
		$model->expects($this->any())->method('loadBy')->will($this->returnValue(FALSE));
		$model1 = $this->_persistor->loadBy($model, array('name' => 'bill'));

		$model1->phone = '7021234567';
		$this->_persistor->save($model1);

		$this->assertEquals('7021234567', $this->_state->test->phone);
	}

	public function testSaveUsesDatabaseFormats()
	{
		// this model converts date_created to/from a date string/timestamp, see below
		$model = new PersistorTestModel2();
		$model->test_id = 1;
		$model->date_created = 1243882051; //2009-06-01 11:47:31

		$this->_persistor->save($model);

		$data = $this->_state->test->getData();
		$this->assertEquals('2009-06-01 11:47:31', $data[0]['date_created']);
	}
	
	public function testSaveReferenceDataOnLoadBySuccess()
	{
		$loadby = array(
			'col1' => "Hello World"
		);
		
		$model2 = $this->getMock('VendorAPITestModel', array('loadBy'));
		$model2->expects($this->once())->method('loadBy')
			->with($loadby)
			->will($this->returnValue(TRUE));
			
		$model2->col1 = "Hello World";
		$model2->test_id = 24;
		
		$model = new PersistorTestModel1();
		$model->related_model_id = new VendorAPI_ReferenceColumn_Locator($model2);
		$model->related_model_id->addLoadByMethod('loadBy', $loadby);
		
		$this->_persistor->save($model);
		$data = $this->_state->test->getData();
		$this->assertEquals($model2->test_id, $data[0]['related_model_id']);
	}
	
	public function testSaveReferenceDataOnLoadByFail()
	{
		$loadby = array(
			'col1' => "Hello World"
		);
		
		$model2 = $this->getMock('VendorAPITestModel', array('loadBy'));
		$model2->expects($this->once())->method('loadBy')
			->with($loadby)
			->will($this->returnValue(FALSE));
			
		$model2->col1 = "Hello World";
				
		$model = new PersistorTestModel1();
		$model->related_model_id = new VendorAPI_ReferenceColumn_Locator($model2);
		$model->related_model_id->addLoadByMethod('loadBy', $loadby);
		
		$this->_persistor->save($model);
		$data = $this->_state->test->getData();
		$this->assertEquals($model->related_model_id, $data[0]['related_model_id']);
		$this->assertEquals(
			array(
				'test' => array(
					array(
						'col1' => $model2->col1
					),
				),
			),
			$this->_state->getReferenceData()
		);
		
	}
}

class PersistorTestModel1 extends DB_Models_WritableModel_1
{
	public function getTableName()
	{
		return 'test';
	}

	public function getColumns()
	{
		return array(
			'test_id',
			'name',
			'phone',
			'related_model_id'
		);
	}

	public function getPrimaryKey()
	{
		return array('test_id');
	}

	public function getAutoIncrement()
	{
		return 'test_id';
	}
}

class PersistorTestModel2 extends DB_Models_WritableModel_1
{
	public function getTableName()
	{
		return 'test';
	}

	public function getColumns()
	{
		return array(
			'test_id',
			'date_created',
		);
	}

	public function getPrimaryKey()
	{
		return array('test_id');
	}

	public function getAutoIncrement()
	{
		return  NULL;
	}

	public function getColumnData()
	{
		$data = parent::getColumnData();
		$data['date_created'] = date('Y-m-d H:i:s', $data['date_created']);
		return $data;
	}

	public function setColumnData(array $data)
	{
		$data['date_created'] = strtotime($data['date_created']);
		parent::setColumnData($data);
	}
}

?>
