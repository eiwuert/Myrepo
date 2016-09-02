<?php
	require_once ("prpc/client.php");
	require_once ("mysql.3.php");

	class epm_collect
	{

	    function SaveToFile($data_array)
	    {
	    	$fp = fopen("/tmp/Datran".date("Ymd").".dat");
			fputs($fp, serialize($data_array)."\n");
			fclose($fp);
	    }

	    function bclean($str)
	    {
	    	return mysql_escape_string(trim($str));
	    }

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
			$query = "INSERT INTO data (first, last, email, segmentcode, ip_address, address, city, state, zip, phone, datran_group, promo_id) ".
					"VALUES ('".$this->bclean($data_array['first'])."', '".$this->bclean($data_array['last'])."', '".$this->bclean($data_array['email']).
					"', '".$this->bclean($data_array['segmentcode'])."', '".$this->bclean($data_array['IPaddress'])."', '".$this->bclean($data_array['address']).
					"', '".$this->bclean($data_array['city'])."', '".$this->bclean($data_array['state'])."', '".$this->bclean($data_array['zip']).
					"', '".$this->bclean($data_array['home_phone']).
					"', '".$this->bclean($data_array['datran_group']).
                    "', '".$this->bclean($data_array['promo_id'])."')";

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

		function epm_collect ($email, $name_first, $name_last, $site_id, $list_id, $ip_address, $debug = 0, $datran_group = 1)
		{
			if ($_SESSION['config']->mode !== "LIVE" || strpos($email,"sellingsource")!==false) return true;

			$trace_level = 32;

			$info_array["email"]=$email;
			$info_array["first"]=$name_first;
			$info_array["last"]=$name_last;
			$info_array["list_id"]=$list_id;
			$info_array["site_id"]=$site_id;
			$info_array["IPaddress"]=$ip_address;
			$info_array["licensekey"]= $_SESSION['config']->license;
			$info_array["ole_version"]= "ole_mail.2.php";

			//$soap_client = new Prpc_Client ("prpc://ole.1.soapdataserver.com/", $debug, $trace_level);
			//$soap_client->collect($info_array);

			$info_array["datran_group"]=$datran_group;
			$info_array['address'] = $_SESSION['data']['home_street']." ".$_SESSION['data']['home_unit'];
  			$info_array['city'] = $_SESSION['data']['home_city'];
    		$info_array['state'] = $_SESSION['data']['home_state'];
       		$info_array['zip'] = $_SESSION['data']['home_zip'];
        	$info_array['segmentcode'] = $_SESSION['config']->site_name;
        	$info_array['home_phone'] = $_SESSION['data']['phone_home'];
            $info_array['promo_id'] = $_SESSION['data']['promo_id'];
			$this->Datran_Send($info_array);
			return TRUE;
		}
	}

?>
