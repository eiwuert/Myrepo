<?php
/**
 * Base class for inquiry service client calls
 * 
 * @author Matthew Jump <matthew.jump@sellingsource.com>
 * @package WebService
 */
abstract class WebServices_Client_InquiryClient extends WebServices_Client
{
	/**
	 * Query the inquiry service to look for bureau inquery failures
	 * of a certain type within a number of days
	 *
	 * @param string $ssn
	 * @param string $call_type
	 * @param int $days_to_check
	 * @return bool|NULL
	 */
	public function ssnHasFailure($ssn, $call_type, $days_to_check = NULL)
	{
		$retval = FALSE;
		if (!$this->getService()->isEnabled(__FUNCTION__)) return $retval;

		if (is_null($days_to_check))
		{
			$retval = $this->getService()->ssnHasFailure($ssn, $call_type);
		}
		else
		{
			$retval = $this->getService()->ssnHasFailureWithinDays($ssn, $call_type, $days_to_check);
		}

		return $retval;
	}

	/**
	 * Saves a bureau inquiry failure to the application service
	 * returns an id or null if the call failed
	 *
	 * @param string $ssn
	 * @param int $application_id
	 * @param string $source
	 * @param string $call_type
	 * @param string $reason
	 * @param int $status - The PASS|FAIL status as PASS=1 and FAIL=0
	 * @param array $contact_info
	 * @return int|NULL
	 */
	public function recordSkipTrace($ssn, $application_id, $source, $call_type, $reason, $status, $contact_info)
	{
		$retval = FALSE;
		if (!$this->getService()->isEnabled(__FUNCTION__))
		{
			return $retval;
		}

		$retval = $this->getService()->recordSkipTrace($ssn, $application_id, $source, $call_type, $reason, $status, $contact_info);

		return $retval;
	}

	/**
	 * Returns an array of contact information for a bureau inquiry failure
	 * 
	 * @param string $ssn
	 * @return array|FALSE
	 */
	public function getSkipTraceData($ssn)
	{
		$retval = FALSE;
		if (!$this->getService()->isEnabled(__FUNCTION__))
		{
			return FALSE;
		}

		$items = $this->getService()->getSkipTraceData($ssn);
		$retval = (is_array($items->item)) ? array_values($items->item) : array($items->item);
		if (empty($retval[0]))
		{
			$retval = array();
		}

		return $retval;
	}

	/**
	 * Record a bureau inquiry call
	 *
	 * @param array $datr
	 * @return bool
	 */
	public function recordInquiry($datr)
	{
		$retval = FALSE;
		if (!$this->getService()->isInsertEnabled(__FUNCTION__)) return $retval;

		$datr['received_package'] = $datr['receive_package'];
		$retval = $this->getService()->recordInquiry($datr);

		return $retval;
	}

	/**
	 * Finds bureau inquiry records by application id
	 *
	 * @param int $id
	 * @return array
	 */
	public function findInquiriesByApplicationID($id)
	{
		$retval = array();
		if (!$this->getService()->isEnabled(__FUNCTION__)) return $retval;

		$retval = $this->getService()->findInquiriesByApplicationID($id);

		return $retval;	
	}

	/**
	 * Retrieves the latest bureau inquiry
	 * 
	 * @param integer $application_id
	 * @param string $inquiry_type
	 * @param string $bureau_name
	 * @param integer $limit
	 * @return mixed|FALSE
	 */
	public function getReceivedPackages($application_id, $inquiry_type, $bureau_name, $limit)
	{
		$retval = FALSE;
		if (!$this->getService()->isEnabled(__FUNCTION__)) return $retval;

		$retval = $this->getService()->retrieveReceivedPackages($application_id, $inquiry_type, $bureau_name, $limit);

		return $retval;
	}

	/**
	 * Handles any exceptions that need to be thrown for unit tests and extensibility
	 *
	 * @param string $message
	 * @param int $code
	 * @throws Exception built with $message and $code
	 * @return void
	 */
	protected function throwException($message, $code)
	{
		throw new Exception($message, $code);
	}

	/**
	 * Find a bureau inquiry record by id
	 *
	 * @param int $id
	 * @return unknown
	 */
	public function findInquiryById($id)
	{
		$retval = FALSE;
		if (!$this->getService()->isEnabled(__FUNCTION__)) return $retval;

		$retval = $this->getService()->findInquiryById($id);

		return $retval;	
	}

	/**
	 * @param  $ssn
	 * @return array
	 */
	public function getFailuresBySsn($ssn)
	{
		$retval = FALSE;

		$failures = $this->getService()->getFailuresBySsn($ssn);
		if (!empty($failures->item) && is_object($failures->item))
		{
			$failures->item = array($failures->item);
		}
		if (empty($failures->item) || !is_array($failures->item))
		{
			$failures->item = array();
		}

		$retval = $failures->item;

		return $retval;
	}

	/**
	 * Finds bureau inquiry records by application id, looking up the
	 * apps react history to get the inquiries from the last non-react
	 * parent application. [#54991]
	 *
	 * @param int $id
	 * @return array
	 */
	public function findLastNonReactInquiries($id)
	{
		$retval = array();
		if (!$this->getService()->isEnabled(__FUNCTION__)) return $retval;

		$retval = $this->getService()->findLastNonReactInquiries($id);
file_put_contents('/tmp/inquiry_recur', $retval, FILE_APPEND);

		return $retval;	
	}
	
	/**
	 * Performs the call to the underlying service, clearing all buffered calls
	 *
	 * @return mixed
	 */	
	public function flush()
	{
		return $this->getService()->flush();
	}
	/**
	 * enables and disables buffering of calls
	 *
	 * @param boolean $enabled
	 * @return void
	 */	
	public function enableBuffer($enabled)
	{
		$this->getService()->setAggregateEnabled($enabled);
	}
}

?>
