<?php

/**
 * To ease making decoraters for WritableModels.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
abstract class DB_Models_Decorator_WritableModel_1 extends DB_Models_WritableModel_1
{
	/**
	 * @var DB_Models_WritableModel_1
	 */
	protected $model;

	/**
	 * Decorates a writable model.
	 *
	 * @param DB_Models_WritableModel_1 $model
	 */
	public function __construct(DB_Models_WritableModel_1 $model)
	{
		$this->model = $model;
	}

	/**
	 * Clone handler
	 * @return void
	 */
	public function __clone()
	{
		$this->model = clone $this->model;
	}

	/**
	 * Passing this along to the wrapped model.
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public function __set($name, $value)
	{
		$this->model->__set($name, $value);
	}

	/**
	 * Passing this along to the wrapped model.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		return $this->model->__get($name);
	}

	/**
	 * Passing this along to the wrapped model.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function __isset($name)
	{
		return $this->model->__isset($name);
	}

	/**
	 * Passing this along to the wrapped model.
	 *
	 * @param string $name
	 * @return void
	 */
	public function __unset($name)
	{
		$this->model->__unset($name);
	}

	/**
	 * Passing this along to the wrapped model.
	 *
	 * @return array
	 */
	public function getColumnData()
	{
		return $this->model->getColumnData();
	}

	/**
	 * Passing this along to the wrapped model.
	 *
	 * @param array $data
	 * @return void
	 */
	public function setColumnData($data)
	{
		$this->model->setColumnData($data);
	}

	/**
	 * Passing this along to the wrapped model.
	 *
	 * @param mixed $key
	 * @return bool
	 */
	public function loadByKey($key)
	{
		return $this->model->loadBy($key);
	}

	/**
	 * Passing this along to the wrapped model.
	 *
	 * @param array $where_args
	 * @return bool
	 */
	public function loadBy(array $where_args)
	{
		return $this->model->loadBy($where_args);
	}

	/**
	 * Passing this along to the wrapped model.
	 *
	 * @param array $where_args
	 * @return DB_Models_IterativeModel_1
	 */
	public function loadAllBy(array $where_args = NULL)
	{
		return $this->model->loadAllBy($where_args);
	}

	/**
	 * Passing this along to the wrapped model.
	 *
	 * @return array
	 */
	public function getColumns()
	{
		return $this->model->getColumns();
	}

	/**
	 * Passing this along to the wrapped model.
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return $this->model->getTableName();
	}

	/**
	 * Passing this along to the wrapped model.
	 *
	 * @param int $db_inst
	 * @return DB_IConnection_1
	 */
	public function getDatabaseInstance($db_inst = DB_Models_DatabaseModel_1::DB_INST_WRITE)
	{
		return $this->model->getDatabaseInstance($db_inst);
	}

	/**
	 * Passing this along to the wrapped model.
	 *
	 * @return bool
	 */
	public function isStored()
	{
		return $this->model->isStored();
	}

	/**
	 * Passing this along to the wrapped model.
	 *
	 * @return bool
	 */
	public function save()
	{
		return $this->model->save();
	}

	/**
	 * Passing this along to the wrapped model.
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return $this->model->getPrimaryKey();
	}

	/**
	 * Passing this along to the wrapped model.
	 *
	 * @return bool
	 */
	public function isAltered()
	{
		return $this->model->isAltered();
	}

	/**
	 * Passing this along to the wrapped model.
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return $this->model->getAutoIncrement();
	}

	/**
	 * Passing this along to the wrapped model.
	 *
	 * @param int $mode
	 * @return void
	 */
	public function setInsertMode($mode = self::INSERT_STANDARD)
	{
		return $this->model->setInsertMode($mode);
	}

	/**
	 * Passing this along to the wrapped model.
	 *
	 * @return bool
	 */
	public function insert()
	{
		return $this->model->insert();
	}

	/**
	 * Passing this along to the wrapped model.
	 *
	 * @return bool
	 */
	public function update()
	{
		return $this->model->update();
	}

	/**
	 * Passing this along to the wrapped model.
	 *
	 * @return bool
	 */
	public function delete()
	{
		return $this->model->delete();
	}

	/**
	 * Passing this along to the wrapped model.
	 *
	 * @param array $db_row Associative data array
	 * @param string $column_prefix prefix (if any) to the column names in the array
	 * @return void
	 */
	public function fromDbRow(array $db_row, $column_prefix = '')
	{
		return $this->model->fromDbRow($db_row, $column_prefix);
	}

	/**
	 * Passing this along to the wrapped model.
	 *
	 * @return void
	 */
	public function setDataSynched()
	{
		return $this->model->setDataSynched();
	}

	/**
	 * Passing this along to the wrapped model.
	 *
	 * @return int
	 */
	public function getAffectedRowCount()
	{
		return $this->model->getAffectedRowCount();
	}

	/**
	 * Passing this along to the wrapped model.
	 *
		 * @param boolean $state
		 * @return void
	 */
	public function setReadOnly($state = FALSE)
	{
		return $this->model->setReadOnly($state);
	}

	/**
	 * Passing this along to the wrapped model.
	 *
	 * @return bool
	 */
	public function getReadOnly()
	{
		return $this->model->getReadOnly();
	}

	/**
	 * Passing this along to the wrapped model.
	 *
	 * @param bool $delete
	 * @return void
	 */
	public function setDeleted($delete)
	{
		return $this->model->setDeleted($delete);
	}

	/**
	 * Passing this along to the wrapped model.
	 *
	 * @return bool
	 */
	public function getDeleted()
	{
		return $this->model->getDeleted();
	}
}

?>
