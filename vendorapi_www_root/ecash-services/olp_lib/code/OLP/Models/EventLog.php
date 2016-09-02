<?php

/**
 * Database model for event_log.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_Models_EventLog extends OLP_Models_RollingModel implements DB_Models_IReferenceable_1, OLP_Models_IAutoSetupRolling
{
	/**
	 * Query the database to show all tables that are real event_log tables.
	 *
	 * @return void
	 */
	public function autoSetTableNames()
	{
		$db = $this->getDatabaseInstance();
		$query = "SHOW TABLE STATUS LIKE 'event\_log%'";
		
		$result = $db->prepare($query);
		$result->execute();
		
		$table_names = array();
		while ($row = $result->fetch())
		{
			if (strcasecmp($row['Engine'], 'MRG_MyISAM'))
			{
				$table_names[] = $row['Name'];
			}
		}
		
		rsort($table_names);
		
		$this->setTableNames($table_names);
		if (isset($table_names[0]))
		{
			$this->setTableName($table_names[0]);
		}
	}
	
	/**
	 * Attaches reference tables to this model and returns the new referenced model.
	 *
	 * @param DB_Models_ModelFactory_1 $factory
	 * @return DB_Models_ReferencedModel_1
	 */
	public function getReferencedModel(DB_Models_ModelFactory_1 $factory)
	{
		$reference_model = new DB_Models_Decorator_ReferencedWritableModel_1($this);
		
		$reference_model->addReferenceTable($factory->getReferenceTable('Events', FALSE));
		$reference_model->addReferenceTable($factory->getReferenceTable('EventResponses', FALSE));
		$reference_model->addReferenceTable(
			$factory->getReferenceTable(Blackbox_ModelFactory::TARGET_COLLECTION_NAME, FALSE),
			'target_id',
			'property_short'
		);
		
		return $reference_model;
	}
	
	/**
	 * List of columns for this model.
	 *
	 * @return array
	 */
	public function getColumns()
	{
		static $columns = array(
			'id',
			'application_id',
			'event_id',
			'response_id',
			'target_id',
			'mode',
			'date_created',
		);
		
		return $columns;
	}
	
	/**
	 * List of primary keys for this model.
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return array('id');
	}
	
	/**
	 * The auto increment column for this model.
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return 'id';
	}
}

?>
