<?php

/**
 * @brief
 * A class encapsulating a Target record in the database
 */

class Target
{
	// Private fields
	var $date_modified;
	var $date_created;
	var $target_id = 0;
	var $name = '*** NOT NAMED ***';
	var $property_short = '';
	var $phone_number = '';
	var $email_address = '';
	var $customer_service_email = '';
	var $url = '';
	var $tier_id = '-1';
	var $status = 'INACTIVE';
	var $client_id = -1;
	var $username;
	var $parent_target_id = 0;
	var $deleted = 0;

	var $cached_current_campaign = NULL;
	var $cached_ongoing_campaign = NULL;
	var $cached_current_rules    = NULL;
	var $cached_tier             = NULL;

	var $sql;

	function Target()
	{
		global $sql;

		$this->sql = &$sql;
	}

	function find_by_id($id)
	{
		if (!$id)
			return NULL;

		$query = "
			SELECT date_modified, date_created, target_id, name, property_short, phone_number, email_address, customer_service_email, url, tier_id, status, client_id, username, parent_target_id
			FROM target
			WHERE target_id = {$id}
			LIMIT 1";

		global $sql;
		$result = $sql->Query(MYSQL_DB, $query);
		if ($row = $sql->Fetch_Array_Row($result))
		{
			$current_target = new Target();
			$current_target->set_sql($sql);
			$current_target->set_date_modified($row['date_modified']);
			$current_target->set_date_created($row['date_created']);
			$current_target->set_target_id($row['target_id']);
			$current_target->set_name($row['name']);
			$current_target->set_property_short($row['property_short']);
			$current_target->set_phone_number($row['phone_number']);
			$current_target->set_email_address($row['email_address']);
			$current_target->set_customer_service_email($row['customer_service_email']);
			$current_target->set_url($row['url']);
			$current_target->set_tier_id($row['tier_id']);
			$current_target->set_status($row['status']);
			$current_target->set_client_id($row['client_id']);
			$current_target->set_username($row['username']);
			$current_target->set_parent_target_id($row['parent_target_id']);

			return $current_target;
		}

		return NULL;
	}

	function find_by_property_short($property_short)
	{
		if (!$property_short)
			return NULL;

		$query = "
			SELECT date_modified, date_created, target_id, name, property_short, phone_number, email_address, customer_service_email, url, tier_id, status, client_id, username, parent_target_id
			FROM target
			WHERE property_short = '{$property_short}'
			AND status='ACTIVE' AND deleted='FALSE'
			LIMIT 1";

		global $sql;
		$result = $sql->Query(MYSQL_DB, $query);
		if ($row = $sql->Fetch_Array_Row($result))
		{
			$current_target = new Target();
			$current_target->set_sql($sql);
			$current_target->set_date_modified($row['date_modified']);
			$current_target->set_date_created($row['date_created']);
			$current_target->set_target_id($row['target_id']);
			$current_target->set_name($row['name']);
			$current_target->set_property_short($row['property_short']);
			$current_target->set_phone_number($row['phone_number']);
			$current_target->set_email_address($row['email_address']);
			$current_target->set_url($row['url']);
			$current_target->set_tier_id($row['tier_id']);
			$current_target->set_status($row['status']);
			$current_target->set_client_id($row['client_id']);
			$current_target->set_username($row['username']);
			$current_target->set_parent_target_id($row['parent_target_id']);

			return $current_target;
		}

		return NULL;
	}

