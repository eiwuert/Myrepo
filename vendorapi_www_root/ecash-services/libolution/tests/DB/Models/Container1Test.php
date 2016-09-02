<?php
/**
 * DB_Models_Container_1 test case.
 * 
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class DB_Models_Container1Test extends PHPUnit_Framework_TestCase
{
	
	/**
	 * Tests that observers will be updated when validation fails
	 * 
	 * @return void
	 */
	public function testValidationExceptionAlertsObservers()
	{
		$container = new DB_Models_Container_1();
		$observer_1 = $this->getMock("DB_Models_IContainerObserver_1");
		$observer_1->expects($this->once())
			->method("update");
		$container->addObserver($observer_1);

		$validator = $this->getMock("DB_Models_IContainerValidator_1");
		$validator_exception = new DB_Models_ContainerValidatorException_1("Test");
		$validator->expects($this->once())
			->method("validate")
			->will($this->throwException($validator_exception));
		$container->addValidator($validator);
		
		$model = $this->getMock("DB_Models_IWritableModel_1", array(), array(), "", FALSE);
		$model_call_return = "Hello";
		$model->expects($this->once())
			->method("getColumns")
			->will($this->returnValue($model_call_return));
		$container->setAuthoritativeModel($model);
		
		$this->assertEquals($model_call_return, $container->getColumns());
		
		// Verify that the stack was reset after processing by the observers
		$this->assertEquals(0, count($container->getValidationExceptionStack()));
	}

	/**
	 * Data provider for testExceptionCatchingForNonAuthoritativeModels
	 *
	 * @return array
	 */
	public function providerExceptionCatchingForNonAuthoritativeModels()
	{
		return array(
			array(new DB_Models_Container_1(), FALSE), // Default does not continue
			array(new DB_Models_Container_1(TRUE), FALSE), // TRUE does not continur
			array(new DB_Models_Container_1(FALSE), TRUE), // FALSE will continue
		);
		
	}

	/**
	 * Test the constructor parameter funcitonality for swallowing non-authoritative
	 * model exceptions.
	 *
	 * @param DB_Models_Container_1 $container
	 * @param bool $continue
	 * @return void
	 * @dataProvider providerExceptionCatchingForNonAuthoritativeModels
	 */
	public function testExceptionCatchingForNonAuthoritativeModels($container, $continue)
	{
		$second_non_count = $container_info["second_non_count"];
		$auth_model = $this->getMock("DB_Models_IWritableModel_1", array(), array(), "", FALSE);
		$model_call_return = "Hello";
		$auth_model->expects($this->exactly(1))
			->method("setReadOnly")
			->will($this->returnValue($model_call_return));
		$non_auth_model_1 = $this->getMock("DB_Models_IWritableModel_1", array(), array(), "", FALSE);
		$non_auth_model_1->expects($this->exactly(1))
			->method("setReadOnly")
			->will($this->throwException(new Exception("E")));
		$non_auth_model_2 = $this->getMock("DB_Models_IWritableModel_1", array(), array(), "", FALSE);
		$non_auth_model_2->expects($this->exactly((int)$continue))
			->method("setReadOnly")
			->will($this->throwException(new Exception("E")));
			
		$container->setAuthoritativeModel($auth_model);
		$container->addNonAuthoritativeModel($non_auth_model_1);
		$container->addNonAuthoritativeModel($non_auth_model_2);
		
		if (!$continue) $this->setExpectedException("Exception");
		$container->setReadOnly();
	}
	
	
	/**
	 * Data provider for testNecessaryCallsHitAllModels
	 *
	 * @return array
	 */
	public function providerNecessaryCallsHitAllModels()
	{
		return array(
			array("save", array()),
			array("insert", array()),
			array("update", array()),
			array("delete", array()),
			array("fromDbRow", array(array(), "")),
			array("loadBy", array(array())),
			array("loadByKey", array("key")),
			array("setReadOnly", array(FALSE)),
			array("setDeleted", array(FALSE)),
			array("setInsertMode", array(1)),
			array("setDataSynched", array()),
			array("__call", array("unknownMethod", array("method", "parameters"))),
		);
	}

	/**
	 * Test that methods that should be called on all models are indeed called
	 *
	 * @param string $function_name
	 * @param array $args
	 * @return void
	 * @dataProvider providerNecessaryCallsHitAllModels
	 */
	public function testNecessaryCallsHitAllModels($function_name, $args)
	{
		$container = new DB_Models_Container_1();
		$auth_model = $this->getMock("DB_Models_Container_1", array(), array(), "", FALSE);
		$model_call_return = "Hello";
		$auth_model->expects($this->exactly(1))
			->method($function_name);
		$non_auth_model_1 = $this->getMock("DB_Models_Container_1", array(), array(), "", FALSE);
		$non_auth_model_1->expects($this->exactly(1))
			->method($function_name);
		$non_auth_model_2 = $this->getMock("DB_Models_Container_1", array(), array(), "", FALSE);
		$non_auth_model_2->expects($this->exactly(1))
			->method($function_name);
			
		$container->setAuthoritativeModel($auth_model);
		$container->addNonAuthoritativeModel($non_auth_model_1);
		$container->addNonAuthoritativeModel($non_auth_model_2);
		
		call_user_func_array(array($container, $function_name), $args);
		
	}
	
	/**
	 * Data provider for testNecessaryCallsHitAllModels
	 *
	 * @return array
	 */
	public function providerNecessaryCallsHitOnlyOneModel()
	{
		return array(
			array("getColumns", array()),
			array("getTableName", array()),
			array("getPrimaryKey", array()),
			array("getAutoIncrement", array()),
			array("isStored", array()),
			array("isAltered", array(array())),
			array("getAlteredColumnData", array()),
			array("getAffectedRowCount", array()),
			array("getReadOnly", array()),
			array("getColumnData", array()),
		);
	}

	/**
	 * Test that methods that should be called on all models are indeed called
	 *
	 * @param string $function_name
	 * @param array $args
	 * @return void
	 * @dataProvider providerNecessaryCallsHitOnlyOneModel
	 */
	public function testNecessaryCallsHitOnlyOneModel($function_name, $args)
	{
		$container = new DB_Models_Container_1();
		$auth_model = $this->getMock("DB_Models_IWritableModel_1", array(), array(), "", FALSE);
		$model_call_return = "Hello";
		$auth_model->expects($this->exactly(1))
			->method($function_name)
			->will($this->returnValue($model_call_return));
		$non_auth_model_1 = $this->getMock("DB_Models_IWritableModel_1", array(), array(), "", FALSE);
		$non_auth_model_1->expects($this->exactly(0))
			->method($function_name);
		$non_auth_model_2 = $this->getMock("DB_Models_IWritableModel_1", array(), array(), "", FALSE);
		$non_auth_model_2->expects($this->exactly(0))
			->method($function_name);
			
		$container->setAuthoritativeModel($auth_model);
		$container->addNonAuthoritativeModel($non_auth_model_1);
		$container->addNonAuthoritativeModel($non_auth_model_2);
		
		$this->assertEquals(
			$model_call_return,
			call_user_func_array(array($container, $function_name), $args));
		
	}
	
	/**
	 * Data Provider for magic method tests
	 *
	 * @return array
	 */
	public function providerMagicMethods()
	{
		return array(
			array("name", "value"),
			array("NaMe", "VaLue"),
			array("__name", "____value"),
			array("name___________", "value___________"),
		);
	}
	
	/**
	 * Test that container data access will get the value from the authoritative
	 * model
	 *
	 * @param string $name Name to use for get
	 * @param string $value Value to return
	 * @return void
	 * @dataProvider providerMagicMethods
	 */
	public function testGet($name, $value)
	{
		$container = new DB_Models_Container_1();
		
		// Mock model containers because we know they have __get to mock
		// and implement the necessary interface to be used by the container
		// for testing
		$model = $this->getMock("DB_Models_Container_1");
		$model->expects($this->once())
			->method("__get")
			->with($this->equalTo($name))
			->will($this->returnValue($value));
		$container->setAuthoritativeModel($model);
		
		$model = $this->getMock("DB_Models_Container_1");
		$model->expects($this->never())
			->method("__get");
		$container->addNonAuthoritativeModel($model);
		
		$this->assertEquals($value, $container->{$name});
	}
	
	/**
	 * Test that container data access will set the value on all models
	 *
	 * @param string $name Name to use for set
	 * @param string $value Value to set
	 * @return void
	 * @dataProvider providerMagicMethods
	 */
	public function testSet($name, $value)
	{
		$container = new DB_Models_Container_1();
		
		// Mock model containers because we know they have __get to mock
		// and implement the necessary interface to be used by the container
		// for testing
		$model = $this->getMock("DB_Models_Container_1");
		$model->expects($this->once())
			->method("__set")
			->with($this->equalTo($name), $this->equalTo($value));
		$container->setAuthoritativeModel($model);
		
		$model = $this->getMock("DB_Models_Container_1");
		$model->expects($this->once())
			->method("__set")
			->with($this->equalTo($name), $this->equalTo($value));
		$container->addNonAuthoritativeModel($model);
		
		$model = $this->getMock("DB_Models_Container_1");
		$model->expects($this->once())
			->method("__set")
			->with($this->equalTo($name), $this->equalTo($value));
		$container->addNonAuthoritativeModel($model);
		
		$container->{$name} = $value;
	}
	
	/**
	 * Test that container data access will get the value from the authoritative
	 * model
	 *
	 * @param string $name Name to use for set
	 * @param string $value Value to set
	 * @return void
	 * @dataProvider providerMagicMethods
	 */
	public function testIsSet($name, $value)
	{
		$container = new DB_Models_Container_1();
		
		// Mock model containers because we know they have __get to mock
		// and implement the necessary interface to be used by the container
		// for testing
		$model = $this->getMock("DB_Models_Container_1");
		$model->expects($this->once())
			->method("__isset")
			->with($this->equalTo($name))
			->will($this->returnValue($value));
		$container->setAuthoritativeModel($model);
		
		$model = $this->getMock("DB_Models_Container_1");
		$model->expects($this->never())
			->method("__isset");
		$container->addNonAuthoritativeModel($model);
		
		$model = $this->getMock("DB_Models_Container_1");
		$model->expects($this->never())
			->method("__isset");
		$container->addNonAuthoritativeModel($model);
		
		$this->assertEquals($value, isset($container->{$name}));
	}
	
	/**
	 * Test that container data access will unset the value on all models
	 *
	 * @param string $name Name to use for get
	 * @param string $value Value to return
	 * @return void
	 * @dataProvider providerMagicMethods
	 */
	public function testUnset($name, $value)
	{
		$container = new DB_Models_Container_1();
		
		// Mock model containers because we know they have __get to mock
		// and implement the necessary interface to be used by the container
		// for testing
		$model = $this->getMock("DB_Models_Container_1");
		$model->expects($this->once())
			->method("__unset")
			->with($this->equalTo($name));
		$container->setAuthoritativeModel($model);
		
		$model = $this->getMock("DB_Models_Container_1");
		$model->expects($this->once())
			->method("__unset")
			->with($this->equalTo($name));
		$container->addNonAuthoritativeModel($model);
		
		$model = $this->getMock("DB_Models_Container_1");
		$model->expects($this->once())
			->method("__unset")
			->with($this->equalTo($name));
		$container->addNonAuthoritativeModel($model);
		
		unset($container->{$name});
	}
	
	/**
	 * Test loadAllBy funcitonality by different method calls
	 * to make sure that it will return an interator
	 * of containers with the correct models 
	 *
	 * @return void
	 */
	public function testLoadAllBy()
	{
		$arg = array("mwah hah hah");

		$base_container = new DB_Models_Container_1();
		$base_container->setMatchColumns(array("key"));
		$auth = $this->getMock("DB_Models_Container_1");
		
		$base_container->setAuthoritativeModel($auth);

		$non_1 = $this->getMock("DB_Models_Container_1");
		$base_container->addNonAuthoritativeModel($non_1);

		$non_2 = $this->getMock("DB_Models_Container_1");
		$base_container->addNonAuthoritativeModel($non_2);

		foreach (array($auth, $non_1, $non_2) as $master)
		{
			$child1 = $this->getMock("DB_Models_Container_1");
			$child1->expects($this->any())
				->method("__get")
				->with("key")
				->will($this->returnValue(array("child1")));

			$child2 = $this->getMock("DB_Models_Container_1");
			$child2->expects($this->any())
				->method("__get")
				->with("key")
			->will($this->returnValue(array("child2")));
			
			$iterator = new DB_Models_Iterator_1(array($child1, $child2));

			$master->expects($this->once())
				->method("loadAllBy")
				->with($this->equalTo($arg))
				->will($this->returnValue($iterator));
				
		}
		
		$new_container = $base_container->loadAllBy($arg);

		$new_container->current()->key = "child1";
		$new_container->next();
		$new_container->current()->key = "child2";
	}


	/**
	 * Clone a container and validate that new model clones
	 * have been given to the cloned container
	 *
	 * @return void
	 */
	public function testClone()
	{
		$auth_model = new DB_Models_Container_1();

		$non_auth_model = new DB_Models_Container_1();

		$container = new DB_Models_Container_1(TRUE);
		$container->setAuthoritativeModel($auth_model);
		$container->addNonAuthoritativeModel($non_auth_model);
		
		$cloned = clone $container;
		
		$this->assertFalse($auth_model === $cloned->getAuthoritativeModel());

		
		$models = $cloned->getNonAuthoritativeModels();
		$this->assertFalse($models[0] === $non_auth_model);
	}
}

