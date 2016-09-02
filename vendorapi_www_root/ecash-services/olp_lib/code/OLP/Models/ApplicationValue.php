<?php

/** Database model for application_value
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class OLP_Models_ApplicationValue extends OLP_Models_WritableModel implements DB_Models_IReferenceable_1
{
	/**
	 * Constructor sets the insert mode
	 *
	 * @param DB_IConnection_1 $db Database connection for the model
	 * @return void
	 */
	public function __construct(DB_IConnection_1 $db = NULL)
	{
		parent::__construct($db);
		$this->setInsertMode(self::INSERT_ON_DUPLICATE_KEY_UPDATE);
	}
	
	/** Attaches reference tables to this model and returns the new referenced model.
	 *
	 * @param DB_Models_ModelFactory_1 $factory
	 * @return DB_Models_ReferencedModel_1
	 */
	public function getReferencedModel(DB_Models_ModelFactory_1 $factory)
	{
		$reference_table = $factory->getReferenceTable('ApplicationValueField');
		
		$reference_model = new DB_Models_Decorator_ReferencedWritableModel_1($this);
		$reference_model->addReferenceTable($reference_table);
		
		return $reference_model;
	}
	
	/** List of columns for this model.
	 *
	 * @return array
	 */
	public function getColumns()
	{
		static $columns = array(
			'application_value_id',
			'date_created',
			'date_modified',
			'application_id',
			'application_value_field_id',
			'value',
		);
		
		return $columns;
	}
	
	/** List of primary keys for this model.
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return array('application_value_id');
	}
	
	/** The auto increment column for this model.
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return 'application_value_id';
	}
	
	/** The table name for this model.
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'application_value';
	}
	
}

?>
