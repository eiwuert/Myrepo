<?php

/**
 * Represents an entry in the target_tag table.
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package Blackbox
 */
class Blackbox_Models_TargetTag extends Blackbox_Models_WriteableModel 
{
	/**
	 * Get the columns for the table this model represents.
	 *
	 * @return array list of column names
	 */
	public function getColumns() {
		static $columns = array();
		
		if (empty($columns))
		{
			$columns[] = $this->getAutoIncrement();
			$columns[] = 'tag';
		}
		return $columns;
	}
		
	/**
	 * Returns the name of the table this model represents.
	 *
	 * @return string table name
	 */
	public function getTableName() {
		return 'target_tag';
	}		
}

?>
