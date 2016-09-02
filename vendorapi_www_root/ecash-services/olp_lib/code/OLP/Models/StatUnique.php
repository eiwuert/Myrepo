<?php

/** Database model for stat_unique
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_Models_StatUnique extends OLP_Models_WritableModel implements DB_Models_IReferenceable_1
{
	/** Sets insert mode.
	 *
	 * @param DB_IConnection_1 $db
	 */
	public function __construct(DB_IConnection_1 $db = NULL)
	{
		parent::__construct($db);
		
		$this->setInsertMode(self::INSERT_IGNORE);
	}
	
	/** Attaches reference tables to this model and returns the new referenced model.
	 *
	 * @param DB_Models_ModelFactory_1 $factory
	 * @return DB_Models_ReferencedModel_1
	 */
	public function getReferencedModel(DB_Models_ModelFactory_1 $factory)
	{
		$stat_name_table = $factory->getReferenceTable('StatName');
		$stat_track_table = $factory->getReferenceTable('StatTrack', FALSE);
		
		$reference_model = new DB_Models_Decorator_ReferencedWritableModel_1($this);
		$reference_model->addReferenceTable($stat_name_table, NULL, 'stat_name');
		$reference_model->addReferenceTable($stat_track_table);
		
		return $reference_model;
	}
	
	/** List of columns for this model.
	 *
	 * @return array
	 */
	public function getColumns()
	{
		static $columns = array(
			'stat_track_id',
			'application_id',
			'stat_name_id',
			'date_created',
		);
		
		return $columns;
	}
	
	/** List of primary keys for this model.
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return array('stat_track_id', 'application_id', 'stat_name_id');
	}
	
	/** The auto increment column for this model.
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return NULL;
	}
	
	/** The table name for this model.
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'stat_unique';
	}
}

?>
