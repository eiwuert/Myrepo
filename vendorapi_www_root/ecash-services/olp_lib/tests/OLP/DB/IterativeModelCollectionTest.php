<?php

/**
 * Test OLP_DB_IterativeModelCollection.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_DB_IterativeModelCollectionTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests that getClassName() will call the iterator's model.
	 *
	 * @return void
	 */
	public function testGetClassName()
	{
		$mock_class_name = 'MOCKED_CLASS_NAME';
		
		$iterative_collection = new OLP_DB_IterativeModelCollection();
		
		$iterative_model = $this->getMock(
			'DB_Models_IterativeModel_1',
			array(
				'getClassName',
				'getDatabaseInstance',
				'createInstance',
			)
		);
		$iterative_model->expects($this->once())
			->method('getClassName')
			->will($this->returnValue($mock_class_name));
		
		$iterative_collection->add($iterative_model);
		
		$this->assertEquals($mock_class_name, $iterative_collection->getClassName());
	}
	
	/**
	 * Tests that if we have no base iterator, getClassName() fails.
	 *
	 * @expectedException RuntimeException
	 *
	 * @return void
	 */
	public function testGetClassNameException()
	{
		$iterative_collection = new OLP_DB_IterativeModelCollection();
		$iterative_collection->getClassName();
	}
	
	/**
	 * Data provider for testCount().
	 *
	 * @return array
	 */
	public static function dataProviderTestCount()
	{
		return array(
			array(
				array(0),
				0,
			),
			
			array(
				array(1),
				1,
			),
			
			array(
				array(1, 2, 3),
				6,
			),
			
			array(
				array(0, 1, 1, 2, 3, 5, 8, 13, 21, 34),
				88,
			),
		);
	}
	
	/**
	 * Tests count().
	 *
	 * @dataProvider dataProviderTestCount
	 *
	 * @return void
	 */
	public function testCount(array $models_count, $total)
	{
		$iterative_collection = new OLP_DB_IterativeModelCollection();
		
		foreach ($models_count AS $count)
		{
			$iterative_model = $this->getMock(
				'DB_Models_IterativeModel_1',
				array(
					'getClassName',
					'getDatabaseInstance',
					'createInstance',
					'count',
				)
			);
			$iterative_model->expects($this->once())
				->method('count')
				->will($this->returnValue($count));
			
			$iterative_collection->add($iterative_model);
		}
		
		$this->assertEquals($total, $iterative_collection->count());
	}
	
	/**
	 * Tests rewind() on a simple collection.
	 *
	 * @return void
	 */
	public function testRewind()
	{
		$iterative_collection = new OLP_DB_IterativeModelCollection();
		
		$iterative_model = $this->getMock(
			'DB_Models_IterativeModel_1',
			array(
				'getClassName',
				'getDatabaseInstance',
				'createInstance',
				'rewind',
			)
		);
		$iterative_model->expects($this->once())
			->method('rewind');
		
		$iterative_collection->add($iterative_model);
		
		$iterative_collection->rewind();
	}
	
	/**
	 * Tests rewind() on a simple collection that was looped already.
	 *
	 * @return void
	 */
	public function testRewindLooped()
	{
		$iterative_collection = new OLP_DB_IterativeModelCollection();
		
		$iterative_model = $this->getMock(
			'DB_Models_IterativeModel_1',
			array(
				'getClassName',
				'getDatabaseInstance',
				'createInstance',
				'rewind',
				'valid',
				'next',
			)
		);
		$iterative_model->expects($this->exactly(2))
			->method('rewind');
		$iterative_model->expects($this->once())
			->method('valid')
			->will($this->returnValue(FALSE));
		$iterative_model->expects($this->once())
			->method('valid')
			->will($this->returnValue(NULL));
		$iterative_collection->add($iterative_model);
		
		foreach ($iterative_collection AS $item)
		{
			$this->fail('Foreach should never find an item.');
		}
		
		$iterative_collection->rewind();
	}
	
	/**
	 * Tests rewind() on a complex collection. The first model was cycled,
	 * the second one returned a result, and the last one will never be called.
	 *
	 * @return void
	 */
	public function testRewindComplex()
	{
		$loop_first_result = 'LOOP FIRST RESULT';
		
		$iterative_collection = new OLP_DB_IterativeModelCollection();
		
		// This one will be "empty" and get cycled.
		$iterative_model = $this->getMock(
			'DB_Models_IterativeModel_1',
			array(
				'getClassName',
				'getDatabaseInstance',
				'createInstance',
				'rewind',
				'valid',
				'current',
				'next',
			)
		);
		$iterative_model->expects($this->exactly(2))
			->method('rewind');
		$iterative_model->expects($this->once())
			->method('valid')
			->will($this->returnValue(FALSE));
		$iterative_model->expects($this->never())
			->method('current');
		$iterative_model->expects($this->never())
			->method('next');
		$iterative_collection->add($iterative_model);
		
		// This one will return a value, and stop the loop.
		$iterative_model = $this->getMock(
			'DB_Models_IterativeModel_1',
			array(
				'getClassName',
				'getDatabaseInstance',
				'createInstance',
				'rewind',
				'valid',
				'current',
				'next',
			)
		);
		$iterative_model->expects($this->exactly(2))
			->method('rewind');
		$iterative_model->expects($this->any())
			->method('valid')
			->will($this->returnValue(TRUE));
		$iterative_model->expects($this->once())
			->method('current')
			->will($this->returnValue($loop_first_result));
		$iterative_model->expects($this->never())
			->method('next')
			->will($this->returnValue('This "creates" the current() instance.'));
		$iterative_collection->add($iterative_model);
		
		// This one will never be called, as it is has not been looped into yet
		$iterative_model = $this->getMock(
			'DB_Models_IterativeModel_1',
			array(
				'getClassName',
				'getDatabaseInstance',
				'createInstance',
				'rewind',
				'valid',
				'current',
				'next',
			)
		);
		$iterative_model->expects($this->never())
			->method('rewind');
		$iterative_model->expects($this->never())
			->method('valid');
		$iterative_model->expects($this->never())
			->method('current');
		$iterative_model->expects($this->never())
			->method('next');
		$iterative_collection->add($iterative_model);
		
		// Run the test
		foreach ($iterative_collection AS $item)
		{
			$this->assertEquals($loop_first_result, $item);
			break; // Break on first loop result
		}
		$iterative_collection->rewind();
	}
	
	/**
	 * Tests next().
	 *
	 * @return void
	 */
	public function testNext()
	{
		$iterative_collection = new OLP_DB_IterativeModelCollection();
		
		// Will return 5 items, then be empty
		$iterative_model = $this->getMock(
			'DB_Models_IterativeModel_1',
			array(
				'getClassName',
				'getDatabaseInstance',
				'createInstance',
				'rewind',
				'valid',
				'current',
				'next',
			)
		);
		$iterative_model->expects($this->once())
			->method('rewind');
		$iterative_model->expects($this->exactly(6))
			->method('valid')
			->will($this->returnCallBack(array($this, 'callbackTestNext_Valid_A')));
		$iterative_model->expects($this->exactly(5))
			->method('current')
			->will($this->returnCallBack(array($this, 'callbackTestNext_Current_A')));
		$iterative_model->expects($this->exactly(5))
			->method('next')
			->will($this->returnCallBack(array($this, 'callbackTestNext_Next_A')));
		$iterative_collection->add($iterative_model);
		
		// Will return 3 items, then be empty
		$iterative_model = $this->getMock(
			'DB_Models_IterativeModel_1',
			array(
				'getClassName',
				'getDatabaseInstance',
				'createInstance',
				'rewind',
				'valid',
				'current',
				'next',
			)
		);
		$iterative_model->expects($this->once())
			->method('rewind');
		$iterative_model->expects($this->exactly(5)) // Because when switching, test valid()
			->method('valid')
			->will($this->returnCallBack(array($this, 'callbackTestNext_Valid_B')));
		$iterative_model->expects($this->exactly(3))
			->method('current')
			->will($this->returnCallBack(array($this, 'callbackTestNext_Current_B')));
		$iterative_model->expects($this->exactly(3))
			->method('next')
			->will($this->returnCallBack(array($this, 'callbackTestNext_Next_B')));
		$iterative_collection->add($iterative_model);
		
		$i = 1;
		foreach ($iterative_collection AS $item)
		{
			$this->assertEquals($i++, $item);
		}
		$this->assertEquals(9, $i);
	}
	
	/**
	 * Callback for testNext()'s first iterative model, for valid().
	 *
	 * @return mixed
	 */
	public function callbackTestNext_Valid_A()
	{
		static $i = 5;
		
		return $i-- > 0;
	}
	
	/**
	 * Callback for testNext()'s first iterative model, for next().
	 *
	 * @return mixed
	 */
	public function callbackTestNext_Next_A()
	{
		static $i = 5;
		
		return $i-- > 0;
	}
	
	/**
	 * Callback for testNext()'s first iterative model, for current().
	 *
	 * @return mixed
	 */
	public function callbackTestNext_Current_A()
	{
		static $i = 1;
		
		return $i++;
	}
	
	/**
	 * Callback for testNext()'s second iterative model, for valid().
	 *
	 * @return mixed
	 */
	public function callbackTestNext_Valid_B()
	{
		static $i = 4; // This is 4 instead of 3, because switching to next model calls valid() to verify
		
		return $i-- > 0;
	}
	
	/**
	 * Callback for testNext()'s second iterative model, for next().
	 *
	 * @return mixed
	 */
	public function callbackTestNext_Next_B()
	{
		static $i = 3;
		
		return $i-- > 0;
	}
	
	/**
	 * Callback for testNext()'s second iterative model, for current().
	 *
	 * @return mixed
	 */
	public function callbackTestNext_Current_B()
	{
		static $i = 6;
		
		return $i++;
	}
}

?>