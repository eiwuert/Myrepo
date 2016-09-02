<?php

/**
 * Exclusion suppression list rule to exclude matches to the list
 *
 * @author Jim Wu <jim.wu@sellingsource.com>
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_Suppression_Exclude extends VendorAPI_Blackbox_Rule
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
		return "LIST_EXCLUDE_".strtoupper($this->list_name);
	}

	/**
	 * Run this rule.  Test if the employer is
	 * on the SSI suppression list and the applicant's age is under 62.
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
			return FALSE;
		}

		return TRUE;
	}
	
	/**
	 * Return a comment?
	 * @return string
	 */
	protected function failureComment()
	{
		return 'Failed exclude suppression list: ' . $this->list_name;
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
