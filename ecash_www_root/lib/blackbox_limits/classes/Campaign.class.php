<?php

/**
 * @brief
 * A class encapsulating a Campaign record in the database
 */

class Campaign
{
	// Private fields
	var $date_modified;
	var $date_created;
	var $campaign_id = 0;
	var $target_id = 0;
	var $percentage = 0; // For ongoing (type = ONGOING) campaigns only; used only if (get_tier()->get_weight_type() == 'PERCENT')
	var $status = 'INACTIVE';
	var $type = 'NONE'; // ONGOING / BY_DATE
	var $start_date = '0000-00-00'; // for temporary (type = BY_DATE) campaigns only
	var $end_date = '0000-00-00'; //       "                      "
	var $limit = 0; // Daily Limit
	var $thank_you_content = '';
	var $username; // Webadmin2 user name of who created this record
	var $total_limit = 0; // Total limit for the life of a temporary (type = BY_DATE) campaign
	var $lead_amount = 0; // Dollar amount paid by target, per lead; Used only if (get_tier()->get_weight_type() == 'AMOUNT')
	var $dd_ratio = 0; // Ratio of direct deposit leads to send vs non direct deposit leads to send
	var $max_deviation = 0; // Maximum % the system can deviate from the dd ratio when selecting a winner
	var $priority = 1; // The priority weight of the campaign
	var $hourly_limit = '';
	var $daily_limit = '';
	var $overflow = 0;

	var $sql;

	function Campaign()
	{
		global $sql;

		$this->sql = &$sql;
	}

	function find_by_id($id)
	{
		if (!$id)
			return NULL;

		$query = "
			SELECT
				date_modified,
				date_created,
				campaign_id,
				target_id,
				percentage,
				status,
				type,
				start_date,
				end_date,
				`limit`,
				thank_you_content,
				username,
				total_limit,
				lead_amount,
				dd_ratio,
				max_deviation,
				priority,
				hourly_limit,
				daily_limit,
				overflow
			FROM campaign
			WHERE campaign_id = {$id}
			LIMIT 1";

		global $sql;
		$result = $sql->Query(MYSQL_DB, $query);
		if ($row = $sql->Fetch_Array_Row($result))
		{
			$current_campaign = new Campaign();
			$current_campaign->set_sql($sql);
			$current_campaign->set_date_modified($row['date_modified']);
			$current_campaign->set_date_created($row['date_created']);
			$current_campaign->set_campaign_id($row['campaign_id']);
			$current_campaign->set_target_id($row['target_id']);
			$current_campaign->set_percentage($row['percentage']);
			$current_campaign->set_status($row['status']);
			$current_campaign->set_type($row['type']);
			$current_campaign->set_start_date($row['start_date']);
			$current_campaign->set_end_date($row['end_date']);
			$current_campaign->set_thank_you_content($row['thank_you_content']);
			$current_campaign->set_username($row['username']);
			$current_campaign->set_total_limit($row['total_limit']);
			$current_campaign->set_lead_amount($row['lead_amount']);
			$current_campaign->set_dd_ratio($row['dd_ratio']);
			$current_campaign->set_max_deviation($row['max_deviation']);
			$current_campaign->set_priority($row['priority']);
			$current_campaign->set_hourly_limit(unserialize($row['hourly_limit']));
			$current_campaign->set_daily_limit(unserailize($row['daily_limit']));
			$current_campaign->set_overflow($row['overflow']);

			return $current_campaign;
		}

		return NULL;
	}

