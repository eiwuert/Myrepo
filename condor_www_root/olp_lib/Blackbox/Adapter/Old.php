<?php

/**
 * An adapter that allows you to hook into the old Blackbox.
 *
 * @author Chris Barmonde <christopher.barmonde@sellingsource.com>
 */
class Blackbox_Adapter_Old extends Blackbox_Adapter
{
	/**
	 * Oldschool BFW Blackbox class
	 *
	 * @var BlackBox_OldSchool
	 */
	protected $blackbox;
	
	/**
	 * Configures a new BlackBox_OldSchool object.
	 *
	 * @return void
	 */
	protected function preConfigure()
	{
		$this->blackbox = new BlackBox_OldSchool(
			$this->config_data,
			TRUE,
			$this->config_data->preferred_targets,
			$this->mode
		);
	}
	
	/**
	 * Only needed in new Blackbox
	 *
	 * @return void
	 */
	public function postConfigure()
	{
		// Don't need to do anything here.
	}
	
	/**
	 * Gets or sets the current mode for Blackbox.
	 *
	 * @param string $mode Use the constants defined in blackbox.php
	 * @return string
	 */
	public function mode($mode = NULL)
	{
		return $this->blackbox->Mode($mode);
	}
	
	/**
	 * Sets up specific debug options inside of Blackbox.  These
	 * are flags passed in during the running of the application
	 * that can alter the behavior of Blackbox, such as bypassing
	 * rules checks.
	 *
	 * @param array $debug_opt An array of debug flags.
	 * @return void
	 */
	public function setDebugOptions($debug_opt)
	{
		$this->blackbox->Debug_Option($debug_opt);
	}
	
	/**
	 * Restrict or excludes targets and tiers.  To restrict or exclude
	 * specific targets, include a 'FIND' => array('prop1', 'prop2')
	 * list of targets.
	 *
	 * @param array $targets An array of targets/tiers to restrict.
	 * @param bool $restrict TRUE if you want to restrict to only the
	 * 	targets specified, FALSE if you want to exclude those targets.
	 * 
	 * @return void
	 */
	public function restrict($targets, $restrict = TRUE)
	{
		$this->blackbox->Restrict($targets, !$restrict);
	}
	
	/**
	 * Restricts and excludes tiers only.
	 *
	 * @param array $tiers An array of tiers to restrict.
	 * @param bool $restrict TRUE to restrict, FALSE to exclude.
	 * 
	 * @return void
	 */
	public function restrictTiers($tiers, $restrict = TRUE)
	{
		$this->blackbox->Use_Tier($tiers, $restrict);
	}
	
	/**
	 * Picks a winner from Blackbox.
	 *
	 * @param bool $reset Reset the Blackbox object.
	 * @param bool $bypass_used_info Bypass the used_info check.
	 * @return BlackBox_Target_OldSchool Will return FALSE if
	 * 	no valid winners are found.
	 */
	public function pickWinner($reset = FALSE, $bypass_used_info = FALSE)
	{
		$this->winner = $this->blackbox->Pick_Winner($reset, $bypass_used_info);
		
		if ($this->winnerExists())
		{
			$this->winners[] = $this->getPropertyShort();
		}
		
		return $this->winner;
	}
	
	/**
	 * Gets info about the winner from Blackbox.
	 *
	 * @return array
	 * 	array(
	 * 		tier			//Tier for this target (Will be 1 if an enterprise company)
	 * 		original_tier	//The original tier for this target
	 * 		winner			//Property short of the winner
	 * 		fund_amount		//Fund amount qualified for
	 * 		react			//TRUE if this app is a react.
	 * 	)
	 */
	public function winner()
	{
		return $this->blackbox->Winner();
	}
	
	/**
	 * Returns the current winner's property short.
	 * 
	 * @return string The property short of the winner
	 */
	public function getPropertyShort()
	{
		$property_short = NULL;
		
		if ($this->winnerExists())
		{
			$property_short = $this->winner->Name();
		}
		
		return $property_short;
	}
	
