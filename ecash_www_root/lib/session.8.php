<?php
	// Version 6.0.0
	// A tool to handle sessions

	/*
		6.0.0 - split among tables to reduce locking contention and avoid 4GB table limit
	*/

	// OLP has code that expects session.8.php to include setstat.2.php
	require_once ("setstat.2.php");
	
	// This is what we actually use.
	require_once ("setstat.3.php");

	class Session_8
	{
		
		protected $sql;
		protected $database;
		protected $table;
		protected $compression;
		protected $max_session_size;
		
		private $name;
		private $pixel_handler;
		protected $current_pixels;
		private $stats_sql;

		function __construct (&$sql, $database, $table, $sid = NULL, $name = 'ssid', $compression = 'gz', $autocall_session_write_close = false, $max_size = 1000000)
		{
			
			if (is_array($sql) && ($sql['session'] && $sql['stats']))
			{
				$this->sql = &$sql['session'];
				$this->stats_sql = &$sql['stats'];
			}
			else
			{
				$this->sql = &$sql;	
				$this->stats_sql = &$sql;
			}
			
			// Set the object properties
			$this->database = $database;
			$this->table = $table;
			$this->name = $name;
			$this->current_pixels = "";
			$this->compression = $compression;
			$this->max_session_size = $max_size;
			
			// Turn the pixel handler off by default
			$this->pixel_handler = 0;
			
			// Set the session name
			session_name($this->name);
			
			// Set the session id
			if ($sid != NULL && $sid != "")
			{
				session_id($sid);
			}
			else
			{
				$sid = session_id();
			}
			
			$this->table = 'session_'.strtolower(substr($sid, 0, 1));
			
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
			
			// DLH, 2006.04.14, the write function was crashing because the database object was
			// destroyed before write was called.  I think whether or not this happens is dependent
			// on the version of php.  I'm using $autocall_session_write_close = false as default
			// to make sure I don't break any existing code.  If you're having problems with a mysql
			// exception in the write() method, just pass true in for $autocall_session_write_close.
			// see: http://www.php.net/manual/en/function.session-set-save-handler.php (boswachter at xs4all nl)
			if ($autocall_session_write_close) register_shutdown_function('session_write_close');
			
			// Start the session
			session_start();
			
			// All done
			return TRUE;
			
		}
		
		public function Table($id)
		{
			$this->table = 'session_'.strtolower($id{0});
		}

		public function Reset_Stat($batch_id = NULL)
		{
			
			try
			{
				
				$this->batch_id = $batch_id;
				
				$_SESSION['stat_info'] = Set_Stat_3::Setup_Stats(
					null,
					$_SESSION['config']->site_id,
					$_SESSION['config']->vendor_id,
					$_SESSION['config']->page_id,
					$_SESSION['promo']['promo_id'],
					$_SESSION['promo']['promo_sub_code'],
					$_SESSION ['config']->promo_status,
					$this->batch_id
				);
				
			}
			catch( Exception $e )
			{
				throw $e;
			}
			
			return TRUE;
			
		}

		public function Hit_Stat ($name, $value = 1, $unique = TRUE, $stat_model = null)
		{
			
			$name = strtolower($name);
			
			if (!$unique || !isset($_SESSION['unique_stat']->{$name}))
			{
				
				try
				{
					
					// If we have cached Setup_Stats args in the stat_info and the date has changed re-call it
					$_SESSION['stat_info']->stat_time = time();
					
					if (isset($_SESSION['stat_info']->cache) && $_SESSION['stat_info']->stat_date != date('Y-m-d', $_SESSION['stat_info']->stat_time))
					{
						
						$promo_status = new stdClass();
						$promo_status->valid = 'valid';
						
						$_SESSION['stat_info'] = Set_Stat_3::Setup_Stats(
							$_SESSION['stat_info']->stat_time,
							$_SESSION['stat_info']->cache->site_id,
							$_SESSION['stat_info']->cache->vendor_id,
							$_SESSION['stat_info']->cache->page_id,
							$_SESSION['stat_info']->cache->promo_id,
							$_SESSION['stat_info']->cache->promo_sub_code,
							$promo_status,
							$_SESSION['stat_info']->cache->batch_id
						);
						
					}
					
					$set_stat = new Set_Stat_3();
					if (isset($_SESSION['config']->mode))
					{
						$set_stat->Set_Mode($_SESSION['config']->mode);
					}
					else 
					{
						$set_stat->Set_Mode(Lib_Mode_2::Get_Mode());
					}
					
					$set_stat->Set_Stat(
						$_SESSION['config']->property_id,
						$name,
						$value
					);
					
					$_SESSION ["unique_stat"]->$name = TRUE;
					
				}
				catch (MySQL_Exception $e)
				{
					throw $e;
				}
				
				// If the pixel handler is on, check for tracking pixels for this column in config.
				if ($this->pixel_handler)
				{
					$this->Check_Pixel($name);
				}
				
				return TRUE;
				
			}

			return FALSE;
		}

		public function Open($save_path, $session_name)
		{
			return true;
		}

		public function Close()
		{
			return true;
		}
		
		public function Read ($id)
		{
			
			$attempts = 7;
			$pause = 10;
			
			// assume it's blank
			$data = '';
			$row = FALSE;
			
			if (preg_match("/^[0-9A-Fa-f]{32}$/", $id))
			{
				
				try
				{
					// read the record
					$row = $this->Record_Fetch($id, $attempts, $pause);
				}
				catch (Exception $e)
				{
				}
				
				if ($row !== FALSE)
				{
					
					// decompress session data
					switch($row['compression'])
					{
						
						case 'gz':
							$row['session_info'] = gzuncompress($row['session_info']);
							break;
							
						case 'bz':
							$row['session_info'] = bzdecompress($row['session_info']);
							break;
							
					}
					
					// get our data
					$data = $row['session_info'];
					unset($row['session_info']);
					
					// lock our session
					$this->Record_Lock($id);
					
				}
				else
				{
					// create a new session
					$this->Record_Create($id);
				}
				
			}
			
			// return session data
			return $data;
			
		}
		
		public function Write($id, $session_info)
		{
			
			$result = FALSE;
			
			if (preg_match("/^[0-9A-Fa-f]{32}$/", $id))
			{
				
				// compress session data
				switch($this->compression)
				{
					
					case "gz":
						$session_info = gzcompress($session_info);
						break;
						
					case "bz":
						$session_info = bzcompress($session_info);
						break;
						
				}
				
				// enforce maximum session size
				if (($this->max_session_size === NULL) || (strlen($session_info) <= $this->max_session_size))
				{
					
					try
					{
						// DLH, 2006.04.14, if you get mysql exceptions here, it might be because
						// the database object is destroyed before this write method is called.
						// see the notes in the constructor - you can probably fix the problem by
						// simply passing $autocall_session_write_close=true to the constructor.
						$result = $this->Record_Write($id, $session_info, $this->compression);
						
					}
					catch (Exception $e)
					{
					}
					
				}
				else
				{
					$app_log = new Applog();
					$app_log->Write('Session exceeded max session size of '.$this->max_session_size.'. Session ID: '.session_id());
				}
				
			}
			
			// All went well
			return $result;
			
		}
		
		public function Destroy ($id)
		{
			
			// Blow it off the datase
			$query = "DELETE FROM {$this->table} WHERE session_id='{$id}'";
			$result = $this->sql->Query($this->database, $query);
			
			return TRUE;
			
		}
		
		public function Garbage_Collection ($session_life)
		{
			// Not clear what to do here, so return true to make all happy
			return TRUE;
		}
		
		public function Record_Exists ($id)
		{
			
			$query = "SELECT COUNT(*) AS n FROM {$this->table} WHERE session_id = '{$id}'";
			$result = $this->sql->Query ($this->database, $query);
			
			$row = $this->sql->Fetch_Array_Row($result);
			
			return $row['n'];
			
		}
		
		public function Record_Fetch($id, $attempts = 7, $pause = 10)
		{
			
			do
			{
				
				if (isset($row) && ($pause !== NULL)) sleep($pause);
				
				$query = "
					SELECT
						IF(DATE_ADD(date_locked, INTERVAL 60 SECOND) > NOW(), 0, 1) AS lock_timeout,
						date_locked,
						session_lock,
						session_info,
						compression
					FROM
						`{$this->table}`
					WHERE
						session_id = '{$id}'
				";
				$result = $this->sql->Query ($this->database, $query);
				$row = $this->sql->Fetch_Array_Row($result);
				
			}
			while ($row && $row['session_lock'] && !$row['lock_timeout'] && $attempts--);
			
			return $row;
			
		}
		
		public function Record_Create($id)
		{
			
			$query = "
				INSERT INTO
					`{$this->table}`
				(
					session_id,
					date_created,
					date_locked
				)
				VALUES
				(
					'{$id}',
					NOW(),
					NOW()
				)
			";
			$result = $this->sql->Query($this->database, $query);
			
			return ($result !== FALSE);
			
		}
		
		public function Record_Lock($id)
		{
			
			$query = "
				UPDATE
					`{$this->table}`
				SET
					date_locked = NOW(),
					session_lock = 1
				WHERE
					session_id = '{$id}'
			";
			$result = $this->sql->Query($this->database, $query);
			
			return ($result !== FALSE);
			
		}
		
		public function Record_Write($id, $data, $compression)
		{
			
			$this->sql->Connect();
			
			$query = "
				UPDATE
					`{$this->table}`
				SET
					session_lock = 0,
					session_info = '".mysql_escape_string($data)."',
					compression = '{$compression}'
				WHERE
					session_id = '{$id}'
			";
			$result = $this->sql->Query($this->database, $query);
			
			return ($result !== FALSE);
			
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
			
			// If its an old school tracking pixel and we are on accepted, add the old school pixel to our array.
			if ($name == "accepted" && isset($_SESSION['config']->tracking_pixel) && strlen(trim($_SESSION['config']->tracking_pixel)))
			{
				$_SESSION['config']->event_pixel[$name][] = array('tracking_pixel' => $_SESSION['config']->tracking_pixel);
				unset($_SESSION['config']->tracking_pixel);
			}
			
			// gather all the events we should be firing pixels for
			$events = $this->Get_Meta_Events($name);
			$events[] = $name;
			
			// Set our available expansion stuff.
			$replace = array(
				'unique_id' => session_id(),
				'application_id' => isset($_SESSION['application_id']) ? $_SESSION['application_id'] : null,
				'email' => $this->Get_Replacement_Value('email_primary'),
				'promo_sub_code' => $_SESSION['stat_info']->cache->promo_sub_code,
				'return_data' => $this->Get_Replacement_Value('return_data'),
				'return_data_2' => $this->Get_Replacement_Value('return_data_2'),
				'pwadvid' => $this->Get_Replacement_Value('pwadvid'),
				'uid' => $this->Get_Replacement_Value('uid'),
				'pubtransid' => $this->Get_Replacement_Value('pubtransid'),
				'promo_id' => $_SESSION['stat_info']->cache->promo_id,
			);
			
			foreach ($events as $event)
			{
				$this->Fire_Pixels($event, $replace);
			}
			
			return TRUE;
			
		}
		
		/**
		 * Gets the value for a replacement variable
		 *
		 * If we're in customer service, $_SESSION['data'] won't have
		 * any of the customer information, so we're gonna need to
		 * merge the two together (data will still have stuff like the
		 * pwad in it).  
		 * 
		 * @param string $name
		 * @return string
		 */
		protected function Get_Replacement_Value($name)
		{
			if (isset($_SESSION['data'][$name]))
			{
				return $_SESSION['data'][$name];
			}
			else if (isset($_SESSION['cs'][$name]))
			{
				return $_SESSION['cs'][$name];
			}
			return NULL;
		}
		
		/**
		 * Fires the pixels (if any) placed on a given event.
		 * @param string $event
		 * @param array $replace Replacement variables for the pixel URL
		 * @return void
		 */
		protected function Fire_Pixels($event, $replace)
		{
			
			// Do we have any event pixels for this stat column?
			if (isset($_SESSION['config']->event_pixel[$event])
				&& is_array($_SESSION['config']->event_pixel[$event])
				&& count($_SESSION['config']->event_pixel[$event]))
			{
				
				// Loop thru event pixels for this stat column.
				foreach($_SESSION['config']->event_pixel[$event] as $pixel)
				{
					
					// If we have a sub code in our pixel, and our current sub code does not equal, skip adding this pixel.
					if (isset($pixel['subcode']) && $pixel['subcode'] != $_SESSION['config']->promo_sub_code)
					{
						continue;
					}
					
					// If we find expansion stuff in our pixel use replace with whats in the replace array.
					$pixel['tracking_pixel'] = preg_replace('/%%%(.*?)%%%/e', '$replace[\\1]', $pixel['tracking_pixel']);
					
					if (isset($_SESSION['statpro']['statpro_obj']) && preg_match('/([^.\/]+)\.linkstattrack.com/', $pixel['tracking_pixel']))
					{
						$this->current_pixels .= "<!-- <img src=\"{$pixel['tracking_pixel']}\" width=\"1\" height=\"1\" border=\"0\" alt=\"promo_id:{$_SESSION['config']->promo_id}\"> -->";
						$_SESSION['statpro']['statpro_obj']->Url_Event($pixel['tracking_pixel']);
					}
					elseif (substr(trim($pixel['tracking_pixel']), 0, 4) == 'http')
					{
						// If the tracking pixel starts with http, lets add some formatting.
						$this->current_pixels .= "<img src=\"{$pixel['tracking_pixel']}\" width=\"1\" height=\"1\" border=\"0\" alt=\"promo_id:{$_SESSION['config']->promo_id}\">";
					}
					else // Leave as is
					{
						$this->current_pixels .= $pixel['tracking_pixel'];
					}
					
				}
				
			}
			
			return;
			
		}
		
		// Returns our current pixel string if one exists and it has data.
		public function Fetch_Pixels()
		{
			
			if (isset($this->current_pixels) && strlen($this->current_pixels))
			{
				
				if (strtoupper($_SESSION['config']->mode) != 'LIVE')
				{
					$return_pixels = "<!-- {$this->current_pixels} -->";
				}
				else
				{
					$return_pixels = $this->current_pixels;
				}

				// Reset old pixels
				$this->current_pixels = '';
				
				return $return_pixels;
				
			}
			
			return FALSE;
			
		}
		
		/**
		 * Returns any meta-events that the provided event exists within
		 * @param string $name The event name
		 * @return array Meta-event names
		 */
		protected function Get_Meta_Events($name)
		{
			
			$match = '/'.preg_quote($name, '/').'\s*(?:,|$)/i';
			$events = array();

			if (isset($_SESSION['config']->event_pixel) && is_array($_SESSION['config']->event_pixel))
			{
			
				// look for meta-events with pixels on them
				if ($meta_events = preg_grep('/^meta_/i', array_keys($_SESSION['config']->event_pixel)))
				{
					
					foreach ($meta_events as $name)
					{
						// make sure we have and are in the definition for this meta-event
						if (isset($_SESSION['config']->{$name}) && preg_match($match, $_SESSION['config']->{$name}))
						{
							$events[] = $name;
						}
					}
					
				}

			}
			
			return $events;
			
		}
		
		/**
		 * Returns the maximum session size. If $size is provided,
		 * will set the maximum session size.
		 *
		 * @param int $size
		 * @return int
		 */
		public function Maximum_Size($size = null)
		{
			if (!is_null($size)) $this->max_session_size = $size;
			return $this->max_session_size;
		}

		/* I don't think anyone is still using these -- not testing -- mikeg 04-14-2005
		public function Session_Config ($license_key, $promo_id, $promo_sub_code, $batch_id = NULL)
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

		public function Session_Config2($license, $promo_id, $promo_sub_code, $batch_id = NULL, $site_type = NULL)
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

		public function Session_Config_Ext ($license_key, $promo_id, $promo_sub_code, $batch_id = NULL)
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
		}*/
	}
?>
