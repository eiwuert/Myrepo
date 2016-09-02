<?php
	
	defined('SESSION9_LOCK_RETRY_COUNT') || define('SESSION9_LOCK_RETRY_COUNT', 50);   // how many attempts to read a locked record.
	defined('SESSION9_LOCK_SLEEP_SEC')   || define('SESSION9_LOCK_SLEEP_SEC', 5);      // how long to sleep if the session is locked.
	defined('SESSION9_LOCK_TIMEOUT_SEC') || define('SESSION9_LOCK_TIMEOUT_SEC', 240);  // how many seconds old before a session_lock is considered expired.
	                                                                                   // hopefully, there won't be any transactions lasting longer than this.
	                                                                                   // SESSION9_LOCK_RETRY_COUNT * SESSION9_LOCK_SLEEP_SEC should be > SESSION9_LOCK_TIMEOUT_SEC

	// this stuff is for debugging what's happening with your session.
	// just define any of these constants before including this code to activate or change the constant.
	// -------------------------------------------------------------------------------------------------
	defined('SESSION9_DEBUG')         || define('SESSION9_DEBUG', false);         // for logging everything
	defined('SESSION9_DEBUG_READ')    || define('SESSION9_DEBUG_READ', false);    // for logging when the session is read
	defined('SESSION9_DEBUG_WRITE')   || define('SESSION9_DEBUG_WRITE', false);   // for logging when the session is written
	defined('SESSION9_DEBUG_LOCKED')  || define('SESSION9_DEBUG_LOCKED', false);  // for logging lock info
	defined('SESSION9_PORTION_BEGIN') || define('SESSION9_PORTION_BEGIN', '');    // for logging a portion of the session_info
	defined('SESSION9_PORTION_LEN')   || define('SESSION9_PORTION_LEN', 100);     // how long a portion of the session_info should be logged
	
	
	require_once('logsimple.php');
	
	// ---------------------------------------------------------------------------------------------------------------
	// A tool to handle sessions.  This was copied from session.4.php.
	// The differences between this and session.4.php are:
	//     1.  This version is php 5 rather than php 4.
	//     2.  This version is pure session stuff, no stats.
	//     3.  This version uses mysqli rather than mysql.
	//     4.  This version makes minimal assumptions about the structure of the session table.
	//         Only two fields are assumed, "session_id" and "session_info".  If it's desired to
	//         insert or update additional fields (with constant values) besides session_id and
	//         session_info, they can be passed in with an array.  For example, if you want
	//         a field called "date_created" to be set with the date the record is created,
	//         simply pass this array in the $extra_insert_fields_array variable:
	//
	//             $extra_insert_fields_array = array( 'date_created' => 'now()' );
	//
	//         Likewise, if you want a field called date_modified to be updated whenever
	//         the session record is updated, simply pass this array in the $extra_update_fields_array
	//         variable:
	//
	//             $extra_update_fields_array = array( 'date_updated' => 'now()' );
	//
	//         NOTE:  If you need quotes around the values in the extra fields arrays you will have to add them
	//                yourself like this: $extra_update_fields_array = array( 'some_field' => "'some value'" );
	// ---------------------------------------------------------------------------------------------------------------


	class Session_9
	{
		private $debug = true;
	
		private $mysqli;                      // holds the mysqli object that interfaces with the database.
		private $database;                    // (NOT USED) name of database containing session table
		private $table;                       // name of session table
		private $sid;                         // session id - REQUIRED - this class does not create a session id
		private $name;                        // name of cookie to hold session_id (PHP default is phpsessid or something like that)
		private $extra_insert_fields_array;   // optional fields to be inserted into session record with a constant value.
		private $extra_update_fields_array;   // optional fields to be updated on session record with a constant value.
		private $compression;                 // desired compression (gz, bz, or none).
		private $portion_string_begin = '';   // Use method Log_Portion_of_Session() to write to the log part of the session on read and write.
		private $portion_length = 100;        // Use method Log_Portion_of_Session() to write to the log part of the session on read and write.

		

		public function __construct( $mysqli, $database, $table='session', $sid=NULL, $extra_insert_fields_array=NULL, $extra_update_fields_array=NULL, $name='ssid', $multiple_tables=false, $auto_session_write_close=true, $compression='gz' )
		{
			if (SESSION9_DEBUG) logsimplewrite(__METHOD__ . ": entering");

			$this->mysqli      = $mysqli;
			$this->database    = $database;
			$this->table       = $table;
			$this->sid         = $sid;
			$this->name        = $name;
			$this->compression = strtolower($compression);
			$this->compression = $this->compression == 'gz' || $this->compression == 'bz' ? $this->compression : '';
			

			$this->extra_insert_fields_array = '';
			if ( isset( $extra_insert_fields_array ) && is_array( $extra_insert_fields_array ) )
			{
				foreach( $extra_insert_fields_array as $key => $val )
				{
					$this->extra_insert_fields_array .= ", $key=$val";
				}
			}

			$this->extra_update_fields_array = '';
			if ( isset( $extra_update_fields_array ) && is_array( $extra_update_fields_array ) )
			{
				foreach( $extra_update_fields_array as $key => $val )
				{
					$this->extra_update_fields_array .= ", $key=$val";
				}
			}

			session_name ($this->name);

			if ( !is_null($sid) )
			{
				session_id ($sid);
			}
			
			$sid = session_id();
			
			if ( $multiple_tables ) $this->table = 'session_' . strtolower(substr($sid, 0, 1));
			
			// Establish the methods to be called by PHP's session handler.
			// In my testing, I found that Open and Read are called right after the session is established.
			// Then, Write is called right after the script ends.  Destroy is only called if the script
			// specifically calls "session_destroy()".  I've never seen Garbage_Collection get called and
			// don't know how to cause it to be called.  If you want to write the session data to the
			// database before the script ends, call session_write_close().  From the notes on php.net
			// it apppears that this SHOULD be called before doing a redirect or else there can be
			// missing session data.  You only get one shot at writing session data to the database, though,
			// so don't call session_write_close() unless necessary and only right before your
			// script ends (like right before you issue a redirect).

			session_set_save_handler
			(
				array (&$this, "Open"),
				array (&$this, "Close"),
				array (&$this, "Read"),
				array (&$this, "Write"),
				array (&$this, "Destroy"),
				array (&$this, "Garbage_Collection")
			);

			// http://www.php.net/manual/en/function.session-set-save-handler.php (boswachter at xs4all nl)
			// If you're creating a sessionhandler class, and use a database-class and you are
			// experiencing problems because of destroyed objects when write is called, you can
			// fix this relatively easily:   register_shutdown_function("session_write_close");
			// This way, the session gets written of before your database-class is destroyed.
			
			if ( $auto_session_write_close ) register_shutdown_function('session_write_close');

			$this->Log_Portion_of_Session();

			session_start();

			return TRUE;
		}

		function Open ($save_path, $session_name)
		{
			if (SESSION9_DEBUG) logsimplewrite(__METHOD__ . ": entering");
			return true;
		}

		function Close ()
		{
			if (SESSION9_DEBUG) logsimplewrite(__METHOD__ . ": entering");
			return true;
		}

		function Read ($session_id)
		{
			if (SESSION9_DEBUG || SESSION9_DEBUG_READ) logsimplewrite_t(__METHOD__ . ": entering, session_id=$session_id");
				
			$tablename = $this->table;
			$extradata = $this->extra_insert_fields_array;

			if ( $this->does_session_exist($session_id) )
			{
				$attempts = SESSION9_LOCK_RETRY_COUNT;
				$timeout = SESSION9_LOCK_TIMEOUT_SEC;

				while ( $attempts-- > 0 )
				{
					$sql = "select
								if(ifnull(timestampdiff(second, date_locked, now()), 1000000) > $timeout, 0, 1) as locked,
								compression, session_info from $tablename where session_id = '$session_id'";
								
					$q = $this->mysqli->Query($sql);
		
					if ( $q )
					{
						if ( $row = $q->Fetch_Object_Row() )
						{
							if (SESSION9_DEBUG || SESSION9_DEBUG_LOCKED) logsimplewrite(__METHOD__ . ": locked='$row->locked', sql=" . logsimpledump($sql) );

							if ( $row->locked == 0 ) // row is not locked
							{
								$this->lock_row($session_id);
								$session_info = $this->decompress_session($row->compression, $row->session_info);
								if ($this->portion_string_begin != '')
								{
									$len = strlen($session_info);
									$this->Display_Portion_of_Session( __METHOD__. " (len=$len, session_id=$session_id) ", $session_info, $this->portion_string_begin, $this->portion_length );
								}
								return $session_info;
							}
						}
						else
						{
							// this should never happen because we already check that session exists
							logsimplewrite(__METHOD__ . ": FAILED to get row, sql=$sql, errorno=" . $this->mysqli->Get_Errno() . ', error=' . $this->mysqli->Get_Error() );
						}
					}
					else
					{
						logsimplewrite(__METHOD__ . ": FAILED to get result set, sql=$sql, errorno=" . $this->mysqli->Get_Errno() . ', error=' . $this->mysqli->Get_Error() );
					}

					sleep(SESSION9_LOCK_SLEEP_SEC);
				}
			}
			else
			{
				$sql = "insert into $tablename set compression='', session_id='$session_id' $extradata";
				$q = $this->mysqli->Query($sql);
				return '';
			}

			logsimplewrite(__METHOD__ . ": FAILED to get session_info, probably session was locked too long, sql=" . logsimpledump($sql));
			return '';
		}

		function Write($session_id, $session_info='')
		{
			if (SESSION9_DEBUG || SESSION9_DEBUG_WRITE) logsimplewrite_t(__METHOD__ . ": entering, session_id=$session_id, session_info=" . logsimpledump($session_info));
			if ($this->portion_string_begin != '')
			{
				$len = strlen($session_info);
				$this->Display_Portion_of_Session( __METHOD__ . " (len=$len, session_id=$session_id) ", $session_info, $this->portion_string_begin, $this->portion_length );
			}
			$tablename = $this->table;
			$data = mysql_escape_string($this->compress_session($session_info));
			if ($this->portion_string_begin != '') logsimplewrite_t(__METHOD__ . ': length of session_info after compression=' . strlen($data));
			$extradata = $this->extra_update_fields_array;
			$sql = "update $tablename set date_locked = '0000-00-00 00:00:00', compression='$this->compression', session_info='$data' $extradata where session_id='$session_id'";

			try
			{
				$this->mysqli->Query($sql);
			}
			catch (Exception $e)
			{
				logsimplewrite(__METHOD__ . ": query FAILED, exception=" . $e->getMessage() . ", errorno=" . $this->mysqli->Get_Errno() . ', error=' . $this->mysqli->Get_Error() );
			}

			return true;
		}

		function Destroy ($session_id)
		{
			if (SESSION9_DEBUG) logsimplewrite(__METHOD__ . ": entering");
			$tablename = $this->table;
			
			$sql = "delete from $tablename where session_id='$session_id'";
			$q = $this->mysqli->Query($sql);

			return TRUE;
		}

		function Garbage_Collection ($session_life)
		{
			if (SESSION9_DEBUG) logsimplewrite(__METHOD__ . ": entering");
			return TRUE;
		}

		public function Log_Portion_of_Session( $string_begin=SESSION9_PORTION_BEGIN, $length=SESSION9_PORTION_LEN )
		{
			$this->portion_string_begin = $string_begin;   
			$this->portion_length = $length;        
		}

		public function does_session_exist($session_id)
		{
			$tablename = $this->table;
			$sql = "select count(*) as count from $tablename where session_id = '$session_id'";
			$q = $this->mysqli->Query($sql);
			if ( $q )
			{
				if ( $row = $q->Fetch_Object_Row() )
				{
					$count = $row->count;
					if ( $count > 0 ) return true;
				}
			}
			else
			{
				// failed to get result set
				logsimplewrite(__METHOD__ . ": FAILED to get result set, sql=$sql, errorno=" . $this->mysqli->Get_Errno() . ', error=' . $this->mysqli->Get_Error() );
			}
		
			return false;
		}

		protected function lock_row($session_id)
		{
			$tablename = $this->table;
			$sql = "update $tablename set date_locked = now() where session_id = '$session_id'";
			$this->mysqli->Query($sql);
		}

		protected function decompress_session( $compression_type, &$session_info )
		{
			switch($compression_type)
			{
				case "gz": return gzuncompress($session_info);
				case "bz": return bzdecompress($session_info);
				default:   return $session_info;
			}
		}
		
		protected function compress_session( &$session_info )
		{
			switch($this->compression)
			{
				case "gz": return gzcompress($session_info);
				case "bz": return bzcompress($session_info);
				default:   return $session_info;
			}
		}
		
		protected function Display_Portion_of_Session( $label, $session_info, $strstr, $len )
		{
			$s = strstr($session_info, $strstr);
			$s = substr($s, 0, $len);
			logsimplewrite_t($label . ':: ' . $s);
		}
		
	}
?>
