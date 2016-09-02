<?php

/**
 * Extends the Blackbox_Rule class to include funcationality for VendorAPI.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
require_once('DBWriter.php');
abstract class VendorAPI_Blackbox_Rule extends Blackbox_StandardRule
{
	/**
	 * Whether the rule should be permitted to skip
	 * @var bool
	 */
	protected $skippable;

	/**
	 * @var VendorAPI_Blackbox_EventLog
	 */
	protected $event_log;

	/**
	 * @var string
	 */
	protected $event_name = null;
    
    protected $DBwriter;

	/**
	 * @param VendorAPI_Blackbox_EventLog $event_log
	 */
 	public function __construct(VendorAPI_Blackbox_EventLog $event_log)
	{
		$this->event_log = $event_log;
        $this->DBwriter = new VendorAPI_Application_log_DBWriter();
	}

	/**
	 * Sets the bool that determines whether onSkip returns true or false.
	 *
	 * @param bool $skippable TRUE if skipping the rule is success, FALSE if it should fail on skip
	 * @return void
	 */
	public function setSkippable($skippable = TRUE)
	{
		$this->skippable = $skippable;
	}

	/**
	 * Called when the rule is skipped (canRun returns FALSE)
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return bool
	 */
	protected function onSkip(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if ($event = $this->getEventName())
		{
			$response = 'SKIP_'.($this->skippable ? 'PASS' : 'FAIL');
			$this->logEvent($event, $response, VendorAPI_Blackbox_EventLog::DEBUG);
		}
		// Run onInvalid if the rule is not skippable to log the fail properly
		if (!$this->skippable) $this->onInvalid($data, $state_data);
		return $this->skippable;
	}

	/**
	 * Runs when the rule returns valid.
	 *
	 * @param Blackbox_Data $data the data used to validate the rule
	 * @param Blackbox_IStateData $state_data the target state data
	 * @return void
	 */
	protected function onValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if ($event = $this->getEventName())
		{
			$this->logEvent($event, 'PASS', VendorAPI_Blackbox_EventLog::PASS);
		}
	}

	/**
	 * Runs when the rule returns invalid.
	 *
	 * @param Blackbox_Data $data the data used to validate the rule
	 * @param Blackbox_IStateData $state_data the target state data
	 * @return void
	 */
	protected function onInvalid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		parent::onInvalid($data, $state_data);
        
		if (($event = $this->getEventName()) !== NULL)
		{
			$this->logEvent($event, 'FAIL', VendorAPI_Blackbox_EventLog::FAIL);
		} else {
            $event = 'NULL';
        }

		// if we have a failure reason, add it
		if (($reason = $this->getFailureReason()) !== NULL)
		{
			$this->addFailureReason($state_data, $reason);
			$reason->short = ' - '.$reason->short;
		} else {
            $reason->short = '';
        }
		
        $this->DBwriter->writeApplicationResult($data, $event.$reason->short);
		/**
		 * By default, on any sort of failure, we'll set the fail type
		 * to just fail for the campaign.  This means OLP may retry the
		 * lead again at some point, which is the default behavior.
		 */
		if(isset($state_data->fail_type) && is_a($state_data->fail_type, 'VendorAPI_BlackBox_FailType'))
		{
			$state_data->fail_type->setFail(VendorAPI_BlackBox_FailType::FAIL_CAMPAIGN);
		}
	}

	/**
	 * Triggered when an error is encountered during rule processing.
	 * @param Blackbox_Data $data User data
	 * @param Blackbox_IStateData $state Target state
	 * @return boolean
	 */
	protected function onError(Blackbox_Exception $e,Blackbox_Data $data, Blackbox_IStateData $state)
	{
		if ($event = $this->getEventName())
		{
			$this->logEvent($event, 'ERROR', VendorAPI_Blackbox_EventLog::FAIL);
		}
	}

	/**
	 * Hit an event in the event log
	 * @return void
	 */
	protected function logEvent($event, $result, $level = VendorAPI_Blackbox_EventLog::DEBUG)
	{
		$this->event_log->logEvent(
			$event,
			$result,
			$level
		);
	}

	/**
	 * Determines whether this rule can run.
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return boolean
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return TRUE;
	}

	/**
	 * Return an event name to use when writing to the event log
	 * @return string
	 */
	protected function getEventName()
	{
		return is_null($this->event_name) ? get_class($this) : $this->event_name;
	}

	public function setEventName($name)
	{
		$this->event_name = (is_string($name) ? $name : null);
	}

	/**
	 * @todo Make this the method underlying classes implement, instead of failureComment()/Short()
	 * @return null|VendorAPI_Blackbox_FailureReason
	 */
	protected function getFailureReason()
	{
		$fail_short = $this->failureShort();
		$fail_comnt = $this->failureComment();

		// If we ahve a comment or a short
		// add it as a reason
		if (!empty($fail_short) || !empty($fail_comnt))
		{
			return new VendorAPI_Blackbox_FailureReason($fail_short, $fail_comnt);
		}
	}

	/**
	 * Return a comment?
	 * @return string
	 */
	protected function failureComment()
	{
	}

	/**
	 * Return a failure short?
	 * @return string
	 */
	protected function failureShort()
	{
		return $this->getEventName();
	}

	/**
	 * Set the failure reason in the state data
	 *
	 * @param Blackbox_IStateData $state_data
	 * @param VendorAPI_Blackbox_FailureReason $reason
	 * @return void
	 */
	protected function addFailureReason(Blackbox_IStateData $state_data, VendorAPI_Blackbox_FailureReason $reason)
	{
		$state_data->failure_reason = $reason;
	}

	/** Allows you to get a nice pretty print out of the entire blackbox
	 * tree instead of having to do a print_r, or similar, and get the entire
	 * structure dumped to the screen.
	 *
	 * @return string
	 */
	public function __toString()
	{
		if (is_array($this->params[self::PARAM_VALUE]))
		{
			$param_value = ' ('.join(",", $this->params[self::PARAM_VALUE]).')';
		}
		elseif ($this->params[self::PARAM_VALUE])
		{
			$param_value = ' ('.$this->params[self::PARAM_VALUE].')';
		}
		else
		{
			$param_value = '';
		}

		$string = "Rule: " . $this->event_name . "      [" . $this->name . $param_value . "]\n";
		return $string;
	}
}
?>
