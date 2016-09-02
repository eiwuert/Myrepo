<?php

/**
 * A collection of iterative models that acts like an iterative model.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_DB_IterativeModelCollection extends DB_Models_IterativeModel_1
{
	/**
	 * The current iterative model index.
	 *
	 * @var int
	 */
	protected $current_model_index = 0;
	
	/**
	 * An array of iterative models we have not loaded yet.
	 *
	 * @var array
	 */
	protected $models = array();
	
	/**
	 * Adds an iterative model to the list.
	 *
	 * @param DB_Models_IterativeModel_1
	 * @return void
	 */
	public function add(DB_Models_IterativeModel_1 $model)
	{
		$this->models[] = $model;
	}
	
	/**
	 * Returns the current iterative model.
	 *
	 * @return DB_Models_IterativeModel_1
	 */
	protected function getCurrentModel()
	{
		if (!isset($this->models[$this->current_model_index]))
		{
			throw new RuntimeException("Attempting to get the current iterative model, but none exist.");
		}
		
		return $this->models[$this->current_model_index];
	}
	
	/**
	 * Goes to the next valid iterative model, if any.
	 *
	 * @return bool
	 */
	protected function goToNextIterativeModel()
	{
		$next = FALSE;
		
		while (!$next && isset($this->models[$this->current_model_index + 1]))
		{
			$this->current_model_index++;
			$this->models[$this->current_model_index]->rewind();
			$next = $this->models[$this->current_model_index]->valid();
		}
		
		return $next;
	}
	
	/**
	 * Not needed.
	 *
	 * @param array $db_row
	 * @return DB_Models_DatabaseModel
	 */
	protected function createInstance(array $db_row)
	{
		throw new RuntimeException("Calling a function (createInstance) that is not implemented.");
	}
	
	/**
	 * Uses the current iterative model's getClassName().
	 *
	 * @return string
	 */
	public function getClassName()
	{
		$current_model = $this->getCurrentModel();
		
		return $current_model->getClassName();
	}
	
	/**
	 * Returns the number of rows for all iterative models.
	 *
	 * @return int
	 */
	public function count()
	{
		$total = 0;
		
		foreach ($this->models AS $model)
		{
			$total += $model->count();
		}
		
		return $total;
	}
	
	/**
	 * Resets all iterative models.
	 *
	 * @return void
	 */
	public function rewind()
	{
		for ($i = $this->current_model_index; $i >= 0; $i--)
		{
			if (isset($this->models[$i]))
			{
				$this->models[$i]->rewind();
			}
		}
		
		$this->current_model_index = 0;
	}
	
	/**
	 * Asks our current iterative model.
	 *
	 * @return DB_Models_ModelBase
	 */
	public function current()
	{
		$current = $this->getCurrentModel()->current();
		
		if ($current === NULL && $this->goToNextIterativeModel())
		{
			$current = $this->getCurrentModel()->current();
		}
		
		return $current;
	}
	
	/**
	 * Asks our current iterative model.
	 *
	 * @return array
	 */
	public function currentRawData()
	{
		return $this->getCurrentModel()->currentRawData();
	}
	
	/**
	 * Asks our current iterative model.
	 *
	 * @return string
	 */
	public function key()
	{
		return $this->getCurrentModel()->key();
	}
	
	/**
	 * Gets the next model. If our current iterative model is empty, go to the next one.
	 *
	 * @return DB_Models_ModelBase
	 */
	public function next()
	{
		$next = $this->getCurrentModel()->next();
		
		if (!$next)
		{
			while ($this->goToNextIterativeModel())
			{
				$next = $this->valid();
				
				if ($next) break;
			}
		}
		
		return $next;
	}
	
	/**
	 * Asks our current iterative model.
	 *
	 * @return DB_Models_ModelBase
	 */
	public function valid()
	{
		$valid = $this->getCurrentModel()->valid();
		
		if (!$valid && $this->goToNextIterativeModel())
		{
			$valid = $this->getCurrentModel()->valid();
		}
		
		return $valid;
	}
	
	/**
	 * Merges all model arrays together.
	 *
	 * @return array
	 */
	public function toArray()
	{
		$a = array();
		
		foreach ($this->models AS $model)
		{
			$a = array_merge($a, $model->toArray());
		}
		
		return $a;
	}
	
	/**
	 * Asks our current iterative model.
	 *
	 * @return DB_IConnection_1
	 */
	public function getDatabaseInstance($db_inst = DB_Models_DatabaseModel_1::DB_INST_WRITE)
	{
		return $this->getCurrentModel()->getDatabaseInstance($db_inst);
	}
}

?>
