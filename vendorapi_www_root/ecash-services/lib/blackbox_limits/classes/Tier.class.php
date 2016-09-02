<?php

/**
 *	@brief
 *		A class encapsulating a Tier record in the database
 */

class Tier
{
	// Private fields
	var $date_modified;
	var $date_created;
	var $tier_id; // auto increment field
	var $tier_number = -1; // 1st tier = 1, 2nd tier = 2, ...
	var $name = '*** NOT NAMED ***';
	var $weight_type = 'AMOUNT'; // AMOUNT/PERCENT
	var $status = 'INACTIVE';

	var $sql;

	function Tier()
	{
		global $sql;

		$this->set_sql($sql);
	}

	function find_by_id($id)
	{
		if (!$id)
			return NULL;
		
		$query = "
			SELECT date_modified, date_created, tier_id, tier_number, name, weight_type, status 
			FROM tier 
			WHERE tier_id = {$id} 
			LIMIT 1";

		global $sql;
		$result = $sql->Query(MYSQL_DB, $query);
		if ($row = $sql->Fetch_Array_Row($result))
		{
			$current_tier = new Tier();
			$current_tier->set_sql($sql);
			$current_tier->set_date_modified($row['date_modified']);
			$current_tier->set_date_created($row['date_created']);
			$current_tier->set_tier_id($row['tier_id']);
			$current_tier->set_tier_number($row['tier_number']);
			$current_tier->set_name($row['name']);
			$current_tier->set_weight_type($row['weight_type']);
			$current_tier->set_status($row['status']);

			return $current_tier;
		}

		return NULL;
	}

	function find_by_tier_number($tier_number)
	{
		if (!$tier_number)
			return NULL;

		$query = "
			SELECT date_modified, date_created, tier_id, tier_number, name, weight_type, status 
			FROM tier 
			WHERE tier_number = {$tier_number} 
			LIMIT 1";

		global $sql;
		$result = $sql->Query(MYSQL_DB, $query);
		if ($row = $sql->Fetch_Array_Row($result))
		{
			$current_tier = new Tier();
			$current_tier->set_sql($sql);
			$current_tier->set_date_modified($row['date_modified']);
			$current_tier->set_date_created($row['date_created']);
			$current_tier->set_tier_id($row['tier_id']);
			$current_tier->set_tier_number($row['tier_number']);
			$current_tier->set_name($row['name']);
			$current_tier->set_weight_type($row['weight_type']);
			$current_tier->set_status($row['status']);

			return $current_tier;
		}

		return NULL;
	}

	function find_all($where = '', $order_by = 'tier_number')
	{
		$where_clause = join_where_clause($where);

		$query = "
			SELECT date_modified, date_created, tier_id, tier_number, name, weight_type, status 
			FROM tier 
			{$where_clause} 
			ORDER BY {$order_by}";

//die($query);
		global $sql;
		$result = $sql->Query(MYSQL_DB, $query);
		$tier_array = Array();
		while ($row = $sql->Fetch_Array_Row($result))
		{
			$current_tier = new Tier();
			$current_tier->set_sql($sql);
			$current_tier->set_date_modified($row['date_modified']);
			$current_tier->set_date_created($row['date_created']);
			$current_tier->set_tier_id($row['tier_id']);
			$current_tier->set_tier_number($row['tier_number']);
			$current_tier->set_name($row['name']);
			$current_tier->set_weight_type($row['weight_type']);
			$current_tier->set_status($row['status']);

			$tier_array[] = $current_tier;
		}

		return $tier_array;
	}

	function update()
	{
		if (!$this->get_tier_id())
			return $this->insert();
		
		$query = "
			UPDATE tier 
			SET 
				date_modified = sysdate(),
				tier_number ='" . $this->get_tier_number() . "', 
				name ='" . $this->get_name() . "', 
				weight_type ='" . $this->get_weight_type() . "', 
				status ='" . $this->get_status() . "' 
			WHERE tier_id = '" . $this->get_tier_id() . "' 
			LIMIT 1";

		$result = $this->sql->Query(MYSQL_DB, $query);
		
		return $result;
	}

	function insert()
	{
		$query = "
			INSERT INTO tier(date_modified, date_created, name, tier_number, weight_type, status)
			VALUES(
				sysdate(),
				sysdate(),
				'" . $this->get_name() . "',
				'" . $this->get_tier_number() . "',
				'" . $this->get_weight_type() . "',
				'" . $this->get_status() . "')";

		$result = $this->sql->Query(MYSQL_DB, $query);
		if (!is_a($result, 'Error_2'))
			$this->set_tier_id($this->sql->Insert_Id());
		else
			print_r($result) && die;
		
		return $result;
	}

	function delete()
	{
		$tier_id = (int)$this->get_tier_id();

		$query = "
			DELETE FROM tier 
			WHERE tier_id = '{$tier_id}'
			LIMIT 1
			";

		$result = $this->sql->Query(MYSQL_DB, $query);
		if (is_a($result, 'Error_2'))
			print_r($result) && die();

		return $result;
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

	function set_tier_id($tier_id)
	{
		$this->tier_id = $tier_id;
	}

	function get_tier_id()
	{
		return $this->tier_id;
	}

	function set_name($name)
	{
		$this->name = $name;
	}

	function get_name()
	{
		return $this->name;
	}

	function set_tier_number($tier_number)
	{
		$this->tier_number = $tier_number;
	}

	function get_tier_number()
	{
		return $this->tier_number;
	}
	
	function __toString()
	{
		return $this->get_tier_number();
	}

	function set_weight_type($weight_type)
	{
		$this->weight_type = $weight_type;
	}

	function get_weight_type()
	{
		return $this->weight_type;
	}

	function set_status($status)
	{
		$this->status = $status;
	}

	function get_status()
	{
		return $this->status;
	}

	function set_sql(&$sql)
	{
		$this->sql = &$sql;
	}

	function get_sql()
	{
		return $this->sql;
	}
}
