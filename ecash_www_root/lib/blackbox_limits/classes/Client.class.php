<?php

/**
 * @brief
 * A class encapsulating a Client record in the database
 */

class Client
{
	// Private fields
	var $date_modified;
	var $date_created;
	var $client_id;
	var $name = '*** NOT NAMED ***';
	var $phone_number = '';
	var $email_address = '';
	var $contact = '';
	var $status = 'INACTIVE';

	var $cached_current_campaign = NULL;
	var $cached_ongoing_campaign = NULL;
	
	var $sql;

	function Client()
	{
		global $sql;

		$this->set_sql($sql);
	}

	function find_by_id($id)
	{
		if (!$id)
			return NULL;
		
		$query = "
			SELECT date_modified, date_created, client_id, name, phone_number, email_address, contact, status 
			FROM client 
			WHERE client_id = {$id} 
			LIMIT 1";

		global $sql;
		$result = $sql->Query(MYSQL_DB, $query);
		if ($row = $sql->Fetch_Array_Row($result))
		{
			$current_client = new Client();
			$current_client->set_sql($sql);
			$current_client->set_date_modified($row['date_modified']);
			$current_client->set_date_created($row['date_created']);
			$current_client->set_client_id($row['client_id']);
			$current_client->set_name($row['name']);
			$current_client->set_email_address($row['email_address']);
			$current_client->set_contact($row['contact']);
			$current_client->set_status($row['status']);

			return $current_client;
		}

		return NULL;
	}

	function find_all($where = '', $order_by = 'date_modified DESC')
	{
		$where_clause = join_where_clause($where);

		$query = "
			SELECT date_modified, date_created, client_id, name, phone_number, email_address, contact, status 
			FROM client 
			{$where_clause}
			ORDER BY {$order_by}";

//die($query);
		global $sql;
		$result = $sql->Query(MYSQL_DB, $query);
		$client_array = Array();
		while ($row = $sql->Fetch_Array_Row($result))
		{
			$current_client = new Client();
			$current_client->set_sql($sql);
			$current_client->set_date_modified($row['date_modified']);
			$current_client->set_date_created($row['date_created']);
			$current_client->set_client_id($row['client_id']);
			$current_client->set_name($row['name']);
			$current_client->set_phone_number($row['phone_number']);
			$current_client->set_email_address($row['email_address']);
			$current_client->set_contact($row['contact']);
			$current_client->set_status($row['status']);

			$client_array[] = $current_client;
		}

		return $client_array;
	}

	function update()
	{
		if (!$this->get_client_id())
			return $this->insert();
		
		$query = "
			UPDATE client 
			SET 
				date_modified = sysdate(),
				name ='" . $this->get_name() . "', 
				phone_number ='" . $this->get_phone_number() . "', 
				email_address ='" . $this->get_email_address() . "', 
				contact ='" . $this->get_contact() . "', 
				status ='" . $this->get_status() . "' 
			WHERE client_id = '" . $this->get_client_id() . "' 
			LIMIT 1";

		$result = $this->sql->Query(MYSQL_DB, $query);
		
		return $result;
	}

	function insert()
	{
		$query = "
			INSERT INTO client(date_modified, date_created, name, phone_number, email_address, contact, status)
			VALUES(
				sysdate(),
				sysdate(),
				'" . $this->get_name() . "',
				'" . $this->get_phone_number() . "',
				'" . $this->get_email_address() . "',
				'" . $this->get_contact() . "', 
				'" . $this->get_status() . "')";

		$result = $this->sql->Query(MYSQL_DB, $query);
		if (!is_a($result, 'Error_2'))
			$this->set_client_id($this->sql->Insert_Id());
		else
			print_r($result) && die;
		
		return $result;
	}

	function delete()
	{
		$client_id = (int)$this->get_client_id();

		$query = "
			DELETE FROM client 
			WHERE client_id = '{$client_id}'
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

	function set_client_id($client_id)
	{
		$this->client_id = $client_id;
	}

	function get_client_id()
	{
		return $this->client_id;
	}

	function set_name($name)
	{
		$this->name = $name;
	}

	function get_name()
	{
		return $this->name;
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

	function set_contact($contact)
	{
		$this->contact = $contact;
	}

	function get_contact()
	{
		return $this->contact;
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
