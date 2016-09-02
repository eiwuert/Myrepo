<?php
/**
 * Storage management for target data
 * 
 * @author Matthew Jump <matthew.jump@sellingsource.com>
 */
class OLPBlackbox_Factory_Storage_TargetData implements OLPBlackbox_Factory_IStorage
{
	/**
	 * Constructor for the OLPBlackbox_Factory_Storage_TargetData object
	 *
	 * @param DB_Database_1 $db_connection
	 * @return void
	 */
	public function __construct(DB_Database_1 $db_connection)
	{
		$this->db_connection = $db_connection;
	}

	/**
	 * Add target data lists to (memcache) storage
	 *
	 * @param string $key
	 * @param array $object
	 * @return void
	 */
	public function add($key, $object)
	{
		$this->getConfig()->memcache->set('TargetData/'.$key,$object);
	}

	/**
	 * Retrieve an associative target data list array by key (target_id)
	 *
	 * @param string $key
	 * @return array
	 */
	public function get($key)
	{
		$target_data_array = $this->getConfig()->memcache->get('TargetData/'.$key);
		if (!$target_data_array)
		{
			$target_data_model = new Blackbox_Models_TargetData($this->db_connection);
			$target_data_results = $target_data_model->loadAllBy(array('target_id'=>$key));
			$model_factory = new Blackbox_ModelFactory($this->db_connection);
			$target_data_type_ref = $model_factory->getReferenceTable('TargetDataType');
			// Convert the iterative model into an associative array for easy access
			$target_data_array = array();
			foreach ($target_data_results as $target_data_result)
			{
				if($type_name = $target_data_type_ref->toName($target_data_result->target_data_type_id))
				{
					$target_data_array[$type_name] = $target_data_result->data_value;
				}
			}
			$this->add($key,$target_data_array);
		}
		return $target_data_array;
	}
	
	/**
	 * Returns the ModelFactory we want to reuse for static functions here.
	 *
	 * @return Blackbox_ModelFactory
	 */
	protected function getModelFactory()
	{
		return new Blackbox_ModelFactory($this->getDbConnection());
	}
}

?>
