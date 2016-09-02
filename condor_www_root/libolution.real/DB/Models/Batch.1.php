<?php

/**
 * A transaction/batch for model modifications
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class DB_Models_Batch_1
{
	/**
	 * @var DB_IConnection_1
	 */
	protected $db;

	/**
	 * @var array
	 */
	protected $save = array();

	/**
	 * @var array
	 */
	protected $delete = array();

	/**
	 * @param DB_IConnection_1 $db
	 * @return void
	 */
	public function __construct(DB_IConnection_1 $db)
	{
		$this->db = $db;
	}

	/**
	 * Schedules a model to be saved
	 *
	 * @param DB_Models_WritableModel_1 $m
	 * @return void
	 */
	public function save(DB_Models_WritableModel_1 $m)
	{
		if ($m->getDeleted())
		{
			$this->delete[] = $m;
		}
		else if ($m->isAltered())
		{
			$class = get_class($m);
			$this->getList($class)->add($m);
		}
	}

	/**
	 * Schedules a model to be deleted
	 * @deprecated use $model->setDeleted(TRUE); $batch->save($model);
	 * @param DB_Models_WritableModel_1 $m
	 * @return void
	 */
	public function delete(DB_Models_WritableModel_1 $m)
	{
		$m->setDeleted(TRUE);
		$this->save($m);
	}

	/**
	 * Executes all pending operations
	 * @return void
	 */
	public function execute()
	{
		foreach ($this->delete as $model) $model->save();
		foreach ($this->save as $list) $list->save();

		$this->clear();
	}

	/**
	 * Resets the internal arrays
	 * @return void
	 */
	public function clear()
	{
		$this->delete = array();
		$this->save = array();
	}

	/**
	 * Gets a model list for the given class type
	 *
	 * @param string $name
	 * @return DB_Models_ModelList_1
	 */
	protected function getList($name)
	{
		if (isset($this->save[$name]))
		{
			return $this->save[$name];
		}
		return $this->save[$name] = new DB_Models_ModelList_1($name, $this->db);
	}
}

?>