<?

/**
 * Event Log - Singleton Wrapper
 * event log wrapper using the singleton pattern
 */
 
class Event_Log_Singleton
{
	static private $instance = array();
	
	function __construct($mode, $application_id)
	{		
		// set event log table
		$table = isset( $_SESSION['event_log_table'] ) ? $_SESSION['event_log_table'] : NULL;
		
		// run setup db
		include_once(BFW_CODE_DIR . 'setup_db.php');
		$sql = Setup_DB::Get_Instance('event_log', $mode, null);
		
		// set event_log instance
		include_once(BFW_CODE_DIR . 'event_logging.php');
		self::$instance[$application_id] = new Event_Log( $sql, $sql->db_info['db'], $application_id, $table );
		
		$_SESSION['event_log_table'] = $this->event->table;
	}
	
	static public function Get_Instance($mode, $application_id)
	{
		if ( !isset(self::$instance[$application_id]) )
		{
			new Event_Log_Singleton($mode, $application_id);
		}	
		return self::$instance[$application_id];
	}
	
}