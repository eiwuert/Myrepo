<?php
/**
 * Defines the OLPBlackbox_Rule_UFC_WinnerVerifiedStatus class.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */

/**
 * Class for UFC for post-winner verification rules. (Flags for UFC to inspect manually)
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Enterprise_CLK_UFC_Rule_WinnerVerifiedStatus extends OLPBlackbox_Enterprise_Generic_Rule_WinnerVerifiedStatus
{
	/**
	 * Determines if the Rule object can run.
	 *
	 * @param Blackbox_Data $data application state data.
	 * @param Blackbox_IStateData $state_data state data of the ITarget calling this rule.
	 *
	 * @return bool whether the Rule is runnable or not.
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return $this->canRunSameWorkAndHomePhoneCheck($data, $state_data)		// overriden version
			&& $this->canRunBenefitsIncomeCheck($data, $state_data)
			&& $this->canRunDataXReferralsCheck($data, $state_data)
			&& $this->canRunFraudCheck($data, $state_data);
	}

	/**
	 * Determines whether the rule can run it's benefits income check.
	 *
	 * @param Blackbox_Data $data application state data.
	 * @param Blackbox_IStateData $state_data state data of the ITarget calling this rule.
	 *
	 * @return bool whether the benefits method is runnable or not.
	 */
	protected function canRunBenefitsIncomeCheck(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return !empty($data->income_type);
	}

	/**
	 * Determines whether the rule can run it's DataX referrals check.
	 *
	 * @param Blackbox_Data $data application state data.
	 * @param Blackbox_IStateData $state_data state data of the ITarget calling this rule.
	 *
	 * @return bool whether the DataX referrals method is runnable or not.
	 */
	protected function canRunDataXReferralsCheck($data, $state_data)
	{
		$config = $this->getConfig();

		return $config->blackbox_mode == OLPBlackbox_Config::MODE_BROKER;
	}

	/**
	 * Run the Verification rules for UFC.
	 *
	 * The verification process for UFC is a bit strange. If verify fails for UFC,
	 * then all of CLK is failed. However, since the picker for CLK will repick,
	 * we throw an OLPBlackbox_FailException instead of just returning false from runRule().
	 *
	 * @param Blackbox_Data $data application state data.
	 * @param Blackbox_IStateData $state_data state data of the ITarget calling this rule.
	 * 
	 * @throws OLPBlackbox_FailException
	 *
	 * @return bool whether the Rule object can be run.
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if (!$this->sameWorkAndHomePhoneCheck($data, $state_data)) 
		{
			throw new OLPBlackbox_FailException(
				sprintf('work/home phone check failed for %s', __CLASS__)
			);
		}

		$benefits = $this->benefitsIncomeCheck($data, $state_data);

		if (!$benefits)
		{
			if (!$this->dataXReferralsCheck($data, $state_data))
			{
				throw new OLPBlackbox_FailException(
					sprintf('datax referrals check failed for %s', __CLASS__)
				);
			}
		}

		$this->fraudCheck($data, $state_data);

		return TRUE;
	}

	// overriden version of the base class' home/work phone check
	/**
	 * Check to see if the applicant has entered the same phone for home and 
	 * work, which indicates verification needed.
	 *
	 * Note: This function is overridden primarily because the other 
	 * WinnerVerifiedStatus calls won't fail the target. This function CAN 
	 * fail the target.
	 *
	 * @param Blackbox_Data $data application state data.
	 * @param Blackbox_IStateData $state_data state data of the ITarget calling this rule.
	 *
	 * @return bool whether or not the rule passes.
	 */
	protected function sameWorkAndHomePhoneCheck(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$event_name = OLPBlackbox_Config::EVENT_VERIFY_SAME_WH_PHONE;

		if ($this->debugSkip())
		{
			$this->logEvent(
				$event_name, 
				OLPBlackbox_Config::EVENT_RESULT_DEBUG_SKIP, 
				$state_data->name
			);
			return TRUE;
		}

		if (strcasecmp($data->phone_home, $data->phone_work) === 0
			&& strcasecmp($data->income_type, 'EMPLOYMENT') === 0)
		{
			$this->logEvent($event_name, 'FAIL', $state_data->name);
			return FALSE;	// UFC doesn't want this applicant
		}
		else
		{
			$this->logEvent($event_name, 'PASS', $state_data->name);
			return TRUE;
		}
	}

	/**
	 * Check if the applicant has listed his income as "benefits"
	 *
	 * @param Blackbox_Data $data application state data.
	 * @param Blackbox_IStateData $state_data state data of the ITarget calling this rule.
	 *
	 * @return bool whether or not the rule passes.
	 */
	protected function benefitsIncomeCheck(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		// event log name
		$event_name = OLPBlackbox_Config::EVENT_VERIFY_BENEFITS_CHECK;

		if ($this->debugSkip())
		{
			$this->logEvent(
				$event_name, 
				OLPBlackbox_Config::EVENT_RESULT_DEBUG_SKIP, 
				$state_data->name
			);
			return TRUE;
		}

		if (strcasecmp($data->income_type, 'BENEFITS') == 0)
		{
			$this->logEvent($event_name, 'PASS', $state_data->name);
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Do some kind of DataX validation that could potentially fail the Target.
	 *
	 * @param Blackbox_Data $data application state data.
	 * @param Blackbox_IStateData $state_data state data of the ITarget calling this rule.
	 *
	 * @return bool whether or not the rule passes.
	 */
	protected function dataXReferralsCheck(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$event_name = OLPBlackbox_Config::EVENT_VERIFY_DATAX_REFERRAL;

		if ($this->debugSkip())
		{
			$this->logEvent(
				$event_name, 
				OLPBlackbox_Config::EVENT_RESULT_DEBUG_SKIP, 
				$state_data->name
			);
			return TRUE;
		}

		$response = $this->getDataXResponse($data, $state_data);

		if ($response)
		{
			$response = $response['received_package'];
			$response = @simplexml_load_string($response);

			$buckets = $response->Response->Summary->DecisionBuckets->Bucket;

			foreach ($buckets as $bucket)
			{
				if (strstr($bucket, 'R'))
				{
					$this->logEvent($event_name, 'FAIL', $state_data->name);
					return FALSE;
				}
			}

			$this->logEvent($event_name, 'PASS', $state_data->name);
			return TRUE;
		}

		return TRUE;
	}
}
?>
