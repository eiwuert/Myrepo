<?php

/**
 * Database model for correct_word
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_Models_CorrectWord extends OLP_Models_WritableModel implements DB_Models_IReferenceable_1
{
	/**
	 * Attaches reference tables to this model and returns the new referenced model.
	 *
	 * @param DB_Models_ModelFactory_1 $factory
	 * @return DB_Models_ReferencedModel_1
	 */
	public function getReferencedModel(DB_Models_ModelFactory_1 $factory)
	{
		$correct_type_table = $factory->getReferenceTable('CorrectType');
		
		$reference_model = new DB_Models_Decorator_ReferencedWritableModel_1($this);
		$reference_model->addReferenceTable($correct_type_table);
		
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
			'correct_word_id',
			'date_created',
			'correct_type_id',
			'original_word',
			'replacement_word',
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
		return array('correct_word_id');
	}
	
	/**
	 * The auto increment column for this model.
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return 'correct_word_id';
	}
	
	/**
	 * The table name for this model.
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'correct_word';
	}
}

?>
