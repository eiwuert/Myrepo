<?php

/**
 * Database model for mailing_list_address
 *
 * @author Chris Barmonde <chris.barmonde@sellingsource.com>
 */
class OLP_Models_MailingListAddress extends OLP_Models_WritableModel implements DB_Models_IReferenceable_1
{
	/**
	 * Attaches reference tables to this model and returns the new referenced model.
	 *
	 * @param DB_Models_ModelFactory_1 $factory
	 * @return DB_Models_ReferencedModel_1
	 */
	public function getReferencedModel(DB_Models_ModelFactory_1 $factory)
	{
		$application_type_table = $factory->getReferenceTable('MailingList');
		
		$reference_model = new DB_Models_Decorator_ReferencedWritableModel_1($this);
		$reference_model->addReferenceTable($application_type_table);
		
		return $reference_model;
	}
	
	/**
	 * Get the table's columns
	 *
	 * @return array
	 */
	public function getColumns()
	{
		static $columns = array(
			'mailing_list_id',
			'email_address',
		);
		
		return $columns;
	}
	
	/**
	 * Get the primary key(s) for the table
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return array('mailing_list_id', 'email_address');
	}
	
	/**
	 * The auto increment column for the table
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return NULL;
	}
	
	/**
	 * Get the table's name
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'mailing_list_address';
	}
}

?>
