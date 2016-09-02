<?php
/**
 * Base model class for the blackbox post data tables
 * @author stephan soileau <stephan.soileau@sellingsource.com>
 *
 */
abstract class OLP_Models_BlackboxPostData extends OLP_Models_CryptWritableModel
{
	/**
	 * The column in this table that stores the actual data
	 * @return string
	 */
	abstract protected function getDataColumn();
	
	/**
	 * The list of columns this model contains
	 * @return array string[]
	 */
	public function getColumns()
	{
		$columns = array(
			'blackbox_post_data_sent_id', 'date_created',
			'date_modified', 'blackbox_post_id', $this->getDataColumn()
		);
		
		return $columns;
	}
	
	/**
	 * only save if our data column has been modified
	 * @return boolean
	 */
	public function save()
	{
		if (is_array($this->altered_columns) && in_array($this->getDataColumn(), $this->altered_columns))
		{
			return parent::save();
		}
		return TRUE;
	}
	
	/**
	 * Returns an array of columns that need extra processing.
	 *
	 * @return array
	 */
	public function getProcessedColumns()
	{
		$processed_columns = array(
			$this->getDataColumn() => array(self::PROCESS_COMPRESS, self::PROCESS_ENCRYPT),
		);
		
		// Merge in any processed columns from parent
		$parent_processed_columns = parent::getProcessedColumns();
		if (is_array($parent_processed_columns))
		{
			$processed_columns = array_merge_recursive($parent_processed_columns, $processed_columns);
		}
		
		return $processed_columns;
	}
}