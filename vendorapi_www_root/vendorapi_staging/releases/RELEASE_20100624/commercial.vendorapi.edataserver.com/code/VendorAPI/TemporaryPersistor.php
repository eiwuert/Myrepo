<?php

/**
 * Temporarily persists models. This allows code to be written against
 * the {@link VendorAPI_IModelPersistor} interface to be used prior to the
 * data actually being persisted to a database.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_TemporaryPersistor implements VendorAPI_IModelPersistor
{
	/**
	 * @var VendorAPI_IModelPersistor
	 */
	protected $persistor;

	/**
	 * @var array
	 */
	protected $models = array();

	public function __construct(VendorAPI_IModelPersistor $persistor = NULL)
	{
		$this->persistor = $persistor;
	}

	/**
	 * Saves a model to the persistor
	 *
	 * NOTE: this does NOT set the saved flag(s) on the model.
	 *
	 * @param DB_Model_WritableModel_1 $model
	 * @return bool
	 */
	public function save(DB_Models_IWritableModel_1 $model)
	{
		$hash = spl_object_hash($model);
		if (!isset($this->models[$hash]))
		{
			$this->models[$hash] = $model;
		}
	}
	/**
	 * Updates all models with a new application_id
	 *
	 * @param Integer $application_id
	 * @return void
	 */
	public function updateApplicationId($application_id)
	{
		foreach ($this->models as $model)
		{
			if ($model->getTableName() == 'react_affiliation')
			{
				$model->react_application_id = $application_id;
			}
			elseif (in_array('application_id', $model->getColumns()))
			{
				$model->application_id = $application_id;
			}
		}
	}

	/**
	 * Saves all models to another persistor
	 *
	 * @param VendorAPI_IModelPersistor $persistor
	 * @return void
	 */
	public function saveTo(VendorAPI_IModelPersistor $persistor)
	{
		foreach ($this->models as $model)
		{
			/* @var $model DB_Models_IWritableModel_1 */
			if ($model->isAltered())
			{
				$persistor->save($model);
				$model->setDataSynched();
			}
		}
	}

	/**
	 * Loads data into a model of the given type and returns it
	 *
	 * Returns FALSE if a matching model cannot be loaded.
	 * NOTE: The model returned may not be the same instance passed in.
	 *
	 * @param DB_Model_WritableModel_1 $model
	 * @param array $where
	 * @return DB_Model_WritableModel_1|bool
	 */
	public function loadBy(DB_Models_IWritableModel_1 $model, array $where)
	{
		$table = $model->getTableName();

		if ($this->persistor
			&& ($m = $this->persistor->loadBy($model, $where)) !== FALSE)
		{
			return $m;
		}

		foreach ($this->models as $m)
		{
			if ($m->getTableName() == $table
				&& $this->matches($m, $where))
			{
				return $m;
			}
		}
		return FALSE;
	}

	/**
	 * Loads all matching models and returns them as an array
	 *
	 * @param DB_Models_IWritableModel_1 $model
	 * @param array $where
	 * @return array
	 */
	public function loadAllBy(DB_Models_IWritableModel_1 $model, array $where, $check_db = TRUE)
	{
		$table = $model->getTableName();

			$found = $this->persistor && $check_db
				? $this->persistor->loadAllBy($model, $where)
				: array();

		foreach ($this->models as $m)
		{
			if ($m->getTableName() == $table
				&& $this->matches($m, $where))
			{
				$found[] = $m;
			}
		}

		return $found;
	}

	/**
	 * Matches the given model against the provided conditions
	 * @param DB_Models_IWritableModel_1 $model
	 * @param array $where
	 * @return bool
	 */
	protected function matches(DB_Models_IWritableModel_1 $model, array $where)
	{
		foreach ($where as $col=>$value)
		{
			if ($model->{$col} != $value)
			{
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * Set the version of the object we're persisting
	 * @param Integer $version
	 * @return void
	 */
	public function setVersion($version) {}
}

?>
