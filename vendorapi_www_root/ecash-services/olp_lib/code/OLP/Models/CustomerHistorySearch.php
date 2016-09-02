<?php

/** Database model for customer_history_search
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_Models_CustomerHistorySearch extends OLP_Models_WritableModel implements DB_Models_IReferenceable_1
{
	/** Attaches reference tables to this model and returns the new referenced model.
	 *
	 * @param DB_Models_ModelFactory_1 $factory
	 * @return DB_Models_ReferencedModel_1
	 */
	public function getReferencedModel(DB_Models_ModelFactory_1 $factory)
	{
		$customer_history_status_table = $factory->getReferenceTable('CustomerHistoryStatus');
		$target_property_short_table = $factory->getReferenceTable('TargetPropertyShort', FALSE);
		
		$reference_model = new DB_Models_Decorator_ReferencedWritableModel_1($this);
		$reference_model->addReferenceTable($customer_history_status_table, NULL, 'match_status');
		$reference_model->addReferenceTable($target_property_short_table, NULL, NULL, FALSE);
		$reference_model->addReferenceTable($target_property_short_table, 'match_target_id', 'match_property_short', FALSE);
		
		return $reference_model;
	}
	
	/** List of columns for this model.
	 *
	 * @return array
	 */
	public function getColumns()
	{
		static $columns = array(
			'customer_history_search_id',
			'date_created',
			'application_id',
			'target_id',
			'match_application_id',
			'match_target_id',
			'customer_history_status_id',
		);
		
		return $columns;
	}
	
	/** List of primary keys for this model.
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return array('customer_history_search_id');
	}
	
	/** The auto increment column for this model.
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return 'customer_history_search_id';
	}
	
	/** The table name for this model.
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'customer_history_search';
	}
}

?>
