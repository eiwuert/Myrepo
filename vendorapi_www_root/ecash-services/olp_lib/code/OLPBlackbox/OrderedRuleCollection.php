<?php
/**
 * The OrderedRuleCollection is a rule collection that returns isValid=TRUE
 * after the first successful rule.  This allows you to bypass additional
 * rules if one rule passes.  If no rules pass, isValid=FALSE.
 *
 * OrderedRuleCollection
 *   Rule 1
 *   Rule 2
 *   Rule 3
 *
 * In the above example:
 *   If "Rule 1" returns TRUE, "Rule 2" and "Rule 3" are skipped.
 *   If "Rule 1" returns FALSE, "Rule 2" returns TRUE, "Rule 3" is skipped.
 *
 * OrderedRuleCollection
 *   Rule 1
 *   RuleCollection
 *     Rule 2
 *     Rule 3
 *
 * In the above example:
 *   If "Rule 1" returns TRUE, "Rule Collection" is skipped.
 *   If "Rule 1" returns FALSE, "Rule Collection" is ran:
 *     If "Rule 2" returns TRUE, "Rule 3" will still be ran.
 *
 * Notice, if you always want "Rule 3" to run even if "Rule 2" passes,
 * "Rule 2" and "Rule 3" must be wrapped in a normal rule collection.
 * 
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class OLPBlackbox_OrderedRuleCollection extends Blackbox_RuleCollection
{
	/**
	 * Determines if the rule is valid, if so it returns true and skips all
	 * aditional rules.
	 *
	 * @param Blackbox_Data       $data       data to run validation checks on
	 * @param Blackbox_IStateData $state_data the target state data
	 * @return bool
	 */
	public function isValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$valid = TRUE;
		
		for ($i = 0; $i < count($this->rules); $i++)
		{
			$rule = $this->rules[$i];
			
			if ($rule->isValid($data, $state_data))
			{
				$valid = TRUE;
				break;
			}
			else
			{
				$valid = FALSE;
			}
		}

		return $valid;
	}
}
?>
