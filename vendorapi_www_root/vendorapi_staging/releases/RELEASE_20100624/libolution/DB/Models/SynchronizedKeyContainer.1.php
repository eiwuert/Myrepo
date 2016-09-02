<?php
/**
 * @see DB_Models_Container_1
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class DB_Models_SynchronizedKeyContainer_1 extends DB_Models_Container_1
{
	/**
	 * @see DB_Models_Container_1#save
	 * @return bool
	 */
	public function save()
	{
		return $this->synchronizedKeyCall(__FUNCTION__, array());
	}

	/**
	 * @see DB_Models_Container_1#insert
	 * @return int
	 */
	public function insert()
	{
		return $this->synchronizedKeyCall(__FUNCTION__, array());
	}
	
	/**
	 * @see DB_Models_Container_1#update
	 * @return int
	 */
	public function update()
	{
		return $this->synchronizedKeyCall(__FUNCTION__, array());
	}
	
	/**
	 * Make a call to all models synchronizing the promary key values from the authoritative
	 * model to the notn-authoritative models
	 * @param string $method_name
	 * @param array $parameters
	 * @return mixed
	 */
	protected function synchronizedKeyCall($function_name, array $args)
	{
		$key_vals = array();
		
		$model = $this->getAuthoritativeModel();
		$return_value = call_user_func_array(
			array($model, $function_name),
			$args);

		foreach ($model->getPrimaryKey() as $key)
		{
			$key_vals[$key] = $model->{$key};
		}
		
		foreach ($this->getNonAuthoritativeModels() as $model)
		{
			try
			{
				foreach ($key_vals as $key => $value)
				{
					$model->{$key} = $value;
				}
				call_user_func_array(
					array($model, $function_name),
					$args);
			}
			catch (Exception $e)
			{
				$this->handleNonAuthoritativeModelException($e);
			}
		}
		$this->validate($function_name, $args);
		return $return_value;
	}
	
}
?>
