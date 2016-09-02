<?PHP
/*
		@version
				5.0.0 2004-02-14 - Nick White
					- A tool to handle sessions/auto process using DB2 and MySQL
		
		
		Updates:
		-2/17	Modified the read function to use the new db2.1.php Long_Data function.
		-2/18	Turned Auto_Proc on and tested it's functionality, it works.
		-2/18	Added schema to session 5 constructor
		-2/19	Moved session_info row to 64536 Clob, adjusted queries as needed.
		-5/06	Changed Auto_Proc to Addtl_Info -JRF //and tested it's functionality, it works.
		-5/07	Added backup session writing with MySql - Nick
	
		Notes:
			- This file is well commented,... keep it that way.
	
		To-Do's:
			High Priority:
			
			Med Priority:
		
			Low Priority:
				- Would be nice to find another place to store the auto_proc field array rather than have it
				  hard coded here in this file.  Line: 371
*/
	
	require_once ("config.3.php");
	require_once ("setstat.1.php");

	class Session_5
	{
		var $sql;
		var $db2;
		var $database;
		var $schema;
		var $table;
		var $name;
		var $mysql_backup;
		var $backup_site;
		var $pixel_handler;
		var $current_pixels;

		/**
		 * @return bool
		 * @param $sql
		 * @param $db2 
		 * @param $database string
		 * @param $schema string
		 * @param $table string
		 * @param $sid
		 * @param $name string
		 * @param $backup
		 * @param $backup_site
		 * @desc Constructor to setup the initial values needed for sessions
		 */
		function Session_5(&$sql, &$db2, $database, $schema, $table, $sid = NULL, $name = 'ssid', $backup=FALSE, $backup_site=NULL)
		{
			// Set the object properties
			$this->sql = &$sql;
			$this->db2 = &$db2;
			$this->database = $database;
			$this->schema = $schema;
			$this->table = $table;
			$this->name = $name;
			$this->backup_site = $backup_site;
			$this->mysql_backup = $backup;

			// Set the session name
			session_name($this->name);

			// Set the session id
			if (!is_null($sid))
			{
				session_id($sid);
			}
			
			// Turn the pixel handler off by default
			$this->pixel_handler = 0;
			
			// Establish the session parameters
			session_set_save_handler
			(
				array (&$this, "Open"),
				array (&$this, "Close"),
				array (&$this, "Read"),
				array (&$this, "Write"),
				array (&$this, "Destroy"),
				array (&$this, "Garbage_Collection")
			);

			// Start the session
			session_start();
					
			// All done
			return TRUE;
		}
				
		/**
		 * @return bool
		 * @param $license_key string
		 * @param $promo_id int
		 * @param $promo_sub_cdoe string
		 * @param $batch_id=null
		 * @desc Sets up session config and gets stats ready
		 */
		function Session_Config($license_key, $promo_id, $promo_sub_code, $batch_id = NULL)
		{
			// Identity Block (Session settings always override hand code)
			if (! is_object($_SESSION ["config"]))
			{
				$this->batch_id = $batch_id;

				// Not in the session create the data
				$result = Config_3::Get_Site_Config ($license_key, $promo_id, $promo_sub_code);

				if(Error_2::Check($result) || ! strlen ($result->site_name))
				{
					return $result;
				}
				else
				{
					$_SESSION ["config"] = $result;
					$_SESSION ["stat_info"] = Set_Stat_1::Setup_Stats ($_SESSION ["config"]->site_id, $_SESSION ["config"]->vendor_id, $_SESSION ["config"]->page_id, $_SESSION ["config"]->promo_id, $promo_sub_code, $this->sql, $_SESSION ["config"]->stat_base, $_SESSION ["config"]->promo_status, $this->batch_id);

					$_SESSION ["promo"]["promo_id"] = $_SESSION ["config"]->promo_id;
					$_SESSION ["promo"]["promo_sub_code"] = $promo_sub_code;

					$_SESSION ["unique_stat"] = new stdClass ();
				}
				return TRUE;
			}

			return FALSE;
		}
		
		function Session_Config2($license, $promo_id, $promo_sub_code, $batch_id = NULL, $site_type = NULL)
		{
			require_once("config.4.php");
			
			// Identity Block (Session settings always override hand code)
			if (! is_object($_SESSION ["config"]))
			{
				$this->batch_id = $batch_id;
				
				// Not in the session create the data
				$result = Config_4::Get_Site_Config($license, $promo_id, $promo_sub_code, $site_type);
				
				if(Error_2::Check($result) || ! strlen ($result->site_name))
				{
					return $result;
				}
				else
				{
					$_SESSION ["config"] = $result;
					$_SESSION ["stat_info"] = Set_Stat_1::Setup_Stats ($_SESSION ["config"]->site_id, $_SESSION ["config"]->vendor_id, $_SESSION ["config"]->page_id, $_SESSION ["config"]->promo_id, $promo_sub_code, $this->sql, $_SESSION ["config"]->stat_base, $_SESSION ["config"]->promo_status, $this->batch_id);

					$_SESSION ["promo"]["promo_id"] = $_SESSION ["config"]->promo_id;
					$_SESSION ["promo"]["promo_sub_code"] = $promo_sub_code;

					$_SESSION ["unique_stat"] = new stdClass ();
				}
				return TRUE;
			}

			return FALSE;
		}
		
		function Session_Config_Ext($license_key, $promo_id, $promo_sub_code, $batch_id = NULL)
		{
			// Identity Block (Session settings always override hand code)
			if (! is_object($_SESSION ["config"]))
			{
				$this->batch_id = $batch_id;

				// Not in the session create the data
				$result = Config_3::Get_Site_Config ($license_key, $promo_id, $promo_sub_code);

				if(Error_2::Check($result) || ! strlen ($result->site_name))
				{
					return $result;
				}
				else
				{
					$_SESSION ["config"] = $result;
					$_SESSION ["stat_info"] = Set_Stat_1::Setup_Stats ($_SESSION ["config"]->site_id, $_SESSION ["config"]->vendor_id, $_SESSION ["config"]->page_id, $promo_id, $promo_sub_code, $this->sql, $_SESSION ["config"]->stat_base, $_SESSION ["config"]->promo_status, $this->batch_id);

					$_SESSION ["promo"]["promo_id"] = $promo_id;
					$_SESSION ["promo"]["promo_sub_code"] = $promo_sub_code;

					$_SESSION ["unique_stat"] = new stdClass ();
				}
				return TRUE;
			}

			return FALSE;
		}

		function Reset_Stat($batch_id = NULL)
		{
			$this->batch_id = $batch_id;
			$_SESSION ["stat_info"] = Set_Stat_1::Setup_Stats ($_SESSION ["config"]->site_id, $_SESSION ["config"]->vendor_id, $_SESSION ["config"]->page_id, $_SESSION ["promo"]["promo_id"], $_SESSION ["promo"]["promo_sub_code"], $this->sql, $_SESSION ["config"]->stat_base, $_SESSION ["config"]->promo_status, $this->batch_id);

			return TRUE;
		}

		/**
		 * @return bool
		 * @param $name string
		 * @param $value int
		 * @param $unique
		 * @desc Hit a specific stat with the value passed, 1 by default
		 */
		function Hit_Stat ($name, $value = 1, $unique = TRUE)
		{
			if (!$unique || ! isset ($_SESSION ["unique_stat"]->$name))
			{
				Set_Stat_1::Set_Stat ($_SESSION ["stat_info"]->block_id, $_SESSION ["stat_info"]->tablename, $this->sql, $_SESSION ["config"]->stat_base, $name, $value);
				$_SESSION ["unique_stat"]->$name = TRUE;
				
				// If the pixel handler is on, check for tracking pixels for this column in config.
				if($this->pixel_handler)
				{
					$this->Check_Pixel($name);								
				}

				return TRUE;
			}

			return FALSE;
		}

		function Open($save_path, $session_name)
		{
			return TRUE;
		}

		function Close()
		{
			return TRUE;
		}
		
		function Read($session_id)
		{
			// If the backup is set to true insert into mysql also
			if($this->mysql_backup)
			{
				$mysql_query = "
					SELECT 
						session_info
					FROM
						`session`
					WHERE
						session_id = '".$session_id."'";
				$mysql_result = $this->sql->Query('session_backup', $mysql_query, "\t".__FILE__."->".__LINE__."\n");
				$mysql_row_count = $this->sql->Row_Count($mysql_result);

				// If the session is not found, create it
				if($mysql_row_count <  1)
				{
					$mysql_query = "
						INSERT INTO
							session
							(session_id,date_modified,date_created,session_info,site)
						VALUES
							('".$session_id."',NOW(),NOW(),'".$session_info."','".$this->backup_site."')";

					$result = $this->sql->Query('session_backup', $mysql_query, "\t".__FILE__."->".__LINE__."\n");	
				}
			}
			
			// Count if session exists
			$query = "
				SELECT 
					count(*)
				FROM
					".$this->schema.".".$this->table."
				WHERE
					session_id = '".$session_id."'";
			
			$result = $this->db2->Execute($query);

			// Get the count in a var	
			$count = $result->Fetch_Array(0);
			
			// Determine if we found a row
			if($count[1] > 0)
			{
				$sql_select = "
				SELECT 
					session_info
				FROM
					".$this->schema.".".$this->table."
				WHERE
					session_id = '".$session_id."'
				FOR READ ONLY";
			
				$result = $this->db2->Execute($sql_select);
				
				// Give the session information back
				$session_info = $result->Fetch_Array();

				return $session_info['SESSION_INFO'];
			}
			// There were no rows
			else
			{
				// Set any defaults here (APP_COMPLETED should be 0 not null)
								 
				// Start a new session in DB2
				$query = "
					INSERT INTO 
						".$this->schema.".".$this->table."
						(date_modified, date_created, session_id, session_info)					VALUES
						(CURRENT TIMESTAMP, CURRENT TIMESTAMP, ?, ?)";
				// Prepare the query
				$query_insert = $this->db2->Query($query);
		
				// Execute the query and give it the parameters needed
				$result = $query_insert->Execute($session_id,
												 $session_info
												 );
			}
				
			// Return nothing, because there was nothing
			return "";
		}
		
		/**
		 * @return bool
		 * @param $session_id string
		 * @param $session_info string
		 * @desc Writes the date to the session tables
		 */
		function Write($session_id, $session_info)
		{
			//If you want to do any debugging in this method,
			//you best write output to a file b/c you'll never
			//see it in the browser
			//$fp = fopen("/tmp/session.txt", "w");
			//fwrite($fp, print_r($session_info, true));
			//fclose($fp);	
		
			// If mysql_backup is set to true,.. update mysql
			if($this->mysql_backup)
			{
				$mysql_update = "
					UPDATE
						session
					SET
						session_info = '".$session_info."'
					WHERE
						session_id = '".$session_id."'";
				$result = $this->sql->Query('session_backup', $mysql_update, "\t".__FILE__."->".__LINE__."\n");	
			}
			
			// Generate the query using parametization to db2
			$sql_update = "
				UPDATE 
					".$this->schema.".".$this->table."
				SET
					date_modified = CURRENT TIMESTAMP,
					session_info = ?,
					NAME_FIRST = ?,
					NAME_LAST = ?,
					STATE_ID = ?,
					HAS_PHONE = ?,
					ACTIVE_EMAIL_ADDRESS = ?,
					TIME_ZONE_ID = ?
				WHERE
					session_id = ?";

			// Prepare the query
			$query_update = $this->db2->Query($sql_update);
			
			// Write addtl_info if needed
			$addtl_info = $this->Addtl_Info($GLOBALS['HTTP_SESSION_VARS']['data']);

			// Execute the query and give it the parameters needed
			$result = $query_update->Execute($session_info,
											 $addtl_info['NAME_FIRST'],
											 $addtl_info['NAME_LAST'],
											 $addtl_info['STATE_ID'],
											 $addtl_info['HAS_PHONE'],
											 $addtl_info['ACTIVE_EMAIL_ADDRESS'],
											 $addtl_info['TIME_ZONE_ID'],
											 $session_id
											 );

			//return TRUE;
			//Let's return TRUE if it passed the error test
			return !Error_2::Error_Test($result);
		}
		
		/**
		 * @return bool
		 * @param $session_id string
		 * @desc Remove the session from the database
		 */
		function Destroy($session_id)
		{
			// Remove the session id from the database
			$query = "
				DELETE
				FROM
					".$this->schema.".".$this->table."
				WHERE
					session_id = '".$session_id."'";
							
			$result = $this->db2->Execute($query);

			
			//return TRUE;
			//Let's return TRUE if it passed the error test
			return !Error_2::Error_Test($result);
		}

		/**
		 * @return bool
		 * @param $session_life string
		 * @desc Does nothing right now.
		 */
		function Garbage_Collection($session_life)
		{
			// Not clear what to do here, so return true to make all happy
			return TRUE;
		}
		
		/**
		 * @return string
		 * @param $session_info varchar
		 * @desc Currently runs mysql escape on the session info passed in, this will most likely
		 * @desc expand at a later time when we figure out what db2 doesn't like.
		 */
		function Db2_Escape_String($session_info)
		{
			return mysql_escape_string($session_info);	
		}
		
		/**
		 * @return string
		 * @param $session_id string
		 * @desc Returns the session_row_id column associated with a session_id.
		 */
		function Get_Session_Row_Id($session_id)
		{
			// Select the row id for this session id
			$query = "
				SELECT
					session_row_id
				FROM
					".$this->schema.".session
				WHERE
					session_id = '".$session_id."'";
			
			$result = $this->db2->Execute($query);
			$row_id = $result->Fetch_Array();
			
			// Return the row id
			return $row_id['SESSION_ROW_ID'];
		}
		
		/**
		 * @return array('NAME_FIRST', 'NAME_LAST', 'STATE_ID', 'HAS_PHONE', 'ACTIVE_EMAIL_ADDRESS', 'TIME_ZONE_ID')
		 * @param $session_data string
		 * @desc Addtl_Info gets Additional Information
		 * which should be pulled out of the session_data
		 * and put in a seperate selectable column.
		 */
		function Addtl_Info($session_data)
		{
			//let's just set all these to zero(false)/NULL so we only have to
			//set the ones we find
			$addtl_info = array(
				'NAME_FIRST' => NULL,
				'NAME_LAST' => NULL,
				'STATE_ID' => NULL,
				'HAS_PHONE' => 0,
				'ACTIVE_EMAIL_ADDRESS' => NULL,
				'TIME_ZONE_ID' => NULL);
			
			//now resolve any special columns such as HAS_PHONE, STATE_ID
			//and TIME_ZONE_ID
			
			if(strlen($session_data['home_state']))
			{
				$state_info = $this->_Get_State_Info($session_data['home_state']);
				$addtl_info['STATE_ID'] = $state_info['STATE_ID'];
				$addtl_info['TIME_ZONE_ID'] = $state_info['TIME_ZONE_ID'];
			}
			
			if(strlen($session_data['phone_home']) ||
			   strlen($session_data['phone_work']) ||
			   strlen($session_data['phone_cell']) )
			{
				$addtl_info['HAS_PHONE'] = 1;
			}
			
			// Create a map for the other column names to what we're
			// looking for.		
			$field_map = array(
				'NAME_FIRST' => "name_first",
				'NAME_LAST' => "name_last",
				'ACTIVE_EMAIL_ADDRESS' => "email_primary"
				);
			
			
			foreach($field_map AS $column=>$session_var)
			{
				// If the session_info passed in contains the fields in our array
				if(strlen($session_data[$session_var]))
				{
					// Add to the array
					$addtl_info[$column] = $session_data[$session_var];
				}
			}
			
 			return $addtl_info;
		}

		/**
		 * @return bool
		 * @param $session_id string
		 * @param $sub_status_id int
		 * @desc Sets the session transaction substatus
		 * to the substatus_id passed in.
		 */
		function Set_Sub_Status($session_id, $sub_status_id)
		{
			// Generate the query using parametization to db2
			$sql_update = "
				UPDATE 
					".$this->schema.".".$this->table." as session
				SET
					date_modified = CURRENT TIMESTAMP,
					TRANSACTION_SUB_STATUS = {$sub_status_id}
				WHERE
					(TRANSACTION_SUB_STATUS IS NULL OR
				    TRANSACTION_SUB_STATUS <> (SELECT TRANSACTION_SUB_STATUS_ID FROM ".$this->schema.".TRANSACTION_SUB_STATUS
												WHERE NAME = 'CASHLINE'))
					and session_id = '{$session_id}'";

			// Prepare the query
			$result = $this->db2->Execute($sql_update);
			
			//return TRUE;
			//Let's return TRUE if it passed the error test
			return !Error_2::Error_Test($result);
		}

		/**
		 * @return bool
		 * @param $session_id string
		 * @param $is_short_form bool
		 * @desc Sets the session short form flag if it is a short form site
		 */
		function Set_Short_Form($session_id, $is_short_form)
		{
			if(!$is_short_form) { $is_short_form = 0; }
			
			$sql_update = "
				UPDATE 
					".$this->schema.".".$this->table."
				SET
					date_modified = CURRENT TIMESTAMP,
					short_form_site = {$is_short_form}
				WHERE
					session_id = '{$session_id}'";

			// Execute the query
			$result = $this->db2->Execute($sql_update);
			
			//return TRUE;
			//Let's return TRUE if it passed the error test
			return !Error_2::Error_Test($result);
		}

		 /**
		 * @return array
		 * @param $home_state string
		 * @desc Return the state id and timezone associated with the home_state param
		 */
		function _Get_State_Info($home_state)
		{
			// Get the id for the state
			$query = "
					SELECT
						state_id, time_zone_id
					FROM
						".$this->schema.".STATE
					WHERE
						name = '".$home_state."'";
					
			$result = $this->db2->Execute($query);
					
			$data = $result->Fetch_Array();
			
			if(!isset($data['STATE_ID']))
			{
				$data['STATE_ID'] = NULL;
			}

			if(!isset($data['TIME_ZONE_ID']))
			{
				$data['TIME_ZONE_ID'] = NULL;
			}
						
			return $data;
		}
		
		// This needs to be called for the tracking pixel handler to be enabeled.
		function Enable_Pixel_Handler()
		{
			$this->pixel_handler = 1;
			
			return TRUE;
		}
		
		// If the tracking pixel handler is enabeled, hit stat will call this to check/add pixels.
		function Check_Pixel($name)
		{
			// Clear out pixels from previous stats.  If we aren't live begin html block comment.
			$this->current_pixels = ( strtoupper($_SESSION['config']->mode)  == "LIVE") ? "" : " <!-- ";
									
			// If its an old school tracking pixel and we are on accepted, add the old school pixel to our array.
			if( $name == "accepted" && isset($_SESSION['config']->tracking_pixel) && strlen( trim($_SESSION['config']->tracking_pixel) ) )
			{
				$_SESSION['config']->event_pixel[$name][] = array( "tracking_pixel" => $_SESSION['config']->tracking_pixel );	
			}

			// Do we have any event pixels for this stat column?
			if (isset($_SESSION['config']->event_pixel[$name]) 
					&& is_array($_SESSION['config']->event_pixel[$name])
					&& count($_SESSION['config']->event_pixel[$name]) )
			{
				// Set our available expansion stuff.
				$replace['unique_id'] = session_id();
				$replace['application_id'] = $_SESSION["application_id"];
				$replace['email'] = $_SESSION["data"]["email_primary"];
				$replace['promo_sub_code'] = $_SESSION["config"]->promo_sub_code;
				$replace['return_data'] = $_SESSION['data']['return_data'];
				$replace['pwadvid'] = $_SESSION["data"]['pwadvid'];
				
				// Loop thru event pixels for this stat column.
				foreach($_SESSION['config']->event_pixel[$name] as $pixel)
				{
					// If we have a sub code in our pixel, and our current sub code does not equal, skip adding this pixel.
					if( isset($pixel['subcode']) && $pixel['subcode'] != $_SESSION['config']->promo_sub_code )
					{
						continue;
					}
					
					// If we find expansion stuff in our pixel use replace with whats in the replace array.
					$pixel['tracking_pixel'] = preg_replace ("/%%%(.*?)%%%/e", "\$replace[\\1]", $pixel['tracking_pixel']);
					
					// If the tracking pixel starts with http, lets add some formatting.
					if( substr( trim($pixel['tracking_pixel']),0,4) == "http" )
					{
						$this->current_pixels .= "<img src=\"{$pixel['tracking_pixel']}\" width=\"1\" height=\"1\" border=\"0\" alt=\"promo_id:{$_SESSION['config']->promo_id}\">";
					}
					else // Leave as is
					{
						$this->current_pixels .= $pixel['tracking_pixel'];
					}
				}
			}
			
			// Test for our mode again and if we aren't live end our block comment.
			if( strtoupper($_SESSION['config']->mode)  != "LIVE" )
			{
				$this->current_pixels .= " --> ";
			}
			
			return TRUE;
		}
		
		// Returns our current pixel string if one exists and it has data.
		function Fetch_Pixels()
		{
			if( isset($this->current_pixels) && strlen($this->current_pixels) )
			{
				return $this->current_pixels;
			}
			return FALSE;
		}
	}
?>