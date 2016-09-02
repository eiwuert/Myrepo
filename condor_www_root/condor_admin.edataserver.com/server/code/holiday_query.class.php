<?php

class Holiday_Query
{
	private $server;
	private $holiday_list;

	public function __construct(Server $server)
	{
		$this->server = $server;
		$this->holiday_list = array();
	}

	public function Fetch_Holiday_List()
	{
		$query = "SELECT holiday FROM holiday";

		$q_obj = $this->server->MySQLi()->Query($query);

		while( $row = $q_obj->Fetch_Object_Row() )
		{
			$this->holiday_list[] = $row->holiday;
		}

		$_SESSION['holidays'] = $this->holiday_list;

		return $this->holiday_list;
	}
}

?>