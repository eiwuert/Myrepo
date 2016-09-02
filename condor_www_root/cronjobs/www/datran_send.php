<?php
	$global_domains = array("111cash.com", "internetpayday.com", "500dollaradvance.com", "aaapayday.com", "americacashadvance.com", "
        americapaydayadvance.com", "americapaydayloan.com", "cashadvance2000.com", "cashadvance500.com", "cash-advance-city.com", "
        cashadvancecity.com", "cashadvanceman.com", "cashadvancenow.com", "cashadvanceusa.com", "cashloans4u.com", "citizens-cash-advance.com", "
        citizenscashadvance.com", "dollaradvance.com", "equityloans4u.com", "highdeltahunts.com", "lightening-cash.com", "lighteningcash.com", "
        moneyloans4u.com", "mypaydaytoday.com", "national-payday-loan.com", "nationalpaydayloan.com", "nationscashadvance.com", "
        paydaycash2000.com", "payday-cash-now.com", "paydaycashnow.com", "paydaycity.com", "paydayfromhome.com", "paydayloanman.com", "
        paydayloans4u.com", "payday-loan-usa.com", "paydayloanusa.com", "peoplespayday.com", "prestacash.com", "thepaydayloanplace.com", "
        unitedchecks.com", "sellingsource.com", "123onlinecash.com", "500fastcash.com", "cashbackvalues.com", "casinoratingclub.com", "
        driveawayloans.com", "epointmarketing.com", "equity1auto.com", "equityoneauto.com", "essenceofjewels.com", "expressgoldcard.com", "
        extremetrafficteam.com", "fast-funds-online.com", "fastcashsupport.com", "fcpcard.com", "financialhosting.com", "greatweboffers.com", "
        kingtutspub.com", "leadershipservices.com", "management.soapdataserver.com", "mbcash.com", "my-payday-loan.com", "mycash-online.com", "
        oledirect.com", "oledirect2.com", "oneclickcash.com", "partnerweekly.com", "preferredcashloans.com", "safetyprepared.com", "
        smartshopperonline.com", "ssbusadmin.com", "steaksofstlouis.com", "telewebcallcenter.com", "telewebmarketing.com", "
        unitedcashloans.com", "usfastcash.com", "yourcashnetwork.com", "yourfastcash.com", "flyfone.com", "swiftglobaltelecom.com", "
        swiftphone.com", "123cheapcigarette.com", "webfastcash.com", "fastcashpreferred.com", "louisianapaydayloans.com", "tssmasterd.com
        imagedataserver.com", "homelandproducts.net", "fastcashcard.com", "500fastcash.com", "sirspeedycash.com", "123onlinecash.com", "
        thesellingsource.com", "xenlog.com", "xenlog1.com", "pwccomm.com", "bonustravelcoupons.com", "gasreward.com", "i-cami.com", "peoplespayday.com",
		"eblasterpro.com");

	require_once("/virtualhosts/lib/mysql.3.php");
	require_once("/virtualhosts/lib/error.2.php");	
/*Functions ****************************************/	
	function Datran_Send($data_array)
	{
	   	  $data_array['categoryid'] = 44;
		if( ($data_array['state']=="CA") || ($data_array['state']=="ca") ) return false;
		  $data = array();
	      $data['f'] = $data_array["first"];
	      $data['l'] = $data_array["last"];
		  $data['e'] = $data_array["email"];
		  //$data['p'] = "SELSRC";
		  $data['p'] = "PWEEK";
		  $data['d'] = (strpos($data_array['segmentcode'], "http") !== false)? $data_array['segmentcode'] : "http://".$data_array['segmentcode'];
	      $data['IP'] = $data_array['ip_address'];
	      $data['a'] = $data_array['address'];
	      $data['h'] = $data_array['address2'];
	      $data['c'] = $data_array['city'];
		  $data['s'] = $data_array['state'];
	      $data['z'] = $data_array['zip'];
	      $data['t'] = $data_array['home_phone'];
	      $data['x'] = "USA";
	      $data['b'] = $data_array['dob'];
	      $data['g'] = $data_array['gender'];
	      $data['n'] = $data_array['categoryid'];
	      $data['r'] = "1";
	      
			foreach ($data as $null_check)
			{
				if(strlen($null_check) == 0)
				{
					$data['r'] = 1;
					break;
				}else $data['r'] = 2;
			}
	      
	            
	      foreach ($data as $key=>$value) {
	          $get .= $key."=".str_replace(" ", "%20",trim($value))."&";
	      }
	      //$get = "http://209.133.76.44/c.aspx?".substr($get, 0, -1);
	      $get = "http://selsc.superautoresponders.com/c.aspx?".substr($get, 0, -1);
	      echo $get."\n";
	      $response = file($get);
		  return true;
	}
	function compare($str1, $str2)
	{		
		return (strcasecmp(trim($str1), trim($str2)))? false : true;		
	}
	
	function CanSendData($row)
	{
		global $sql, $global_domains;
		$domain = substr($row['email'], strpos($row['email'], "@")+1);
		if (in_array($domain, $global_domains))
		{
			return false;
		}
		if (compare($row->state, "CA")) {
			return false;			
		}
		$query = "SELECT count(*) as count FROM nms_funded WHERE email='".$row['email']."'";
		$result = $sql->Query ("scrubber", $query);
		if (Error_2::Error_Test ($result))
		{
			var_dump($result);
		}
		$row = $sql->Fetch_Array_Row($result);
		if($row['count']>0)
		{			
			return false;
		}		
		return true;		
	}
	
	function UpdateDatran($id, $column, $value)
	{
		global $sql;
		$value++;
		$query = "UPDATE datran set $column = $value, date_sent = '".date("Y-m-d H:i:s")."'  WHERE id = $id";
		echo $query."\n";		
		$sql->Query("oledirect2", $query);
		if (Error_2::Error_Test ($result))
		{
			var_dump($result);
			die("Could not update datran dabase for row $id\n");
		}
	}
		
/*Main ********************************************/

	$sent_array = array();
	$denied_array = array();
	$select_date = date("YmdHis", time()-(3600*24*2));
	$sql = new MySQL_3 (); 
	$sql->Connect ("BOTH", "selsds001", "sellingsource", 'password');
	
	if(!$sql)
	{
		$fp = fopen("/tmp/datran.txt", "a");
    	fputs($fp, "Could not connect to database\n");
		return false;
	}
	$query = "SELECT * FROM datran WHERE date_create < '$select_date' and sent='0' and denied='0'";
	//$query = "SELECT * FROM datran limit 100";
	echo $query."\n";
			
	$result = $sql->Query ("oledirect2", $query);
	if (Error_2::Error_Test ($result))
	{
		var_dump($result);
		
	}
	if ($sql->Row_Count($result)==0) {
			die("Nothing to send to Datran\n");
		}	
	
	while ($row = $sql->Fetch_Array_Row($result)) 
	{		
		if(CanSendData($row))
		{
			 Datran_Send($row);
			 UpdateDatran($row['id'], 'sent', $row['sent']);			
		} else{
			 echo "Don't send ".$row['email'].".\n";
			 UpdateDatran($row['id'], 'denied', $row['denied']);			 
		}
	//	print_r($row);
	}
	  
?>