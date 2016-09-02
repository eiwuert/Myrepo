<?php
/**
 * Blackbox Reference Model class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
abstract class Blackbox_Models_Reference_Model extends DB_Models_ReferenceModel_1
{
	/**
	 * Database instance handler.
	 *
	 * @var Blackbox_Models_DatabaseInstanceHandler
	 */
	protected $db_instance_handler;

	/**
	 * Sets the database instance handler or sets the database intance if given
	 *
	 * @param DB_IConnection_1 $db
	 * @return void
	 */
	public function __construct(DB_IConnection_1 $db = NULL)
	{
		if (is_null($db))
		{
			$this->db_instance_handler = new Blackbox_Models_DatabaseInstanceHandler();
		}

		parent::__construct($db);
	}
}
