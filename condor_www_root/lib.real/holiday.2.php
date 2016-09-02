<?php
	// Version 2.0.0

class Holiday_2
{
	var $holiday_array;
	var $db;
	
	function Holiday_2($mysql)
	{
		$this->db = $mysql;
	}
	
	function Fetch_Holiday_Array() 
	{
		if(isset($this->holiday_array)) { return $this->holiday_array; }
		
		$query = "
			select
				holiday,
				name
			from
				holiday
			where
				holiday between now() and date_add(now(), interval 4 month)";
		
		// Execute the query
		$result_id = $this->db->Query($this->db->db, $query);

		$this->holiday_array = array();
		
		// Go through the rows and build an array.
		while($data = $this->db->Fetch_Object_Row($result_id))
		{
			$this->holiday_array[] = $data->holiday;
		}
		
		// Return the array of dates
		return $this->holiday_array;	
	}
}

?>