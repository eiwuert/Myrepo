<?php

/**
 * Related to new type of suppression ("ROUTE") so that if a non-hard failing parameter of a request (rows 8-16 of the Campaign Rule spreadsheet, Assembla 3)
 * is out of range specified in the rules for a campaign, the ROUTE mechanism starts looking for the appropriate candidate for request from the rules.
 * If no candidate is found, the request is denied. For example, if the request has campaign mls3_1b with age > 40, change the campaign to mls3_1c.
 */
class VendorAPI_Blackbox_Rule_Suppression_RouteM extends VendorAPI_Blackbox_Rule
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
		return "LIST_ROUTE_M_".strtoupper($this->list_name);
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
		if (!empty($data->application_id)) return TRUE;

                $this->list = $this->suppression_list->getByName($this->list_name);

                if (isset($this->list))
		{
                        $empty_fields_check = array("income_direct_deposit","dob",
                                                    "income_frequency","income_source","income_monthly");
                        foreach ($empty_fields_check as $field_check)
                        {
                                if (!isset($data->{$field_check}))
                                {
                                        return FALSE;
                                }
                        }

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
                                        if ($field == 'campaign') //campaign is not set in $data
                                        {
                                                $pattern = $this->campaign;
                                                //for not relevant campaign
                                                if (!preg_match($value, $pattern))
                                                {
                                                        break; //proceed with next campaign unless relevant is found
                                                }
                                        }
                                        else if (isset($data->{$field}))
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

                                        if (!preg_match($value, $pattern))
                                        {
                                                /*
                                                $routed = $this->route($data, $field_array, $value_array);

                                                if ($routed !== FALSE)
                                                {
                                                        //$data->campaign_name = $routed;
                                                        return TRUE;
                                                }
                                                else
                                                {
                                                        $this->failed_field = $field;
                                                        $this->failed_value = $pattern;
                                                        return FALSE;
                                                }
                                                */
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
	 * Finds the appropriate candidate.
	 *
         * @param Blackbox_Data $data
	 * @param $field_array
         * @param $value_array
	 * @return string
	 */
        protected function route(Blackbox_Data $data, $field_array, $value_array)
	{
                foreach ($value_array as $value)
                {
                        $right_candidate = TRUE;

                        $current_value_array = explode(";",$value);

                        $field_value_array = array_combine($field_array,$current_value_array);  //array field => value like campaign=>'/^mls3_1b$/',
                                                                                                // age=>'/^([0-9]|1[0-7])$/'

                        foreach($field_value_array as $field => $value)
                        {
                                if ($field == 'campaign')
                                {
                                        $pattern = $this->campaign;
                                        //only other than original campaign to find candidate
                                        if (preg_match($value, $pattern))
                                        {
                                                $right_candidate = FALSE;
                                                break;
                                        }
                                        else
                                        {
                                                $candidate_campaign = $value;
                                                continue;
                                        }
                                }
                                else if (isset($data->{$field}))
                                {
                                        $pattern = $data->{$field};
                                }
                                else if (isset($this->{$field}))
                                {
                                        $pattern = $this->{$field};
                                }

                                if (!preg_match($value, $pattern))
                                {
                                        $right_candidate = FALSE;
                                        break; // this set does not match, proceed with the next db row
                                }
                        }

                        if ($right_candidate)
			{
                                return $candidate_campaign;
			}
                }

                return FALSE;
	}
	
	/**
	 * Return a comment?
	 * @return string
	 */
	protected function failureComment()
	{
		return 'Failed multiple route: ' . $this->list_name . ' ' . $this->campaign . ', ' . $this->failed_field . ':' . $this->failed_value;
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
