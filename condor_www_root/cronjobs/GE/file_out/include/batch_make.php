<?php

class GE_Batch
{
	var $current_date;
	// e records = customers
	var $count_e_records;
	// ea records =  records per customer
	var $count_ea_records;
	// reset counter every 200 records per GE's request
	var $batch_200_inc;
	// counts iterations of 200 records
	var $batch_200_count;
	// multi-dimensional array of customers and data
	var $cust_array;

	var $batch_temp;

	function GE_Batch ()
	{
		$this->current_date = date ("mdy");
		$this->count_e_records = 0;
		$this->count_ea_records = 0;
		$this->batch_200_inc = 0;
		// batch_200_count starts at 1 because it has to be at least one, then increments up 1 for every 200 records
		$this->batch_200_count = 1;
		$this->cust_array = array ();
	}

	function Make_Batch ($current_batch_id, $batch_obj)
	{

		// HEADER
		$record_header = "0" . " " . $this->Zero_Fill ($current_batch_id, 4) . $this->current_date . "Q;" . $this->Space_Fill ("", 66) . "\n";

		$cc_type_map = array (
			"visa"=>"VI",
			"mastercard"=>"MA",
			"amercian express"=>"AM",
			"americanexpress"=>"AM",
			"discover"=>"DSC"
		);
		
		foreach ($batch_obj as $customer)
		{
			$cc_type = strtolower ($customer->card_type);
			$customer->card_type = isset ($cc_type_map[$cc_type]) ? $cc_type_map[$cc_type] : FALSE;
			
			if ($customer->card_type)
			{
				// reset $this->batch_200_count
				if($this->batch_200_inc == 200)
				{
					$this->batch_200_inc = 0;
					$this->batch_200_count++;
				}

				$customer->phone = preg_replace ('/\D/', '', $customer->phone);
				$customer->zip = preg_replace ('/\D/', '', $customer->zip);
	
				
				// record A
				$record_a = "EA\$TPD" . $this->Space_Fill ($customer->card_type, 3) . $this->Space_Fill ($customer->promo_code, 8) . "12" . $this->Space_Fill ($customer->card_num, 20) . $this->Space_Fill ("", 38) . "TSS" . "\n";
				$this->count_e_records++;
	
				// record B
	
				$exp_date = $customer->card_exp;
				$month = substr($exp_date, 0, 2);
				$year = substr($exp_date, -2);
				$timestamp = mktime(0,0,0,$month,1,'20'.$year);
				$record_b = "EB" . date ("mty", $timestamp) . $this->Space_Fill ("", 11) . $this->Zero_Fill ($this->batch_200_count , 5) . $this->Space_Fill ("", 2) . strtoupper($this->Space_Fill ($customer->site_code, 8)) . $this->Zero_Fill ($customer->order_id, 10) . $this->Space_Fill ("", 2) . $this->Space_Fill ($customer->promo_id, 9) . $this->Zero_Fill ($customer->phone, 10) . $this->Space_Fill ($customer->first_name, 15) . "\n";
				$this->count_e_records++;
	
				// record C
				$record_c = "EC" . $this->Space_Fill (substr ($customer->middle_name, 0, 1), 1) . $this->Space_Fill ($customer->last_name, 20) . $this->Space_Fill (substr ($customer->address1, 0, 25), 25) . $this->Space_Fill ($customer->address2, 25) . $this->Space_Fill ("", 7) . "\n";
				$this->count_e_records++;
	
				// record D
				if (isset($customer->dob) && ($customer->dob != ""))
				{
					$customer_dob = substr (preg_replace ("/-/", "", $customer->dob), 2);
				}
				else
				{
					$customer_dob = "";
				}
				$record_d = "ED" . $this->Space_Fill($customer->city, 18) . $this->Space_Fill ($customer->state, 2) . $this->Space_Fill ($customer->zip, 9) . $this->Space_Fill ("", 3) . $this->Space_Fill ($customer_dob,6) . $this->Space_Fill ("", 20) . $this->Space_Fill (substr ($customer->date_of_sale, 4, 2) . substr ($customer->date_of_sale, 6, 2) . substr ($customer->date_of_sale, 2, 2),6) . $this->Space_Fill ("", 14) . "\n";
				$this->count_e_records++;
	
	
				// Compile all manditory records into one array element
				$customer_data = $record_a . $record_b . $record_c . $record_d;
	
				// record I (email only): report only for products that require email
				if (isset($customer->email) && ($customer->email != ""))
				{
					$record_i = "EUI:" . $this->Space_Fill ($customer->email, 48)  . $this->Space_Fill ("", 28) . "\n";
					$this->count_e_records++;
					$customer_data .= $record_i;
				}
	
				// Set final customer array element
				$this->cust_array[] = $customer_data;
	
				$this->count_ea_records++;
				$this->batch_200_inc++;
			}
		}

		//TRAILER
		$record_trailer = "9" . $this->Zero_Fill ($this->count_e_records, 7) . $this->Zero_Fill ($this->count_ea_records, 7) . str_repeat (0, 65) . "\n";

		return strtoupper ($record_header . implode ("", $this->cust_array) . $record_trailer);
	}
	
	function Zero_Fill ($str, $length)
	{
		$str =  str_pad ($str, $length, "0", STR_PAD_LEFT);
		return $str;
	}

	function Space_Fill($str, $length)
	{
		$str =  str_pad ($str, $length);
		return $str;
	}

	function Custom_Fill($str, $length, $pad, $justify="left")
	{
		if ($justify == "left")
		{
			$justify = STR_PAD_LEFT;
		}
		else
		{
			$justify = STR_PAD_RIGHT;
		}
		$str =  str_pad ($str, $length, $pad, $justify);
		return $str;
	}
}
?>
