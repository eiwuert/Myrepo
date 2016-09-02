<?php

/** Represents a row in a reference table
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
abstract class OLP_Models_References_ReferenceModel extends DB_Models_ReferenceModel_1
{
	/**
	 * Need to set insert mode to insert ignore to prevent race conditions.
	 *
	 * @param DB_IConnection_1 $db
	 */
	public function __construct(DB_IConnection_1 $db = NULL)
	{
		parent::__construct($db);
		
		$this->setInsertMode(self::INSERT_IGNORE);
	}
	
	/** Returns the active database connection
	 *
	 * @param int $db_inst
	 * @return DB_IConnection_1
	 */
	public function getDatabaseInstance($db_inst = DB_Models_DatabaseModel_1::DB_INST_WRITE)
	{
		if (isset($this->db))
		{
			return $this->db;
		}
		
		return DB_Connection::getInstance('BLACKBOX', BFW_MODE);
	}
	
	/** In the case of two different processes trying to isnert the same
	 * row, the first one will succeed. The second will return 0 rows
	 * inserted. In that case, we need to select from the database to find
	 * our ColumnID based upon our ColumnName.
	 *
	 * @return bool
	 */
	public function insert()
	{
		$return = parent::insert();
		
		if (!$return)
		{
			// We didn't insert a new row, try loading from the database.
			$return = $this->loadBy(array($this->getColumnName() => $this->getName()));
		}
		
		return $return;
	}
}

?>
