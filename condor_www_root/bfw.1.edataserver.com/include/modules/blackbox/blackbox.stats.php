<?php
	
	/**
		
		@name BlackBox_Stats
		@version 0.1
		@author Andrew Minerd
		
		@desc
			
			A class to handle database retrieval and storage of
			target statistics: daily lead counts, the percent of
			total leads that are direct deposit (or vice versa),
			or anything else it's possible to write an SQL query
			for.
			
			This could be considered confusing: everywhere you see
			TOTAL think percents. If you specify $total when
			creating $stat_name, those two stats are now joined at
			the hips: when running Check_Stats, the value computed
			for the	$stat_name is the value for $stat_name divided
			by the value for $total.
			
			NOTE: If you're going to add a stat, PLEASE define a
			constant (the rest are at the top of blackbox.php).
			
	*/
	class BlackBox_Stats
	{
		
		// associative arrays of stat names
		// and their values, limits, or margins
		private $stats;
		private $limits;
		private $margin;
		
		// IMPORTANT: an associative array of stat names
		// and the stat _name_ to use when computing the percentage
		private $total;
		
		// link to the sql server
		private $config;
		
		// who we are
		private $target;
		
		// the stat name that failed
		private $failed;
		
		public function BlackBox_Stats(&$config, $target_name,$target_id)
		{
			
			$this->config = &$config;
			$this->target = $target_name;
			$this->target_id = $target_id;
			
			$this->stats = array();
			$this->limits = array();
			$this->margin = array();
			$this->total = array();
			
		}
		
		public function __destruct()
		{
			//unset($this->config);
			unset($this->sql);
			unset($this->log);
			
		}
		
		/**
			
			@desc Create a stat, specify the limit, define the margin of error,
				and link to a total stat.
			
			@param $stat_name string Statistic name
			@param $limit integer The limit for this stat
			@param $margin integer The margin of error for this stat
			@param $total string A statistic name
			
		*/
		public function Setup_Stat($stat_name, $limit = NULL, $margin = NULL, $total = NULL)
		{
			
			if (!array_key_exists($stat_name, $this->stats))
			{
				
				// this'll add it to our list,
				// but won't retrieve it yet
				$this->stats[$stat_name] = '';
				
				// do a jig
				if (is_numeric($limit)) $this->Limit($stat_name, $limit);
				if (is_numeric($margin)) $this->Margin($stat_name, $margin);
				if (is_string($total)) $this->Total($stat_name, $total);
				
			}
			
		}
		
		/**
			
			@desc Return the name of the stat that failed during Check_Stats
			
		*/
		public function Failed()
		{
			
			if ($this->failed) $failed = $this->failed;
			else $failed = FALSE;
			
			return($failed);
			
		}
		
		/**
			
			@desc Returns an associative array of stat names
				and their values.
				
			@param $stat_names array Optional array of stat names: if left
				blank, all cached stats will be returned
			
			@return int If only one name is specified, this will return
				the integer value for that statistic
			@return array If more than one stat name is specified, this
				will return an associate array of stat values
			
		*/
		public function Stats($stat_names = NULL)
		{
			
			if (is_string($stat_names))
			{
				// return one stat
				$stats = $this->Stat($stat_names);
			}
			elseif (is_array($stat_names))
			{
				
				$stats = array();
				
				// get only the stats they want
				foreach ($stat_names as $stat)
				{
					if ($value = $this->Stat($stat)) $stats[$stat] = $value;
				}
				
			}
			else
			{
				// default to all
				$stats = $this->stats;
			}
			
			return($stats);
			
		}
		
		/**
			
			@desc Returns an associative array of stat names
				and their limits.
				
			@param $stat_names array Optional array of stat names: if left
				blank, all limits will be returned
			
			@return int If only one name is specified, this will return
				the integer limit for that statistic
			@return array If more than one stat name is specified, this
				will return an associate array of stat limits
			
		*/
		public function Limits($stat_names = NULL)
		{
			
			if (is_string($stat_names))
			{
				// return one stat
				$limits = $this->Limit($stat_names);
			}
			elseif (is_array($stat_names))
			{
				
				$limits = array();
				
				// get only the stats they want
				foreach ($stat_names as $stat)
				{
					if ($value = $this->Limit($stat)) $limits[$stat] = $value;
				}
				
			}
			else
			{
				// default to all
				$limits = $this->limits;
			}
			
			return($limits);
			
		}
		
		/**
			
			@desc Returns an associative array of stat names
				and the VALUES of their total stats.
				
			@param $stat_names array Optional array of stat names: if left
				blank, all totals will be returned
			
			@return int If only one name is specified, this will return
				the integer total for only that statistic
			@return array If more than one stat name is specified, this
				will return an associate array of stat totals
			
		*/
		public function Totals($stat_names = NULL)
		{
			
			if (is_string($stat_names))
			{
				// return one stat
				$totals = $this->Total($stat_names);
			}
			else
			{
				
				// default to all
				if (!is_array($stat_names)) $stat_names = keys($this->totals);
				
				$totals = array();
				
				foreach ($stat_names as $stat)
				{
					if ($value = $this->Total($stat)) $totals[$stat] = $value;
				}
				
			}
			
			return($totals);
			
		}
		
		/**
			
			@desc Returns an array of the names of the stats
				we're storing values of locally
				
			@return array Array of key names
			
		*/
		public function Stat_Names()
		{
			
			// all the names of the stats we have
			return(keys($this->stats));
			
		}
		
		/**
			
			@desc Returns the value of a stat. If we
				don't have the value yet, it'll go get it.
				
			@param $name string Name of the statistic to return
				
			@return Integer The integer value of a statistic, or
				FALSE upon failure
			
		*/
		public function Stat($name, $auto_fetch = TRUE)
		{
			
			$value = NULL;
			
			if ((!array_key_exists($name, $this->stats)) || ($this->stats[$name]==''))
			{
				
				if ($auto_fetch)
				{
					
					// get it from the database
					$this->Retrieve_Stat($name);
					$value = $this->stats[$name];
					
				}
				
			}
			else
			{
				// get our local copy
				$value = $this->stats[$name];
			}
			
			if (!is_numeric($value)) 
				$value = FALSE;
			
			return($value);
			
		}
		
		/**
			
			@desc Set or return the limit for a stat.
			
			@param $stat_name string The statistic to set the limit for
			@param $limit integer The value of this limit
			
			@return integer The new limit, or FALSE
			
		*/
		public function Limit($stat_name, $limit = NULL)
		{
			
			if (is_numeric($limit))
			{
				$this->limits[$stat_name] = $limit;
			}
			elseif (is_string($limit))
			{
				$this->limits[$stat_name] = $limit;
				$limit = $this->Stat($limit, FALSE);
			}
			elseif (array_key_exists($stat_name, $this->limits))
			{
				$limit = $this->limits[$stat_name];
				if (is_string($limit)) $limit = $this->Stat($limit);
			}
			
			if (is_null($limit)) $limit = FALSE;
			return($limit);
			
		}
		
		/**
			
			@desc Set or return the "margin of error" for a stat
			
			@param $stat_name string The statistic to set the margin for
			@param $limit integer The value of the margin
			
			@return integer The new margin, or FALSE
			
		*/
		public function Margin($stat_name, $margin = NULL)
		{
			
			if (is_numeric($margin))
			{
				$this->margin[$stat_name] = $margin;
			}
			elseif (array_key_exists($stat_name, $this->margin))
			{
				$margin = $this->margin[$stat_name];
			}
			
			if (is_null($margin)) $margin = FALSE;
			return($margin);
			
		}
		
		/**
			
			@desc LINK a stat to it's total, or return the
				current value of the total for a stat
				
			@param $stat_name string The statistic to set the total for
			@param $total_stat string The _NAME_ of the stat to use as the total
			
			@return integer The value of the total stat, or FALSE
			
		*/
		public function Total($stat_name, $total_stat = NULL)
		{
			
			$total = NULL;
			
			if (is_string($total_stat))
			{
				$this->total[$stat_name] = $total_stat;
				$total = $this->Stat($total_stat, FALSE);
			}
			elseif (array_key_exists($stat_name, $this->total))
			{
				$total_stat = $this->total[$stat_name];
				$total = $this->Stat($total_stat);
			}
			
			if (is_null($total)) $total = FALSE;
			return($total);
			
		}
		
		/**
			
			@desc Compare stats to their limits and
				return the outcome.
				
			@param $blackbox BlackBox
			@param $stat_names string The name of one stat
			@param $stat_names array Array of stat names
				
			@return boolean The cumulative outcome of the checks
			
		*/
		public function Check_Stats(&$blackbox, $stat_names = NULL, $simulate = FALSE)
		{
			
			// reset this flag
			$this->failed = FALSE;
			
			// assume we pass
			$valid = TRUE;

			if (is_string($stat_names))
			{
				// avoid the whole loop thing
				$valid = $this->Check_Stat($blackbox, $stat_names, $simulate);
			}
			else
			{

				// default to ALL stats
				if (!is_array($stat_names))
				{
					$stat_names = array_keys($this->stats);
				}
				
				foreach ($stat_names as $stat_name)
				{

					// check our stats
					$valid = $this->Check_Stat($blackbox, $stat_name, $simulate);
					if (!$valid) break;
					
				}
				
			}

			return($valid);
			
		}
		
		/**
			
			@desc Go to sleep. Used before serialization to
				decrease the size of the serialized object.
			
			@return array An array representing the object,
				that could later be used to rebuild it.
			
		*/
		
		public function Sleep()
		{
			
			$stats = array();
			$stats['target'] = $this->target;
			$stats['stats'] = $this->stats;
			$stats['limits'] = $this->limits;
			$stats['margin'] = $this->margin;
			$stats['total'] = $this->total;
			
			return($stats);
			
		}
		
		private function Valid($data)
		{
			
			$valid = is_array($data);
			
			if ($valid) $valid = (array_key_exists('target', $data) && is_string($data['target']));
			if ($valid) $valid = (array_key_exists('limits', $data) && is_array($data['limits']));
			if ($valid) $valid = (array_key_exists('stats', $data) && is_array($data['stats']));
			if ($valid) $valid = (array_key_exists('margin', $data) && is_array($data['margin']));
			
			return($valid);
			
		}
		
		public function Restore($data, &$config = NULL)
		{
			
			if (BlackBox_Stats::Valid($data))
			{
				
				$target = $data['target'];
				$target_id = $data['target_id'];
				
				if (isset($this) && ($this instanceof BlackBox_Stats))
				{
					
					$new_stats = &$this;
					$new_stats->target = $target;
					$new_stats->target_id = $target_id;
					
					if ($config)
					{
						$this->config = &$config;
						$this->database = $config->database;
					}
					
				}
				else
				{
					
					if ($config)
					{
						$new_stats = new BlackBox_Stats($config, $target,$target_id);
					}
					
				}
				
				if ($new_stats instanceof BlackBox_Stats)
				{
					$new_stats->stats = $data['stats'];
					$new_stats->limits = $data['limits'];
					$new_stats->margin = $data['margin'];
					$new_stats->total = $data['total'];
				}
				
			}
			
			return $new_stats;
			
		}
		
		/**
			
			@desc Checks a stat against it's limit. NOTE: this
				doesn't need to be called by itself; Check_Stats
				is more flexible.
			
			@param $blackbox BlackBox
			@param $stat_name string Stat name to check
			@param $simulate boolean
			
			@return boolean Outcome
			
		*/
		private function Check_Stat(&$blackbox, $stat_name, $simulate = FALSE)
		{
			
			$valid = TRUE;
			$margin = 0;
		
			if (isset($this->stats[$stat_name]))
			{
				
				// check our limit first: if we don't have a limit, there's no
				// point in hitting the database for the stat value
				$limit = $this->Limit($stat_name);
				
				if (is_numeric($limit))
				{
					// get stat information
					($value = $this->Stat($stat_name)) ? TRUE : $value = 0;
					
					if ($value !== FALSE)
					{
						$margin = $this->Margin($stat_name);
						$total = $this->Total($stat_name);
					
						// if we have a "total", then we're
						// supposed to be a percentage of that
						if (is_numeric($total) && ($total > 0))
						{
							$value = round(($value / $total), 4);							
							$value = round($value * 100,2);
						}
						
						
						if ($value >= ($limit + $margin))
						{
							$outcome = EVENT_OVER_LIMIT;
							$valid = FALSE;
						}

					}

					if ($valid)
					{
						$outcome = EVENT_PASS;
					}
					else
					{
						// store the stat that failed
						$this->failed = $stat_name;
					}
					
					// add this to the application log
					if ($simulate && (!$valid)) $outcome = EVENT_PREFIX_SIMULATE.$outcome;
					$blackbox->Log_Event($stat_name, $outcome, $this->target);
					
				}
				
			}
			
			return($valid);
			
		}
		
		/**
			
			@desc Get a stat from the database. This is
				called automatically by Stat() when we don't
				have a cached value for $name.
			
			@param $name string Statistic name
			
			@return int The statistic value, or FALSE
				upon failure.
			
		*/
		private function Retrieve_Stat($name)
		{
			
			// get our configuration for this stat
			$config = $this->Stat_Config($name);
			$sql = &$this->config->sql;
			
			$value = NULL;
			
			if ($config)
			{
				
				try
				{
					
					// run the query
					$result = $sql->Query($config['database'], $config['query']);
					$row = $sql->Fetch_Array_Row($result);
					
					if (is_array($row) && array_key_exists($name, $row))
					{
						
						foreach ($row as $stat_name=>$value)
						{
							$this->stats[$stat_name] = $value;
						}
						//$value = $row[$name];
					}
					
				}
				catch (MySQL_Exception $e)
				{
				}
				
			}
			
			unset($sql);
			
			// just assign 0 if there
			// was a problem
			if (is_null($value)) $value = FALSE;
			return($value);
			
		}
		
		/**
			
			@desc Get the queries and database names needed
				to retrieve stat values.
				
				Because, at this time, we are running both versions of
				BlackBox concurrently, the actual stat values are
				updated in a central place: the old database. This
				means that, in some cases, we must pull from both
				the new database and the old database (for the campaign
				limits, for instance - the actual campaign data, like
				the start and end dates, are stored in the new database
				but the stat values are in the old database).
				
			@param $name string Name of the stat
			
			@return array Config information for the stat:
				$config['query']
				$config['database']
			
		*/
		private function Stat_Config($name)
		{
			
			$config = FALSE;
			$query = NULL;
			$database = NULL;
			
			// These are our old and new databases
			$server = Server::Get_Server($this->config->mode, 'BLACKBOX');
			$blackbox_db = $server['db'];
			
			$server = Server::Get_Server($this->config->mode, 'BLACKBOX_STATS');
			$stat_db = $server['db'];
			
			switch (strtoupper($name))
			{
				
				case STAT_DIRECT_DEPOSIT:
					$query = "SELECT SUM(direct_deposit='TRUE') AS `{$name}`, COUNT(*) AS TOTAL_APPS 
						FROM application
						INNER JOIN bank_info_encrypted ON bank_info_encrypted.application_id=application.application_id
						WHERE application.target_id= $this->target_id  
						AND (application.created_date > CURRENT_DATE())
						AND application_type NOT IN ('DISAGREED','FAILED','CONFIRMED_DISAGREED')";
					$database = $blackbox_db;
					
					break;
					
				case STAT_NO_DIRECT_DEPOSIT:
					$query = "SELECT SUM(direct_deposit!='TRUE') AS `{$name}`, COUNT(*) AS TOTAL_APPS
						FROM application 
						INNER JOIN bank_info_encrypted ON bank_info_encrypted.application_id=application.application_id
						WHERE application.target_id= $this->target_id  
						AND (application.created_date > CURRENT_DATE())
						AND application_type NOT IN ('DISAGREED','FAILED','CONFIRMED_DISAGREED')";

					$database = $blackbox_db;
					
					break;
					
				// these use the same query, but were
				// given different names for the event_log
				case STAT_DAILY_LEADS:
				case STAT_HOURLY_LEADS:
				case STAT_OVERFLOW_LEADS:
					
					$field = $this->Stat_Limit_Field($this->target, $name);
					
					$query = "SELECT count AS `{$name}` FROM stat_limits WHERE stat_date=CURRENT_DATE()
						AND stat_name='{$field}' AND site_id = 0 AND promo_id = 0 AND vendor_id = 0 LIMIT 1";
					$database = $stat_db;

					break;
					
				case STAT_TOTAL_LEADS:
					
					$field = $this->Stat_Limit_Field($this->target, $name);
					
					$query = "SELECT SUM(count) AS `{$name}` FROM target, campaign, `{$stat_db}`.stat_limits
						WHERE target.property_short='{$this->target}' AND target.status='ACTIVE'
						AND campaign.type='BY_DATE' AND NOW() BETWEEN campaign.start_date AND campaign.end_date
						AND campaign.status='ACTIVE' AND stat_limit_date BETWEEN campaign.start_date AND campaign.end_date
						AND stat_limit.stat_name='{$field}'";
					$database = $blackbox_db;
					
					break;
					
			}
			
			if ($query && $database)
			{
				$config = array();
				$config['query'] = $query;
				$config['database'] = $database;
			}
			
			if (!$config) $config = FALSE;
			return($config);
			
		}
		
		private function Stat_Limit_Field($target_name, $type = '')
		{
			// <superhack>
			//$stat = ($type == STAT_OVERFLOW_LEADS) ? 'popconfirm' : 'agree';

			// As of Dec. 18 2006, CLK wants to base their leads on popconfirms.
			//$stat = 'popconfirm';
			// As of Feb. 04 2008, we need to use look because of new DataX Perf rules on UFC
			$stat = 'look';

			// <hack>
			$fields = array(
				'pcl' => 'bb_pcl_' . $stat,
				'ucl' => 'bb_ucl_' . $stat,
				'ca'  => 'bb_ca_'  . $stat,
				'ufc' => 'bb_ufc_' . $stat,
				'd1'  => 'bb_d1_'  . $stat,
				'cap' => 'bb_cap_agree',
			);

			$target_name = strtolower($target_name);
			
			if (array_key_exists($target_name, $fields))
			{
				$field = $fields[$target_name];
			}
			else
			{
				$field = "bb_{$target_name}";
			}
			// </hack>
			
			return($field);
			
		}
		
	}
	
	
	// to do...
	class BlackBox_Stat
	{
		
		protected $total;
		protected $limit;
		protected $margin;
		
		protected $value;
		
		public function Total()
		{
		}
		
		public function Limit()
		{
		}
		
		public function Margin()
		{
		}
		
		public function Value($auto_fetch = TRUE)
		{
		}
		
		public function Check()
		{
			
			// get our value
			$value = $this->Value();
			
			$total = self::Blah($this->total);
			$limit = self::Blah($this->limit);
			$margin = self::Blah($this->margin);
			
			// are we supposed to be a percent?
			if ($total) $value = ($value / $total);
			
			if ($limit)
			{
				
				// are we dealing with a percent value,
				// but not a percent limit?
				if ($limit > 1 && (($value > 0) && ($value < 1)))
				{
					$value = round(($value * 100), 2);
				}
				
				$valid = ($value  < ($limit + $margin));
				
			}
			else
			{
				$valid = TRUE;
			}
			
			return($valid);
			
		}
		
		private static function Blah(&$stat)
		{
			
			$value = NULL;
			
			if (is_numeric($stat))
			{
				$value = $stat;
			}
			elseif ($stat instanceof BlackBox_Stat)
			{
				$value = $stat->Value();
			}
			
			return($value);
			
		}
		
	}
	
?>
