<?php

	/*
	*Description: This class is virtually a copy of ole_mail.2.php except that it does not use session data and relies
	* completely on passed in variables.
	*/
	require_once ("prpc/client.php");
	require_once ("mysql.3.php");
	
	class olp_list_manager
	{
	    // This function is copied from ole_mail.2.php untouched.
	    function SaveToFile($data_array)
	    {
	    	$fp = fopen("/tmp/Datran".date("Ymd").".dat");
			fputs($fp, serialize($data_array)."\n");
			fclose($fp);
	    }

	    // This function is copied from ole_mail.2.php untouched.
	    function bclean($str)
	    {
	    	return mysql_escape_string(trim($str));
	    }

	    
	    // This function is copied from ole_mail.2.php untouched.
	    function Datran_Send($data_array)
	    {
		//require_once("/virtualhosts/site_config/server.cfg.php");
	    	$sql = new MySQL_3 ();
			$sql->Connect ("BOTH", 'writer.dx.tss', 'olp', '7Kr8NmdS');

			if(!$sql)
			{
				$fp = fopen("/tmp/datran.txt", "a");
            			fputs($fp, "Could not connect to database\n");
            			fclose($fp);
				$this->SaveToFile($data_array);
				return false;
			}
			$query = "INSERT INTO data (application_id, first, last, email, segmentcode, ip_address, address, city, state, zip, phone, datran_group, phone_cell, dob, promo_id, bb_vendor_bypass, tier) ".
					"VALUES ('".$this->bclean($data_array['application_id'])."', '".$this->bclean($data_array['first'])."', '".$this->bclean($data_array['last'])."', '".$this->bclean($data_array['email']).
					"', '".$this->bclean($data_array['segmentcode'])."', '".$this->bclean($data_array['IPaddress'])."', '".$this->bclean($data_array['address']).
					"', '".$this->bclean($data_array['city'])."', '".$this->bclean($data_array['state'])."', '".$this->bclean($data_array['zip']).
					"', '".$this->bclean($data_array['home_phone']).
					"', '".$this->bclean($data_array['datran_group']).
					"', '".$this->bclean($data_array['phone_cell']).
					"', '".$this->bclean($data_array['dob']).
       				"', '".$this->bclean($data_array['promo_id']).
       				"', '".$this->bclean($data_array['bb_vendor_bypass']).
       				"', '".$this->bclean($data_array['tier'])."')";

			$result = $sql->Query ("livefeed", $query);
			if (Error_2::Error_Test ($result))
			{
				$fp = fopen("/tmp/datran.txt", "a");
            			fputs($fp, $query."\n");
            			fclose($fp);
				$this->SaveToFile($data_array);
				return false;
			}
			return true;
		}

		// This function is copied from ole_mail.2.php removing the usage of $_SESSION data.
		function olp_list_manager (
			$application_id,
			$email, 
			$name_first, 
			$name_last, 
			$site_id, 
			$list_id, 
			$ip_address, 
			$debug = 0, 
			$datran_group = 1,	
			$license = '',
			$home_street_unit = '',
			$home_city = '',
			$home_state = '',
			$home_zip = '',
			$site_name = '',
			$home_phone = '',
			$phone_cell = '',
			$dob = '',
			$promo_id = '',
			$bb_vendor_bypass = '',
			$tier = ''
		)
		{
		
				$trace_level = 32;
				$info_array["application_id"]=$application_id;
				$info_array["email"]=$email;
				$info_array["first"]=$name_first;
				$info_array["last"]=$name_last;
				$info_array["list_id"]=$list_id;
				$info_array["site_id"]=$site_id;
				$info_array["IPaddress"]=$ip_address;
				$info_array["licensekey"]= $license;
				$info_array["ole_version"]= "ole_mail.2.php";
				$info_array["datran_group"]=$datran_group;
				$info_array["address"] = $home_street_unit;
	  			$info_array["city"] = $home_city;
    			$info_array["state"] = $home_state;
       			$info_array["zip"] = $home_zip;
    			$info_array["segmentcode"] = $site_name;
    			$info_array["home_phone"] = $home_phone;
    			$info_array["phone_cell"] = $phone_cell;
    			$info_array["dob"] = $dob;
      			$info_array["promo_id"] = $promo_id;
      			$info_array["bb_vendor_bypass"] = $bb_vendor_bypass;
      			$info_array["tier"] = $tier;
				$this->Datran_Send($info_array);
				return TRUE;

		}
		
		
	}

?>