	function find_all($where = '', $order_by = 'date_created DESC')
	{
		$where_clause = join_where_clause($where);
		$query = "
			SELECT
				date_modified,
				date_created,
				campaign_id,
				target_id,
				percentage,
				status,
				type,
				start_date,
				end_date,
				`limit`,
				thank_you_content,
				username,
				total_limit,
				lead_amount,
				dd_ratio,
				max_deviation,
				priority,
				hourly_limit,
				daily_limit,
				overflow
			FROM campaign
			{$where_clause}
			ORDER BY {$order_by}";

		global $sql;
		$result = $sql->Query(MYSQL_DB, $query);
		$campaign_array = Array();
		while ($row = $sql->Fetch_Array_Row($result))
		{
			$current_campaign = new Campaign();
			$current_campaign->set_sql($sql);
			$current_campaign->set_campaign_id($row['campaign_id']);
			$current_campaign->set_target_id($row['target_id']);
			$current_campaign->set_percentage($row['percentage']);
			$current_campaign->set_status($row['status']);
			$current_campaign->set_type($row['type']);
			$current_campaign->set_start_date($row['start_date']);
			$current_campaign->set_end_date($row['end_date']);
			$current_campaign->set_limit($row['limit']);
			$current_campaign->set_thank_you_content($row['thank_you_content']);
			$current_campaign->set_username($row['username']);
			$current_campaign->set_total_limit($row['total_limit']);
			$current_campaign->set_lead_amount($row['lead_amount']);
			$current_campaign->set_dd_ratio($row['dd_ratio']);
			$current_campaign->set_max_deviation($row['max_deviation']);
			$current_campaign->set_priority($row['priority']);
			$current_campaign->set_hourly_limit(unserialize($row['hourly_limit']));
			$current_campaign->set_daily_limit(unserialize($row['daily_limit']));
			$current_campaign->set_overflow($row['overflow']);

			$campaign_array[] = $current_campaign;
		}

		return $campaign_array;
	}

	function update()
	{
		if (!$this->campaign_id)
			return $this->insert();

		$query = "
			UPDATE campaign
			SET
				date_modified = sysdate(),
				target_id ='" . $this->get_target_id() . "',
				percentage ='" . $this->get_percentage() . "',
				status ='" . $this->get_status() . "',
				type ='" . $this->get_type() . "',
				start_date ='" . $this->get_start_date() . "',
				end_date ='" . $this->get_end_date() . "',
				`limit` ='" . $this->get_limit() . "',
				thank_you_content ='" . $this->get_thank_you_content() . "',
				username ='" . $this->get_username() . "',
				total_limit = '" . $this->get_total_limit() . "',
				lead_amount = '" . $this->get_lead_amount() . "',
				dd_ratio = '" . $this->get_dd_ratio() . "',
				max_deviation = '" . $this->get_max_deviation . "',
				priority = '" . $this->get_priority() . "',
				hourly_limit = '" . serialize($this->get_hourly_limit()) . "',
				daily_limit = '" . serialize($this->get_daily_limit()) . "',
				overflow = '" . $this->get_overflow() . "'
			WHERE campaign_id = '" . $this->get_campaign_id() . "'
			LIMIT 1";
		
		$result = $this->sql->Query(MYSQL_DB, $query);
		if (is_a($result, 'Error_2'))
			print_r($result) && die;

		return $result;
	}

	function insert()
	{
		// We don't want to serialize an empty hourly_limit
		$hourly_limit = $this->get_hourly_limit();
		if(!empty($hourly_limit))
		{
			$hourly_limit = serialize($this->get_hourly_limit());
		}
		
		// We don't want to serialize an empty daily_limit
		$daily_limit = $this->get_daily_limit();
		if(!empty($daily_limit))
		{
			$daily_limit = serialize($this->get_daily_limit());
		}

		

		$query = "
			INSERT INTO campaign(date_modified, date_created, target_id, percentage, status, type, start_date, end_date, `limit`, thank_you_content, username, total_limit, lead_amount, dd_ratio, max_deviation, priority, overflow, hourly_limit, daily_limit)
			VALUES(
				sysdate(),
				sysdate(),
				'" . $this->get_target_id() . "',
				'" . $this->get_percentage() . "',
				'" . $this->get_status() . "',
				'" . $this->get_type() . "',
				'" . $this->get_start_date() . "',
				'" . $this->get_end_date() . "',
				'" . $this->get_limit() . "',
				'" . $this->get_thank_you_content() . "',
				'" . $this->get_username() . "',
				'" . $this->get_total_limit() . "',
				'" . $this->get_lead_amount() . "',
				'" . $this->get_dd_ratio() . "',
				'" . $this->get_max_deviation() . "',
				'" . $this->get_priority() . "',
				'" . $this->get_overflow() . "',
				'$hourly_limit',
				'$daily_limit')";

		$result = $this->sql->Query(MYSQL_DB, $query);
		if (!is_a($result, 'Error_2'))
			$this->set_campaign_id($this->sql->Insert_Id());
		else
			print_r($result) && die;

		return $result;
	}

