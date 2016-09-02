<?php

	/**

		@name BlackBox_Tier_1
		@version 0.1
		@author Chris Barmonde

		@desc
			Tier 1 (CLK) class.

	*/


	class BlackBox_Tier_1 extends BlackBox_Tier
	{

		/*public function Pick($bypass_used_info = false)
		{
			$picker = new BlackBox_Picker_Tier1($this->config, $this->targets, $this->datax);
			return $picker->Pick($bypass_used_info);
		}*/

		public function Validate($data, &$config = NULL, $bypass_used_info = FALSE)
		{

			// holds targets that we've had
			// previous loans with
			$this->config->react = array();
			
			$validation = new BlackBox_Validation($this->config);
			

			//Run SOAP No-Ref Limit check
			//$validation->Check_SOAP_No_Ref_Limit($this->config->data);

			
			// ENTERPRISE SITES RUN CASHLINE FIRST! [AM]
			if($validation->Valid() && $this->config->is_enterprise)
			{
				
				// We will be tailoring rule-sets to react customers on
				// enterprise sites, so we need to know this first. [AM]
				$this->Run_Cashline($validation);
				
				// reactivations on the enterprise site use the same rule-set as an eCash react
				if($this->config->bb_mode == MODE_DEFAULT && $validation->Valid() && !empty($this->config->react))
				{
					$this->config->old_bb_mode = $this->config->bb_mode;
					$this->config->bb_mode = MODE_ECASH_REACT;
					
					$opts = array(
						DEBUG_RUN_USEDINFO,
						DEBUG_RUN_DATAX_IDV,
						DEBUG_RUN_DATAX_PERF,
						DEBUG_RUN_STATS
					);

					foreach($opts as $opt)
					{
						$this->config->debug->Debug_Option($opt, FALSE);
					}
					
					$this->config->debug->Save_Snapshot('debug_opt', $this->config->debug->Get_Options());
				}
				
			}
			
			if($this->Valid())
			{
				$this->Validate_Rules($data);
				$this->Validate_CFE($data);
				$this->Validate_Stats();
				
				$validation->Valid($this->Valid());
			}
			
			// ENTERPRISE SITES RUN CASHLINE FIRST! [AM]
			if($this->Valid() && !$this->config->is_enterprise)
			{
				/*
					Moved this back after the stats. We weren't denying reacts on bad
					Cashline checks, now we are... again. The call above would return
					TRUE if we skipped the stat_check... so anything returned by
					Validate_Cashline would be overwritten.
				*/
				$this->Run_Cashline($validation);
			}


			// special case for Tier 1 only
			//$validation->Validate_Used_Info($this->Get_CLK_Targets(), $bypass_used_info);

			// return the outcome
			return $this->Valid();
		}


		public function Get_CLK()
		{
			return $this->targets->Target('CLK');
		}
		
		
		protected function Get_DataX_Account(&$target = NULL)
		{
			return (!is_null($target)) ? $target->Get_DataX_Account() : 'BB';
		}

		
		public function Run_Cashline(&$validation = NULL)
		{
			$valid = (is_null($validation)) ? false : $validation->Valid();
			
			$clk = $this->Get_CLK();
			if($clk !== false)
			{
				$valid = $clk->Run_Cashline($validation, $this);
			}
			
			return $valid;
		}
		
		
		public function Reset()
		{
			foreach($this->targets as $target)
			{
				$target->Reset();
			}
		}

	}

?>
