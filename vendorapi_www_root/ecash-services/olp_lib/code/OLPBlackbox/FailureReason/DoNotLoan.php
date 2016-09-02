<?php

/**
 * Transport object for sending DNL failure reasons back to OLP from Blackbox.
 *
 * @package OLPBlackbox
 * @subpackage FailureReasons
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLPBlackbox_FailureReason_DoNotLoan extends OLPBlackbox_FailureReason
{
	protected $company_list; /**< @var array */
	
	/**
	 * Constructor for DNL failures.
	 */
	public function __construct(array $company_list)
	{
		$this->company_list = $company_list;
	}
	
	/**
	 * Describes this failure reason.
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return 'React was denied, on DNL list.';
	}
}
?>