	// May potentially return an ongoing campaign
	function find_current_by_target_id($target_id)
	{
		global $sql;

		$query = "
			SELECT
				date_modified,
				date_created,
				campaign_id,
				target_id,
				percentage,
				status,
				type,
				start_date,
				end_date,
				`limit`,
				thank_you_content,
				username,
				total_limit,
				lead_amount,
				dd_ratio,
				max_deviation,
				priority,
				hourly_limit,
				daily_limit,
				overflow
			FROM campaign
			WHERE
				target_id = '{$target_id}'
				AND ((start_date <= curdate()
					AND ((end_date IS NULL) OR (end_date > curdate())))
				OR (type = 'ONGOING'))
-- temporary campaign (BY_DATE) takes priority over ongoing campaign
			ORDER BY (type = 'ONGOING')
			LIMIT 1";


		$result = $this->sql->Query(MYSQL_DB, $query);
		if (is_a($result, 'Error_2'))
			print_r($result) && die;

		if ($row = $sql->Fetch_Array_Row($result))
		{
			$current_campaign = new Campaign();
			$current_campaign->set_sql($sql);
			$current_campaign->set_date_modified($row['date_modified']);
			$current_campaign->set_date_created($row['date_created']);
			$current_campaign->set_campaign_id($row['campaign_id']);
			$current_campaign->set_target_id($row['target_id']);
			$current_campaign->set_percentage($row['percentage']);
			$current_campaign->set_status($row['status']);
			$current_campaign->set_type($row['type']);
			$current_campaign->set_start_date($row['start_date']);
			$current_campaign->set_end_date($row['end_date']);
			$current_campaign->set_limit($row['limit']);
			$current_campaign->set_thank_you_content($row['thank_you_content']);
			$current_campaign->set_username($row['username']);
			$current_campaign->set_total_limit($row['total_limit']);
			$current_campaign->set_lead_amount($row['lead_amount']);
			$current_campaign->set_dd_ratio($row['dd_ratio']);
			$current_campaign->set_max_deviation($row['max_deviation']);
			$current_campaign->set_priority($row['priority']);
			$current_campaign->set_hourly_limit(unserialize($row['hourly_limit']));
			$current_campaign->set_daily_limit(unserialize($row['daily_limit']));
			$current_campaign->set_overflow($row['overflow']);

			return $current_campaign;
		}

		return NULL;
	}

	function find_ongoing_by_target_id($target_id)
	{
		global $sql;

		$query = "
			SELECT
				date_modified,
				date_created,
				campaign_id,
				target_id,
				percentage,
				status,
				type,
				start_date,
				end_date,
				`limit`,
				thank_you_content,
				username,
				total_limit,
				lead_amount,
				dd_ratio,
				max_deviation,
				priority,
				hourly_limit,
				daily_limit,
				overflow
			FROM campaign
			WHERE
				target_id = '{$target_id}'
				AND status = 'ACTIVE'
				AND type = 'ONGOING'
			LIMIT 1";

		$result = $this->sql->Query(MYSQL_DB, $query);
		if (is_a($result, 'Error_2'))
			print_r($result) && die;

		if ($row = $sql->Fetch_Array_Row($result))
		{
			$current_campaign = new Campaign();
			$current_campaign->set_sql($sql);
			$current_campaign->set_date_modified($row['date_modified']);
			$current_campaign->set_date_created($row['date_created']);
			$current_campaign->set_campaign_id($row['campaign_id']);
			$current_campaign->set_target_id($row['target_id']);
			$current_campaign->set_percentage($row['percentage']);
			$current_campaign->set_status($row['status']);
			$current_campaign->set_type($row['type']);
			$current_campaign->set_start_date($row['start_date']);
			$current_campaign->set_end_date($row['end_date']);
			$current_campaign->set_limit($row['limit']);
			$current_campaign->set_thank_you_content($row['thank_you_content']);
			$current_campaign->set_username($row['username']);
			$current_campaign->set_total_limit($row['total_limit']);
			$current_campaign->set_lead_amount($row['lead_amount']);
			$current_campaign->set_dd_ratio($row['dd_ratio']);
			$current_campaign->set_max_deviation($row['max_deviation']);
			$current_campaign->set_priority($row['priority']);
			$current_campaign->set_hourly_limit(unserialize($row['hourly_limit']));
			$current_campaign->set_daily_limit(unserialize($row['daily_limit']));
			$current_campaign->set_overflow($row['overflow']);

			return $current_campaign;
		}

		return NULL;
	}