	function find_all($where = '', $order_by = 'date_modified DESC')
	{
		if (is_array($where))
			$where[] = "deleted = 'FALSE'";
		else
			$where = "deleted = 'FALSE'";
		$where_clause = join_where_clause($where);

		$query = "
			SELECT target.date_modified, target.date_created, target.target_id, target.name, target.property_short, target.phone_number, target.email_address, target.customer_service_email, target.url, target.tier_id, target.status, target.client_id, target.username, target.parent_target_id
			FROM target
			LEFT JOIN tier ON target.tier_id = tier.tier_id
			{$where_clause}
			ORDER BY {$order_by}";

		global $sql;
		$result = $sql->Query(MYSQL_DB, $query);
		Error_2::Error_Test($result, FATAL_DEBUG);
		$target_array = Array();
		while ($row = $sql->Fetch_Array_Row($result))
		{
			$current_target = new Target();
			$current_target->set_sql($sql);
			$current_target->set_date_modified($row['date_modified']);
			$current_target->set_date_created($row['date_created']);
			$current_target->set_target_id($row['target_id']);
			$current_target->set_name($row['name']);
			$current_target->set_property_short($row['property_short']);
			$current_target->set_phone_number($row['phone_number']);
			$current_target->set_email_address($row['email_address']);
			$current_target->set_url($row['url']);
			$current_target->set_tier_id($row['tier_id']);
			$current_target->set_status($row['status']);
			$current_target->set_client_id($row['client_id']);
			$current_target->set_username($row['username']);
			$current_target->set_parent_target_id($row['parent_target_id']);

			$target_array[] = $current_target;
		}

		return $target_array;
	}

	function update()
	{
		if (!$this->target_id)
			return $this->insert();

		$query = "
			UPDATE target
			SET
				date_modified = sysdate(),
				name = '" . $this->get_name() . "',
				property_short = '" . $this->get_property_short() . "',
				phone_number = '" . $this->get_phone_number() . "',
				email_address = '" . $this->get_email_address() . "',
				customer_service_email = '". $this->get_customer_service_email() . "',
				url = '" . $this->get_url() . "',
				tier_id = '" . $this->get_tier_id() . "',
				status = '" . $this->get_status() . "',
				client_id = '" . $this->get_client_id() . "',
				username = '" . $this->get_username() . "',
				parent_target_id = '" . $this->get_parent_target_id() . "'

			WHERE target_id = '" . $this->get_target_id() . "'
			LIMIT 1";

		$result = $this->sql->Query(MYSQL_DB, $query);
		if (is_a($result, 'Error_2'))
			print_r($result) && die();

		return $result;
	}

	function insert()
	{
		$query = "
			INSERT INTO target(date_modified, date_created, name, property_short, phone_number, email_address, customer_service_email, url, tier_id, status, client_id, username, parent_target_id, deleted)
			VALUES(
				sysdate(),
				sysdate(),
				'" . $this->get_name() . "',
				'" . $this->get_property_short() . "',
				'" . $this->get_phone_number() . "',
				'" . $this->get_email_address() . "',
				'" . $this->get_customer_service_email() . "',
				'" . $this->get_url() . "',
				'" . $this->get_tier_id() . "',
				'" . $this->get_status() . "',
				'" . $this->get_client_id() . "',
				'" . $this->get_username() . "',
				'" . $this->get_parent_target_id() . "',
				'FALSE')";

		$result = $this->sql->Query(MYSQL_DB, $query);
		if (!is_a($result, 'Error_2'))
			$this->set_target_id($this->sql->Insert_Id());
		else
			print_r($result) && die;

		return $result;
	}

	function delete()
	{
		/*
		$target_id = $this->get_target_id();
		$query = "
			DELETE FROM target
			WHERE target_id = '{$target_id}'
			LIMIT 1
			";

		$result = $this->sql->Query(MYSQL_DB, $query);
		if (is_a($result, 'Error_2'))
			print_r($result) && die;
		*/

		$target_id = $this->get_target_id();
		$query = "
			UPDATE target
			SET deleted = 'TRUE'
		   WHERE target_id = '{$target_id}'
			LIMIT 1
			";

		$result = $this->sql->Query(MYSQL_DB, $query);
		if (is_a($result, 'Error_2'))
			print_r($result) && die;

		return TRUE;
	}

