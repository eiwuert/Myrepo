<?php


	/**
	 * Parent class for containing subtargets.
	 */
	abstract class BlackBox_Parent extends BlackBox_Preferred
	{
		protected $winner = false;
		protected $failed_overflow = 0;
		
		public function Valid()
		{
			return $this->targets->Has_Open();
		}
		
		public function __destruct()
		{
			parent::__destruct();
		}
		
		public function Validate($data, &$config = null, $bypass_used_info = false)
		{
			$this->collection->Open($this->Name(), FALSE);

			return (count($this->targets->Get_Open()) > 0);
		}
		
		public function Pick($bypass_used_info = false)
		{
			$pick = new Blackbox_Picker($this->config, $this->targets, $this->datax, $this->weight_type);
			return $pick->Pick($bypass_used_info);		
		}
		
		public function Run_Rules(&$config, &$data)
		{
			$targets = $this->targets->Get_Open();

			foreach($targets as $name)
			{
				$target = &$this->targets->Target($name);

				if($target)
				{
					$valid = $target->Run_Rules($config, $data);

					if(!$valid)
					{
						$this->failed[$name] = $target->Failed();
					}

					// leave them open if they pass
					$this->targets->Open($name, $valid);
				}
			}

			// return array of open targets
			return $this->targets->Has_Open();
		}
		
		public function Run_Filters(&$config, $data)
		{
			$targets = $this->targets->Get_Open();

			foreach($targets as $name)
			{
				$target = &$this->targets->Target($name);

				if($target)
				{
					$valid = $target->Run_Filters($config, $data);

					if(!$valid)
					{
						$this->failed[$name] = $target->Failed();
					}

					// leave them open if they pass
					$this->targets->Open($name, $valid);
				}
			}

			// return array of open targets
			return $this->targets->Has_Open();
		}
		
		public function Check_Stats(&$config, $stat_names, $simulate = false)
		{
			$targets = $this->targets->Get_Open();

			foreach($targets as $name)
			{
				$target = &$this->targets->Target($name);

				if($target)
				{
					//are we "simulating" this run?
					$simulated = (($simulate === TRUE) || (is_array($simulate) && in_array($name, $simulate)));

					//check their stats
					$valid = $target->Check_Stats($config, $stat_names, $simulated);

					if(!$valid)
					{
						$this->failed[$name] = $target->Failed();
						
						//If we failed overflow leads, we'll increment the counter.
						//We'll use this later to determine if all the targets in a
						//tier failed this stat, in which case it's time to do some
						//overflow work.
						if($target->Get_Failed_Stat() == STAT_OVERFLOW_LEADS)
						{
							$this->failed_overflow++;
						}
					}

					if(!$simulated)
					{
						// modify $this->open
						$this->targets->Open($name, $valid);
					}
				}
			}

			// return array of open targets
			return $this->targets->Has_Open();
		}
		
		public function Get_Target_List($in_use = true, $flat = true, $use_objects = false)
		{
			if(!empty($this->targets))
			{
				return $this->targets->Get_Target_List($in_use, $flat, $use_objects);
			}
			else
			{
				return parent::Get_Target_List($in_use, $flat, $use_objects);
			}
		}
	}









	class BlackBox_Parent_CLK extends BlackBox_Parent
	{
		
		public function Validate($data, &$config = null, $bypass_used_info = false)
		{
			$validation = new BlackBox_Validation($this->config);
			
			// CLK requires that the Used_Info check is run for all companies (gforge 5345)
			$clk_shorts = Enterprise_Data::getCompanyProperties('CLK');
			foreach ($clk_shorts as $clk_short)
			{
				if (!$validation->Valid()) break;
				$validation->Validate_Used_Info($this->targets, $bypass_used_info, $clk_short);
			}
			
			if($validation->Valid())
			{
				$datax_valid = $validation->Validate_DataX($this->datax, EVENT_DATAX_IDV, $this->Get_DataX_Account(), BlackBox_DataX::SOURCE_CLK);
				
				if($datax_valid === FALSE)
				{
					
					// for tier 1 only, close all targets
					// if we fail either IDV or Performance
					$this->collection->Open($this->Name(), FALSE);
					
					$aa_denial = NULL;
					//If the call failed and it was IDV or PERF, we'll likely need
					//to hit the appropriate Adverse Action stat [CB]
					switch($this->datax->Get_DataX_Type(EVENT_DATAX_IDV, BlackBox_DataX::SOURCE_CLK))
					{
						case BlackBox_DataX::TYPE_IDV_REWORK:
						case BlackBox_DataX::TYPE_IDV_CLK:
							$aa_denial = 'aa_denial_datax';
							break;
						case BlackBox_DataX::TYPE_PERF:
							$aa_denial = 'aa_denial_teletrack';
							break;
					}

					if(!is_null($aa_denial) && !isset($_SESSION['adverse_action']))
					{
						$_SESSION['adverse_action'] = $aa_denial;
					}
				}
			}
			
			
			if(!$validation->Valid() && !isset($_SESSION['adverse_action']))
			{
				//If we were denied by CLK and we didn't have a DataX error,
				//then we should be hitting the generic Adverse Action stat [CB]
				$_SESSION['adverse_action'] = 'aa_denial_clk';
			}
			
			$this->collection->Open($this->Name(), FALSE);
			
			return $validation->Valid();
		}

		public function Get_DataX_Account(&$target = NULL)
		{
			return (!is_null($target)) ? $target->Get_DataX_Account() : 'BB';
		}

		public function Pick($bypass_used_info = false)
		{
			$pick = new Blackbox_Picker_CLK($this->config, $this->targets, $this->datax, $this->weight_type);
			return $pick->Pick($bypass_used_info);
		}

		public function Run_Cashline(&$validation = NULL, &$parent = NULL)
		{
			if(is_null($validation))
			{
				$validation = new BlackBox_Validation($this->config);
			}

			if(is_null($parent))
			{
				$parent = $this;
			}

			$validation->Validate_Cashline(NULL, $this->targets, $parent);
			return $validation->Valid();
		}

		
		public function Check_Stats(&$config, $stat_names = array(), $simulate = false)
		{
			$stat_names = array();
			
			if(($this->config->bb_mode != MODE_CONFIRMATION)
				&& isset($this->config->data['income_direct_deposit'])
				&& ($this->config->data['income_direct_deposit'] != 'TRUE'))
			{
				$stat_names[] = STAT_NO_DIRECT_DEPOSIT;
			}
			
			//$stat_names = array_diff($stat_names, array(STAT_DAILY_LEADS, STAT_HOURLY_LEADS, STAT_TOTAL_LEADS));
			
			if(isset($_SESSION['enable_overflow']))
			{
				$stat_names[] = STAT_OVERFLOW_LEADS;
			}
			
			$valid = parent::Check_Stats($config, $stat_names, $simulate);
			
			if(isset($_SESSION['enable_overflow']) && $this->config->bb_mode == MODE_DEFAULT)
			{
				//If all targets failed the overflow lead check, we need to run overflow, possibly.
				if($this->failed_overflow > 0 && count($this->targets->Get_Targets()) == $this->failed_overflow)
				{
					$_SESSION['process_overflow'] = true;
					Stats::Hit_Stats('overflow', $this->config->session, $this->config->log, $this->config->applog, $this->config->application_id);
				}
			}
			
			return $valid;
		}
	}




?>
