<?php

	/*
	*Description: This class is virtually a copy of ole_mail.2.php except that it does not use session data and relies
	* completely on passed in variables.
	*/
	require_once ("prpc/client.php");
	require_once ("mysql.3.php");
	
	class olp_list_manager
	{	
		const MODE_LIVE = 'LIVE';
		const MODE_DEV =  'DEV';
		
		protected $mode;
		
		protected $connection_params = array(
			self::MODE_LIVE => array("BOTH", 'writer.dx.tss', 'olp', '7Kr8NmdS'),
			self::MODE_DEV => array("BOTH", 'monster.tss:3316', 'datax', 'iex2ahTi')
		);
		
		public function __construct($mode) 
		{
			if ($mode != self::MODE_LIVE && $mode != self::MODE_DEV) 
			{
				throw new InvalidArgumentException("Invalid mode {$mode}.");
			}
			$this->mode = $mode;	
		} 
		
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
			$sql = $this->getDatabase();
	    	
			if(!$sql)
			{
				$fp = fopen("/tmp/datran.txt", "a");
            			fputs($fp, "Could not connect to database\n");
            			fclose($fp);
				$this->SaveToFile($data_array);
				return false;
			}
			$query = "INSERT INTO data (application_id, first, last, email, segmentcode, ip_address, address, city, state, zip, phone, datran_group, phone_cell, phone_work, unbanked, dob, promo_id, bb_vendor_bypass, tier, target, sold_to_amg) ".
					"VALUES ('".$this->bclean($data_array['application_id'])."', '".$this->bclean($data_array['first'])."', '".$this->bclean($data_array['last'])."', '".$this->bclean($data_array['email']).
					"', '".$this->bclean($data_array['segmentcode'])."', '".$this->bclean($data_array['IPaddress'])."', '".$this->bclean($data_array['address']).
					"', '".$this->bclean($data_array['city'])."', '".$this->bclean($data_array['state'])."', '".$this->bclean($data_array['zip']).
					"', '".$this->bclean($data_array['home_phone']).
					"', '".$this->bclean($data_array['datran_group']).
					"', '".$this->bclean($data_array['phone_cell']).
					"', '".$this->bclean($data_array['phone_work']).
					"', '".$this->bclean($data_array['unbanked']).
					"', '".$this->bclean($data_array['dob']).
       				"', '".$this->bclean($data_array['promo_id']).
       				"', '".$this->bclean($data_array['bb_vendor_bypass']).
       				"', '".$this->bclean($data_array['tier']).
       				"', '".$this->bclean($data_array['target']).
					"', '".$this->bclean($data_array['sold_to_amg'])."' )";

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
		function addLead (
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
			$phone_work = '',
			$unbanked = 0,
			$dob = '',
			$promo_id = '',
			$bb_vendor_bypass = '',
			$tier = '',
			$target ='',
			$sold_to_amg = 0
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
    			$info_array["phone_work"] = $phone_work;
    			$info_array["unbanked"] = $unbanked;
    			$info_array["dob"] = $dob;
      			$info_array["promo_id"] = $promo_id;
      			$info_array["bb_vendor_bypass"] = $bb_vendor_bypass;
      			$info_array["tier"] = $tier;
      			$info_array['target'] = $target;
      			$info_array['sold_to_amg'] = $sold_to_amg;
				$this->Datran_Send($info_array);
				return TRUE;

		}
		
		protected function getDatabase() 
		{

			$sql = new MySQL_3 ();
			

			call_user_func_array(array($sql, 'Connect'), self::$connection_params[$this->mode]);
			return $sql;
		}
		
	}

?>
