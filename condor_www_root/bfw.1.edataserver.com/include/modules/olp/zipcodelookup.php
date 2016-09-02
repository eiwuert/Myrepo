<?php
require_once(BFW_CODE_DIR.'Ajax_Request.php');
require_once(BFW_CODE_DIR.'Ajax_Response.php');

class ZipCodeLookup extends Ajax_Request
{
	public function Generate_Response()
	{
		$response = new Ajax_Response();
		$zip = $this->collected_data['zip_code'];
		if(is_numeric($zip) && strlen($zip) == 5)
		{
			$query = "SELECT 
				city,state 
			FROM 
				zip_lookup 
			WHERE 
				zip_code='$zip'";
			$res = $this->olp_db->Query($this->olp_db->db_info['db'], $query);
			
			 if($row = $this->olp_db->Fetch_Object_Row($res))
			{
				$r = new stdClass();
				$r->ZipCode = $zip;
				$r->City = $row->city;
				$r->State = $row->state;
				$response->Zip = $r;
			}
			else 
			{
				$error = new stdClass();
				$error->code = 1;
				$error->message = 'Unknown zipcode.';
				$response->error = $error;
			}
			
		}
		else
		{
			$error = new stdClass();
			$error->code = 2;
			$error->message = 'Invalid zip format.';
			$response->error = $error;
		}
		return $response;
	}
}
