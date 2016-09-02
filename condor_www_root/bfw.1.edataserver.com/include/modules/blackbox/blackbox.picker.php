<?php

	include_once(BFW_MODULE_DIR . 'olp/stat_limits.php');

	define('WEIGH_ORDER_LARGEST', 'FURTHEST');
	define('WEIGH_ORDER_SMALLEST', 'SMALLEST');

	// PROPERTIES
	define('TARGET_RATE', 'Rate');

	class BlackBox_Picker
	{
		protected $config;
		protected $targets;
		protected $weight_type;
		protected $datax;

		
		public function __construct(&$config, &$targets, &$datax, $weight_type)
		{
			$this->config = &$config;
			$this->targets = &$targets;
			$this->datax = &$datax;
			$this->weight_type = $weight_type;
		}
		
		public function __destruct()
		{
			//unset($this->datax);
			//unset($this->targets);
			//unset($this->config);
		}
		
		public function Set_Weight_Type($weight_type)
		{
			$this->weight_type = $weight_type;
		}


		public function Pick($bypass_used_info = FALSE)
		{
			$winner = FALSE;

			// weigh the open targets
			$choices = $this->Weigh_Targets();

			if(is_array($choices))
			{
				// save for later
				$this->config->Save_Snapshot('weighted', $choices);

				$limits = new Stat_Limits($this->config->sql, $this->config->sql->db_info['db']);
				
				$prev_target = FALSE;
				while(count($choices) && (!$winner))
				{
					//If we have sequential targets, let's use 'em
					if(count($this->Get_Sequential($choices)) > 0)
					{
						$winner = $this->Pick_Sequential($choices);
					}
					elseif(isset($_SESSION['process_overflow']))
					{
						// If we're doing an overflow, we want whoever's going to
						// give us the most cash monies and eventually make us
						// all millionaires!
						$winner = $this->Pick_Overflow($choices);
					}
					elseif($this->config->config->force_priority)
					{
						$winner = $this->Pick_Weighted($choices);
					}
					else
					{
						// now, pick one randomly, but weighted
						$winner = $this->Pick_Random($choices);
					}

					//after all the excitement, let's take a look at the percentile caps:
					$winner = $this->rePickFreq($choices,$winner);

					$target = &$this->targets->Target($winner);
					$prop_short = $winner;
					
					if(is_object($target))
					{
						/**
						 * Increment a bb_target_look stat for each vendor we try to post to.
						 * Don't check reacts since this will initially be used to for CLK's
						 * weighting percentages (of which reacts don't count)
						 */
						if ($this->config->bb_mode == MODE_DEFAULT &&
							!((is_array($this->config->react)
								&& in_array(Enterprise_Data::resolveAlias($winner), $this->config->react))
								|| isset(SiteConfig::getInstance()->ecash_react)))
						{
							if(!$limits->Increment('bb_' . strtolower($winner) . '_look', NULL, NULL, NULL))
							{
								$this->config->Applog_Write("Couldn't increment bb_{$winner}_look.");
							}
						}
						
						// validate the winner
						$valid = $target->Validate($this->config->data, $this->config, $bypass_used_info);
					}
					else
					{
						$valid = FALSE;
						$this->targets->Open(NULL, FALSE);
						$this->config->Applog_Write('Invalid Winner in Tier->Pick() for app_id ' . $this->config->application_id);
					}

					
					// can they win?
					if(!$valid)
					{
						$winner = FALSE;
					}
					else
					{
						$winner = $target->Pick();
						if(is_object($winner))
						{
							$prop_short = $winner->Name();
						}
						else
						{
							$valid = false;
						}
					}

					// unset choices that got closed
					$closed = array_diff(array_keys($choices), $this->targets->Get_Open());
					
					foreach($closed as $name)
					{
						unset($choices[$name]);
					}

					$datax_decision = (isset($this->datax)) ? $this->datax->Get_Decisions() : NULL;

					//I really loathe this code.  If we have Impact failing its PDX call as a tier 2 vendor
					//we need to make sure we quit out here, or else we'll end up closing a bunch of targets
					//we should be selling to!
					if(!$valid
						&& $this->config->Is_Impact($prop_short)
						&& $this->config->bb_mode == MODE_DEFAULT
						&& ($this->config->config->online_confirmation
							&& $this->config->config->enable_rework
							&& !$_SESSION['IDV_REWORK']
							&& !$_SESSION['REWORK_RAN'])
						&& ($datax_decision['DATAX_IDV'] == 'N' || $datax_decision['DATAX_PDX_IMPACT'] == 'N'))
					{
						break;
					}
				}
			}

			return $winner;
		}




		/**

			@desc Performs a random pick out of our
				final target choices, based upon the
				targets' weight. The actual weighting of
				the targets is computed in Teir->Weigh_By_*

			@param $targets array An associative array of
				target names and their weights.

			@return string The name of one target, picked
				randomly, but according to weight, fom the
				targets array.

		*/
		protected function Pick_Random($targets)
		{
			//Mix up targets
			$targets = $this->Jumble($targets);
		
			// get the total
			$sum = array_sum($targets);

			// nobody (yet)
			$winner = NULL;

			// pick a number between 0 and ($sum * 10)
			$percent = 10;
			$random = mt_rand(1, round($sum * $percent));
			$count = 0;

			$choices = array();

			foreach($targets as $name => $weight)
			{

				$count += round($weight * $percent);
				$choices[$name] = $count;

				if (!isset($winner) && $random <= $count)
				{
					$winner = $name;
				}
			}

			// save these for later
			$this->config->Save_Snapshot('choices', $choices);
			$this->config->Save_Snapshot('random', $random);

			return $winner;
		}
	/**

			@desc Performs a weighted pick out of our
				final target choices, based upon the
				targets' priority set in webadmin2.

			@param $targets array An associative array of
				target names and their weights.

			@return string The name of one target, picked
				randomly, but according to weight, fom the
				targets array.

		*/
		protected function Pick_Weighted($targets)
		{
			$winner = key($targets);
			return $winner; 
		}

		protected function Pick_Overflow($targets)
		{
			$array = array();
			
			/* We want to change the array's contents from
			 *
			 * array(TARGET => VALUE, TARGET => VALUE, etc)
			 * to
			 * array(VALUE => array(TARGETS), VALUE => array(TARGETS)
			 * 
			 * so that we can pick a random winner from a
			 * group that has the same payout
			 */
			foreach($targets as $prop_short => $weight)
			{
				if(!isset($array[$weight]))
				{
					$array[$weight] = array();
				}
				
				$array[$weight][] = $prop_short;
			}

			// We want to get the ones who give us the most cash, so pick
			// the group from the top (the group who will pay out the most)
			// and choose a random one.
			krsort($array);
			$top = array_shift($array);
			
			sort($top);

			$winner = $top[array_rand($top)];

			$this->config->Save_Snapshot('overflow_choices', $array);

			return $winner;
		}
	

		/**

			@desc The actual math to compute the ranking of targets.
				This is used by Rank_By_Prop and Rank_By_Stat. It's
				rather hard to explain using words, so here's a sample,
				using completely bogus data:

				1. Sort by value, largest first (or smallest first,
					depending on the value of $weigh_order):

					(UCL=>$18, CA=>$14, D1=>$14, VP2=>$12)

				2. Compute ranking:

					(UCL=>4, CA=>3, D1=>2, VP2=>1)

				3. Share ties:

					(UCL=>4, CA=>2.5, D1=>2.5, VP2=>1)

				(Steps 2 and 3 are actually done at once, but it's
				easier to see when they're split up).

			@param $array array Associative array of targets and
				their values (ex. rates or stats).
			@param $weigh_order string The order in which to rank:
				either largest first, or smallest first.
			@param $prev_weights array Associate array of targets
				that have already been ranked using this function:
				this allows you to rank by two properties.

			@return array Weighted array of targets

		*/
		protected function Rank($array, $weigh_order = WEIGH_ORDER_LARGEST, $prev_rank = NULL)
		{
			$weights = array();

			if(is_array($array))
			{
				$values = array_unique($array);

				// sort the array numerically largest first
				if($weigh_order == WEIGH_ORDER_LARGEST)
				{
					arsort($values, SORT_NUMERIC);
				}
				else
				{
					asort($values, SORT_NUMERIC);
				}

				$total = count($array);
				$pos = $total;

				foreach($values as $value)
				{
					// figure out how many targets are tied
					// for this value, if any
					$targets = array_keys($array, $value);
					$count = count($targets);

					// do some math
					$base = (($pos - $count) + 1);
					$weight = round(((($pos - $base) / $count) + $base), 2);

					// assign the weights
					foreach ($targets as $name)
					{
						if (is_array($prev_rank) && array_key_exists($name, $prev_rank))
						{
							$weights[$name] = ($weight + $prev_rank[$name]);
						}
						else
						{
							$weights[$name] = $weight;
						}
					}

					// keep moving through the targets
					$pos -= $count;
				}

				if(is_array($prev_rank))
				{
					// reorder the numbers
					$weights = $this->Rank($weights, $weigh_order);
				}

			}

			if(!$weights)
			{
				$weights = FALSE;
			}

			return $weights;
		}
		
		/**

			@desc The actual math to compute the weighting of targets.
				Returns an associate array of target names and weights. while
				this function attempts to return percentages, the actual sum
				may be slightly less or more than 100.

		*/
		protected function Weigh($array, $weigh_order = WEIGH_ORDER_LARGEST, $prev_weights = NULL)
		{

			if(is_array($array) && (($total = array_sum($array)) !== 0))
			{
				// holds our weights
				$weights = array();

				foreach($array as $name=>$value)
				{
					// get our percentage of the total
					$value = floor(($value / $total) * 100);
					if ($value < 1)
					{
						$value = 1;
					}

					// assign our weight
					$weights[$name] = $value;
				}

				if($weigh_order == WEIGH_ORDER_SMALLEST)
				{
					// we'll need these sorted
					asort($weights);

					// if we're ordering smallest first, then
					// simply reverse the order of the targets
					$weights = array_combine(array_rev(array_keys($weights)), $weights);
				}

				if(is_array($prev_weights) && (($total_prev = array_sum($prev_weights)) !== 0))
				{
					foreach ($weights as $name=>$value)
					{
						// get our previous weight as a percentage
						// of the total (this allows ranks to be used)
						$prev_value = (isset($prev_weights[$name]) ? $prev_weights[$name] : 0);
						$prev_value = floor(($prev_value / $total_prev) * 100);

						// now get our combined weight as a percentage of the
						// total (in this case, the total is always 200, since
						// both weights will be percentages at this point)
						$value = floor((($value + $prev_value) / 200) * 100);
						if($value < 1)
						{
							$value = 1;
						}

						// assign our new weight
						$weights[$name] = $value;
					}
				}

				// make 'em pretty
				arsort($weights);
			}

			if(!isset($weights))
			{
				$weights = FALSE;
			}

			return $weights;
		}
		
		/**
		 * Jumble targets
		 * 
		 * Randomize an array
		 * @param array 
		 * @return array
		 */
		protected function Jumble($targets)
		{
			if(!is_array($targets) || empty($targets) || count($targets) == 1)
			{
				return $targets;
			}
			
			$array_keys = array_rand($targets, count($targets));
	
			$jumbled = array();
			foreach($array_keys as $key)
			{
				$jumbled[$key] = $targets[$key];
			}

			return $jumbled;
		}
		
		
		/**
			@desc Weighs the targets within this tier and returns the targets
				and their weights in an array.

			@return mixed An associative array of target names and
				their computed	weights.

		*/
		public function Weigh_Targets()
		{
			// get our priority/percent/rate
			$choices = $this->Fetch_Property(TARGET_RATE);
			
			if($this->weight_type != 'PRIORITY')
			{
				$choices = $this->Rank($choices);
			}
			else
			{
				$choices = $this->Weigh($choices);
			}
			
			return $choices;
		}
		
		
		/**

			@desc A little hairy, but will allow you to weigh
				by a "property": either a function or variable in the
				target object. For instance, this is used when
				weighing open targets by their rate (so we make the
				most money!!).

			@param $prop_name string The name of a function or variable
				in the BlackBox_Target object
			@param $target_names array Targets to check. Defaults to
				all open targets.

			@return array An associative array of target names and
				their computed	weights.

		*/
		public function Fetch_Property($prop_name, $target_names = NULL)
		{
			// allow them to specify one target, an array
			// of targets, or just default to all targets
			if(is_string($target_names))
			{
				$target_names = array($target_names);
			}
			elseif(!$target_names)
			{
				$target_names = $this->targets->Get_Open();
			}

			$values = array();

			foreach($target_names as $name)
			{
				$target = &$this->targets->Target($name);

				if($target)
				{
					// get the local value
					$value = $this->Get_Prop($target, $prop_name);
					if(!is_null($value))
					{
						$values[$name] = $value;
					}
				}
			}

			return $values;
		}
		


		/**
			@desc Weigh possible targets by a statistic. If the statistic
				has been associated with a total, this will weigh the
				targets according to "need": the proportionate distance
				from their limit ((limit - value) / limit).

			@param $stat_name string A statistic name
			@param $target_names array Targets to check. Defaults to
				all open targets.

			@return array An associative array of target names and
				their computed	weights.
		*/
		protected function Fetch_Stat($stat_name, $target_names = NULL)
		{
			// allow them to specify one target, an array
			// of targets, or just default to all targets
			if(is_string($target_names))
			{
				$targets = array($target_names);
			}
			elseif(!$target_names)
			{
				$target_names = $this->targets->Get_Open();
			}

			$values = array();

			foreach($target_names as $name)
			{

				// avoid copying the object
				$target = &$this->targets->Target($name);

				if($target)
				{
					$value = $target->Stats($stat_name);
					$stat_limit = $target->Limits($stat_name);
					$stat_total = $target->Totals($stat_name);

					// if we're a percent based stat,
					// get our current percentage
					if ($stat_total) $value = round(($value /  $stat_total) * 100);

					// if we have a limit, figure out
					// how far we are from it, in percent
					if ($stat_limit) $value = round((($stat_limit - $value) / $stat_limit) * 100);

					// save our weight
					$values[$name] = ($value !== FALSE) ? $value : 0;
				}
			}

			return $values;
		}

		
		/**

			@desc Used exclusively by Weigh_By_Prop, this will
				return the value of a BlackBox_Target property,
				$prop_name, either a function or public variable.

			@param $target object Reference to a BlackBox_Target object
			@param $prop_name string The property to return

			@return mixed The value of the property

		*/
		protected function Get_Prop(&$target, $prop_name)
		{
			$value = NULL;

			if(is_object($target))
			{

				// try as a function
				if(method_exists($target, $prop_name))
				{
					$value = $target->$prop_name();
				}
				elseif(isset($target->$prop_name))
				{
					$value = $target->$prop_name;
				}

			}
			elseif(is_array($target))
			{
				// try as a key
				if(isset($target[$prop_name]))
				{
					$value = $target[$prop_name];
				}
			}

			return $value;
		}
		
		/**
		 * Checks to see if any of the targets in the input array has a set Freq Score % cap
		 *
		 * @param mixed $choices array of targets as pshort=>weight or string single target pshort
		 * @return boolean TRUE if one or more of the targets has a set Freq Score % cap
		 */
		protected function hasFreqOrders($choices)
		{
		
			$limitArr=array();
			$compareArr=array(0,0,0);
			
			if(is_string($choices))
			{
				$swapper=$choices;
				$choices = array(
					strtoupper($swapper) => 50
				);
			}

			$targets = array();
			foreach(array_keys($choices) as $choice)
			{
				//We need to check each of the choices to see if it has children
				//because it's possible the person creating the WA2 option
				//will use the children instead of the parent.  Because of this
				//we need to check to make sure the children also exist.

				if($this->targets->Target_Has_Children($choice))
				{
					$children = $this->targets->Target($choice)->Get_Target_List();
					$targets = array_merge($targets, $children);
				}
				$targets[$choice] = $choice;
			}
			
			$limitArr=$this->Fetch_Property('frequency_limits', array_keys($targets));
			
			foreach($limitArr as $targetName=>$targetLimits)
			{
				if(is_array($targetLimits))
				{
					//compare independent of the max_freq limits
					$tempLimits=array_slice($targetLimits,6);
					$tempDiff=array_diff($tempLimits, $compareArr);
					if (count($tempDiff))
					{
						return TRUE;
					}
				}
			}
			return FALSE;
		}
		
		/**
		 * If a target has a freq percentile cap, checks among the choices for other percentile
		 * capped targets at the current lead_freq_decline level.  If any are found, it returns  
		 * the one that has the greatest positive distance between the current 
		 * vendor_freq_decline and the cap.  If no capped targets are found, it returns the 
		 * submitted target.  
		 * 
		 * @see Accept_Ratio_Singleton orderVendorsByFreq()
		 *
		 * @param array $choices the possible targets
		 * @param string $target the property short of the target currently selected
		 * @return string the (re)selected target
		 */
		protected function rePickFreq($choices, $target)
		{
			// change the pick based on the distance from the magic goal
			
			//check this target for percentile cap, if not just return it
			if(!$this->hasFreqOrders($target))
			{
				return $target;
			}
			
			$nowmail=$this->config->data['email_primary'];
			
			$freqObject=Accept_Ratio_Singleton::getInstance($this->config->sql);
			$leadFreq=$freqObject->getMemScore($nowmail);
			
			// return the same target if the *lead* freq score is over 3
			if($leadFreq > 3) 
			{
				return $target;
			}
			
			$freqOrdered = array();
			
			
			$limitArr=$this->Fetch_Property('frequency_limits', array_keys($choices));
			
			// the current target is percentile-capped; now we need to see if anyone else 
			//among the choices is also capped
			$cappedchoices=array();
			
			
			
			// get the ordered scores, sorted by proximity from goal %
			$scores=$freqObject->orderVendorsByFreq($limitArr, $choices, $nowmail);
			
			//if the lead freq has changed to > 3 in the last few millis, it's possible 
			//the scores array will be empty
			if (empty($scores))
			{
				return $target;
			}
			
			$idealtargets=$scores[$leadFreq];
			$uncappedtargets=array();
			
			$leadFreqCopy=$leadFreq;
			
			while ($leadFreqCopy < 3)
			{
				foreach ($scores[$leadFreqCopy] as $prop_short=>$distance)
				{
					//skip dudes without limits
					if ($distance == 100)
					{
						//this target has no cap, add it to the uncapped dudes
						$uncappedtargets[]=$prop_short;
					}
					elseif ($distance >= 0)
					{
						//check for negatives; otherwise the first pick is the best one
							return $prop_short;
					}
				}
				// none of these capped buggers made it on that particular look
				//Stats::Hit_Stats();
				
				// added 02/11: concern over race condition with highest-capped first look vendor
				// **if** there are only capped vendors available among the choices
				return $target;
				
				//are there other vendors available?
				if (count($uncappedtargets))
				{
					//if so, return the uncapped one, as none of the capped ones flew on this "look"
					//grab the first one among the choices, to preserve whatever ordering is in place
					$goodguy=array_intersect(array_keys($choices),$uncappedtargets);
					if (count($goodguy))
					{
						return $goodguy[0];
					}
					else
					{
						//awful error
					}
				}
				// there are no more uncapped targets, return the best choice at the current "look"
				
				//removed because of concerns over 100% lead distance calculation
				//$thetarget=array_shift(array_keys($scores[$leadFreqCopy]));
				
				return $thetarget;
				
				$leadFreqCopy++;
			}
	
			return $target;  //failsafe
		}
		
		
		/**
		 * GFORGE 4253 sequential preferred targets
		 * Gets a list of sequential targets.
		 *
		 * @param array $choices A list of potential choices
		 * @return array A list of sequential targets
		 */
		protected function Get_Sequential($choices)
		{
			$sequence_targets = array();
			
			//Only do it if we have the config option
			if(!empty(SiteConfig::getInstance()->bb_sequential_preferred))
			{
				$targets = array();
				foreach(array_keys($choices) as $choice)
				{
					//We need to check each of the choices to see if it has children
					//because it's possible the person creating the config option
					//will use the children instead of the parent.  Because of this
					//we need to check to make sure the children also exist.
					if($this->targets->Target_Has_Children($choice))
					{
						$children = $this->targets->Target($choice)->Get_Target_List();
						$targets = array_merge($targets, $children);
					}

					$targets[$choice] = $choice;
				}

				$sequence = array_map('trim', explode(',', strtoupper(SiteConfig::getInstance()->bb_sequential_preferred)));
				
				//We only want the ones that are in both
				$sequence_targets = array_unique(array_intersect($sequence, $targets));
			}
				
			return $sequence_targets;
		}
		
		/**
		 * GFORGE 4253 sequential preferred targets
		 * Picks a target from a sequential list
		 *
		 * @param array $choices A list of potential winners
		 * @return string The name of the target who is chosen
		 */
		protected function Pick_Sequential($choices)
		{
			//The the possible choices
			$targets = $this->Get_Sequential($choices);

			$this->config->Save_Snapshot('sequence', $targets);
			
			//Grab the first in the sequence.
			$winner = array_shift($targets);
			$target_obj = $this->targets->Target($winner);
			
			//If we have a parent object, we need to return it
			if($target_obj->Has_Parent())
			{
				$parent = $target_obj->Get_Parent()->Name();
				
				//But only if that parent object is actually part of the current collection.
				//Otherwise, when the parent was picking its children, it would blow up since
				//it would return a target that doesn't exist in the collection.
				if($this->targets->Has_Target($parent))
				{
					$winner = $parent;
				}
			}

			return $winner;
		}
	}
	


	class BlackBox_Picker_Percent extends BlackBox_Picker
	{
		public function __construct(&$config, &$targets, &$datax)
		{
			parent::__construct($config, $targets, $datax, 'PERCENT');
		}

		
		protected function Rank($choices)
		{
			//Only run if we actually have to make a choice.
			if(count($choices) > 1)
			{
				$stats = $this->Fetch_Stat(STAT_DAILY_LEADS);

				$total = array_sum($stats);

				foreach($choices as $target => $value)
				{
					//We'll grab their current percentage of leads
					$percent = ($total > 0) ? round(($stats[$target] / $total) * 100, 3) : $value;

					//Then figure out the difference between actual and expected and rank them that way
					$choices[$target] = $value - $percent;
				}
			}

			return $choices;
		}
		
		/**
		 * Pick "Random"
		 * 
		 * Overrides the random picker in blackbox tier just picks the one that
		 * is furthest away from its target
		 * @param array Targets
		 * @return string Winner
		 */
		protected function Pick_Random($targets)
		{
			$winner = array_search(max($targets), $targets);

			// save these for later
			$this->config->Save_Snapshot('choices', $targets);

			return $winner;
		}
	}

	
	class BlackBox_Picker_Tier1 extends BlackBox_Picker_Percent
	{
		protected function Fetch_Stat($stat_name, $target_names = NULL)
		{
			include_once(BFW_MODULE_DIR . 'olp/stat_limits.php');
			$stat_limits = new Stat_Limits($this->config->sql, $this->config->database);

			if(empty($target_names))
			{
				$target_names = array_keys($this->targets->Get_Targets(false));
			}

			foreach($target_names as $target)
			{
				//For CLK, we'll get the sum of all their properties.
				if($target == 'CLK')
				{
					$values[$target] = 0;
					foreach($this->config->clk_properties as $clk_prop)
					{
						$clk_prop = strtolower($clk_prop);
						$values[$target] += $stat_limits->Fetch("bb_{$clk_prop}_look", 0, 0, 0, date('Y-m-d'));
					}
				}
				else
				{
					$values[$target] = $stat_limits->Fetch("bb_{$target}", 0, 0, 0, date('Y-m-d'));
				}
			}

			return $values;
		}
	}

	
	class BlackBox_Picker_CLK extends BlackBox_Picker_Percent
	{
		protected function Fetch_Stat($stat_name, $target_names = NULL)
		{
			$values = parent::Fetch_Stat($stat_name, $target_names);

			$closed = array_diff(array_map('strtoupper', $this->config->clk_properties), array_keys($values));

			//We still need to grab the totals for CLK targets that were removed.
			//Otherwise, we won't be able to get an accurate percentage when we rank
			//(since the totals wouldn't be based on all CLK companies, only the ones
			// who were still active).
			if(!empty($closed))
			{
				include_once(BFW_MODULE_DIR . 'olp/stat_limits.php');
				$stat_limits = new Stat_Limits($this->config->sql, $this->config->database);

				foreach($closed as $target)
				{
					$values[$target] = $stat_limits->Fetch("bb_{$target}_look", 0, 0, 0, date('Y-m-d'));
				}
			}

			return $values;
		}
	}
	
?>
