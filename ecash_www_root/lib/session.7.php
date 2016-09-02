<?PHP
/*
	@version
			7.0.0 2004-10-01 - Nick White
					
	Updates:
	-10/01	A tool to handle sessions/auto process using DB2 w/ compression

	Notes:
		- This file is well commented,... keep it that way.
*/
	
require_once ("config.5.php");
require_once ("setstat.1.php");

class Session_7
{
	protected $sql;
	protected $db2;
	protected $database;
	protected $table;
	protected $name;
	protected $pixel_handler;
	protected $current_pixels;
	protected $compression;

	/**
	* @return bool
	* @param $sql obj
	* @param $db2 obj
	* @param $database string
	* @param $table string
	* @param $sid string
	* @param $name string
	* @param $compression string (gz,bz,none)
	* @desc Constructor to setup the initial values needed for sessions
	*/
	function __construct(&$sql, &$db2, $database, $table, $sid = NULL, $name = 'ssid'/*,$compression = 'gz'*/)
	{
		// Set the object properties
		$this->sql = &$sql;
		$this->db2 = &$db2;
		$this->database = $database;
		$this->table = $table;
		$this->name = $name;
		/*$this->compression = $compression;*/

		// Set the session name
		session_name($this->name);

		// Set the session id
		if(!is_null($sid))
		{
			session_id($sid);
		}
		
		// Turn the pixel handler off by default
		$this->pixel_handler = 0;

		// Establish the session parameters
		session_set_save_handler
		(
			array(&$this, "Open"),
			array(&$this, "Close"),
			array(&$this, "Read"),
			array(&$this, "Write"),
			array(&$this, "Destroy"),
			array(&$this, "Garbage_Collection")
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
	public function Session_Config($license_key, $promo_id, $promo_sub_code, $batch_id = NULL)
	{
		// Identity Block (Session settings always override hand code)
		if(!is_object($_SESSION["config"]))
		{
			$this->batch_id = $batch_id;

			// Not in the session create the data

			$result = Config_5::Get_Site_Config($license_key, $promo_id, $promo_sub_code, $page);

			if(Error_2::Check($result) || ! strlen($result->site_name))
			{
				return $result;
			}
			else
			{
				$_SESSION["stat_info"] = Set_Stat_1::Setup_Stats ($_SESSION["config"]->site_id, $_SESSION["config"]->vendor_id, $_SESSION["config"]->page_id, $_SESSION["config"]->promo_id, $promo_sub_code, $this->sql, $_SESSION["config"]->stat_base, $_SESSION["config"]->promo_status, $this->batch_id);
				$_SESSION["promo"]["promo_id"] = $_SESSION["config"]->promo_id;
				$_SESSION["promo"]["promo_sub_code"] = $promo_sub_code;

				$_SESSION["unique_stat"] = new stdClass ();
			}
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @return bool
	 * @param $license_key string
	 * @param $promo_id int
	 * @param $promo_sub_cdoe string
	 * @param $batch_id=null
	 * @desc Sets up session config and gets stats ready
	 */
	public function Session_Config2($license_key, $promo_id, $promo_sub_code, $batch_id = NULL)
	{
		if(!is_object($_SESSION['config']))
		{
			$this->batch_id = $batch_id;
			
			$config = new Config_5($_SERVER['SERVER_NAME'],'AUTO');
			$result = $config->Get_Site_Config($license_key, $promo_id, $promo_sub_code, $page);
			if(Error_2::Check($result) || ! strlen($result->site_name))
			{
				return $result;
			}
			else 
			{
				$_SESSION['config'] = $result;
				$_SESSION["stat_info"] = Set_Stat_1::Setup_Stats ($_SESSION["config"]->site_id, $_SESSION["config"]->vendor_id, $_SESSION["config"]->page_id, $_SESSION["config"]->promo_id, $promo_sub_code, $this->sql, $stat_db, $_SESSION["config"]->promo_status, $this->batch_id);
				$_SESSION["promo"]["promo_id"] = $_SESSION["config"]->promo_id;
				$_SESSION["promo"]["promo_sub_code"] = $promo_sub_code;

				$_SESSION["unique_stat"] = new stdClass ();
			}
			return true;
		}
		return FALSE;
	}
	
	/**
	 * @return bool
	 * @param $nbatch_id int
	 * @desc Reset the stat module and setup a new one
	 */
	public function Reset_Stat($batch_id = NULL)
	{
		$this->batch_id = $batch_id;
		$_SESSION["stat_info"] = Set_Stat_1::Setup_Stats($_SESSION["config"]->site_id, $_SESSION["config"]->vendor_id, $_SESSION["config"]->page_id, $_SESSION["promo"]["promo_id"], $_SESSION["promo"]["promo_sub_code"], $this->sql, $_SESSION["config"]->stat_base, $_SESSION["config"]->promo_status, $this->batch_id);

		return TRUE;
	}

	/**
	 * @return bool
	 * @param $name string
	 * @param $value int
	 * @param $unique
	 * @desc Hit a specific stat with the value passed, 1 by default
	 */
	public function Hit_Stat($name, $value = 1, $unique = TRUE)
	{
		if(!$unique || ! isset($_SESSION["unique_stat"]->$name) && session_name() == 'unique_id')
		{
			Set_Stat_1::Set_Stat($_SESSION["stat_info"]->block_id, $_SESSION["stat_info"]->tablename, $this->sql, $_SESSION["config"]->stat_base, $name, $value);
			$_SESSION["unique_stat"]->$name = TRUE;
			$_SESSION['unique_session'][session_id()] = $_SESSION['unique_session'][session_id()] +1;
			
			// If the pixel handler is on, check for tracking pixels for this column in config.
			if($this->pixel_handler)
			{
				$this->Check_Pixel($name);								
			}
			return TRUE;
		}

		return FALSE;
	}

	public function Open($save_path, $session_name)
	{

		return TRUE;
	}

	public function Close()
	{
		return TRUE;
	}
	
	/**
	 * @return void(0)
	 * @param $session_id
	 * @desc Read the session data out of the database
	 */
	public function Read($session_id)
	{
		// Count if session exists
		$query = "
			SELECT 
				count(*) as exists
			FROM
				".$this->table."
			WHERE
				session_id = '".$session_id."' FOR READ ONLY";

		// Get the count in a var
		$result = $this->db2->Execute($query);
		$count = $result->Fetch_Array();

		// Determine if we found a row
		if($count[EXISTS] > 0)
		{
			$query = "
			SELECT 
				session_info
			FROM
				".$this->table."
			WHERE
				session_id = '".$session_id."'
			";
			
			$query .= " ORDER BY DATE_MODIFIED DESC FETCH FIRST 1 ROW ONLY FOR READ ONLY";

			$result = $this->db2->Execute($query);
			// Give the session information back
			$session_info = $result->Fetch_Array();

			// Give the session information back
			switch("asdf"/*$session_info['COMPRESSION']*/)
			{
				case "gz":
				$re = gzuncompress($session_info['SESSION_INFO']);
				break;

				case "bz":
				$re = bzdecompress($session_info['SESSION_INFO']);
				break;

				default:
				$re = $session_info['SESSION_INFO'];
				break;
			}

			if($re === FALSE)
			{
				throw new Db2_Exception('Decompression of type "'.$session_info['SESSION_INFO'].'" has failed');	
			}
			
			return $re;
		}
		else
		{
			// Start a new session in DB2
			/*$query = "
				INSERT INTO 
					".$this->table."
					(date_modified, date_created, session_id, session_info, compression)
				VALUES
					(CURRENT TIMESTAMP, CURRENT TIMESTAMP, ?, ?, ?)";
			*/
			
			$query = "
				INSERT INTO 
					".$this->table."
					(date_modified, date_created, session_id, session_info)
				VALUES
					(CURRENT TIMESTAMP, CURRENT TIMESTAMP, ?, ?)";
			
			// Prepare the query
			$query_insert = $this->db2->Query($query);
			
			// Choose a compression
			switch("asdf"/*$this->compression*/)
			{
				case "gz":
				$session_info = gzcompress($session_info); 
				break;
				
				case "bz":
				$session_info = bzcompress($session_info); 
				break;
				
				default:
				NULL;
				break;
			}
			// Execute the query and give it the parameters needed
			$result = $query_insert->Execute($session_id, $session_info/*, $this->compression*/);
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
	public function Write($session_id, $session_info)
	{
		// Generate the query using parametization to db2
		/*$query = "
		UPDATE 
			".strtoupper($this->table)."
		SET
			DATE_MODIFIED = CURRENT TIMESTAMP,
			SESSION_INFO = ?,
			NAME_FIRST = ?,
			NAME_LAST = ?,
			STATE_ID = ?,
			HAS_PHONE = ?,
			ACTIVE_EMAIL_ADDRESS = ?,
			TIME_ZONE_ID = ?,
			COMPRESSION = ?
		WHERE
			session_id = ?";
		*/
		
		$query = "
		UPDATE 
			".strtoupper($this->table)."
		SET
			DATE_MODIFIED = CURRENT TIMESTAMP,
			SESSION_INFO = ?,
			NAME_FIRST = ?,
			NAME_LAST = ?,
			STATE_ID = ?,
			HAS_PHONE = ?,
			ACTIVE_EMAIL_ADDRESS = ?,
			TIME_ZONE_ID = ?
		WHERE
			session_id = ?";
		
		// Prepare the query
		$query_update = $this->db2->Query($query);
		
		// Write addtl_info if needed
		$addtl_info = $this->Addtl_Info($GLOBALS['HTTP_SESSION_VARS']['data']);

		switch("asdf"/*$this->compression*/)
		{
			case "gz":
			$session_info = gzcompress($session_info); 
			break;
			
			case "bz":
			$session_info = bzcompress($session_info); 
			break;
			
			default:
			NULL;
			break;
		}
//		echo "<pre>$session_id\n";print_r($session_info);exit;
		if($session_info === FALSE)
		{
			throw new Db2_Exception('Compression of type "'.$this->compression.'" has failed');	
		}
		// Execute the query and give it the parameters needed
		$result = $query_update->Execute($session_info,
								   $addtl_info['NAME_FIRST'],
								   $addtl_info['NAME_LAST'],
								   $addtl_info['STATE_ID'],
								   $addtl_info['HAS_PHONE'],
								   $addtl_info['ACTIVE_EMAIL_ADDRESS'],
								   $addtl_info['TIME_ZONE_ID'],
								   /*$this->compression,*/
								   $session_id
								   );
//								   print_r($result);exit;
		return TRUE;
	}
	
	/**
	 * @return bool
	 * @param $session_id string
	 * @desc Remove the session from the database
	 */
	public function Destroy($session_id)
	{
		// Remove the session id from the database
		$query = "
			DELETE
			FROM
				".$this->table."
			WHERE
				session_id = '".$session_id."'";
						
		$result = $this->db2->Execute($query);

		return TRUE;
	}

	/**
	 * @return bool
	 * @param $session_life string
	 * @desc Does nothing right now.
	 */
	public function Garbage_Collection($session_life)
	{
		return TRUE;
	}
		
	/**
	 * @return string
	 * @param $session_id string
	 * @desc Returns the session_row_id column associated with a session_id.
	 */
	public function Get_Session_Row_Id($session_id)
	{
		// Select the row id for this session id
		$query = "
			SELECT
				session_row_id
			FROM
				".$this->table.".
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
	public function Addtl_Info($session_data)
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
	public function Set_Sub_Status($session_id, $sub_status_id)
	{
		// Generate the query using parametization to db2
		$query = "
			UPDATE 
				".$this->table." as session
			SET
				date_modified = CURRENT TIMESTAMP,
				TRANSACTION_SUB_STATUS = {$sub_status_id}
			WHERE
				(TRANSACTION_SUB_STATUS IS NULL OR
			    TRANSACTION_SUB_STATUS <> (SELECT TRANSACTION_SUB_STATUS_ID FROM TRANSACTION_SUB_STATUS
											WHERE NAME = 'CASHLINE'))
				and session_id = '{$session_id}'";

		// Prepare the query
		$result = $this->db2->Execute($query);
		
		return TRUE;
	}

	/**
	 * @return bool
	 * @param $session_id string
	 * @param $is_short_form bool
	 * @desc Sets the session short form flag if it is a short form site
	 */
	public function Set_Short_Form($session_id, $is_short_form)
	{
		if(!$is_short_form)
		{ 
			$is_short_form = 0;
		}
		
		$query = "
			UPDATE 
				".$this->table."
			SET
				date_modified = CURRENT TIMESTAMP,
				short_form_site = {$is_short_form}
			WHERE
				session_id = '{$session_id}'";

		// Execute the query
		$result = $this->db2->Execute($query);
		
		return TRUE;
	}

	 /**
	 * @return array
	 * @param $home_state string
	 * @desc Return the state id and timezone associated with the home_state param
	 */
	public function _Get_State_Info($home_state)
	{
		// Get the id for the state
		$query = "
				SELECT
					state_id, time_zone_id
				FROM
					STATE
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
	public function Enable_Pixel_Handler()
	{
		$this->pixel_handler = 1;
		
		return TRUE;
	}
	
	// If the tracking pixel handler is enabeled, hit stat will call this to check/add pixels.
	public function Check_Pixel($name)
	{
		// Clear out pixels from previous stats.  If we aren't live begin html block comment.
		$this->current_pixels = (strtoupper($_SESSION['config']->mode)  == "LIVE") ? "" : " <!-- ";
								
		// If its an old school tracking pixel and we are on accepted, add the old school pixel to our array.
		if( $name == "accepted" && isset($_SESSION['config']->tracking_pixel) && strlen(trim($_SESSION['config']->tracking_pixel) ) )
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
			
			// Loop thru event pixels for this stat column.
			foreach($_SESSION['config']->event_pixel[$name] as $pixel)
			{
				// If we have a sub code in our pixel, and our current sub code does not equal, skip adding this pixel.
				if(isset($pixel['subcode']) && $pixel['subcode'] != $_SESSION['config']->promo_sub_code )
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
	public function Fetch_Pixels()
	{
		if(isset($this->current_pixels) && strlen($this->current_pixels) )
		{
			return $this->current_pixels;
		}
		return FALSE;
	}
}
?>
