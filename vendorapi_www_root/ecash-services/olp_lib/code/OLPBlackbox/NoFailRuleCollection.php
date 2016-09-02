<?php
/**
 * The NoFailRuleCollection is a rule collection that will not return FALSE in isValid.
 * 
 * This is useful for things like CATCH suppression lists so that they can return isValid = FALSE
 * and stop running additional lists, but not actually fail the application.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_NoFailRuleCollection extends Blackbox_RuleCollection
{
	/**
	 * Runs all the rules in the collection, never failing, but stopping when we get a FALSE back
	 * from one of the rules.
	 *
	 * @param Blackbox_Data       $data       data to run validation checks on
	 * @param Blackbox_IStateData $state_data the target state data
	 * @return bool
	 */
	public function isValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$continue = TRUE;
		
		for ($i = 0; $i < count($this->rules) && $continue; $i++)
		{
			$rule = $this->rules[$i];
			
			if (!$rule->isValid($data, $state_data))
			{
				$continue = FALSE;
			}
		}

		return TRUE;
	}
}
?>
