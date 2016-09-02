<?php
/**
 * Model for the target_data table.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Blackbox_Models_TargetData extends Blackbox_Models_WriteableModel
{
	/**
	 * Returns the columns in the table.
	 *
	 * @return array
	 */
	public function getColumns()
	{
		static $columns = array(
			'target_id', 'target_data_type_id', 'data_value'
		);
		return $columns;
	}
	
	/**
	 * Returns the primary keys.
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return array('target_id', 'target_data_type_id');
	}
	
	/**
	 * Returns the auto increment column.
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return NULL;
	}
	
	/**
	 * Returns the table name.
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'target_data';
	}
	
	/**
	 * Returns the column data.
	 *
	 * @return array
	 */
	public function getColumnData()
	{
		$column_data = parent::getColumnData();
		
		return $column_data;
	}
	
	/**
	 * Sets the column data.
	 *
	 * @param array $data
	 * @return void
	 */
	public function setColumnData($data)
	{
		$this->column_data = $data;
	}
	
	/**
	 * Load target data (either all of it or only certain types) for all targets
	 * with property shorts specified in $property_shorts.
	 *
	 * @param array $property_shorts Campaigns, targets or collections.
	 * @param array $target_data_type_ids The target data type ids to limit the
	 * results to.
	 * @return DB_Models_IterativeModel_1 List of target data belonging to a
	 * property short in $property_shorts and conforming to the $target_data_type_ids
	 * restriction.
	 */
	public function loadAllByPropertyShorts(array $property_shorts, array $target_data_type_ids = array())
	{
		$args = array();
		$property_short_part = '';
		$type_ids_part = '';
		
		if (!$property_shorts)
		{
			throw new InvalidArgumentException(
				'load by property shorts requires property shorts'
			);
		} 
		else
		{
			$property_short_part = " AND t.property_short IN (" 
				. implode(', ', array_fill(0, count($property_shorts), '?')) . ")";
			$args = array_merge($args, $property_shorts);
		}
		
		if ($target_data_type_ids)
		{
			$type_ids_part = " AND td.target_data_type_id IN ("
				. implode(', ', array_fill(0, count($target_data_type_ids), '?')) . ")";
			$args = array_merge($args, $target_data_type_ids);
		}
		
		
		$query = "
			SELECT td.*
			FROM target t
			JOIN target_data td ON td.target_id=t.target_id
			WHERE t.active
			$property_short_part
			$type_ids_part
		";
		
		$stmnt = $this->db->prepare($query);
		$stmnt->execute($args);
		
		return $this->factoryIterativeModel($stmnt, $this->db);
	}
}
?>