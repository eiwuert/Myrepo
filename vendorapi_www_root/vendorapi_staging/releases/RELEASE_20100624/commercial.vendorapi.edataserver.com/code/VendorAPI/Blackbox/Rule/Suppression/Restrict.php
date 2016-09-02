<?php

/**
 * Suppression list rule which restricts values to matches to the list
 *
 * @author Adam Englander<adam.englander@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_Suppression_Restrict extends VendorAPI_Blackbox_Rule
{
	/**
	 * @var VendorAPI_SuppressionList_ILoader
	 */
	protected $suppression_list;

	protected $list_name;

	/**
	 * @var VendorAPI_SuppressionList_Wrapper
	 */
	protected $list;
	
	/**
	 * @param VendorAPI_Blackbox_EventLog $log
	 * @param VendorAPI_SuppressionList_ILoader $suppression_list
	 */
	public function __construct(VendorAPI_Blackbox_EventLog $log, VendorAPI_SuppressionList_ILoader $suppression_list, $list_name)
	{
		$this->suppression_list = $suppression_list;
		$this->list_name = $list_name;
		parent::__construct($log);
	}

	/**
	 * @return string
	 */
	public function getEventName()
	{
		return "LIST_RESTRICT_".strtoupper($this->list_name);
	}

	/**
	 * Run this rule.  Test if the field is in the verify list, add
	 * loan action if it is.  Always return TRUE
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return boolean
	 */
	public function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$this->list = $this->suppression_list->getByName($this->list_name);

		if (isset($this->list)
			&& isset($data->{$this->list->getField()})
			&& !empty($data->{$this->list->getField()})
			&& $this->list->match($data->{$this->list->getField()}))
		{
			return TRUE;
		}

		return FALSE;
	}
	
	/**
	 * Return a comment?
	 * @return string
	 */
	protected function failureComment()
	{
		return 'Failed restrict suppression list: ' . $this->list_name;
	}

	/**
	 * Return a failure short?
	 * @return string
	 */
	protected function failureShort()
	{
		return 'SUPPRESSION_LIST_FAIL';
	}
}

?>
