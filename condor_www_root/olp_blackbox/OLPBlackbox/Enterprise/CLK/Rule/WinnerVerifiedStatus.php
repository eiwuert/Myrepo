<?php
/**
 * Class definition of OLPBlackbox_Enterprise_CLK_Rule_WinnerVerifiedStatus class.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */

/**
 * Class for doing post-pickWinner() verification for CLK companies.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Enterprise_CLK_Rule_WinnerVerifiedStatus extends OLPBlackbox_Enterprise_Generic_Rule_WinnerVerifiedStatus
{
	/**
	 * Determines whether or not this rule can actually run.
	 *
	 * @param Blackbox_Data $data application state data.
	 * @param Blackbox_IStateData $state_data state data of the ITarget calling this rule.
	 *
	 * @return bool whether or not this rule object should run.
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return $this->canRunWorkPhoneTypeCheck($data, $state_data)
			&& $this->canRunZipCheck($data, $state_data)
			&& $this->canRunFraudCheck($data, $state_data);
	}

	/**
	 * Actually run the rules for this object.
	 *
	 * @param Blackbox_Data $data application state data.
	 * @param Blackbox_IStateData $state_data state data of the ITarget calling this rule.
	 *
	 * @return bool Whether or not this rule is valid.
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$this->workPhoneTypeCheck($data, $state_data);
		$this->zipCheck($data, $state_data);
		$this->fraudCheck($data, $state_data);
		return TRUE;
	}

	/**
	 * Determines if the correct variables exist to run verifyWorkPhoneType
	 *
	 * @param Blackbox_Data $data application state data.
	 * @param Blackbox_IStateData $state_data state data of the ITarget calling this rule.
	 *
	 * @return bool Whether or not we can run.
	 */
	protected function canRunWorkPhoneTypeCheck(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		/*
		 * DataX performance doe NOT get re-run in confirmation mode,
		 * so there's no point in repulling the records here.
		 */
		$config = $this->getConfig();
		return ($config->blackbox_mode !== OLPBlackbox_Config::MODE_CONFIRMATION
			&& $config->blackbox_mode !== OLPBlackbox_Config::MODE_ONLINE_CONFIRMATION);
	}

	/**
	 * Flags phone numbers that may need to be looked into by CLK.
	 *
	 * @param Blackbox_Data $data application state data.
	 * @param Blackbox_IStateData $state_data state data of the ITarget calling this rule.
	 *
	 * @return void
	 */
	protected function workPhoneTypeCheck(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$config = $this->getConfig();

		$response = $this->getDataXResponse($data, $state_data);

		if ($response)
		{
			$response = $response['received_package'];
			$response = simplexml_load_string($response);
		}

		//All are verified unless they appear in a bucket.
		$bucket_statuses = array('R1'=>'VERIFIED',
				'R2'=>'VERIFIED',
				'R3'=>'VERIFIED',
				'R4'=>'VERIFIED',
				'R5'=>'VERIFIED'
				);

		if ($response)
		{
			// NOTE: There may be some difference between simplexml in different versions,
			// because apparently this call below gets an array of buckets on live, but
			// not in testing.
			$buckets = $response->Response->Summary->DecisionBuckets->Bucket;
			foreach ($buckets as $bucket)
			{
				$bucket_statuses["$bucket"] = 'VERIFY';
			}
		}
		else
		{
			$bucket_statuses = array('ALL' => 'ERROR');
		}

		foreach ($bucket_statuses as $bucket => $bucket_status)
		{
			$event_names = array();
			switch ($bucket)
			{
				case 'R1':
					$event_names[] = 'VERIFY_SAME_WH';
					break;
				case 'R2':
					$event_names[] = 'VERIFY_W_TOLL_FREE';
					break;
				case 'R3':
					$event_names[] = 'VERIFY_WH_AREA';
					break;
				case 'R4':
					$event_names[] = 'VERIFY_W_PHONE';
					break;
				case 'R5':
					$event_names[] = 'VERIFY_SAME_CR_W_PHONE';
					break;
				case 'ALL':
					$event_names[] = 'VERIFY_SAME_WH';
					$event_names[] = 'VERIFY_W_TOLL_FREE';
					$event_names[] = 'VERIFY_WH_AREA';
					$event_names[] = 'VERIFY_W_PHONE';
					$event_names[] = 'VERIFY_SAME_CR_W_PHONE';
					break;
				default:
					break;
			}

			foreach ($event_names as $event_name)
			{
				if ($this->debugSkip())
				{
					$this->logEvent($event_name, OLPBlackbox_Config::EVENT_RESULT_DEBUG_SKIP, $state_data->name);
				}
				else
				{
					$this->logEvent($event_name, $bucket_status, $state_data->name);
				}
			}
		}
		unset($config);
	}

	/**
	 * Verify that we have enough information to run the verify zip method.
	 *
	 * @param Blackbox_Data $data application state data.
	 * @param Blackbox_IStateData $state_data state data of the ITarget calling this rule.
	 *
	 * @return bool Whether or not we can run.
	 */
	protected function canRunZipCheck(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return !empty($data->home_zip) && !empty($data->home_city) && !empty($data->home_state);
	}

	/**
	 * Runs a check to verify the zip code being used and sets a flag if the app needs to be looked into.
	 *
	 * @param Blackbox_Data $data application state data.
	 * @param Blackbox_IStateData $state_data state data of the ITarget calling this rule.
	 *
	 * @return void
	 */
	protected function zipCheck(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$event_name = 'zip_verify';

		if ($this->debugSkip())
		{
			$this->logEvent($event_name, OLPBlackbox_Config::EVENT_RESULT_DEBUG_SKIP, $state_data->name);
			return;
		}

		$count = $this->getVerifiedZipCount($data->home_zip, $data->home_city, $data->home_state);

		if ($count < 1)
		{
			$this->logEvent($event_name, 'VERIFY', $state_data->name);
		}
	}

	/**
	 * Helper method, designed to be mocked in the PHPUnit tests.
	 *
	 * @param int $home_zip zip code of applicant
	 * @param string $home_city home city of applicant
	 * @param string $home_state home state of applicant
	 *
	 * @throws Blackbox_Exception
	 *
	 * @return int number of matches for the paramters in the olp db
	 */
	protected function getVerifiedZipCount($home_zip, $home_city, $home_state)
	{
		$config = $this->getConfig();

		$query = sprintf("SELECT count(*) as c
					FROM
						zip_lookup
					WHERE
						zip_code = '%s'
						AND city = '%s'
						AND state = '%s'",
						mysql_real_escape_string($home_zip),
						mysql_real_escape_string($home_city),
						$home_state);

		$res = $config->olp_db->Query($config->olp_db->db_info['db'], $query);
		if ($row = $config->olp_db->Fetch_Object_Row($res))
		{
			return $row->c;
		}
		throw new Blackbox_Exception("unable to fetch zip information.");
	}
}
?>
