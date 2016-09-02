<?php
require_once 'stat_lib/Stats/StatPro/CustomerSchema.php';
require_once 'stat_lib/Stats/StatPro/Customer.php';
require_once 'stat_lib/Date/Util.php';

/**
 * Stat_Get class will allow for interfacing with StatPro2 stats
 * 
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class Stats_Get
{
	const CALC_TYPE_COUNT = 1;
	const CALC_TYPE_SUM = 2;
	/**
	 * Database object
	 *
	 * @var DB_Database_1
	 */
	protected $db_conn;
	/**
	 * Mode for determining which StatPro database to hit
	 *
	 * @var string
	 */
	protected $mode;
	
	/**
	 * Constructor
	 *
	 * @param string $mode Mode for determining which StatPro database to hit
	 * @return void
	 */
	public function __construct($mode)
	{
		$this->mode = $mode;
	}
	
	/**
	 * Returns an instance of the Database object
	 *
	 * @return DB_Database_1
	 */
	protected function getDbInstance()
	{
		if (!$this->db_conn instanceof DB_Database_1)
		{
			$this->db_conn = DB_Connection::getInstance('statpro', $this->mode);
		}

		return $this->db_conn;
	}
	
	/**
	 * Returns an associative array of stat counts based on the stat names and type submitted
	 * to the function and a date range 
	 *
	 * @param mixed $stats String of a stat or an array of strings of stats to count 
	 * @param string $stat_type Stat type
	 * @param string $start_time An strtotime convertable date/time string
	 * @param string $end_time An strtotime convertable date/time string
	 * @return array
	 */
	public function countEvents($stats, $stat_type, $start_time, $end_time)
	{
		return $this->calc(self::CALC_TYPE_COUNT,'event_log',$stats, $stat_type, $start_time, $end_time);
	}
	
	/**
	 * Returns an associative array of stat counts based on the table identifier, stat names, 
	 * and type submitted to the function and a date range
	 *
	 * @param integer $calc_type Identifier for type of calculation to perform
	 * @param string $table_identifier Table prefix that holds stats i.e. event_log.  See ept_lib for more info
	 * @param mixed $stats String of a stat or an array of strings of stats to count 
	 * @param string $stat_type Stat type
	 * @param string $start_time An strtotime convertable date/time string
	 * @param string $end_time An strtotime convertable date/time string
	 * @return array
	 */
	protected function calc($calc_type, $table_identifier, $stats, $stat_type, $start_time, $end_time)
	{
		// If we were passed a string instead of an array in $stats, convert the string to an array
		$stats_array = (is_array($stats)) ? $stats : explode(',',$stats);
		
		if (FALSE === $start_timestamp = strtotime($start_time))
		{
			throw new InvalidArgumentException("$start_time is not a valid start date/time");
		}
		
		if (FALSE === $end_timestamp = strtotime($end_time))
		{
			throw new InvalidArgumentException("$end_time is not a valid end date/time");
		}
		
		$table_suffixes = $this->getMonths($start_timestamp, $end_timestamp);
		// Get the schemas to query based on the $stat_type
		$customer_schema = new Stats_StatPro_CustomerSchema($stat_type);
		$schemas = $customer_schema->buildDatabaseArray();
		
		$stat_counts = array();
		$db = $this->getDbInstance();

		$stat_cols = array();
		
		// Determine what to sum for the calculation based on the calculation type
		foreach ($stats_array as $stat)
		{
			switch ($calc_type)
			{
				case self::CALC_TYPE_COUNT:
					$calc_amount = 1;
					break;
				case self::CALC_TYPE_SUM:
				default:
					$calc_amount = $stat;
					break;
			}
			
			// Define the column defs for the select statement 
			$stat_column_defs[] = "SUM(IF(et.event_type_key IN ('"
				.implode("','",Stats_Aliases::getAliases($stat))
				."'),"
				.$calc_amount
				.",0)) AS $stat";
				
			$stat_cols = array_merge($stat_cols,Stats_Aliases::getAliases($stat));
			$stat_counts[$stat] = 0;
		}
		foreach ($schemas as $schema)
		{
			foreach ($table_suffixes as $table_suffix)
			{
				$data_table = $table_identifier.'_'.$table_suffix['year'].'_'.$table_suffix['month'];
				$event_type_table = 'event_type_'.$table_suffix['year'].'_'.$table_suffix['month'];
				$query = 
					"SELECT "
						.implode(',',$stat_column_defs)
						." FROM $event_type_table et"
							." INNER JOIN $data_table el USE INDEX (ix_occured)"
								." USING (event_type_id)"
						." WHERE et.event_type_key IN ('" . implode("','",$stat_cols) . "')"
							." AND el.date_occurred BETWEEN $start_timestamp AND $end_timestamp";

				try
				{
					$db->selectDatabase($schema);
					$statement = $db->query($query);
					
					if ($row = $statement->fetch(PDO::FETCH_OBJ))
					{
						foreach ($stats_array as $stat)
						{
							$stat_counts[$stat] += $row->$stat;
						}
					}
				}
				catch (PDOException $e)
				{
					// If the event type/log table doesn't exist, this is not exceptional behavior
					// If we get a MySQL exception other the afore mentioned, throw it
					if (strpos($e->getMessage(), "Table '{$schema}.{$data_table}' doesn't exist") === FALSE
						&& strpos($e->getMessage(), "Table '{$schema}.{$event_type_table}' doesn't exist") === FALSE)
					{
						throw $e;
					}
				}
			}
		}

		return $stat_counts;
	}

	/**
	 * Gets an array of month and year elements based on a start/end unix timestamp 
	 *
	 * @param int $start_time
	 * @param int $end_time
	 * @return array
	 */
	protected function getMonths($start_time, $end_time)
	{
		$months = array();
		foreach (Date_Util::getMonthsBetween($start_time, $end_time) as $month)
		{
			$month_parts = array();
			$month_parts['year'] = Date('Y', $month);
			$month_parts['month'] = Date('m', $month);
			$months[] = $month_parts;
		}
		return $months;
	}
}
?>