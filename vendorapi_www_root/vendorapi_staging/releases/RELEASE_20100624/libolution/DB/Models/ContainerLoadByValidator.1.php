<?php
/**
 * Container validator to verify load syncronization
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class DB_Models_ContainerLoadByValidator_1 implements DB_Models_IContainerValidator_1
{
	/**
	 * Array of functions to watch and validate
	 *
	 * @var array
	 */
	protected $watch_functions = array("loadBy", "loadByKey");
	
	/**
	 * 
	 * @param DB_Models_IContainer_1 $container Container to validate 
	 * @param string $function_name Name of container function 
	 * @param array $function_args Array of arguments passed to container function 
	 * @return void 
	 * @throws DB_Models_ContainerValidatorException_1
	 * @see DB_Models_IContainerValidator_1::validate()
	 */
	public function validate(DB_Models_IContainer_1 $container, $function_name, array $function_args)
	{	
		// If the function executed is not one of the functions we are watching, return
		if (!in_array($function_name, $this->watch_functions)) return;

		// Get the authoritative model for validation
		$authoritative_model = $container->getAuthoritativeModel();
		
		// If the authoritative model does not support getColumns() throw an exception
		if (!($authoritative_model instanceof DB_Models_IWritableModel_1))
		{
			throw new DB_Models_ContainerValidatorException_1(
				"Unable to validate: authoritative model does not implement DB_Models_IWritableModel_1.");
		}

		// Get the list of columns to validate from the authoritative model 
		$columns = $authoritative_model->getColumns();
		
		$unmatched = array();
		
		// Iterate through the non-authoritative models and validate
		foreach ($container->getNonAuthoritativeModels() as $model)
		{
			foreach ($columns as $column)
			{
				if ($authoritative_model->{$column} != $model->{$column})
				{
					$unmatched[$column]["auth"] = $authoritative_model->{$column};
					$unmatched[$column]["non-auth"] = $model->{$column};
				}
			}
		}
		
		if (count($unmatched) > 0)
		{
			$message = "Discrepancies found by DB_Models_ContainerLoadByValidator_1:";
			foreach ($unmatched as $column => $values)
			{
				$message .= " Column: " . $column;
				foreach ($values as $key => $value)
				{
					$message .= " " . $key . ": " . $value;
				}
			}
		}
	}
}
?>