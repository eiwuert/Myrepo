<?php
/**
 * OLPBlackbox_PreferredCollection is a special collection for CashNet and CLK to do some
 * fancy weight distributing and make money.
 *
 * @author Chris Barmonde <chris.barmonde@sellingsource.com>
 */
class OLPBlackbox_PreferredCollection extends OLPBlackbox_TargetCollection
{
	/**
	 * Whether or not this lead is considered a 'preferred' lead.
	 * This is determined by checking to see if there are any non-CLK
	 * targets left after the business rules have been run.
	 *
	 * @var bool
	 */
	protected $preferred_lead = FALSE;
	
	/**
	 * Picks a target from the available, valid targets.
	 *
	 * By default, it will pick the first valid target in the list. This needs to be
	 * overwritten.
	 *
	 * @param Blackbox_Data $data data to use for any validation
	 * @return Blackbox_IWinner|bool
	 */
	public function pickTarget(Blackbox_Data $data)
	{
		foreach ($this->target_list as $target)
		{
			// If we have a non-CLK target available, we'll consider this a 'preferred lead'
			if (EnterpriseData::getCompany($target->getStateData()->campaign_name) !== CompanyData::COMPANY_CLK
				&& $target->isValid($data, $target->getStateData()))
			{
				$this->preferred_lead = TRUE;
				break;
			}
		}

		$this->updateCurrentLeads();
		
		// Reset the picked targets for this run
		$this->picker->resetPickedTargets();
		$winner = parent::pickTarget($data);

		$config = OLPBlackbox_Config::getInstance();
		if ($config->blackbox_mode == OLPBlackbox_Config::MODE_BROKER)
		{
			// Note that this doesn't grab child targets from their parents
			$targets = $this->picker->getPickedTargets();
			$total_targets = count(SpecialTier::getInstance($config->preferred_tier)->getTargets());

			foreach ($targets as $target)
			{
				if ($target->getStateData()->frequency_score == 1 && !$target->getStateData()->is_react)
				{
					if ($this->preferred_lead)
					{
						$company = strtolower(CompanyData::getCompany(EnterpriseData::resolveAlias($target->getStateData()->campaign_name)));
						
						$target->getStateData()->preferred_lead = $target->getStateData()->frequency_score;
					}
				}
				elseif ($this->preferred_lead && $target->getStateData()->frequency_score > 1)
				{
					$target->getStateData()->preferred_lead = $target->getStateData()->frequency_score;
				}
				
				if (!empty($target->getStateData()->preferred_lead))
				{
					$company = CompanyData::getCompany(EnterpriseData::resolveAlias($target->getStateData()->campaign_name));
					
					if (!empty($company) && $target->getStateData()->frequency_score >= 1 && $target->getStateData()->frequency_score <= 3)
					{
						$stat_name = "bb_".strtolower($company)."_look{$target->getStateData()->frequency_score}";
		
						// Hit the stat AND event log so QA doesn't whine that it's not there.
						$config->hitSiteStat($stat_name, $target->getStateData());
						
						if ($config->log_stats)
						{
							$config->event_log->Log_Event(
								'STAT_' . $stat_name,
								OLPBlackbox_Config::STAT_RESULT_PASS,
								$target->getStateData()->campaign_name,
								$data->application_id,
								OLPBlackbox_Config::MODE_BROKER
							);
						}
					}
				}
			}
		}

		return $winner;
	}
	
	/**
	 * Updates the current lead count for the campaigns in this collection.
	 * This is necessary because parent targets never have lead counts
	 * created since they don't run the Limit rule.
	 * 
	 * These lead stats are also based on the company the target is owned by
	 * and not the target itself.
	 * 
	 * @return NULL
	 */
	protected function updateCurrentLeads()
	{
		$stats_limits = new Stats_Limits($this->getConfig()->olp_db->getConnection()->getConnection());
		foreach ($this->target_list as $target)
		{
			$company = CompanyData::getCompany(EnterpriseData::resolveAlias($target->getStateData()->campaign_name));
			
			$target->getStateData()->current_leads =
				$stats_limits->count(
					"bb_".strtolower($company)."_preferred_look",
					NULL,
					NULL,
					NULL,
					Blackbox_Utils::getToday()
				);
		}
	}
	
	/**
	 * Allows you to get a nice pretty print out of the entire blackbox
	 * tree instead of having to do a print_r, or similar, and get the entire
	 * structure dumped to the screen.
	 * 
	 * @return string
	 */
	public function __toString()
	{
		$string = "PreferredCollection: " . $this->getStateData()->target_collection_name . "\n";
		
		if ($this->rules)
		{
			$string .= preg_replace('/^/m', '   ', strval($this->rules));
		}
		foreach ($this->target_list as $target)
		{
			$string .= preg_replace('/^/m', '   ', strval($target));
		}
		
		return $string;
	}
}
?>
