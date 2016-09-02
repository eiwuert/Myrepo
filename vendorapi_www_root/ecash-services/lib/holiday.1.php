<?php
	// Version 1.0.0

class Holiday_1
{
	var $holiday_array;
	var $db2;
	
	function Holiday_1($olp_db2)
	{
		$this->db2 = $olp_db2;
	}
	
	function Fetch_Holiday_Array() 
	{
		if(isset($this->holiday_array)) { return $this->holiday_array; }
		
		// HOLIDAY_LIMIT is a view with the appropriate query		
		$query = "
			SELECT
				*
			FROM
				HOLIDAY_LIMIT";
		
		// Execute the query
		$result = $this->db2->Execute($query);

		// How many rows was returned
		$count = $result->Num_Rows();
	
		$this->holiday_array = array();
		
		// Go through the rows and build an array.
		for($i=1; $i <= $count; $i++)
		{
			$data = $result->Fetch_Array($i);
			$this->holiday_array[] = $data['HOLIDAY'];
		}
		
		// Return the array of dates
		return $this->holiday_array;	
	}
}

?>