	function get_current_campaign()
	{
		if (!$this->cached_current_campaign)
			$this->cached_current_campaign = Campaign::find_current_by_target_id($this->get_target_id());
		return $this->cached_current_campaign;
	}

	function get_ongoing_campaign()
	{
		if (!$this->cached_ongoing_campaign)
			$this->cached_ongoing_campaign = Campaign::find_ongoing_by_target_id($this->get_target_id());
		return $this->cached_ongoing_campaign;
	}

	function get_rules()
	{
		if (!$this->cached_current_rules)
			$this->cached_current_rules = Rules::find_current_by_target_id($this->get_target_id());
		return $this->cached_current_rules;
	}

	function get_legacy_arrays($tier_number)
	{
		$prop = Array();
		$open = Array();
		$limit = Array();
		$weight = Array();

		$tier = Tier::find_by_tier_number($tier_number);
		if (!$tier)
			return NULL;

		$targets = Target::find_all();
		foreach ($targets as $target)
		{
			if ($target->get_tier_id() == $tier->get_tier_id())
			{
				$prop[] = $target->get_property_short();
				$open[$target->get_property_short()] = (boolean)($target->get_status() == 'ACTIVE');
				$ongoing_campaign = $target->get_ongoing_campaign();
				if (!$ongoing_campaign)
					$ongoing_campaign = new Campaign();
				$current_campaign = $target->get_current_campaign();
				if (!$current_campaign)
					$current_campaign = new Campaign();
				$limit[$target->get_property_short()] = $current_campaign->get_limit();
				$weight[$target->get_property_short()] = ($ongoing_campaign->get_percentage() / 100);
			}
		}

		$arrays = new StdClass();
		$arrays->prop = $prop;
		$arrays->open = $open;
		$arrays->limit = $limit;
		$arrays->weight = $weight;
		return $arrays;
	}

	function set_tier($tier)
	{
		$this->tier_id = $tier->get_tier_id();
	}

	function get_tier()
	{
		if (!$this->cached_tier)
			$this->cached_tier = Tier::find_by_id($this->get_tier_id());
		return $this->cached_tier;
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

	function set_target_id($target_id)
	{
		$this->target_id = $target_id;
	}

	function get_target_id()
	{
		return $this->target_id;
	}

	function set_name($name)
	{
		$this->name = $name;
	}

	function get_name()
	{
		return $this->name;
	}

	function set_parent_target_id($parent_target_id)
	{
		$this->parent_target_id = $parent_target_id;
	}

	function get_parent_target_id()
	{
		return $this->parent_target_id;
	}

	function set_property_short($property_short)
	{
		$this->property_short = $property_short;
	}

	function get_property_short()
	{
		return $this->property_short;
	}

	function set_phone_number($phone_number)
	{
		$this->phone_number = $phone_number;
	}

	function get_phone_number()
	{
		return $this->phone_number;
	}

	function set_email_address($email_address)
	{
		$this->email_address = $email_address;
	}

	function get_email_address()
	{
		return $this->email_address;
	}

	function set_customer_service_email($email_address)
	{
		$this->customer_service_email = $email_address;
	}

	function get_customer_service_email()
	{
		return $this->customer_service_email;
	}

	function set_url($url)
	{
		$this->url = $url;
	}

	function get_url()
	{
		return $this->url;
	}

	function set_tier_id($tier_id)
	{
		$this->tier_id = $tier_id;
	}

	function get_tier_id()
	{
		return $this->tier_id;
	}

	function set_status($status)
	{
		$this->status = $status;
	}

	function get_status()
	{
		return $this->status;
	}

	function set_client_id($client_id)
	{
		$this->client_id = $client_id;
	}

	function get_client_id()
	{
		return $this->client_id;
	}

	function set_username($username)
	{
		$this->username = $username;
	}

	function get_username()
	{
		return $this->username;
	}

	function set_sql($sql)
	{
		$this->sql = $sql;
	}

	function get_sql()
	{
		return $this->sql;
	}
}
