<?php

	// Make the cron somewhat intelligent, check to make sure there arent
	//   any completed orders for the same customer (maybe based on phone
	//   number?), and dont insert duplicate failed orders.


  #####################################################################
  ## Cronjob for Landing Pages
  ## Runs every hour, pulls list of landing pages that send
  ## to teleweb, and sends off customers who have not completed
  ## the application process.
  ##
  ## -Matt Piper (matt.piper@thesellingsource.com), 4-27-2005
  #####################################################################


	## Includes/Defines
	include_once('/virtualhosts/lib/mysql.3.php');
	## Our landing page database sql object
	$lp_sql = new MySQL_3();
	$lp_sql->Connect("BOTH", "selsds001", "sellingsource", "%selling\$_db", Debug_1::Trace_Code(__FILE__,__LINE__));
	## Our teleweb database sql object
	$tw_sql = new MySQL_3();
	$tw_sql->Connect("BOTH", "selsds001", "sellingsource", "%selling\$_db", Debug_1::Trace_Code(__FILE__,__LINE__));
	
	
	## Start end end date/time for the time period we want to pull.
	$start = date("YmdH0000", strtotime("-2 hours"));
	$end = date("YmdH0000", strtotime("-1 hour"));


	## Test mode will use the rc_ databases.
	define('TEST_MODE', FALSE);

	## Figure out what databases we need to use.
	$lp_config_db = TEST_MODE ? "rc_lp_config" : "lp_config";
	$lp_process_db = TEST_MODE ? "rc_lp_process" : "lp_process";
	$lp_visitor_db = TEST_MODE ? "rc_lp_visitor" : "lp_visitor";
	$teleweb_db = TEST_MODE ? "teleweb" : "teleweb";

	$query = "
		SELECT
			DISTINCT process.process_id, process.landing_page_id,
			config.site_url, 
			personal.visitor_id,
			personal.name_first, personal.name_last,
			personal.phone_home, personal.email,
			address.address_1, address.address_2,
			address.city, address.state, address.zip,
 			teleweb.landing_page_id as tw_landing_page_id, teleweb.project_id as tw_project_id
		FROM
			".$lp_config_db.".config as config
		JOIN
			".$lp_process_db.".process as process on (process.landing_page_id = config.landing_page_id)
		LEFT JOIN
			".$lp_visitor_db.".personal as personal on (personal.visitor_id = process.visitor_id)
		LEFT JOIN
			".$lp_visitor_db.".address as address on (address.visitor_id = process.visitor_id)
		LEFT JOIN
			".$teleweb_db.".landing_page as teleweb on (teleweb.lp_id_map = process.landing_page_id)
		WHERE
			config.send_to_teleweb = 'Y'
			AND process.process_completed = 'N'
			AND process.sent_to_teleweb = 'N'
			AND personal.created_date between '$start' and '$end'
			AND personal.phone_home IS NOT NULL
			AND personal.name_first NOT LIKE '%TEST'
			AND personal.name_first NOT LIKE 'TEST%'
			AND personal.name_first != 'test'
			AND personal.name_first != ''
			AND personal.name_last NOT LIKE '%TEST'
			AND personal.name_last NOT LIKE 'TEST%'
			AND personal.name_last != 'TEST'
			AND personal.name_last != ''
			AND teleweb.lp_id_map != ''
			AND teleweb.project_id != ''
	";
	//echo "<pre>";
	//echo $query . "<BR><BR>";
	//echo "</pre>";
	$lp_to_send = $lp_sql->Query($lp_config_db, $query, Debug_1::Trace_Code(__FILE__,__LINE__));
	
	while ($row = $lp_sql->Fetch_Object_Row($lp_to_send))
  {
		/*echo $row->process_id . " | ";
		echo $row->landing_page_id . " | ";
		echo $row->site_url . " | ";
		echo $row->visitor_id . " | ";
		echo $row->name_first . " | ";
		echo $row->name_last . " | ";
		echo $row->phone_home . " | ";
		echo $row->email . " | ";
		echo $row->address_1 . " | ";
		echo $row->address_2 . " | ";
		echo $row->city . " | ";
		echo $row->state . " | ";
		echo $row->zip . " | ";
		echo $row->tw_landing_page_id . " | ";
		echo $row->tw_project_id . "<BR>";*/
		
		
		## Insert data into teleweb, update sent_to_teleweb='Y'
		$tw_query = "
			INSERT INTO
				customer
			SET
	      project_id=" . $row->tw_project_id . ",
	      landing_page_id=" . $row->tw_landing_page_id . ",
	      company_customer_id='".$row->visitor_id."',
	      created_date=NOW(),
	      url='".$row->site_url."',
	      first_name='".$row->name_first."',
	      last_name='".$row->name_last."',
	      email='".$row->email."',
	      home_phone='".$row->phone_home."'
		";
		//echo "<br>".$tw_query;
		$tw_result = $tw_sql->Query($teleweb_db, $tw_query, Debug_1::Trace_Code(__FILE__,__LINE__));
		
		
		## Update the sent_to_teleweb status to 'Y' so we dont send their information again.
		$status_update_query = "
			UPDATE
				process
			SET
				sent_to_teleweb='Y'
			WHERE
				process_id=" . $row->process_id;
		//echo "<br>".$status_update_query;
		$status_update_result = $tw_sql->Query($lp_process_db, $status_update_query, Debug_1::Trace_Code(__FILE__,__LINE__));				
	}
?>