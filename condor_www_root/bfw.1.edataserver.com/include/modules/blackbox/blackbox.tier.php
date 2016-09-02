<?php

	/**

		@name BlackBox_Tier
		@version 0.1
		@author Andrew Minerd

		@desc

			A class to store information about a tier: essentially,
			a tier is simply a collection	of targets. Some tiers
			(mostly tier 1) have special rules that you won't find
			here -	these are handled by BlackBox.

			NOTE: The BlackBox_Tier class is used by BlackBox,and
			will probably never need to be instantiated on it's own.

	*/

	class BlackBox_Tier implements iBlackBox_Target, iBlackBox_Serializable
	{

		protected $name;
		protected $targets;	//BlackBox_Target_Collection

		protected $weight_type; // The tier's weight type (AMOUNT, PRIORITY, or PERCENT)

		protected $failed;

		protected $config;

		protected $datax;

		protected $collection;

		protected $failed_overflow;	//Number of targets who fail OVERFLOW_LEADS stat
		
		/**
			@desc Constructor for BlackBox_Tier

			@param $tier integer Tier number (ex. 1, 2, 3)
			@param $tagets array Associative array of BlackBox_Target objects
			@param $weight string The weight type for this tier

		*/
		public function __construct($tier = NULL, &$targets = NULL, $weight = NULL, &$bb_config = NULL, &$collection = NULL)
		{

			if (is_numeric($tier))
			{
				$this->name = $tier;
			}

			$this->targets = $targets;//new BlackBox_Target_Collection($bb_config, $targets);

			if (is_string($weight))
			{
				$this->weight_type = $weight;
			}

			$this->config = &$bb_config;
			$this->collection = &$collection;
			
			// Impact is now using it's own BlackBox_DataX class. Create an instance of that if testing an Impact company - GForge [#5731] [DW]
			if(Enterprise_Data::isCompanyProperty(Enterprise_Data::COMPANY_IMPACT, $this->config->config->property_short))
			{
				$this->datax = new BlackBox_DataX_Impact($this->config);
			}
			else
			{
				$this->datax = new BlackBox_DataX($this->config);
			}
			
			if(!empty($targets))
			{
				foreach($this->targets as $target)
				{
					// Impact uses it's own blackbox_datax class. 
					// If target is an Impact target, send the config in
					// case it needs to create a new datax object. GForge[#5731] [DW]
					if($target instanceof BlackBox_Target_Impact)
					{
						$target->Set_DataX($this->datax,$this->config);
					}
					else
					{
						$target->Set_DataX($this->datax);
					}
				}
			}
			
			$this->failed_overflow = 0;

		}

		public function __destruct()
		{
			if(isset($this->datax)) $this->datax->__destruct();
			$this->targets->__destruct();
		}


		/**
			@desc Return the "name" of the tier: this
				is, in actuality, an integer.

			@return integer Tier number
		*/
		public function Name()
		{
			return $this->name;
		}

		/**
			@desc Simple method to check whether this tier
				is still a valid choice for Pick_Winner (or
				any other test). Just checks to make sure
				we still have open targets.

			@return boolean Validity of this target
		*/
		public function Valid()
		{
			$open = $this->targets->Get_Open();
			return (!empty($open));

		}

		/**
			@desc Return a single target object, or an array of
				target objects: if target_name is not specified,
				all targets are returned, regardless of whether
				they are marked as "Open".

			@param integer Optional target name.

			@return object BlackBox_Target object
		*/
		public function Targets($target_name = NULL, $include_children = false)
		{
			// error
			$targets = FALSE;

			if(!is_null($target_name))
			{
				if($this->targets->Has_Target($target_name, $include_children))
				{
					$targets = &$this->targets->Target($target_name);
				}
			}
			else
			{
				$targets = $this->targets->Get_Targets();
			}

			return $targets;
		}

		/**
			@desc Will either:

				1. Set whether one target is open: Open('abc', TRUE);
				2. Set whether many targets are open: Open(array('abc', 'def'), TRUE);
				3. Return whether one target is open: Open('abc');
				4. CLOSE ALL TARGETS: USE WITH CAUTION: Open(NULL, FALSE);
				4. Return an array of open target names: Open(array('abc', 'def'));
					(This will return an array containing only those entries in
					$target_names which are, in fact, open)
				5. Return the entire open array: Open();

				NOTE: Be extremely careful when using Open(NULL, FALSE)...
				as of right now, there's no way to recover which targets
				were open. You'll have to run ALL the rules again!

			@param $target mixed Optional name of one target,
				or an array of target names
			@param $open boolean Optional Used to set the status
				of $target_names

			@return Boolean Whether $target is open
			@return Array Open targets
		*/
		public function Open($target_names = NULL, $open = NULL)
		{
			return $this->targets->Open($target_names, $open);
		}

		/**
			@desc Return the failure reasons for $target_names.

				If $target_names is not an array, the reason for that
				target is returned, otherwise the reasons for all the
				targets in $target_names are returned as an associative
				array. If $target_names is ommitted, all failures are
				returned.

				NOTE: The failed array is reset at the beginning of
				each call to either Run_All_Rules or Check_Stats, and
				it is NOT packaged in BlackBox_Tier::Sleep().

		*/
		public function Failed($target_names = NULL)
		{
			if (!is_null($target_names))
			{
				if (is_array($target_names))
				{
					foreach ($target_names as $name)
					{
						if (array_key_exists($name, $this->failed))
						{
							$failed[$name] = $this->failed[$name];
						}
					}
				}
				else
				{
					if (array_key_exists($target_names, $this->failed))
					{
						$failed = $this->failed[$target_names];
					}
				}
			}
			else
			{
				$failed = $this->failed;
			}

			if (!$failed) $failed = FALSE;
			return $failed;
		}

		/**

			@desc Set target restrictions for this tier.

			See BlackBox::Set_Restrictions for more information.
			The main difference, however, is that this expects
			an array of target names as values, NOT keys.

			@param $target_names array Array of target names to
				restrict
			@param $exclude boolean Sets the function mode:
				restrict (FALSE),	or exclude (TRUE).

		*/
		public function Restrict($target_names, $exclude = FALSE)
		{
			$this->targets->Restrict_Targets($target_names, $exclude);
		}

		/**
			@desc Compare the stat counts and limits for $stat_names,
				if specified, or, if left blank. all stats. If an array
				of target names is given, this will only check those targets.
				Otherwise, it will check all open targets. See
				BlackBox_Stats::Check_Stats for more information.

			@param $blackbox BlackBox
			@param $stat_names array The stat names to check: defaults
				to all stats.
			@param $target_names array The targets to check: defaults
				to all open targets.

			@return array Valid target _names_
		*/
		public function Check_All_Stats($stat_names = NULL, $target_names = NULL, $simulate = FALSE)
		{

			// allow them to specify one target, an array
			// of targets, or just default to all targets
			if (is_string($target_names))	$targets = array($target_names);
			elseif (empty($target_names)) $target_names = $this->targets->Get_Open();

			$failed = array();

			foreach ($target_names as $name)
			{

				// avoid copying the object
				$target = &$this->targets->Target($name);

				if ($target)
				{

					// are we "simulating" this run?
					$simulated = (($simulate===TRUE) || (is_array($simulate) && in_array($name, $simulate)));

					// check their stats
					$valid = $target->Check_Stats($this->config, $stat_names, $simulated);

					if (!$valid)
					{
						$failed[$name] = $target->Failed();
						
						//If we failed overflow leads, we'll increment the counter.
						//We'll use this later to determine if all the targets in a
						//tier failed this stat, in which case it's time to do some
						//overflow work.
						if($target->Get_Failed_Stat() == STAT_OVERFLOW_LEADS)
						{
							$this->failed_overflow++;
						}
					}

					if (!$simulated)
					{
						// modify $this->open
						$this->targets->Open($name, $valid);
					}

				}

			}

			$this->failed = $failed;

			// return array of open targets
			return $this->targets->Get_Open();

		}

		/**
			@desc Run the rules for all our targets. If an array of
				target names is given, this will restrict it's check to
				those targets. Otherwise, all open targets will be
				checked. See BlackBox_Target::Run_Rules for more
				information.

			@param $blackbox BlackBox
			@param $data array Associative array of normalized form data
			@param $target_names array Restricts rule checks to these
				targets. Defaults to all open targets.

			@return array Valid target _names_

		*/
		public function Run_All_Rules($data, $target_names = NULL)
		{

			// allow them to specify one target, an array
			// of targets, or just default to all targets
			if (is_string($target_names))	$targets = array($target_names);
			elseif (empty($target_names)) $target_names = $this->targets->Get_Open();

			$failed = array();

			foreach ($target_names as $name)
			{

				// avoid copying the object
				$target = &$this->targets->Target($name);

				if ($target)
				{

					// run the rules
					$valid = $target->Run_Rules($this->config, $data);
					//$valid = $target->Validate($data, $this->config);

					if (!$valid)
					{
						$failed[$name] = $target->Failed();
					}

					// leave them open if they pass
					$this->targets->Open($name, $valid);

				}

			}

			// store the failures
			$this->failed = $failed;

			// return array of open targets
			return $this->targets->Get_Open();

		}
		
		public function Run_CFE($data, $target_names = NULL)
		{
			if (is_string($target_names))	$targets = array($target_names);
			elseif (empty($target_names)) $target_names = $this->targets->Get_Open();
			
			$failed = array();

			foreach ($target_names as $name)
			{

				// avoid copying the object
				$target = &$this->targets->Target($name);

				if ($target)
				{

					// run the rules
					$valid = $target->Run_CFE($this->config, $data);
					//$valid = $target->Validate($data, $this->config);

					if (!$valid)
					{
						$failed[$name] = $target->Failed();
					}

					// leave them open if they pass
					$this->targets->Open($name, $valid);

				}

			}

			// store the failures
			$this->failed = $failed;

			// return array of open targets
			return $this->targets->Get_Open();
		}
		
		public function Run_All_Filters($data, $target_names = NULL)
		{

			// allow them to specify one target, an array
			// of targets, or just default to all targets
			if (is_string($target_names))	$targets = array($target_names);
			elseif (empty($target_names)) $target_names = $this->targets->Get_Open();

			$failed = array();

			foreach ($target_names as $name)
			{

				// avoid copying the object
				$target = &$this->targets->Target($name);

				if ($target && $target->Has_Filters())
				{

					// run the filters
					$valid = $target->Run_Filters($data);

					if (!$valid)
					{
						$failed[$name] = $target->Failed();
					}

					// leave them open if they pass
					$this->targets->Open($name, $valid);

				}

			}

			// store the failures
			$this->failed = $failed;

			// return array of open targets
			return $this->targets->Get_Open();

		}
		

		/**

			@desc Shrink to an array for serialization. See
				BlackBox::Sleep().

		*/
		public function Sleep()
		{

			$tier = array();
			$tier['name'] = $this->name;
			$tier['open'] = $this->targets->Get_Open();
			$tier['targets'] = $this->targets->Sleep();
			$tier['class_name'] = get_class($this);

			return $tier;

		}

		protected function Valid_Data($data)
		{

			$valid = is_array($data);

			if ($valid) $valid = (isset($data['targets']) && is_array($data['targets']));
			if ($valid) $valid = (isset($data['open']) && is_array($data['open']));
			if ($valid) $valid = (isset($data['name']));

			return $valid;

		}

		public function Restore($data, &$config)
		{

			$new_tier = FALSE;

			if (BlackBox_Tier::Valid_Data($data))
			{

				$tier_name = $data['name'];

				if (isset($this) && ($this instanceof BlackBox_Tier))
				{

					$new_tier = &$this;
					$new_tier->name = $tier_name;

				}
				else
				{

					if ($config)
					{
						$null = NULL;
						$class_name = $data['class_name'];
						$new_tier = new $class_name($tier_name, $null, $null, $config);
					}

				}

				if ($new_tier)
				{
					$new_tier->targets = BlackBox_Target_Collection::Restore($data['targets'], $config);

					foreach($new_tier->targets as $target)
					{
						$target->Set_DataX($new_tier->datax);
					}
					
					// reset the open array
					$new_tier->targets->Open($data['open'], TRUE);

					$new_tier->name = $data['name'];

				}

			}

			return $new_tier;

		}



		public function Pick($bypass_used_info = false)
		{
			$picker = new BlackBox_Picker($this->config, $this->targets, $this->datax, $this->weight_type);
			return $picker->Pick($bypass_used_info);
		}




		// DEPRECATED
//		private function Total_Prop($prop_name, $target_names = '')
//		{
//
//			// allow them to specify one target, an array
//			// of targets, or just default to all targets
//			if (is_string($target_names))	$targets = array($target_names);
//			elseif (!$target_names) $target_names = array_keys($this->target);
//
//			$count = 0;
//
//			foreach ($target_names as $name)
//			{
//
//				$target = &$this->Targets($name);
//
//				if ($target)
//				{
//					$value = $this->Get_Prop($target, $prop_name);
//					if (is_numeric($value)) $count = $count + $value;
//
//				}
//
//			}
//
//			return $count;
//
//		}

		/**
			@desc Count the total number of stats for this tier
			@param $stat_name string The stat to count
			@param $target_names array Specify which targets to check
			@return int Total number of stats
		*/
		protected function Total_Stats($stat_name, $target_names = '')
		{

			// allow them to specify one target, an array
			// of targets, or just default to all targets
			if (is_string($target_names))	$targets = array($target_names);
			elseif (!$target_names) $target_names = array_keys($this->targets->Get_Targets());

			$count = 0;

			foreach ($target_names as $name)
			{

				// avoid copying the object
				$target = &$this->targets->Target($name);

				if ($target)
				{
					$count += $target->Stats($stat_name);
				}

			}

			return $count;

		}

		
		protected function Get_DataX_Account(&$target)
		{
			// all non-CLK calls use the PW account
			return (!is_null($target)) ? $target->Get_DataX_Account() : 'PW';
		}
		


		public function Log_Event($name, $result, $target = NULL)
		{
			$this->config->Log_Event($name, $result, $target);
		}




		public function Validate($data, &$config = NULL, $bypass_used_info = false)
		{
			// holds targets that we've had
			// previous loans with
			$this->config->react = array();

			
			// CHECK RULES
			$this->Validate_Rules($data);
			
			//Run CFE
			$this->Validate_CFE($data);
			
			//Check Filters
			$this->Validate_Filters($data);

			// CHECK STATS
			$this->Validate_Stats();
			
			// return the outcome
			return $this->Valid();
		}

		protected function Validate_Rules($data, $targets = null)
		{
			if ($this->Valid())
			{

				if ($this->config->debug->Debug_Option(DEBUG_RUN_RULES) !== FALSE)
				{
					// weed out targets that fail rule checks
					$this->Run_All_Rules($data, $targets);
				}
				else
				{
					$this->Log_Event(EVENT_RULE_CHECK, EVENT_SKIP);
				}

			}
		}
		
		protected function Validate_Filters($data, $targets = null)
		{
			if ($this->Valid())
			{

				if ($this->config->debug->Debug_Option(DEBUG_RUN_FILTERS) !== FALSE)
				{
					// weed out targets that fail rule checks
					$this->Run_All_Filters($data, $targets);
				}
				else
				{
					$this->Log_Event(EVENT_FILTER_CHECK, EVENT_SKIP);
				}

			}
		}
		
		protected function Validate_CFE($data, $targets = null)
		{
			if ($this->Valid())
			{
				if ($this->config->debug->Debug_Option(DEBUG_RUN_CFE) !== FALSE)
				{
					$this->Run_CFE($data, $targets);
				}
				else 
				{
					$this->Log_Event(EVENT_CFE_CHECK, EVENT_SKIP);
				}
			}
		}


		protected function Validate_Stats($targets = null)
		{
			if ($this->Valid())
			{

				if ($this->config->debug->Debug_Option(DEBUG_RUN_STATS) !== FALSE)
				{

					// NOTE: The stats will get checked in the order in which they appear -
					// we want to move from trivial to more intensive to weed out targets
					// as quickly and efficiently as possible... Also, because we allow them
					// to continue if all targets fail the direct deposit stat (ratio), we do
					// that check last - that way, if they fail DD, we know they passed all
					// the other stat checks and we don't have to rerun Check_All_Stats
					$stats = array(STAT_DAILY_LEADS, STAT_HOURLY_LEADS, STAT_TOTAL_LEADS);


					// We want to prcess NonDD leads but not after they have already agreed
					if(($this->config->bb_mode != MODE_CONFIRMATION) &&
						isset($this->config->data['income_direct_deposit']) &&
						($this->config->data['income_direct_deposit'] != 'TRUE'))
					{
						$stats[] = STAT_NO_DIRECT_DEPOSIT;
						//$stats[] = ($this->config->data['income_direct_deposit'] == 'TRUE') ? STAT_DIRECT_DEPOSIT : STAT_NO_DIRECT_DEPOSIT;
					}

					// get rid of targets that fail limits, etc.
					// We dont want to pass React NonDD
					//$this->Check_All_Stats($stats, NULL, $this->config->react);
					$this->Check_All_Stats($stats, $targets);

					$failed = $this->Failed();

					if ($failed)
					{

						// If all targets fail either DIRECT_DEPOSIT, or NO_DIRECT_DEPOSIT,
						// let them keep going (NOTE: see gargantuan comment above)
						if (count($failed) == array_keys($failed, end($stats)))
						{
							$this->targets->Open($failed_dd, TRUE);
						}

					}

				}
				else
				{
					$this->Log_Event(EVENT_STAT_CHECK, EVENT_SKIP);
				}

			}
			
			return $this->Valid();
		}

		
		
		public function Get_DataX_Decision()
		{
			return (isset($this->datax)) ? $this->datax->Get_Decisions() : NULL;
		}


		public function Set_Collection(&$collection)
		{
			if($collection instanceof BlackBox_Tier_Collection)
			{
				$this->collection = &$collection;
			}
		}


		public function Prepare_Overflow()
		{
			$overflow_targets = array_map('trim', explode(',', $this->config->config->overflow_targets));
			
			//Restrict targets to only the specified overflow targets
			$this->Restrict($overflow_targets);

			//We want to weigh these guys based on how much they'll pay us.
			$this->weight_type = 'AMOUNT';
			
			//Here we'll set the target->rate variable to be the lead_amount
			//so the Weigh_Targets function will work correctly.
			foreach($this->targets as &$target)
			{
				$target->Set_Rate('lead_amount');
			}
		}
		
		
		public function Reset()
		{
			//Might do something later.
		}


		public function Get_Collection()
		{
			return $this->collection;
		}
		
		
		public function Get_Target_List($in_use = true, $flat = true, $use_objects = false)
		{
			return $this->targets->Get_Target_List($in_use, $flat, $use_objects);
		}

	}

?>
