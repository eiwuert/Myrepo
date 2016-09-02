<?php


	class BlackBox_Debug
	{
		private $debug_opt;
		
		private $snapshot;
		private $current_run;
		
		private $applog;
		
		public function __construct()
		{
			$this->debug_opt = array();
			$this->snapshot = array();
			$this->current_run = 0;
			
			$this->applog = OLP_Applog_Singleton::Get_Instance(APPLOG_SUBDIRECTORY, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, NULL, APPLOG_ROTATE, APPLOG_UMASK);
		}

		public function __destruct()
		{
		}
		
		
		
		
		public function Get_Options()
		{
			return $this->debug_opt;
		}
		
		
		
		public function Set_Options($opts = array())
		{
			if(is_array($opts))
			{
				$this->debug_opt = $opts;
			}
			elseif(!is_null($opts))
			{
				$this->debug_opt = array($opts);
			}
			else
			{
				$this->debug_opt = array();
			}
		}
		
		
		
		
		/**
			
			@desc Set BlackBox debug options.
				
				Use for debugging purposes only! This will
				allow you to skip integral parts of BlackBox's
				decision making, in the interest of debugging.
				Look at the DEBUG_* constants above for an idea
				of what to do.
				
				If called with $value, this will return the current
				value of the debug option $name.
				
			@param $name string Name of the debug option
			@param $value mixed Optional value of the debug option
			
			@return mixed Value of the debug option $name, or, if
				$name is ommitted, return an associative array of
				ALL debug options that are set.
			
		*/
		public function Debug_Option($name = NULL, $value = NULL)
		{
			
			if (!is_null($name))
			{
				
				if (is_array($name))
				{
					
					if (is_array($value))
					{
						$name = array_combine($name, $value);
					}
					
					$this->debug_opt = array_merge($this->debug_opt, $name);
					
				}
				elseif ((!is_null($value)))
				{ 
					// set just one
					$this->debug_opt[$name] = $value;
				}
				else
				{
					if (isset($this->debug_opt[$name]))
					{
						$value = $this->debug_opt[$name];
					}
					else
					{
						$value = NULL;
					}
				}
				
			}
			else
			{
				
				$value = $this->debug_opt;
				
			}
			
			return $value;
			
		}
		
		
		
		
		
		
		
		
		
		
		/**
			
			@desc Returns a "snapshot" from a Pick_Winner call.
				
				During the Pick_Winner operation, BlackBox will save
				"snapshots" of it's operational data, useful after the
				fact for determining what BlackBox was thinking when
				it made it's decisions.
				
				If Pick_Winner has been called more than once, specifying
				$num will return the snapshot data for that run only. If
				$num is left blank, the snapshot data for ALL runs will
				be returned.
				
			@param $num Integer Optional run number
			
			@return Array Snapshot data
			
		*/
		public function Snapshot($num = NULL)
		{
			
			$snap = NULL;
			
			// be friendly: index at 1
			$num = ($num - 1);
			
			// retrieve just one of many
			if ($num && is_array($this->snapshot))
			{
				
				// get the first key: we use this to determine
				// if more than one snapshot has been saved
				$firstkey = reset(array_keys($this->snapshot));
				
				if (is_numeric($firstkey) && isset($this->snapshot[$num]))
				{
					$snap = $this->snapshot[$num];
				}
				
			}
			
			// must not have more than one
			if (!$snap) $snap = $this->snapshot;
			return $snap;
			
		}
		
		
		
		
		public function Get_Snapshot()
		{
			return $this->snapshot;
		}
		
		public function Set_Snapshot($snapshot)
		{
			$this->snapshot = $snapshot;
		}
		
		
		
		/**
			
			@desc Save a snapshot of operational data.
				
				This is generally used by the decision making functions
				of BlackBox to document their results for debugging
				and reporting later. Should there be a question as to
				why a loan went where it went, the snapshot provided
				by BlackBox should prove - conclusively - the reasoning
				behind the decision.
				
				NOTE: This function automatically documents more than
				one run through Pick_Winner, if required: in such cases,
				the first snapshot will be stored in $this->snapshot[0],
				the second in $this->snapshot[1], etc. If only one
				call to Pick_Winner is made, the snapshot simply exists
				as $this->snapshot.
				
				Don't try and understand the following code. If it
				looks convoluted, that's probably beacuse it is. Every
				time I look at it, I get a headache.
			
		*/
		public function Save_Snapshot($name, $data)
		{
			
			$found = FALSE;
			
			// do a little data formatting
			if (is_array($data))
			{
				$data = array_map(array(&$this, 'Format_Snapshot_Data'), $data);
			}
			else
			{
				$data = $this->Format_Snapshot_Data($data);
			}
			
			// initialize this run
			if (!isset($this->snapshot[$this->current_run]))
			{
				$this->snapshot[$this->current_run] = array();
			}
			
			if (isset($this->snapshot[$this->current_run][$name]))
			{
				
				if (!is_array($this->snapshot[$this->current_run][$name]))
				{
					$this->snapshot[$this->current_run][$name] = array($this->snapshot[$this->current_run][$name]);
				}
				
				if (is_array($data))
				{
					$this->snapshot[$this->current_run][$name] = array_merge($this->snapshot[$this->current_run][$name], $data);
				}
				else
				{
					$this->snapshot[$this->current_run][$name][] = $data;
				}
				
			}
			else
			{
				$this->snapshot[$this->current_run][$name] = $data;
			}
			
			return;
			
		}
		
		public function New_Snapshot()
		{
			
			// increment our current run
			$this->current_run++;
			//return;
			
		}
		
		protected function Format_Snapshot_Data($data)
		{
			
			if ($data === TRUE) $data = 'TRUE';
			if ($data === FALSE) $data = 'FALSE';
			if (is_null($data)) $data = 'NULL';
			
			return $data;
			
		}
		
		
		
		
		public function Log_Event(&$config, &$mode, $name, $result, $target = NULL)
		{
			
			if (isset($config->log))
			{
				// prepend the mode name to our events
				$config->log->Log_Event($name, $result, $target, NULL, $mode);
			}
			
		}
		
		
		
		public function Applog_Write($message)
		{
			$this->applog->Write($message);
		}
		
		
	}

?>