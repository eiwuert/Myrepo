<?php
	/**
		
		@name BlackBox_Config_OldSchool
		@version 0.1
		@author Chris Barmonde
		
		@desc 
			This class contains configuration settings for BlackBox.
			This is mainly intended to be used by ancillary BB classes,
			so instead of passing in a reference to the BlackBox object
			itself, you can pass in the bb_config and give it the ability
			to have logging, see the mode, take snapshots, etc.
		
	*/

	class BlackBox_Config_OldSchool
	{
		public $bb_mode;
		public $debug;
		public $react;
		
		public $filters;
		
		
		public function __construct(&$config, &$mode, &$react)
		{
			//Merge the config into this object
			foreach($config as $key => $value)
			{
				$this->$key = $value;
			}
			
			$this->bb_mode = &$mode;
			$this->react = &$react;
			
			$this->debug = new BlackBox_Debug();
			$this->filters = array();
			
			$this->Setup_Filters();
		}
	
		
		public function __destruct()
		{
			unset($this->debug);
			unset($this->filters);
		}
		
		
		public function Config()
		{
			return $this;
		}
		
		
		public function Mode()
		{
			return $this->bb_mode;
		}
		
		
		public function Save_Snapshot($name, &$data)
		{
			$this->debug->Save_Snapshot($name, $data);
		}
		
		
		public function Log_Event($name, $result, $target = NULL)
		{
			$this->debug->Log_Event($this, $this->bb_mode, $name, $result, $target);
		}
		
		public function Applog_Write($message)
		{
			$this->debug->Applog_Write($message);
		}
		
		
		/**
			@desc Set up filters for BB vendors.  We'll only ever need
				one instance of a particular class, so why not do it here?
		*/
		private function Setup_Filters()
		{
			$filters = array(
				'Email',
				'Drivers_License',
				'MICR' //ABA + account # check
			);
			
			foreach($filters as $filter)
			{
				$class_name = 'BlackBox_Filter_' . $filter;
				$this->filters[$filter] = new $class_name($filter, $this);
			}
		}
		
		
		public function Is_Impact($property = NULL)
		{
			return Enterprise_Data::isCompanyProperty(Enterprise_Data::COMPANY_IMPACT, $property);
		}
		
		public function Is_Agean($property = NULL)
		{
			return Enterprise_Data::isCompanyProperty(Enterprise_Data::COMPANY_AGEAN, $property);
		}
		
		public function Is_Agean_Site()
		{
			return (strcasecmp($this->config->property_name, 'Agean') === 0 || isset(SiteConfig::getInstance()->is_agean_site));
		}
		
		public function Is_React()
		{
			$acm = new App_Campaign_Manager($this->sql, $this->database, $this->applog);
			$olp_process = $acm->Get_Olp_Process($this->application_id);

			return preg_match('/_react$/', $olp_process);
		}
		
		public function Is_Entgen($property = NULL)
		{
			return Enterprise_Data::isCompanyProperty(Enterprise_Data::COMPANY_GENERIC, $property);
		}

		public function Bypass_Limits($property)
		{
			$result = false;
			
			if(SiteConfig::getInstance()->bypass_limits && !empty(SiteConfig::getInstance()->bb_force_winner))
			{
				$bb_force_winner = array_map('trim', explode(',', SiteConfig::getInstance()->bb_force_winner));
				$result = in_array(strtolower($property), $bb_force_winner);
			}
			
			return $result;
		}
		
		/** Gets the target id from property short.
		 *
		 * @param string $property The property short to find an ID for.
		 * @return int The target id, 0 if not found.
		 */
		public function getTargetID($property)
		{
			static $target_ids = array();
			
			if (!isset($target_ids[$property]))
			{
				$query = "
					SELECT
						target_id
					FROM
						target
					WHERE
						property_short = '" . mysql_real_escape_string($property) . "'";
				
				if (isset($this->sql))
				{
					$result = $this->sql->Query($this->database, $query);
					
					if ($row = $this->sql->Fetch_Array_Row($result))
					{
						$target_ids[$property] = $row['target_id'];
					}
					else
					{
						$target_ids[$property] = 0;
					}
				}
				else
				{
					// If we don't have SQL yet, don't store the value as we may get it later.
					return 0;
				}
			}
			
			return $target_ids[$property];
		}
	}


?>
