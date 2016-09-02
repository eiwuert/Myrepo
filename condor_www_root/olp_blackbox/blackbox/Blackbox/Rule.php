<?php
/**
 * A sample Blackbox_Rule class file.
 *
 * @author Chris Barmonde <christopher.barmonde@sellingsource.com>
 */

/**
 * A Blackbox Rule that implements some standard functionality.
 *
 * @author Chris Barmonde <christopher.barmonde@sellingsource.com>
 */
abstract class Blackbox_Rule implements Blackbox_IRule
{
	/**
	 * Determine whether enough data is present to run the rule
	 *
	 * If this method returns FALSE, onSkip will be called.
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return bool
	 */
	abstract protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data);

	/**
	 * Run the actual validation for the rule
	 *
	 * If this method returns TRUE, onValid will be called and isValid will also
	 * return TRUE. If this method returns FALSE, onInvalid will be called and
	 * isValid will also return FALSE.
	 *
	 * @param Blackbox_Data $data The data used to validate the rule.
	 * @param Blackbox_IStateData $state_data the target state data
	 * @return bool
	 */
	abstract protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data);

	/**
	 * Event that's run when the rule passes (runRule returns TRUE)
	 *
	 * @param Blackbox_Data $data The data used to validate the rule
	 * @param Blackbox_IStateData $state_data the target state data
	 * @return void
	 */
	protected function onValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
	}

	/**
	 * Event that's run when the rule fails (runRule returns FALSE)
	 *
	 * @param Blackbox_Data $data The data used to validate the rule
	 * @param Blackbox_IStateData $state_data the target state data
	 * @return void
	 */
	protected function onInvalid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
	}


	/**
	 * Event that's run when the rue is skipped (canRun returns false)
	 *
	 * If onSkip returns TRUE, the rule will act as if its valid, although
	 * onValid will not be called.
	 *
	 * @param Blackbox_Data $data Data related to the app we're processing.
	 * @param Blackbox_IStateData $state_data Data related to the ITarget running this rule
	 *
	 * @return bool whether to fail (FALSE) or succeed (TRUE) when being skipped.
	 */
	protected function onSkip(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		// by default, rules fail if they are skipped.
		return FALSE;
	}

	/**
	 * Called when the rule produces an error that prevents it from running
	 *
	 * If onError returns TRUE, the rule will act as if its valid, although
	 * onValid will not be called.
	 *
	 * @param Blackbox_Exception $e exception that caused this onError to run.
	 * @param Blackbox_Data $data the data that was passed to the rule
	 * @param Blackbox_IStateData $state_data the state data that was passed to the rule
	 * @return bool
	 */
	protected function onError(Blackbox_Exception $e, Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		// by default, errors will fail the rule
		return FALSE;
	}

	/**
	 * This validates the rule based on the passed-in data.
	 * This function will also call onValid() and onInvalid()
	 * after the rule check is run.
	 *
	 * @param Blackbox_Data $data The data used to validate the rule
	 * @param Blackbox_IStateData $state_data the target state data
	 * @return bool TRUE if the rule passes validation.  If the
	 * 		rule is skipped, NULL will be returned.
	 */
	public function isValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$valid = NULL;

		//If we don't have any data to check against,
		//we will not run the rule.
		if ($this->canRun($data, $state_data))
		{
			try
			{
				$valid = $this->runRule($data, $state_data);

				if ($valid)
				{
					$this->onValid($data, $state_data);
				}
				else
				{
					$this->onInvalid($data, $state_data);
				}
			}
			catch (Blackbox_Exception $e)
			{
				// if onError returns TRUE, act as if we're valid
				// (but without calling onValid)
				$valid = ($this->onError($e, $data, $state_data) === TRUE);
			}
		}
		else
		{
			$valid = ($this->onSkip($data, $state_data) === TRUE);
		}

		return $valid;
	}
}

?>