	/**
	 * Determines whether the current winner is valid.
	 * 
	 * @return bool TRUE if winner object is valid.
	 */
	protected function winnerExists()
	{
		return ($this->winner instanceof iBlackbox_Target);
	}
	
	/**
	 * Runs an individual rule for a specific target.
	 *
	 * @param string $property_short The target you want to run the rule against.
	 * @param string $rule The name of the rule (as defined in Rules_From_Row in blackbox.target.php)
	 * @param mixed $value The value the rule needs to check against.
	 * @return bool The result of the rule.
	 */
	public function runRule($property_short, $rule, $value = NULL)
	{
		$result = NULL;
		
		if (strcasecmp($rule, 'cashline') == 0)
		{
			$result = $this->blackbox->Run_Cashline($property_short);
		}
		elseif (strcasecmp($rule, 'dupe_cell') == 0)
		{
			$result = $this->blackbox->Dupe_Cell_Check();
		}
		else
		{
			$result = $this->blackbox->Run_Rule($property_short, $rule, $value);
		}
		
		return $result;
	}
	
	/**
	 * Returns an individual target.
	 *
	 * @param string $name The property short of the target.
	 * @return BlackBox_Target_OldSchool
	 */
	public function getTarget($name)
	{
		return $this->blackbox->Get_Target(strtoupper($name));
	}
	
	/**
	 * Gets all the tiers currently in use.
	 *
	 * @return array
	 */
	public function getTiers()
	{
		return $this->blackbox->Use_Tier();
	}
	
	/**
	 * Gets the last DataX decision from the last run.
	 *
	 * @return array
	 */
	public function getDataXDecision()
	{
		return $this->blackbox->Get_DataX_Decision();
	}
	
	
	/**
	 * Gets the DataX Track Hash from the last run.
	 *
	 * @return string
	 */
	public function getDataxTrackHash()
	{
		return $this->blackbox->DataX_Track_Hash();
	}
	
	/**
	 * Returns a list of property shorts who could
	 * potentially be sold the lead (mainly leads who
	 * have passed all their business rules).
	 *
	 * @param int $tier The tier you want to look for winners in.
	 * @return array A list of possible winners.
	 */
	public function getPossibleWinners($tier = NULL)
	{
		return $this->blackbox->Get_Possible_Winners($tier);
	}
	
	/**
	 * Withholds targets from being chosen.  Certain vendors
	 * are set up so that if they reject a lead, we will not
	 * attempt to sell to some other vendors.  This is that
	 * process.
	 * 
	 * @return void
	 */
	public function withholdTargets()
	{
		if ($this->winnerExists())
		{
			$this->blackbox->Withhold_Targets($this->winner->Withheld_Targets());
		}
	}

	/**
	 * Returns whether or not the current winner will allow
	 * its leads to be sold to list management
	 *
	 * @return bool
	 */
	public function sellToListManagement()
	{
		$sell = FALSE;
		
		if ($this->winnerExists())
		{
			$sell = $this->winner->Get_Nosell();
		}
		
		return $sell;
	}
	
	/**
	 * Returns the current Blackbox snapshot
	 *
	 * @return stdClass The snapshot data
	 */
	public function getSnapshot()
	{
		return $this->blackbox->Snapshot();
	}
	
	
	/**
	 * Gets the tier number for the current winner.
	 *
	 * @return string The tier for the current winner
	 */
	public function getWinnerTier()
	{
		return ($this->winnerExists()) ? $this->winner->Tier() : NULL;
	}
	
	
	/**
	 * Gets disallowed states for a property
	 *
	 * @param string $property Property short
	 * @return array Array of disallowed states
	 */
	public function getDisallowedStates($property)
	{
		return $this->blackbox->Get_Disallowed_States($property);
	}

	/**
	* Overridden to provide oldschool sleep functionality.
	*
	* @return array blackbox sleep data
	*/
	/*public function sleep()
	{
		return $this->blackbox->Sleep();
	}*/
}

?>
