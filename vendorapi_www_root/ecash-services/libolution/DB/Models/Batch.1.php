<?php

/**
 * A batch for model modifications
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class DB_Models_Batch_1
{
	/**
	 * @var DB_IConnection_1
	 */
	protected $db;

	/**
	 * @var bool
	 */
	protected $use_list;

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
	 * @param bool $use_list Use DB_Models_List_1 internally
	 * @return void
	 */
	public function __construct(DB_IConnection_1 $db = NULL, $use_list = TRUE)
	{
		if (!$db
			&& $use_list)
		{
			throw new InvalidArgumentException('Must supply database connection');
		}

		$this->db = $db;
		$this->use_list = $use_list;
	}

	/**
	 * Schedules a model to be saved
	 *
	 * @param DB_Models_WritableModel_1 $m
	 * @return void
	 */
	public function save(DB_Models_IWritableModel_1 $m)
	{
		if ($m->getDeleted())
		{
			$this->delete[] = $m;
		}
		elseif ($m->isAltered())
		{
			if ($this->use_list)
			{
				$class = get_class($m);
				$this->getList($class)->add($m);
			}
			else
			{
				$this->save[] = $m;
			}
		}
	}

	/**
	 * Schedules a model to be deleted
	 * @deprecated use $model->setDeleted(TRUE); $batch->save($model);
	 * @param DB_Models_WritableModel_1 $m
	 * @return void
	 */
	public function delete(DB_Models_IWritableModel_1 $m)
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

		// assumes that list and model save() has the same signature
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