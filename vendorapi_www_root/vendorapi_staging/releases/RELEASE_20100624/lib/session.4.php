<?php
	// Version 4.2.0
	// A tool to handle sessions

	/*
		4.2.0 - added Session_Config_Ext for hitting teleweb stats for forced promo license keys
		4.1.0 - added Reset_Stat call for return visitor
	*/

	require_once ("config.3.php");
	require_once ("setstat.1.php");

	class Session_4
	{
		var $sql;
		var $database;
		var $table;
		var $name;
		var $pixel_handler;
		var $current_pixels;

		function Session_4 (&$sql, $database, $table, $sid = NULL, $name = 'ssid')
		{
			// Set the object properties
			$this->sql = &$sql;
			$this->database = $database;
			$this->table = $table;
			$this->name = $name;
			$this->current_pixels = "";

			// Turn the pixel handler off by default
			$this->pixel_handler = 0;
			
			// Set the session name
			session_name ($this->name);

			// Set the session id
			if (!is_null ($sid))
			{
				session_id ($sid);
			}

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
			session_start ();

			// All done
			return TRUE;
		}

		function Session_Config ($license_key, $promo_id, $promo_sub_code, $batch_id = NULL)
		{
			// Identity Block (Session settings always override hand code)
			if (! is_object ($_SESSION ["config"]))
			{
				$this->batch_id = $batch_id;

				// Not in the session create the data
				$result = Config_3::Get_Site_Config ($license_key, $promo_id, $promo_sub_code);

				if (Error_2::Check($result) || ! strlen ($result->site_name))
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
			require_once ("config.4.php");
			
			// Identity Block (Session settings always override hand code)
			if (! is_object ($_SESSION ["config"]))
			{
				$this->batch_id = $batch_id;

				// Not in the session create the data
				$result = Config_4::Get_Site_Config($license, $promo_id, $promo_sub_code, $site_type);
				
				if (Error_2::Check($result) || ! strlen ($result->site_name))
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
		
		function Session_Config_Ext ($license_key, $promo_id, $promo_sub_code, $batch_id = NULL)
		{
			// Identity Block (Session settings always override hand code)
			if (! is_object ($_SESSION ["config"]))
			{
				$this->batch_id = $batch_id;

				// Not in the session create the data
				$result = Config_3::Get_Site_Config ($license_key, $promo_id, $promo_sub_code);

				if (Error_2::Check($result) || ! strlen ($result->site_name))
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

		function Reset_Stat ($batch_id = NULL)
		{
			$this->batch_id = $batch_id;
			$_SESSION ["stat_info"] = Set_Stat_1::Setup_Stats ($_SESSION ["config"]->site_id, $_SESSION ["config"]->vendor_id, $_SESSION ["config"]->page_id, $_SESSION ["promo"]["promo_id"], $_SESSION ["promo"]["promo_sub_code"], $this->sql, $_SESSION ["config"]->stat_base, $_SESSION ["config"]->promo_status, $this->batch_id);

			return TRUE;
		}

		function Hit_Stat ($name, $value = 1, $unique = TRUE )
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

		function Open ($save_path, $session_name)
		{
			return true;
		}

		function Close ()
		{
			return true;
		}

		function Read ($session_id)
		{
			// Try to get the result set
			$query = "select session_info from ".$this->table." where session_id = '".$session_id."'";
			$result = $this->sql->Query ($this->database, $query, "\t".__FILE__." -> ".__LINE__."\n");

			// Error checking
			Error_2::Error_Test ($result);

			// Determine if we found a row
			if ($this->sql->Row_Count ($result))
			{
				// Give the session information back
				return $this->sql->Fetch_Column ($result, "session_info");
			}
			// There were no rows
			else
			{
				// Start a new sesssion
				$query = "insert into ".$this->table." (session_id, created_date) values ('".$session_id."', NULL)";
				$result = $this->sql->Query ($this->database, $query, "\t".__FILE__." -> ".__LINE__."\n");

				// Error checking
				Error_2::Error_Test ($result);
			}

			// Return nothing, because there was nothing
			return "";
		}

		function Write($session_id, $session_info)
		{
			// Update the db
			$query = "update ".$this->table." set session_info='".mysql_escape_string ($session_info)."' where session_id='".$session_id."'";
			$result = $this->sql->Query ($this->database, $query, "\t".__FILE__." -> ".__LINE__."\n");

			/*
			$fp = fopen("/tmp/session.pizza", "a");
			fputs($fp, "Session_4->Write()" . date("Y-m-d H:i:m") . "\n");
			fputs($fp, "session_id: " . print_r($session_id, TRUE) . "\n");
			fputs($fp, "session_info: " . print_r($session_id, TRUE) . "\n");
			fputs($fp, "query: " . print_r($query, TRUE) . "\n");
			fputs($fp, "result: " . print_r($result, TRUE) . "\n");
			fclose($fp);
			*/

			// Error checking
			Error_2::Error_Test ($result);

			// All went well
			return TRUE;
		}

		function Destroy ($session_id)
		{
			// Blow it off the datase
			$query = "delete from ".$this->table." where session_id='".$session_id."'";
			$result = $this->sql->Query ($this->database, $query, "\t".__FILE__." -> ".__LINE__."\n");

			// Error checking
			Error_2::Error_Test ($result);

			return TRUE;
		}

		function Garbage_Collection ($session_life)
		{
			// Not clear what to do here, so return true to make all happy
			return TRUE;
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
			//echo "Checking Pixel: {$name}"; die();
												
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
				$replace['global_key'] = $_SESSION["statpro"]['global_key'];
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
			return TRUE;
		}
		
		// Returns our current pixel string if one exists and it has data.
		function Fetch_Pixels()
		{
			if( isset($this->current_pixels) && strlen($this->current_pixels) )
			{
				if( strtoupper($_SESSION['config']->mode)  != "LIVE" )
				{
					$return_pixels = "<!-- {$this->current_pixels} -->";
				}
				else
				{
					$return_pixels = $this->current_pixels;	
				}
				
				// Reset old pixels
				$this->current_pixels = "";
				
				return $return_pixels;
			}
			return FALSE;
		}
	}
?>
