<?php
/**
 * Represents a writable database row for OLP
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
abstract class OLP_Models_WritableModel extends DB_Models_ObservableWritableModel_1
{
	/**
	 * The database constant date format.
	 */
	const DATE_FORMAT = 'Y-m-d H:i:s';
	
	/**
	 * The compression level.
	 */
	const COMPRESSION_LEVEL = 9;
	
	/**
	 * Process modes
	 */
	const PROCESSMODE_APPLY = 1;
	const PROCESSMODE_REMOVE = 2;
	
	/**
	 * Process types
	 */
	const PROCESS_COMPRESS = 'processCompress';
	const PROCESS_COMPRESSMYSQL = 'processCompressMySQL';
	const PROCESS_DATE = 'processDate';
	const PROCESS_SERIALIZE = 'processSerialize';
	
	/**
	 * Return an array of required values.
	 *
	 * @return array
	 */
	public function getRequiredColumns()
	{
		return NULL;
	}
	
	/**
	 * Returns an array of columns that need extra processing.
	 *
	 * @return array
	 */
	public function getProcessedColumns()
	{
		$processed_columns = array(
			'date_modified' => array(self::PROCESS_DATE),
			'date_created' => array(self::PROCESS_DATE),
		);
		
		return $processed_columns;
	}
	
	/**
	 * Returns the active database connection
	 *
	 * @todo This shouldn't be overloaded, it needs to use the default function
	 * @param int $db_inst
	 * @return DB_IConnection_1
	 */
	public function getDatabaseInstance($db_inst = DB_Models_DatabaseModel_1::DB_INST_WRITE)
	{
		if (isset($this->db))
		{
			return $this->db;
		}
		
		return DB_Connection::getInstance('BLACKBOX', BFW_MODE);
	}
	
	/**
	 * If inserting, add the date_created field automatically if does not exist.
	 * 
	 * This is here for tables that have date_created and date_modified, while the
	 * database only auto-populates date_modified.
	 *
	 * @return bool
	 */
	public function insert()
	{
		if (in_array('date_created', $this->getColumns()) && in_array('date_modified', $this->getColumns()) && !isset($this->date_created))
		{
			$this->date_created = time();
		}
		
		return parent::insert();
	}
	
	/**
	 * Handle any common Model -> DB conversions.
	 *
	 * @return array
	 */
	public function getColumnData()
	{
		$column_data = parent::getColumnData();
		
		// Apply any processing needed on columns.
		$column_data = $this->processAll($column_data, $this->getProcessedColumns(), self::PROCESSMODE_APPLY);
		
		return $column_data;
	}
	
	/**
	 * Handle any common DB -> Model conversions.
	 *
	 * @param array $column_data
	 * @return void
	 */
	protected function setColumnData($column_data)
	{
		// Remove any processing applied to columns.
		$column_data = $this->processAll($column_data, $this->getProcessedColumns(), self::PROCESSMODE_REMOVE);
		
		parent::setColumnData($column_data);
	}
	
	/**
	 * Check required fields as well as primary key.
	 *
	 * @param array $column_data
	 * @return bool
	 */
	protected function canInsert(array $column_data = NULL)
	{
		if (parent::canInsert($column_data) && $this->getRequiredColumns())
		{
			// allow this to be passed as NULL for compatibility
			if ($column_data === NULL)
			{
				$column_data = $this->getColumnData();
			}
			
			$required_columns = $this->getRequiredColumns();
			if (is_array($required_columns))
			{
				foreach ($this->getRequiredColumns() as $key)
				{
					if ($column_data[$key] === NULL)
					{
						return FALSE;
					}
				}
			}
		}
		
		return TRUE;
	}
	
	/**
	 * Processes columns.
	 *
	 * @param array $column_data
	 * @param array $processed_columns
	 * @param int $processing_mode
	 * @return array
	 */
	protected function processAll(array $column_data, array $processed_columns, $processing_mode)
	{
		if (is_array($processed_columns))
		{
			// When removing, process in reverse order. This probably
			// doesn't need to happen, but if someone writes a process that
			// depends on other data that may be processed, it should be
			// in the same format as when it was applied.
			if ($processing_mode == self::PROCESSMODE_REMOVE)
			{
				$processed_columns = array_reverse($processed_columns);
			}
			
			foreach ($processed_columns AS $column_name => $functions)
			{
				if (isset($column_data[$column_name]) && is_array($functions))
				{
					// When removing, process in reverse order. This is
					// very important to reverse.
					if ($processing_mode == self::PROCESSMODE_REMOVE)
					{
						$functions = array_reverse($functions);
					}
					
					foreach ($functions AS $function)
					{
						$column_data[$column_name] = call_user_func_array(array($this, $function), array($column_data[$column_name], $processing_mode));
					}
				}
			}
		}
		
		return $column_data;
	}
	
	/**
	 * Convert the date to/from PHP and DB formats.
	 *
	 * @param string $data
	 * @param int $processing_mode
	 * @return string
	 */
	public function processDate($data, $processing_mode)
	{
		$result = NULL;
		
		switch ($processing_mode)
		{
			case self::PROCESSMODE_APPLY:
				$result = date(self::DATE_FORMAT, $data);
				break;
			case self::PROCESSMODE_REMOVE:
				$result = strtotime($data);
				break;
		}
		
		return $result;
	}
	
	/**
	 * Compress or uncompress a value.
	 *
	 * @param string $data
	 * @param int $processing_mode
	 * @return string
	 */
	public function processCompress($data, $processing_mode)
	{
		$result = NULL;
		
		switch ($processing_mode)
		{
			case self::PROCESSMODE_APPLY:
				$result = gzcompress($data, self::COMPRESSION_LEVEL);
				break;
			case self::PROCESSMODE_REMOVE:
				$result = @gzuncompress($data);
				break;
		}
		
		return $result;
	}
	
	/**
	 * Compress or uncompress a value in MySQL compatiable format.
	 *
	 * @param string $data
	 * @param int $processing_mode
	 * @return string
	 */
	public function processCompressMySQL($data, $processing_mode)
	{
		$result = NULL;
		
		switch ($processing_mode)
		{
			case self::PROCESSMODE_APPLY:
				$result = pack('V', strlen($data)) . gzcompress($data, self::COMPRESSION_LEVEL);
				break;
			case self::PROCESSMODE_REMOVE:
				$result = @gzuncompress(substr($data, 4));
				break;
		}
		
		return $result;
	}
	
	/**
	 * Serializes or unserializes an object.
	 *
	 * @param string $data
	 * @param int $processing_mode
	 * @return string
	 */
	public function processSerialize($data, $processing_mode)
	{
		$result = NULL;
		
		switch ($processing_mode)
		{
			case self::PROCESSMODE_APPLY:
				$result = serialize($data);
				break;
			case self::PROCESSMODE_REMOVE:
				$result = @unserialize($data);
				break;
		}
		
		return $result;
	}
	
	/**
	 * Reloads information for this model
	 * 
	 * @return bool
	 */
	public function reload()
	{
		return (!empty($this->{$this->getAutoIncrement()})) ? $this->loadByKey($this->{$this->getAutoIncrement()}) : FALSE;
	}
}

?>
