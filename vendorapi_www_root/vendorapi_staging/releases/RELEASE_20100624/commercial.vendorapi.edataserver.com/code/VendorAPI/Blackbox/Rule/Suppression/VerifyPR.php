<?php

/**
 * Verify Phone Rule suppression list to exclude matches to the multiple suppression list and the rule
 *
 */
class VendorAPI_Blackbox_Rule_Suppression_VerifyPR extends VendorAPI_Blackbox_Rule
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

        protected $campaign;
	
	/**
	 * @param VendorAPI_Blackbox_EventLog $log
	 * @param VendorAPI_SuppressionList_ILoader $suppression_list
         * @param $list_name
         * @param $campaign
	 */
	public function __construct(VendorAPI_Blackbox_EventLog $log, VendorAPI_SuppressionList_ILoader $suppression_list, $list_name, $campaign)
	{
		$this->suppression_list = $suppression_list;
		$this->list_name = $list_name;
                $this->campaign = $campaign;
		parent::__construct($log);
	}

	/**
	 * @return string
	 */
	public function getEventName()
	{
		return "LIST_VERIFY_PR_".strtoupper($this->list_name);
	}

	/**
	 * Run this rule.
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return boolean
	 */
	public function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
                $this->list = $this->suppression_list->getByName($this->list_name);

                if (isset($this->list))
		{
			$field = $this->list->getField(); //multiple field like 'campaign,age'
                        $value_array = $this->list->getInnerList()->getRegexOriginalList();
                        $value = $value_array[0];       //multiple value like '/^.+$/,/^([0-9]|1[0-7])$/'

                        $field_array = explode(",",$field);
                        $value_array = explode(",",$value);
                        $field_value_array = array_combine($field_array,$value_array); //array field => value like campaign=>'/^.+$/',
                                                                                        // age=>'/^([0-9]|1[0-7])$/'
                        foreach($field_value_array as $field => $value)
                        {
                                if ($field == 'campaign') //campaign is not set in $data
                                {
                                        $pattern = $this->campaign;
                                }
                                else if (isset($data->{$field}))
                                {
                                        $pattern = $data->{$field}; //here is what is set in $data, assign it to pattern directly
                                }
                                else
                                {
                                        return TRUE;
                                }
                                
                                if (preg_match($value, $pattern))
                                {
                                        return (($data->phone_home != $data->phone_work) && ($data->phone_cell != $data->phone_work));
                                }
                                else
                                {
                                        return TRUE;
                                }
                        }
		}
                else
                {
                        return TRUE;
                }

                return FALSE;
	}
	
	/**
	 * Return a comment
	 * @return string
	 */
	protected function failureComment()
	{
		return 'Failed exclude multiple suppression list: ' . $this->list_name;
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
