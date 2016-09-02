<?php

/**
 * Global, not depending on campaign, suppression list rule to exclude matches to the multiple suppression list
 *
 */
class VendorAPI_Blackbox_Rule_Suppression_ExcludeG extends VendorAPI_Blackbox_Rule
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
        protected $age;
        protected $employment_length;
        protected $residence_length;
        protected $phone_home_phone_work;
        protected $phone_cell_phone_work;

        protected $failed_field;
        protected $failed_value;

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
		return "LIST_Exclude_G_".strtoupper($this->list_name);
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
                        $this->setData($data);

			$field = $this->list->getField(); //multiple field like 'campaign,age'
                        $field_array = explode(";",$field);

                        $value_array = $this->list->getInnerList()->getRegexOriginalList();
                        foreach ($value_array as $value)
                        {
                                $current_value_array = explode(";",$value);
                                $field_value_array = array_combine($field_array,$current_value_array);  //array field => value like campaign=>'/^.+$/',
                                                                                                        // age=>'/^([0-9]|1[0-7])$/'
                                foreach($field_value_array as $field => $value)
                                {
                                        if (isset($data->{$field}))
                                        {
                                                $pattern = $data->{$field};
                                        }
                                        else if (isset($this->{$field}))
                                        {
                                                $pattern = $this->{$field};
                                        }
                                        else
                                        {
                                                return TRUE;
                                        }

                                        if (preg_match($value, $pattern))
                                        {
                                                $this->failed_field = $field;
                                                $this->failed_value = $pattern;

                                                return FALSE;
                                        }
                                }
                        }
		}
                else
                {
                        return TRUE;
                }

                return TRUE;
	}
	
	/**
	 * Return a comment?
	 * @return string
	 */
	protected function failureComment()
	{
		return 'Failed exclude multiple suppression list: ' . $this->list_name . ' ' . $this->campaign . ', ' . $this->failed_field . ':' . $this->failed_value;
	}

	/**
	 * Return a failure short?
	 * @return string
	 */
	protected function failureShort()
	{
		return 'SUPPRESSION_LIST_FAIL';
	}

        protected function setData(Blackbox_Data $data)
	{
                $this->age = Date_Util_1::getYearsElapsed(strtotime($data->dob));
                $this->employment_length = Date_Util_1::getMonthsElapsed($data->date_hire);
                $this->residence_length = Date_Util_1::getMonthsElapsed($data->residence_start_date);
                $this->phone_home_phone_work = intval(!empty($data->phone_home) && !empty($data->phone_work) && ($data->phone_home == $data->phone_work));
                $this->phone_cell_phone_work = intval(!empty($data->phone_cell) && !empty($data->phone_work) && ($data->phone_cell == $data->phone_work));
	}
}

?>
