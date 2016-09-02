<?php
	// Version 6.0.0
	// A tool to handle sessions

	/*
		6.0.0 - split among tables to reduce locking contention and avoid 4GB table limit
	*/

	require_once ("config.3.php");
	require_once ("setstat.1.php");

	class Session_6
	{
		var $sql;
		var $database;
		var $table;
		var $name;
		var $pixel_handler;
		var $current_pixels;
		var $compression;

		function Session_6 (&$sql, $database, $table, $sid = NULL, $name = 'ssid', $compression = 'gz')
		{
			// Set the object properties
			$this->sql = &$sql;
			$this->database = $database;
			$this->table = $table;
			$this->name = $name;
			$this->current_pixels = "";
			$this->compression = $compression;

			// Turn the pixel handler off by default
			$this->pixel_handler = 0;

			// Set the session name
			session_name ($this->name);

			// Set the session id
			if ($sid)
			{
				session_id ($sid);
			}

			$sid = session_id();

			$this->table = 'session_'.strtolower (substr ($sid, 0, 1));

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

		function Open ($save_path, $session_name)
		{
			return true;
		}

		function Close ()
		{
			return true;
		}

		function Record_Exists ($id)
		{
			$query = "select COUNT(*) AS n from ".$this->table." where session_id = '".$id."'";
			$result = $this->sql->Query ($this->database, $query);
			Error_2::Error_Test ($result, TRUE);
			$row = $this->sql->Fetch_Array_Row ($result);
			return $row['n'];
		}

		function Record_Fetch ($id)
		{
			$query = "select IF(DATE_ADD(date_locked, INTERVAL 60 SECOND) > NOW(), 0, 1) AS lock_timeout,date_locked,session_lock,session_info,compression from ".$this->table." where session_id = '".$id."'";
			$result = $this->sql->Query ($this->database, $query);
			Error_2::Error_Test ($result, TRUE);
			$row = $this->sql->Fetch_Array_Row ($result);
			return $row;
		}

		function Record_Lock ($id)
		{
			$query = "update ".$this->table." set date_locked = NOW(), session_lock = 1 where session_id = '".$id."' limit 1";
			$result = $this->sql->Query ($this->database, $query);
			Error_2::Error_Test ($result, TRUE);
		}

		function Read ($id)
		{
			$attempt = 7;
			$pause = 10;

			if ($this->Record_Exists ($id))
			{
				do
				{
					$row = $this->Record_Fetch ($id);
				}
				while ($row['session_lock'] && !$row['lock_timeout'] && $attempt-- && sleep ($pause));

				// Give the session information back
				switch($row['compression'])
				{
					case "gz":
					$re = gzuncompress($row['session_info']);
					break;

					case "bz":
					$re = bzdecompress($row['session_info']);
					break;

					default:
					$re = $row['session_info'];
					break;
				}
				unset ($row['session_info']);

				if ($row['session_lock'])
				{
					//mail ('rodricg@sellingsource.com', 'SID::'.$id.'::'.date('YmdHis'), var_export($row,1)."\n\n".var_export(@unserialize(@gzuncompress($GLOBALS['HTTP_RAW_POST_DATA'])),1));
				}
				else
				{
					$this->Record_Lock ($id);
				}

				return $re;
			}
			// There were no rows
			else
			{
				// Start a new sesssion
				$query = "insert into ".$this->table." (session_id, date_created) values ('".$id."', NULL)";
				$result = $this->sql->Query ($this->database, $query, "\t".__FILE__." -> ".__LINE__."\n");

				// Error checking
				Error_2::Error_Test ($result);
			}

			// Return nothing, because there was nothing
			return "";
		}

		function Write($id, $session_info)
		{
			switch($this->compression)
			{
				case "gz":
				// Update the db
				$query = "update ".$this->table." set session_lock = 0, session_info='".mysql_escape_string(gzcompress($session_info))."', compression='".$this->compression."' where session_id='".$id."'";
				break;

				case "bz":
				$query = "update ".$this->table." set session_lock = 0, session_info='".mysql_escape_string(bzcompress($session_info))."' compression='".$this->compression."'where session_id='".$id."'";
				break;

				default:
				$query = "update ".$this->table." set session_lock = 0, session_info='".mysql_escape_string($session_info)."' compression='none' where session_id='".$id."'";
				break;
			}

			$result = $this->sql->Query ($this->database, $query, "\t".__FILE__." -> ".__LINE__."\n");

			// Error checking
			Error_2::Error_Test ($result);

			// All went well
			return TRUE;
		}

		function Destroy ($id)
		{
			// Blow it off the datase
			$query = "delete from ".$this->table." where session_id='".$id."'";
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
