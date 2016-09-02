<?php
	
	class Timezone
	{
		
		protected $sql;
		protected $database;
		
		protected $id;
		protected $name;
		protected $abbrev;
		protected $offset = 0;
		
		public function __construct($sql, $database, $timezone = NULL)
		{
			
			$this->sql = &$sql;
			$this->database = $database;
			
			if ($timezone !== NULL)
			{
				$this->Load($timezone);
			}
			
		}
		
		public function ID()
		{
			return $this->id;
		}
		
		public function Name($name = NULL)
		{
			if ($name !== NULL) $this->name = $name;
			return $this->name;
		}
		
		public function Abbreviation($abbrev = NULL)
		{
			if ($abbrev !== NULL) $this->abbrev = $abbrev;
			return $this->abbrev;
		}
		
		public function Offset($offset = NULL)
		{
			if (is_numeric($offset)) $this->offset = $offset;
			return $this->offset;
		}
		
		/**
		 * Load the timezone information from the database.
		 *
		 * @param string $timezone timezone abbreviation
		 * @return boolean
		 */
		public function Load($abbrev)
		{
			
			// assume we fail
			$loaded = FALSE;
			
			try
			{
				
				// pull our info from the database
				$query = "
					SELECT
						time_zone_id,
						name,
						abbrev,
						utc_diff
					FROM
						time_zone
					WHERE
						abbrev='{$abbrev}'
					LIMIT 1";

				$result = $this->sql->Query($this->database, $query);
				
				if ($rec = $this->sql->Fetch_Array_Row($result))
				{
					
					$this->id = $rec['time_zone_id'];
					$this->name = $rec['name'];
					$this->abbrev = $rec['abbrev'];
					$this->offset = $rec['utc_diff'];
					
					$loaded = TRUE;
					
				}
				
			}
			catch (Exception $e)
			{
				// gulp
			}
			
			return $loaded;
			
		}
		
		public function Find_By_State($state)
		{
			
			
			// assume we fail
			$loaded = FALSE;
			
			try
			{
				
				// pull our info from the database
				$query = "
					SELECT
						time_zone.time_zone_id,
						name,
						abbrev,
						utc_diff
					FROM
						state
						JOIN time_zone USING (time_zone_id)
					WHERE
						state='{$state}'
					LIMIT 1";
				$result = $this->sql->Query($this->database, $query);
				
				if ($rec = $this->sql->Fetch_Array_Row($result))
				{
					
					$this->id = $rec['time_zone_id'];
					$this->name = $rec['name'];
					$this->abbrev = $rec['abbrev'];
					$this->offset = $rec['utc_diff'];
					
					$loaded = TRUE;
					
				}
				
			}
			catch (Exception $e)
			{
				// gulp
			}
			
			return $loaded;
			
		}
		
		/**
		 * Similar to date(), but converts the date
		 * to the target timezone (rather than the
		 * local timezone).
		 *
		 * @param string $format date format
		 * @param integer $date timestamp
		 * @return unknown
		 */
		public function Date($format, $date = NULL)
		{
			
			// unix timestamps are, by definition, in UTC,
			// so we'll convert to our timezone first
			$date = $this->UTC_To_Local($date);
			
			// format the date
			$date = gmdate($format, $date);
			return $date;
			
		}
		
		/**
		 * Convert a UTC timestamp to a local timestamp
		 *
		 * @param integer $date timestamp
		 * @param integer $offset UTC offset
		 * @return integer timestamp
		 */
		public function UTC_To_Local($date, $offset = NULL)
		{
			
			if (!is_numeric($offset)) $offset = $this->offset;
			$date += ($this->offset * 3600);
			
			return $date;
			
		}
		
		/**
		 * Convert a local timestamp to a UTC timestamp
		 *
		 * @param integer $date timestamp
		 * @param integer $offset UTC offset
		 * @return integer timestamp
		 */
		public function Local_To_UTC($date, $offset = NULL)
		{
			
			if (!is_numeric($offset)) $offset = $this->offset;
			$date -= ($this->offset * 3600);
			
			return $date;
			
		}
		
	}
	
?>
