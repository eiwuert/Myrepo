<?php

/**
 * Represents a failure of a minumum income rule.
 * 
 * @package OLPBlackbox
 * @subpackage FailureReasons
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_FailureReason_MinimumIncome extends OLPBlackbox_FailureReason
{
	/**
	 * @param int $required The required income for this company to fund apps.
	 * @param int $actual The actual amount of income this applicant reported.
	 */
	function __construct($required, $actual)
	{
		// Required income for the company that failed this applicant.
		$this->data['required'] = intval($required);
		
		// Actual income the applicant reported.
		$this->data['actual'] = intval($actual);
	}
	
	/**
	 * A human readable representation of what this failure reason is.
	 *  
	 * @see OLPBlackbox_FailureReason::getDescription()
	 * @return string
	 */
	public function getDescription()
	{
		// This is the legacy string that was set in this scenario.
		return 'React was denied, Minimum Income not met';
	}
}

?>
