<?php
/**
 * A class containing a list of OLPBlackbox_FailureReason objects.
 * 
 * @package OLPBlackbox
 * @subpackage FailureReasons
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_FailureReasonList extends Collections_List_1
{
	/**
	 * Adds a OLPBlackbox_SuppressionFailure to the list.
	 *
	 * @param OLPBlackbox_SuppressionFailure $failure a suppression list failure object
	 */
	public function add($failure)
	{
		if (!$failure instanceof OLPBlackbox_FailureReason)
		{
			throw new InvalidArgumentException(
				'failure must be OLPBlackbox_FailureReason'
			);
		}
		$this->items[] = $failure;
	}
		
	/**
	 * Returns a whether the list is empty.
	 *
	 * @return bool
	 */
	public function isEmpty()
	{
		return count($this) < 1;
	}
}
?>