	function get_current_hits()
	{
		// TODO: Figure out how to get this working for both ongoing and by_date campaigns. Maybe use SUM() for by_date
		$query = "
			SELECT bb_" . $this->get_property_short() . "
			FROM stat_limit
			WHERE stat_limit_date = curdate()
			";

		return 123;
	}

	function equals($other_campaign)
	{
		$equals = TRUE;
		$equals = ($equals && ($this->get_target_id() == $other_campaign->get_target_id()));
		$equals = ($equals && ($this->get_percentage() == $other_campaign->get_percentage()));
		$equals = ($equals && ($this->get_status() == $other_campaign->get_status()));
		$equals = ($equals && ($this->get_type() == $other_campaign->get_type()));
		$equals = ($equals && ($this->get_start_date() == $other_campaign->get_start_date()));
		$equals = ($equals && ($this->get_end_date() == $other_campaign->get_end_date()));
		$equals = ($equals && ($this->get_limit() == $other_campaign->get_limit()));
		$equals = ($equals && ($this->get_thank_you_content() == $other_campaign->get_thank_you_content()));
		$equals = ($equals && ($this->get_total_limit() == $other_campaign->get_total_limit()));
		$equals = ($equals && ($this->get_lead_amount() == $other_campaign->get_lead_amount()));
		$equals = ($equals && ($this->get_dd_ratio() == $other_campaign->get_dd_ratio()));
		$equals = ($equals && ($this->get_max_deviation() == $other_campaign->get_max_deviation()));
		$equals = ($equals && ($this->get_priority() == $other_campaign->get_priority()));
		$equals = ($equals && ($this->get_hourly_limit() == $other_campaign->get_hourly_limit()));
		$equals = ($equals && ($this->get_daily_limit() == $other_campaign->get_daily_limit()));
		$equals = ($equals && ($this->get_overflow() == $other_campaign->get_overflow()));

		return (boolean)$equals;
	}

	// returns true if this campaign is currently active
	function is_active()
	{
		if ($this->get_status() == 'ACTIVE')
		{
			if ($this->get_type() == 'ONGOING')
				return TRUE;

			$current_time = time();

			$start_date = $this->get_start_date();
			if ($start_date == '0000-00-00')
				return FALSE;

			$end_date = $this->get_end_date();
			if ($end_date == '0000-00-00')
				return FALSE;

			$start_date_time = strtotime($start_date);
			$end_date_time = strtotime($end_date);
			return (($end_date_time > $current_time) && ($start_date_time < $current_time));
		}

		return FALSE;
	}

	// returns true if this campaign is currently active or it will be in the future
	function is_future()
	{
		if ($this->get_status() == 'ACTIVE')
		{
			if ($this->get_type() == 'ONGOING')
				return TRUE;

			$current_time = time();

			$end_date = $this->get_end_date();
			if ($end_date == '0000-00-00')
				return FALSE;

			$end_date_time = strtotime($end_date);
			return ($end_date_time > $current_time);
		}

		return FALSE;
	}

	// Getter and Setter methods
	function set_date_modified($date_modified)
	{
		$this->date_modified = $date_modified;
	}

	function get_date_modified()
	{
		return $this->date_modified;
	}

	function set_date_created($date_created)
	{
		$this->date_created = $date_created;
	}

	function get_date_created()
	{
		return $this->date_created;
	}

	function set_campaign_id($campaign_id)
	{
		$this->campaign_id = $campaign_id;
	}

	function get_campaign_id()
	{
		return $this->campaign_id;
	}

	function set_target_id($target_id)
	{
		$this->target_id = $target_id;
	}

