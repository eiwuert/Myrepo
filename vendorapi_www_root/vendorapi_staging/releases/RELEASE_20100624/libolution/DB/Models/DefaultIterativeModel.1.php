<?php
/**
 * @package DB.Models
 */

/**
 * Basic implementation of IterativeModel for a single table/model
 * @see DB_Models_WritableModel_1::loadAllBy()
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class DB_Models_DefaultIterativeModel_1 extends DB_Models_IterativeModel_1
{
	/**
	 * @var DB_IConnection_1
	 */
	protected $db;

	/**
	 * @var DB_Models_WritableModel_1
	 */
	protected $blank;

	/**
	 * @param DB_IConnection_1 $db
	 * @param DB_IStatement_1 $st
	 * @param DB_Models_WritableModel_1 $blank
	 */
	public function __construct(DB_IConnection_1 $db, DB_IStatement_1 $st, DB_Models_WritableModel_1 $blank)
	{
		$this->db = $db;
		$this->statement = $st;
		$this->blank = $blank;
	}

	/**
	 * Returns the class name of the "top level" model
	 * @return string
	 */
	public function getClassName()
	{
		return get_class($this->blank);
	}

	/**
	 * Returns the active database connection
	 *
	 * @param int $db_inst
	 * @return DB_IConnection_1
	 */
	public function getDatabaseInstance($db_inst = DB_Models_DatabaseModel_1::DB_INST_WRITE)
	{
		return $this->db;
	}

	/**
	 * Creates an instance of the models from the database row
	 *
	 * @param array $row
	 * @return DB_Models_WritableModel_1
	 */
	protected function createInstance(array $row)
	{
		/* @var $model DB_Models_WritableModel_1 */
		$model = clone $this->blank;
		$model->fromDbRow($row);

		return $model;
	}
}

?>