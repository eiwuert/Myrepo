<?php
/**
 * TargetRelation model.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Blackbox_Models_TargetRelation extends Blackbox_Models_WriteableModel
{
	/**
	 * Gets all the TargetRelation models with campaign target ID's for a target.
	 *
	 * @param int $target_id
	 * @return DB_Models_IterativeModel_1
	 */
	public function getCampaigns($target_id)
	{
		return $this->loadAllBy(array('child_id' => $target_id));
	}
	
	/**
	 * Returns an array of column names for this table.
	 *
	 * @return array
	 */
	public function getColumns()
	{
		return array('target_relation_id', 'target_id', 'child_id', 'weight');
	}
	
	/**
	 * Returns an array of the columns that make up the primary key.
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return array('target_relation_id');
	}
	
	/**
	 * Returns the auto increment column name.
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return 'target_relation_id';
	}
	
	/**
	 * Returns the name of the table.
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'target_relation';
	}
	
	/**
	 * Returns the data as an array.
	 *
	 * @return array
	 */
	public function getColumnData()
	{
		$column_data = parent::getColumnData();
		
		return $column_data;
	}
	
	/**
	 * Sets the data from an array.
	 *
	 * @param array $data
	 * @return void
	 */
	public function setColumnData($data)
	{
		$this->column_data = $data;
		
	}
	
	/**
	 * Load the model by Campaign ID
	 *
	 * @param int $campaign_id
	 * @return void
	 */
	public function loadByCampaignId($campaign_id)
	{
		$this->loadBy(array('target_id' => $campaign_id));
	}
	
}
?>