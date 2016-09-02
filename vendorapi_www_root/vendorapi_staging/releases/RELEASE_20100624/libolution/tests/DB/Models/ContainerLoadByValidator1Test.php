<?php
/**
 * DB_Models_ContainerLoadByValidator_1 test case.
 * 
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class DB_Models_ContainerLoadByValidator1Test extends PHPUnit_Framework_TestCase
{
	
	/**
	 * Tests validating a container with a function name not being watched
	 * does nothing
	 * 
	 * @return void
	 */
	public function testValidateUnwatchedFunction()
	{
		$container = $this->getMock("DB_Models_IContainer_1");
		$container->expects($this->never())
			->method("getAuthoritativeModel");
		$container->expects($this->never())
			->method("getNonAuthoritativeModels");
	
		$validator = new DB_Models_ContainerLoadByValidator_1();
		$validator->validate($container, "non-being-watched", array());
	}

	/**
	 * Tests validating a container with an authoritative model that does not
	 * implement a required interface will throw a validation exception
	 * and not process the non-authoritative models
	 * 
	 * @return void
	 */
	public function testValidateBadAuthoritativeModel()
	{
		$container = $this->getMock("DB_Models_IContainer_1");
		$container->expects($this->once())
			->method("getAuthoritativeModel")
			->will($this->returnValue(new stdClass()));
		$container->expects($this->never())
			->method("getNonAuthoritativeModels");
		$this->setExpectedException("DB_Models_ContainerValidatorException_1");
	
		$validator = new DB_Models_ContainerLoadByValidator_1();
		$validator->validate($container, "loadBy", array());
	}
	
	/**
	 * Tests that validating a container in which the models are synchrozined
	 * checks all models and throws no errors
	 * 
	 * @return void
	 */
	public function testValidateSynchronizedModels()
	{
		$non_auth_models = array();
		for ($i = 0; $i < 5; $i++)
		{
			$model = $this->getMock("DB_Models_WritableModel_1", array(), array(), "", FALSE);
			$model->expects($this->exactly(3))
				->method("__get")
				->will($this->returnArgument(0));
			$non_auth_models[] = $model;
		}
		
		$cols = array("one", "two", "three");
		$auth_model = $this->getMock("DB_Models_WritableModel_1", array(), array(), "", FALSE);
		$auth_model->expects($this->once())
			->method("getColumns")
			->will($this->returnValue(array("one", "two", "three")));
		$auth_model->expects($this->exactly(count($cols) * count($non_auth_models)))
			->method("__get")
			->will($this->returnArgument(0));
			
		$container = $this->getMock("DB_Models_IContainer_1");
		$container->expects($this->once())
			->method("getAuthoritativeModel")
			->will($this->returnValue($auth_model));
		
		
		$container->expects($this->once())
			->method("getNonAuthoritativeModels")
			->will($this->returnValue($non_auth_models));
	
		$validator = new DB_Models_ContainerLoadByValidator_1();
		$validator->validate($container, "loadBy", array());
	}

}

