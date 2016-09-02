<?php

/**
 * Builds models.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
abstract class DB_Models_ModelFactory_1
{
	/**
	 * An array of reference tables.
	 *
	 * @var array
	 */
	protected $reference_tables = array();
	
	/**
	 * Returns a DB_Models_WritableModel_1.
	 *
	 * @param string $model_name Name of the model.
	 * @return DB_Models_WritableModel_1
	 */
	abstract public function getModel($model_name);
	
	/**
	 * Returns a referenced model.
	 *
	 * @param string $model_name Name of the model.
	 * @return DB_Models_Decorator_ReferencedWritableModel_1
	 */
	public function getReferencedModel($model_name)
	{
		$model = $this->getModel($model_name);
		
		if (!$model instanceof DB_Models_IReferenceable_1)
		{
			throw new Exception(sprintf(
				"Model '%s' is not referenceable.",
				$model_name
			));
		}
		
		$reference_model = $model->getReferencedModel($this);
		
		return $reference_model;
	}
	
	/**
	 * Return an instance of the reference table.
	 *
	 * @param string $model_name Name of the model.
	 * @param bool $prefetch TRUE to automatically load the reference table.
	 * @param array $where Where arguments.
	 * @return DB_Models_ReferenceTable_1
	 */
	public function getReferenceTable($model_name, $prefetch = FALSE, array $where = NULL)
	{
		$model_hash = $this->getModelHash($model_name, $where);
		
		if (!isset($this->reference_tables[$model_hash]))
		{
			$model = $this->getModel($model_name);
			$this->reference_tables[$model_hash] = new DB_Models_ReferenceTable_1($model, $prefetch, $where);
		}
		
		return $this->reference_tables[$model_hash];
	}
	
	/**
	 * Hashes model names. The where argument is order-specific of parameters.
	 *
	 * @param string $model_name
	 * @param array $where
	 * @return string
	 */
	protected function getModelHash($model_name, array $where = NULL)
	{
		$hash = $model_name;
		
		// An empty where array is the same as NULL, so hash to the same.
		if (!empty($where)) $hash .= ':' . serialize($where);
		
		return $hash;
	}
}

?>
