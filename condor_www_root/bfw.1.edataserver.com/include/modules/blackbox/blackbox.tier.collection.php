<?php
	/**

		@name BlackBox_Tier_Collection
		@version 0.1
		@author Chris Barmonde

		@desc
			If a tier is a collection of targets, this is a
			collection of tiers.  This class provides functionality
			for managing tiers in BlackBox.

	*/

	class BlackBox_Tier_Collection extends BlackBox_Collection
	{
		private $tiers;			//Array of tiers

		private $datax_decision;//DataX decision

		public function __construct(&$bb_config = NULL)
		{
			parent::__construct($bb_config, 'BlackBox_Tier');

			$this->tiers = &$this->objects;

			$this->datax_decision = array();
		}


		public function __destruct()
		{
			parent::__destruct();

			unset($this->tiers);
		}


		/**
			@desc Looks for and returns a tier object.

			@param $tier_name string The name of the tier to find

			@return BlackBox_Tier The tier object

		*/
		public function Tier($tier_name)
		{
			return $this->Object($tier_name);
		}


		/**
			@desc Returns the tiers array

			@return array An array of BlackBox_Tier objects

		*/
		public function Get_Tiers()
		{
			return $this->Get_Objects();
		}


		private function Set_Tiers(&$tiers)
		{
			$this->Set_Objects($tiers);
		}


		/**
			@desc Set or return whether a tier will be used:
				provide friendly access to $this->use. NOTE: the Use
				array should rarely be accessed "by hand"; instead,
				manipulate it via this function.

			@param $tier_name integer The tier
			@param $use boolean Optional: set whether we'll use this tier

			@return boolean Whether we'll using the tier

		*/
		public function Use_Tier($tier_names = NULL, $use = NULL)
		{
			return $this->Use_Object($tier_names, $use);
		}
		

		public function Open($names = null, $use = null)
		{
			return $this->Use_Tier($names, $use);
		}


		/**
			@desc Find a target within all the tiers by its name
			
			@param $name string The name of the target
			
			@return Target object
		*/
		public function Find_Target($name, $include_children = false)
		{
			$target = FALSE;

			if(!empty($name))
			{
				foreach($this->tiers as &$tier)
				{
					$target = &$tier->Targets($name, $include_children);

					if($target !== FALSE)
					{
						unset($tier);
						break;
					}

					unset($tier);
				}
			}

			return $target;
		}


		/**

			@desc Searches the tier->target structure looking for
				$target_names, and returns an array indicating
				the tiers they exist within. The array that is
				returned is in the format required by Restrict():
				that is, tier numbers are keys, and target names
				are values. For instance:

					$results = array(1 => array('UCL'));

				NOTE: If an array is provided in $results, the results
				of the search are inserted into that array (following
				the same format as above).

			@param $target_names array Target names (as values)
				to look for.
			@param $results array Optional array to insert results
				into.

			@return array Search results with tier numbers as keys,
				and target names as values.

		*/
		public function Find_Targets($target_names, &$results = NULL)
		{
			if(!is_array($target_names))
			{
				$target_names = array($target_names);
			}

			if(!is_array($results))
			{
				$results = array();
			}

			$find = array();

			// build a search array
			foreach($this->tiers as $name => &$tier)
			{
				//Get a flat list of all targets in use.
				$targets = $tier->Get_Target_List();
				foreach($targets as $target)
				{
					$find[strtolower($target)] = $name;
				}

				unset($tier);
			}

			foreach($target_names as $name)
			{
				if(isset($find[strtolower($name)]))
				{
					// what tier did we find it in?
					$tier = $find[strtolower($name)];

					// be nice to existing arrays
					if(!isset($results[$tier]) || !is_array($results[$tier]))
					{
						$results[$tier] = array();
					}

					$results[$tier][] = $name;
				}
			}
			
			//Bugfix - Gforge #2945 (Force Winner Bug)
			//Problem: If no results, make sure we return an empty array and not just a 0.
			foreach($results as $index => $value)
			{
				// Going to limit to JUST tiers since I'm not 100% sure on what else $results returns.
				if(in_array($index,array(0,1,2,3,4)) && $value == 0)
				{
					$results[$index] = array();
				}
			}
			

			return $results;
		}



		/**
			@desc Offshoot of BB's Pick_Winner

			@param valid bool Current valid state
			@param return_winner iBlackBox_Target The current BlackBox winner
			@param return_tier int Tier number that return_winner is in
			@param bypass_used_info bool Whether to bypass used_info check

			@return bool true/false depending on whether we found a winner or not
		*/
		public function Find_Winner(&$return_winner = NULL, &$return_tier = NULL, $bypass_used_info = FALSE)
		{
			$valid = FALSE;
			$do_rework = FALSE;
			$overflow_prepared = FALSE;
			
			// Mantis #7510 - Unsetting the Suppression list results 
			unset($_SESSION['suppression_results']);
			
			//This session variable is set when a promo's webadmin1 option is set to cap on submitlevel1 stats
			if($_SESSION['cap_submitlevel1_overflow'])
			{
				$_SESSION['process_overflow'] = TRUE;
				$_SESSION['enable_overflow'] = TRUE;
			}
			else
			{
				unset($_SESSION['process_overflow']);
			}
			
			
			//Reset Adverse Action
			if($this->config->bb_mode == MODE_DEFAULT && is_null($return_winner))
			{
				unset($_SESSION['adverse_action']);
			}
			//$aa_denial = NULL;

			//If we found a bad aba, we need to be sure to set an
			//Adverse Action denial
			if(empty($this->use) && empty($_SESSION['adverse_action']))
			{
				$_SESSION['adverse_action'] = ($this->config->Is_Impact()) ? 'aa_denial_impact' : 'aa_denial_clk';
			}
			
			//********************************************* 
			// Per Mike Lane, on 10/23/2007, we no longer 
			// sell WV leads to anyone in the universe. [CB]
			//
			// These changes from task 6787 will not allow
			// leads to come through from 'WV','GA','VA'
			// except the following sites (listed in the
			// array below) or if the lead came from an 
			// Agean site.
			//
			// GForge #9452 [AuMa] (PREQUAL)
			// Do not reject right away - wait until app is 
			// completed before reject 
			//
			//********************************************* 
			$home_state = strtoupper($this->config->data['home_state']);
			$sitesToAcceptLeads = array(
				// CLK sites
				'500fastcash.com',
				'ameriloan.com',
				'oneclickcash.com',
				'unitedcashloans.com',
				'usfastcash.com',
				'ecashapp.com',

				// Impact sites	
				'impactcashusa.com',
				'cashproviderusa.com',
				'impactcashcap.com',
				'impactsolutiononline.com',
				'cashfirstonline.com',

				// AALM sites
				'loanservicecompany.com'
			);
			if(     in_array($home_state, array('VA','WV','GA')) 
				&&  !in_array(SiteConfig::getInstance()->site_name, $sitesToAcceptLeads)			
				&&  !$this->config->Is_Agean_Site()	
				&&  !in_array($this->config->bb_mode, array(MODE_PREQUAL)) 
				)
			{
				$_SESSION['failure_reason'] = 'WVVAGA_CHECK'; // was used for G[#6972], now useless since G[#10066] [DY]
				
				$this->use = array();
				
				$this->config->Log_Event($home_state . '_LEAD', EVENT_FAIL);
				
				require_once(OLP_DIR . 'app_campaign_manager.php');
				$acm = new App_Campaign_Manager($this->config->sql, $this->config->database, $this->config->applog);
				$acm->Update_Application_Status($this->config->application_id, 'FAILED');
			}
		
			
			// even though the use array may be operated on during this loop,
			// I chose to stick with the for each to avoid any problems due
			// to external functions messing with the array pointer
			foreach ($this->use as $name)
			{
				// avoid copying the object
				$tier = &$this->Tier($name);
					
				// because the contents of the use array
				// may shift during flight, we check it again
				if ($this->In_Use($name) && $tier && !$do_rework)
				{

					// All CLK targets have hit their hourly limits and we're
					// using an overflow promo_id, so we need to do our overflow
					if($_SESSION['enable_overflow'] && $_SESSION['process_overflow'] && !$overflow_prepared)
					{
						
						//Restrict to Tier 2
						$this->Restrict(array($name => $name));

						//Set up some stuff in tier 2 for the overflow process
						$tier->Prepare_Overflow();
						
						$overflow_prepared = TRUE;
					}
					// if we already picked a winner in this tier,
					// we don't need to run the rules again
					if ((!$return_winner) || ($return_tier != $name))
					{
						// run rules, cashline checks, etc.
						$valid = $tier->Validate($this->config->data, $this->config, $bypass_used_info);
					}
					else
					{
						$valid = $tier->Valid();
					}

					if ($valid)
					{
						$winner = &$tier->Pick($bypass_used_info);
					}

					$datax_decision = $tier->Get_DataX_Decision();
					if(!empty($datax_decision))
					{
						$this->datax_decision = $datax_decision;
					}


					// we broke something
					if (!$winner)
					{

						// log this
						$this->config->Log_Event(EVENT_TIER, EVENT_FAIL);
						$valid = FALSE;

						//If we were doing an enterprise react, but it failed somehow, we need to set everything back to normal.
						if($this->config->is_enterprise && $this->config->bb_mode == MODE_ECASH_REACT && !empty($this->config->old_bb_mode))
						{
							$this->config->bb_mode = $this->config->old_bb_mode;
							unset($this->config->old_bb_mode);
							
							$opts = array(
								DEBUG_RUN_USEDINFO,
								DEBUG_RUN_DATAX_IDV,
								DEBUG_RUN_DATAX_PERF,
								DEBUG_RUN_STATS
							);
		
							foreach($opts as $opt)
							{
								$this->config->debug->Debug_Option($opt, TRUE);
							}
							
							$this->config->debug->Save_Snapshot('debug_opt', $this->config->debug->Get_Options());
						}
						
						//If we're on a rework site and we've failed IDV, don't continue on in BB, go to rework
						if($this->config->bb_mode == MODE_DEFAULT
							&& ($this->config->config->online_confirmation 
								&& $this->config->config->enable_rework 
								&& !$_SESSION['IDV_REWORK'])
							&& ($this->datax_decision['DATAX_IDV'] == "N"
								|| ($this->datax_decision[EVENT_DATAX_IC_IDVE] == 'N'
									&& $this->datax_decision['FAIL_TYPE'] == 'IDV')))
						{
							$do_rework = TRUE;
							//$this->Use_Tier(NULL, FALSE);

							//If we're doing an overflow rework for Impact, we don't want to exclude all of tier 2!
							$_SESSION['idv_failed_tier'] = (isset($_SESSION['process_overflow'])) ? 1 : $tier->Name();
						}
						else
						{
							// be nice to our references
							$this->Use_Tier($tier->Name(), FALSE);
						}

						//If we're doing overflow and tier 1 failed, but DIDN'T fail because of overflow
						//and we're using a bb_reject_level.... oh god, kill me now.
						if($_SESSION['enable_overflow'] && !$_SESSION['process_overflow']
							&& isset($this->config->config->bb_reject_level) && $this->config->config->bb_reject_level == 2)
						{
							$this->Use_Tier(2, FALSE);
						}

						unset($tier);

					}
					else
					{

						// save the target object
						$return_winner = $winner;
						$return_tier = $tier->Name();
						break;

					}

				} // if ($tier)

			} // foreach


			//If we didn't find a winner and a CLK company was involved, hit the Adverse Action stat [CB]
			//Also make sure it doesn't trigger if we're doing a rework. [CB]
			if(!$valid && isset($_SESSION['adverse_action']) && $this->config->bb_mode == MODE_DEFAULT && !$do_rework)
			{
				
				try
				{
					include_once(OLP_DIR . 'adverse_action.php');

					//Slightly modified AA functionality for Impact
					if($_SESSION['adverse_action'] == 'aa_denial_datax_impact' || $_SESSION['adverse_action'] == 'aa_denial_impact')
					{
						$aa = new Adverse_Action_Impact($this->config, $_SESSION['adverse_action']);
					}
					else
					{
						$aa = new Adverse_Action($this->config, $_SESSION['adverse_action']);
					}
					
					$aa->Update_Denial_Winner();
				}
				catch (Exception $e)
				{
					//Hey guys, what's going on in this comment?
					$this->config->Applog_Write('[Adverse Action] Something went wrong and it wasn\'t caught: ' . $e->getMessage());
				}
			}

			return $valid;
		}




		public function Get_DataX_Decision()
		{
			return $this->datax_decision;
		}




		public function Restrict_Tiers($names, $exclude = FALSE)
		{
			if($names && !is_array($names))
			{
				$names = array('FIND' => $names);
			}

			if(!empty($names))
			{
				if(isset($names['FIND']))
				{
					$this->Find_Targets($names['FIND'], $names);
					unset($names['FIND']);
				}

				if ($exclude)
				{
					// EXCLUDE
					$this->Exclude($names);
				}
				else
				{
					// RESTRICT
					$this->Restrict($names);
				}
			}
			else
			{
				// restrict us to nothing
				$this->Use_Object(NULL, FALSE);
			}


			// let us know what's going on
			if($exclude)
			{
				$this->config->Save_Snapshot('exclude', $names);
			}
			else
			{
				$this->config->Save_Snapshot('restrict', $names);
			}
		}



		protected function Exclude($names)
		{
			foreach($names as $name => $value)
			{
				if(isset($this->tiers[$name]))
				{
					if(is_array($value))
					{
						if($tier = &$this->Tier($name))
						{
							$tier->Restrict($value, TRUE);
							unset($tier);
						}
					}
					else
					{
						$this->Use_Tier($name, FALSE);
						$this->Remove($name, true);
					}
				}
			}
		}


		protected function Restrict($names)
		{
			$tiers = array_keys($this->tiers);

			foreach($tiers as $name)
			{
				if(isset($names[$name]))
				{
					// handle sub arrays
					if(is_array($value = $names[$name]))
					{
						if($tier = &$this->Tier($name))
						{
							$tier->Restrict($value, FALSE);
							unset($tier);
						}
					}
				}
				elseif(!isset($names['*']))
				{
					$this->Use_Tier($name, FALSE);
					$this->Remove($name, true);
				}
			}
		}
		
		
		
		
		public function Restore($data, &$config)
		{
			return parent::Restore($data, $config, 'BlackBox_Tier', get_class());
		}
		
		
		
		public function Reset()
		{
			parent::Reset();

			unset($_SESSION['adverse_action']);
		}


	}

?>
