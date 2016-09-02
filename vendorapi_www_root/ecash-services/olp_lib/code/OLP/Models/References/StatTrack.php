<?php

/** Database model for stat_track
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_Models_References_StatTrack extends OLP_Models_References_ReferenceModel
{
	const PREFETCH = FALSE;
	
	/**
	 * @var DB_Models_ReferenceTable_1
	 */
	protected static $reference_table;
	
	/** Return an instance of the reference table.
	 *
	 * @return DB_Models_ReferenceTable_1
	 */
	public static function getReferenceTable()
	{
		if (!self::$reference_table)
		{
			self::$reference_table = new DB_Models_ReferenceTable_1(new self(), self::PREFETCH);
		}
		
		return self::$reference_table;
	}
	
	/** List of columns for this model.
	 *
	 * @return array
	 */
	public function getColumns()
	{
		static $columns = array(
			'stat_track_id',
			'date_created',
			'track_key',
		);
		
		return $columns;
	}
	
	/** List of primary keys for this model.
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return array('stat_track_id');
	}
	
	/** The auto increment column for this model.
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return 'stat_track_id';
	}
	
	/** The table name for this model.
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'stat_track';
	}
	
	/** For the reference model, this is the ID column.
	 *
	 * @return string
	 */
	public function getColumnID()
	{
		return 'stat_track_id';
	}
	
	/** For the reference model, this is the VALUE column.
	 *
	 * @return string
	 */
	public function getColumnName()
	{
		return 'track_key';
	}
}

?>
