<?php

/**
 * Models the map between the target table and the target_tag table.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package Blackbox
 */
class Blackbox_Models_TargetTagMap extends Blackbox_Models_WriteableModel 
{
	/**
	 * Returns a list of the columns in the table this object models.
	 * 
	 * @return array 
	 * @see DB_Models_WritableModel_1::getColumns()
	 */
	public function getColumns() {
		static $columns = array();
		
		if (empty($columns))
		{
			$columns[] = 'tag_id';
			$columns[] = 'target_id';
		}
		
		return $columns;
	}
	
	/**
	 * Return the name of the table this object models.
	 * 
	 * @return string 
	 * @see DB_Models_WritableModel_1::getTableName()
	 */
	public function getTableName() {
		return 'target_tag_map';
	}
	
	/**
	 * Returns the primary keys for the table this model represents.
	 * 
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return array('tag_id', 'target_id');
	}
	
	/**
	 * Returns the auto-increment column for the table this model represents.
	 * @return NULL|string
	 */
	public function getAutoIncrement()
	{
		return NULL;
	}
}

?>
