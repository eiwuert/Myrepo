<?php
/**
 * Validator interface for DB_Models_IContainer_1
 * 
 * Container validators will validate data and/or data consistency 
 * within the container
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
interface DB_Models_IContainerValidator_1
{
	/**
	 * Validate the data and/or data integrity of the container based  on the function
	 * called, parameters passed, and current state of the container
	 *
	 * @param DB_Models_IContainer_1 $container Container to validate
	 * @param string $function_name Name of container function
	 * @param array $function_args Array of arguments passed to container function
	 * @return void
	 * @throws DB_Models_ContainerValidatorException_1
	 */
	public function validate(DB_Models_IContainer_1 $container, $function_name, array $function_args);	
	
}
?>