	function get_target_id()
	{
		return $this->target_id;
	}

	function set_percentage($percentage)
	{
		$this->percentage = $percentage;
	}

	function get_percentage()
	{
		return $this->percentage;
	}

	function set_status($status)
	{
		$this->status = $status;
	}

	function get_status()
	{
		return $this->status;
	}

	function set_type($type)
	{
		$this->type = $type;
	}

	function get_type()
	{
		return $this->type;
	}

	function set_start_date($start_date)
	{
		$this->start_date = parse_entered_date($start_date);
	}

	function get_start_date()
	{
		return $this->start_date;
	}

	function set_end_date($end_date)
	{
		$this->end_date = parse_entered_date($end_date);
	}

	function get_end_date()
	{
		return $this->end_date;
	}
	
	function set_limit($limit)
	{
		$this->limit = $limit;
	}

	function get_limit()
	{
		
		// Check to see if the campaign has not yet switched over to the new limit scheme.
		// Eventually this conditional can be removed once all campaigns are converted.
		if(empty($this->daily_limit))
		{
			return $this->limit;
		}
		
		$day_index = date('N')-1;
		
		// Check to see if we are using Detailed Daily Limits or Default Limit	
		if($this->daily_limit[7] == '1')
		{
			// Use Daily Limit
			return $this->daily_limit[$day_index];
		}		
		else
		{
			// Use the Default Limit
			return $this->daily_limit[8];
		}
		
	}

	function set_thank_you_content($thank_you_content)
	{
		$this->thank_you_content = $thank_you_content;
	}

	function get_thank_you_content()
	{
		return $this->thank_you_content;
	}

	function set_username($username)
	{
		$this->username = $username;
	}

	function get_username()
	{
		return $this->username;
	}

	function set_total_limit($total_limit)
	{
		$this->total_limit = $total_limit;
	}

	function get_total_limit()
	{
		return $this->total_limit;
	}

	function set_lead_amount($lead_amount)
	{
		$this->lead_amount = $lead_amount;
	}

	function get_lead_amount()
	{
		return $this->lead_amount;
	}

	function set_dd_ratio($dd_ratio)
	{
		$this->dd_ratio = $dd_ratio;
	}

	function get_dd_ratio()
	{
		return $this->dd_ratio;
	}

	function set_max_deviation($max_deviation)
	{
		$this->max_deviation = $max_deviation;
	}

	function get_max_deviation()
	{
		return $this->max_deviation;
	}

	function set_sql($sql)
	{
		$this->sql = $sql;
	}

	function get_sql()
	{
		return $this->sql;
	}

	function set_priority($priority)
	{
		$this->priority = $priority;
	}

	function get_priority()
	{
		return $this->priority;
	}

	/**
	 * Sets the hourly limit array. Pass an array for hourly limits or pass an
	 * empty string if there are none.
	 *
	 * @param mixed $hourly_limit
	 */
	function set_hourly_limit($hourly_limit)
	{
		$this->hourly_limit = $hourly_limit;
	}

	/**
	 * Returns the hourly limit array.
	 *
	 * @return array
	 */
	function get_hourly_limit()
	{
		return $this->hourly_limit;
	}

	
	/**
	 * Sets the hourly limit array. Pass an array for hourly limits or pass an
	 * empty string if there are none.
	 *
	 * @param mixed $daily_limit
	 */
	function set_daily_limit($daily_limit)
	{
		$this->daily_limit = $daily_limit;
	}

	/**
	 * Returns the daily limit array.
	 *
	 * @return array
	 */
	function get_daily_limit()
	{
		//Just in case $this->daily_limit is never populated.
		// Eventually all campaigns should always have daily_limit
		// populated and this function can be changed to simply
		// return $this->daily_limit
		if(empty($this->daily_limit))
		{
			$this->daily_limit = array_fill(0,9,$this->limit);
			$this->daily_limit[7] = 0;
			return $this->daily_limit;
		}
		else
		{
			return $this->daily_limit;
		}
	}
	

	function set_overflow($overflow)
	{
		$this->overflow = $overflow;
	}

	function get_overflow()
	{
		return $this->overflow;
	}
}
