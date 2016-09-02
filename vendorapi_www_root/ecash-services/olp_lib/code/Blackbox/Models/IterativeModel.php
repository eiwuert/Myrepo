<?php
/**
 * Abstract class for the Blackbox Iterative Model.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
abstract class Blackbox_Models_IterativeModel extends DB_Models_IterativeModel_1
{
	/**
	 * Enter description here...
	 *
	 * @var Blackbox_Models_DatabaseInstanceHandler
	 */
	private $db_instance_handler;
	
	/**
	 * Returns a database instance.
	 *
	 * @param unknown_type $db_inst
	 * @return DB_Database_1
	 */
	public function getDatabaseInstance($db_inst = DB_Models_DatabaseModel_1::DB_INST_WRITE)
	{
		return $this->db_instance_handler->getDatabaseInstance($db_inst);
	}
	
	/**
	 * Creates an instance of the underlying model.
	 *
	 * @param array $db_row
	 * @return mixed
	 */
	public function createInstance(array $db_row)
	{
		$name = $this->getClassName();
		$model = new $name();
		$model->loadBy($db_row);
		return $model;
	}
}
