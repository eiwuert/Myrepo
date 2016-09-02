<?php
	
	/**
		
		@desc A class to support capping on any stat,
			for any combination of site, promotion,
			and vendor.
		
		@author Andrew Minerd
		@version 1.0
		
	*/
	
	class Stat_Limits
	{
		
		protected $sql;
		protected $database;
		protected $table;
		
		public function __construct(&$sql, $db = NULL, $table = 'stat_limits')
		{
			
			if (is_object($sql) && (!is_null($db)))
			{
				
				// use an existing connection
				$this->sql = &$sql;
				$this->database = $db;
				
			}
			elseif (is_array($sql))
			{
				
				$this->sql = &$this->Connect($sql);
				$this->database = $sql['db'];
				
			}
			
			$this->table = $table;
			
			return;
			
		}
		
		public function __destruct()
		{
			//unset($this->sql);
		}
		
		/**
			
			@desc Creates a connection from the array returned
				by the Server::Get_Server function.
			
		*/
		protected function Connect($server)
		{
			
			$sql = FALSE;
			
			if (is_array($server))
			{
				
				// connect to the server
				$sql = new MySQL_4($server['host'], $server['user'], $server['password']);
				$sql->Connect();
				
			}
			
			return($sql);
			
		}
		
		/**
			
			@desc Checks the config for a stat cap on $stat,
				and makes sure we're not over it.
				
				Currently, caps are only supported on one
				kind of ID at a time (site, promo, or vendor,
				in that order of precedence),	and only one
				stat is capped for any given combination.
			
		*/	
		public function Over_Limit($stat, &$config)
		{
			
			// assume we're not
			$over_limit = FALSE;
			
			// check to see if we have a limit,
			// and if our limit is on this stat
			if (isset($config->limits->stat_cap) && (strtolower($config->limits->stat_cap->stat_name) == strtolower($stat)))
			{
				
				// what kind of cap is this? right now,
				// caps are placed on a single ID only
				switch(strtolower($config->limits->stat_cap->type))
				{
					case 'site_id':
						$current = $this->Fetch($stat, $config->site_id);
						break;
					case 'promo_id':
						$current = $this->Fetch($stat, NULL, $config->promo_id);
						break;
					case 'vendor_id':
						$current = $this->Fetch($stat, NULL, NULL, $config->vendor_id);
						break;
				}
				
				if ($current !== FALSE)
				{
					// see if we're over our limit
					$over_limit = ($current >= (int)$config->limits->stat_cap->cap);
				}
				
			}
			
			unset($config);
			unset($log);
			
			return($over_limit);
			
		}
		
		/**
			
			@desc Increments the value for this
				stat, site, promo, and vendor combination.
			
		*/
		public function Increment($stat, $site_id, $promo_id, $vendor_id, $date = NULL)
		{
			
			// make sure we have a valid date, or today
			if (is_string($date)) $date = strtotime($date);
			if ((!is_numeric($date)) || ($date === -1)) $date = time();
			
			if (!is_numeric($site_id)) $site_id = 0;
			if (!is_numeric($promo_id)) $promo_id = 0;
			if (!is_numeric($vendor_id)) $vendor_id = 0;
			
			try
			{
				
				// try to update an existing row...
				$query = "UPDATE `{$this->table}` SET count = (count + 1) WHERE
					stat_date=CURRENT_DATE() AND stat_name='{$stat}' AND site_id={$site_id}
					AND promo_id={$promo_id} AND vendor_id={$vendor_id} LIMIT 1";
				$result = $this->sql->Query($this->database, $query);
				
				if ($this->sql->Affected_Row_Count() == 0)
				{
					
					// add a new row
					$query = "INSERT INTO `{$this->table}`
						(stat_date, stat_name, site_id, promo_id, vendor_id, count)
						VALUES (CURRENT_DATE(), '{$stat}', {$site_id}, {$promo_id}, {$vendor_id}, 1)";
					$result = $this->sql->Query($this->database, $query);
					
				}
				
			}
			catch (Exception $e)
			{
				$result = FALSE;
			}
			
			return($result);
			
		}
		
		public function Fetch($stat, $site_id = NULL, $promo_id = NULL, $vendor_id = NULL, $date = NULL)
		{
			
			// make sure we have a valid date, or today
			if (is_string($date)) $date = strtotime($date);
			if ((!is_numeric($date)) || ($date === -1)) $date = time();
			
			// filter based on date and name
			$where = 'stat_date = \''.date('Y-m-d', $date).'\'';
			$where .= ' AND stat_name = \''.$stat.'\'';
			
			// filter on site_id, vendor_id, promo_id?
			if (is_numeric($site_id)) $where .= ' AND site_id = '.$site_id;
			if (is_numeric($vendor_id)) $where .= ' AND vendor_id = '.$vendor_id;
			if (is_numeric($promo_id)) $where .= ' AND promo_id = '.$promo_id;
			
			try
			{
				
				// run the query
				$query = "SELECT SUM(count) AS count FROM `{$this->table}` WHERE {$where}";
				$result = $this->sql->Query($this->database, $query);
				
				if ($result && ($rec = $this->sql->Fetch_Array_Row($result)))
				{
					$count = (int)$rec['count'];
				}
				
				// a SUM will always return a row, but will be
				// null if zero records matched the where clause
				if ((!isset($count)) || is_null($count))
				{
					$count = 0;
				}
				
			}
			catch (Exception $e)
			{
				$count = FALSE;
			}
			
			return($count);
			
		}
		
	}
	
?>