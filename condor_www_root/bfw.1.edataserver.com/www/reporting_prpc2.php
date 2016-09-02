<?php
/**
 * PRPC interface between ReportPro and BFW
 * 
 * @author Brian Armstrong <brian.armstrong@sellingsource.com>
 */

require_once ('config.php');
require_once ('../include/code/server.php');
require_once ('../include/code/setup_db.php');

require_once ('prpc2/server.php');
require_once ('prpc2/client.php');

/**
 * The class to handle the prpc functionality for the interface
 *
 * @author Brian Armstrong <brian.armstrong@sellingsource.com>
 */
class Report_Prpc2 extends Prpc_Server2
{
	private $server;
	private $database;

	/**
	 * @var MySQL_4
	 */
	private $mysql;

	/**
	 * Initialize the class
	 * @param string $mode
	 */
	public function __construct()
	{
		// Initialize the database array
		$this->mysql = array();

		parent::__construct();
	}
	
	/**
	 * Translate the statpro mode into blackbox terms
	 * @param string $mode
	 * @return string
	 */
	private function translateMode($mode)
	{
		$mode = strtoupper($mode);
		
		// Default
		$ret = 'UNKNOWN';

		// Translate 'DEV' to 'LOCAL'
		if ($mode == 'DEV')
		{
			$ret = 'LOCAL';
		}
		// LEAVE 'LOCAL', 'RC', 'REPORT' as is
		elseif ($mode == 'LOCAL' || $mode == 'RC' || $mode == 'REPORT')
		{
			$ret = $mode;
		}
		// Change 'LIVE' to 'REPORT'
		elseif ($mode == 'LIVE')
		{
			$ret = 'REPORT';
		}

		// Return the mode
		return $ret;
	}
	
	/**
	 * Get the database connection
	 * @param string $vmode
	 * @return MySQL_4
	 */
	private function getDB($vmode)
	{
		// Translate the mode
		$vmode = $this->translateMode($vmode);
		
		if (!isset($this->mysql[$vmode]) || is_null($this->mysql[$vmode]))
		{
			$this->mysql[$vmode] = Setup_DB::Get_Instance('blackbox', $vmode);
		}

		return $this->mysql[$vmode];
	}
	
	/**
	 * Get all yellowpage applications that have been declient on a date range
	 *
	 * @param string $vmode
	 * @param date $start_date
	 * @param date $end_date
	 * @param array $args
	 * @return mixed - either an array or Exception (on error)
	 */
	public function getYellowpageApps($vmode, $start_date, $end_date, $args=array())
	{
		// Run the query
		try
		{
			$db = $this->getDB($vmode);
			
			// Create the query
			$query = "
				SELECT /* Reporting_PRPC.GetTrackInformation */ *
				FROM `application` `a`
				WHERE `olp_process` = 'ecash_yellowpage'
				AND `created_date` BETWEEN '{$start_date}' AND '{$end_date}'";
			
			if (isset($args['declined']))
			{
				$query .= "
				AND `application_type` = 'FAILED'";
			}
			
			$ret = array();
			$result = $db->Query($db->db_info['db'], $query);
			while($app = $db->Fetch_Array_Row($result))
			{
				$ret[] = $app;
			}
		}
		catch (MySQL_Exception $e)
		{
			return $e;
		}
		catch (Exception $e)
		{
			return $e;
		}

		return $ret;
	}
	
	/**
	 * Get the track information base on the track key
	 * Columns Usage:
	 * - The columns can be passed in as either a raw SQL list of the columns
	 * - or as an array. If the key value in the array is non-numeric it is used
	 * - as the alias for the value.
	 * - eg.
	 * -  'vendor' => 'vendor_name' translates to 'vendor_name AS `vendor`'
	 * 
	 * @param string $vmode
	 * @param string $track_key
	 * @param mixed $columns
	 * @return mixed - either an array or Exception (on error)
	 */
	public function Get_Track_Information($vmode, $track_key, $columns='`state`, `lead_amount`')
	{
		// Make sure the track key is not empty
		if (empty($track_key))
		{
			return new Exception('Track key was empty');
		}
		
		// If the columns passed are in an array, join them together
		if (is_array($columns))
		{
			$list = array();
			foreach ($columns as $key => $value)
			{
				// If the key is numeric, just add the value, otherwise the alias
				$list[] = (is_numeric($key))?$value:"{$value} AS `{$key}`";
			}
			$columns = join(', ', $list);
		}
		
		// Make sure the columns list is not empty
		if (empty($columns))
		{
			return new Exception('No columns found for query');
		}
		
		// If the columns list is not a string
		if (!is_string($columns))
		{
			ob_start();
			var_dump($columns);
			$dump = ob_get_clean();
			
			return new Exception('Columns value is not a string: "'.trim($dump).'"');
		}
		
		// Run the query
		try
		{
			$db = $this->getDB($vmode);
			
			// Create the query
			$query = "
				SELECT /* Reporting_PRPC.GetTrackInformation */ {$columns}
				FROM `application` `a`
				INNER JOIN `residence` `r` USING(`application_id`)
				INNER JOIN `campaign` `c` ON `a`.`target_id` = `c`.`target_id`
				WHERE `a`.`track_id` = '".mysql_real_escape_string($track_key)."'
				AND `c`.`status` = 'active'
			";
			
			$ret = array();
			$result = $db->Query($db->db_info['db'], $query);
			while($account =  $db->Fetch_Array_Row($result))
			{
				$ret = $account;
			}
		}
		catch (MySQL_Exception $e)
		{
			return $e;
		}
		catch (Exception $e)
		{
			return $e;
		}

		return $ret;
	}
}

$cm_prpc = new Report_Prpc2();
$cm_prpc->_Prpc_Strict = TRUE;

?>
