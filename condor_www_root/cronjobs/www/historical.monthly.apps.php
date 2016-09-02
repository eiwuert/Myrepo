<?php
	
	/*** 
	
		historical.monthly.apps.php
		--
		monthly job that runs on the 15th of the month and grabs the records from the previous month..
		15 days should be plenty time for sessions to be scrubbed and all that, but im beginning to hear
		that the scrub time on Db2 may be 30 days??? wtf. thats nuts.  this job is for complete
		and partial apps...
		
		if the queries seem a bit weird its because i ganked them out of 5000 lines of SQL i had left
		over from the original batch.. didnt see the need to make them look especially fancy
		
		 john hargrove ( john.hargrove@thesellingsource.com ), 12.17.2004
		
	***/



	require_once('mysql.3.php');
	require_once('debug.1.php');
	require_once('error.2.php');
	
	
	

	
	
	$sql = new MySQL_3();
	
	
	Error_2::Error_Test(
		$sql->connect(NULL, "selsds001", "sellingsource", "%selling\$_db", Debug_1::Trace_Code(__FILE__,__LINE__))
	);



		
	$query_ucl = "
		
		-- UCL -------------------------------------------------------------------------
		INSERT IGNORE INTO lead_generation.historical_completes_all
			(first_name,middle_name,last_name,email,home_phone,work_phone,best_call_time,address_1,address_city,address_state,
			address_zip,direct_deposit,income_net_pay,income_frequency,income_next_paydate_1,income_next_paydate_2,
			employer_name,employer_length,employer_type,social_security_number,driver_license_number,driver_license_state,
			bank_name,bank_routing,bank_account,is_citizen,ip_address
			,ref_01_name,ref_01_phone,ref_01_relationship
			,ref_02_name,ref_02_phone,ref_02_relationship
			,created_date)
		SELECT
			p.first_name,
			p.middle_name,
			p.last_name,
			p.email,
			p.home_phone,
			e.work_phone,
			p.best_call_time,
			r.address_1,
			r.city,
			r.state,
			r.zip,
			b.direct_deposit,
			i.net_pay,
			i.pay_frequency,
			i.paid_on_day_1,
			i.paid_on_day_2,
			e.employer,
			e.date_of_hire,
			e.income_type,
			p.social_security_number,
			p.drivers_license_number,
			r.state AS driver_license_state,
			b.bank_name,
			b.routing_number,
			b.account_number,
			'TRUE' AS is_citizen,
			c.ip_address AS ip_address,
			pc1.full_name,
			pc1.phone,
			pc1.relationship,
			pc2.full_name,
			pc2.phone,
			pc2.relationship,
			a.created_date
		FROM application a
		LEFT JOIN campaign_info c USING (application_id)
		LEFT JOIN	personal p USING (application_id)
		LEFT JOIN	residence r USING (application_id)
		LEFT JOIN	employment e USING (application_id)
		LEFT JOIN	bank_info b USING (application_id)
		LEFT JOIN	income i USING (application_id)
		LEFT JOIN	personal_contact pc1 ON (p.contact_id_1=pc1.contact_id)
		LEFT JOIN	personal_contact pc2 ON (p.contact_id_2=pc2.contact_id)
		WHERE 
			a.created_date BETWEEN '%start%' and '%end%'";
	
	$query_ucl_partial = "
		-- UCL -------------------------------------------------------------------------
		INSERT IGNORE INTO lead_generation.historical_prequals_all
			(first_name,last_name,email,home_phone,work_phone,best_call_time,address_1,address_city,address_state,
			address_zip,income_direct_deposit,income_net_pay,income_frequency,income_next_paydate_1,income_next_paydate_2,
			is_citizen,ip_address,created_date)
		SELECT
			p.first_name,
			p.last_name,
			p.email,
			p.home_phone,
			e.work_phone,
			'EVENING',
			r.address_1,
			r.city,
			r.state,
			r.zip,
			b.direct_deposit,
			i.net_pay,
			i.pay_frequency,
			i.paid_on_day_1,
			i.paid_on_day_2,
			'TRUE',
			'0.0.0.0',
			p.modified_date
		FROM campaign_info c
		LEFT JOIN personal p USING (application_id)
		LEFT JOIN	residence r USING (application_id)
		LEFT JOIN	employment e USING (application_id)
		LEFT JOIN bank_info b USING (application_id)
		LEFT JOIN	income i USING (application_id)
		WHERE 
			c.modified_date BETWEEN '%start%' AND '%end%'";


	$query_ca = "
		-- CA --------------------------------------------------------------------------	
		INSERT IGNORE INTO lead_generation.historical_completes_all
			(first_name,middle_name,last_name,email,home_phone,work_phone,best_call_time,address_1,address_city,address_state,
			address_zip,direct_deposit,income_net_pay,income_frequency,income_next_paydate_1,income_next_paydate_2,
			employer_name,employer_length,employer_type,social_security_number,driver_license_number,driver_license_state,
			bank_name,bank_routing,bank_account,is_citizen,ip_address
			,ref_01_name,ref_01_phone,ref_01_relationship
			,ref_02_name,ref_02_phone,ref_02_relationship
			,created_date)
		SELECT
			p.first_name,
			p.middle_name,
			p.last_name,
			p.email,
			p.home_phone,
			e.work_phone,
			p.best_call_time,
			r.address_1,
			r.city,
			r.state,
			r.zip,
			b.direct_deposit,
			i.net_pay,
			i.pay_frequency,
			i.paid_on_day_1,
			i.paid_on_day_2,
			e.employer,
			e.date_of_hire,
			e.income_type,
			p.social_security_number,
			p.drivers_license_number,
			r.state AS driver_license_state,
			b.bank_name,
			b.routing_number,
			b.account_number,
			'TRUE' AS is_citizen,
			c.ip_address AS ip_address,
			pc1.full_name,
			pc1.phone,
			pc1.relationship,
			pc2.full_name,
			pc2.phone,
			pc2.relationship,
			a.created_date
		FROM application a
		LEFT JOIN campaign_info c USING (application_id)
		LEFT JOIN	personal p USING (application_id)
		LEFT JOIN	residence r USING (application_id)
		LEFT JOIN	employment e USING (application_id)
		LEFT JOIN	bank_info b USING (application_id)
		LEFT JOIN	income i USING (application_id)
		LEFT JOIN	personal_contact pc1 ON (p.contact_id_1=pc1.contact_id)
		LEFT JOIN	personal_contact pc2 ON (p.contact_id_2=pc2.contact_id)
		WHERE 
			a.created_date BETWEEN '%start%' AND '%end%'";
	$query_ca_partial = "
		-- CA --------------------------------------------------------------------------	
		INSERT IGNORE INTO lead_generation.historical_prequals_all
			(first_name,last_name,email,home_phone,work_phone,best_call_time,address_1,address_city,address_state,
			address_zip,income_direct_deposit,income_net_pay,income_frequency,income_next_paydate_1,income_next_paydate_2,
			is_citizen,ip_address,created_date)
		SELECT
			p.first_name,
			p.last_name,
			p.email,
			p.home_phone,
			e.work_phone,
			'EVENING',
			r.address_1,
			r.city,
			r.state,
			r.zip,
			b.direct_deposit,
			i.net_pay,
			i.pay_frequency,
			i.paid_on_day_1,
			i.paid_on_day_2,
			'TRUE',
			'0.0.0.0',
			p.modified_date
		FROM campaign_info c
		LEFT JOIN personal p USING (application_id)
		LEFT JOIN	residence r USING (application_id)
		LEFT JOIN	employment e USING (application_id)
		LEFT JOIN bank_info b USING (application_id)
		LEFT JOIN	income i USING (application_id)
		WHERE 
			c.modified_date BETWEEN '%start%' AND '%end%'";		
	$query_pcl = "
		-- PCL -------------------------------------------------------------------------
		INSERT IGNORE INTO lead_generation.historical_completes_all
			(first_name,middle_name,last_name,email,home_phone,work_phone,best_call_time,address_1,address_city,address_state,
			address_zip,direct_deposit,income_net_pay,income_frequency,income_next_paydate_1,income_next_paydate_2,
			employer_name,employer_length,employer_type,social_security_number,driver_license_number,driver_license_state,
			bank_name,bank_routing,bank_account,is_citizen,ip_address
			,ref_01_name,ref_01_phone,ref_01_relationship
			,ref_02_name,ref_02_phone,ref_02_relationship
			,created_date)
		SELECT
			p.first_name,
			p.middle_name,
			p.last_name,
			p.email,
			p.home_phone,
			e.work_phone,
			p.best_call_time,
			r.address_1,
			r.city,
			r.state,
			r.zip,
			b.direct_deposit,
			i.net_pay,
			i.pay_frequency,
			i.paid_on_day_1,
			i.paid_on_day_2,
			e.employer,
			e.date_of_hire,
			e.income_type,
			p.social_security_number,
			p.drivers_license_number,
			r.state AS driver_license_state,
			b.bank_name,
			b.routing_number,
			b.account_number,
			'TRUE' AS is_citizen,
			c.ip_address AS ip_address,
			pc1.full_name,
			pc1.phone,
			pc1.relationship,
			pc2.full_name,
			pc2.phone,
			pc2.relationship,
			a.created_date
		FROM application a
		LEFT JOIN campaign_info c USING (application_id)
		LEFT JOIN	personal p USING (application_id)
		LEFT JOIN	residence r USING (application_id)
		LEFT JOIN	employment e USING (application_id)
		LEFT JOIN	bank_info b USING (application_id)
		LEFT JOIN	income i USING (application_id)
		LEFT JOIN	personal_contact pc1 ON (p.contact_id_1=pc1.contact_id)
		LEFT JOIN	personal_contact pc2 ON (p.contact_id_2=pc2.contact_id)
		WHERE 
			a.created_date BETWEEN '%start%' and '%end%'";
	$query_pcl_partial = "
		-- PCL -------------------------------------------------------------------------
		INSERT IGNORE INTO lead_generation.historical_prequals_all
			(first_name,last_name,email,home_phone,work_phone,best_call_time,address_1,address_city,address_state,
			address_zip,income_direct_deposit,income_net_pay,income_frequency,income_next_paydate_1,income_next_paydate_2,
			is_citizen,ip_address,created_date)
		SELECT
			p.first_name,
			p.last_name,
			p.email,
			p.home_phone,
			e.work_phone,
			'EVENING',
			r.address_1,
			r.city,
			r.state,
			r.zip,
			b.direct_deposit,
			i.net_pay,
			i.pay_frequency,
			i.paid_on_day_1,
			i.paid_on_day_2,
			'TRUE',
			'0.0.0.0',
			p.modified_date
		FROM campaign_info c
		LEFT JOIN personal p USING (application_id)
		LEFT JOIN	residence r USING (application_id)
		LEFT JOIN	employment e USING (application_id)
		LEFT JOIN bank_info b USING (application_id)
		LEFT JOIN	income i USING (application_id)
		WHERE 
			c.modified_date BETWEEN '%start%' AND '%end%'";
		
		$query_bb = "
		-- BB --------------------------------------------------------------------------
		INSERT IGNORE INTO lead_generation.historical_completes_all
			(first_name,middle_name,last_name,email,home_phone,work_phone,best_call_time,address_1,address_city,address_state,
			address_zip,direct_deposit,income_net_pay,income_frequency,income_next_paydate_1,income_next_paydate_2,
			employer_name,employer_length,employer_type,social_security_number,driver_license_number,driver_license_state,
			bank_name,bank_routing,bank_account,is_citizen,ip_address
			,ref_01_name,ref_01_phone,ref_01_relationship
			,ref_02_name,ref_02_phone,ref_02_relationship
			,created_date)
		SELECT
			p.first_name,
			p.middle_name,
			p.last_name,
			p.email,
			p.home_phone,
			e.work_phone,
			p.best_call_time,
			r.address_1,
			r.city,
			r.state,
			r.zip,
			b.direct_deposit,
			i.net_pay,
			i.pay_frequency,
			i.paid_on_day_1,
			i.paid_on_day_2,
			e.employer,
			e.date_of_hire,
			e.income_type,
			p.social_security_number,
			p.drivers_license_number,
			r.state AS driver_license_state,
			b.bank_name,
			b.routing_number,
			b.account_number,
			'TRUE' AS is_citizen,
			c.ip_address AS ip_address,
			pc1.full_name,
			pc1.phone,
			pc1.relationship,
			pc2.full_name,
			pc2.phone,
			pc2.relationship,
			a.created_date
		FROM application a
		LEFT JOIN campaign_info c USING (application_id)
		LEFT JOIN	personal p USING (application_id)
		LEFT JOIN	residence r USING (application_id)
		LEFT JOIN	employment e USING (application_id)
		LEFT JOIN	bank_info b USING (application_id)
		LEFT JOIN	income i USING (application_id)
		LEFT JOIN	personal_contact pc1 ON (p.contact_id_1=pc1.contact_id)
		LEFT JOIN	personal_contact pc2 ON (p.contact_id_2=pc2.contact_id)
		WHERE 
			a.created_date BETWEEN '%start%' and '%end%'";

	$query_bb_partial = "
		-- BB --------------------------------------------------------------------------
		INSERT IGNORE INTO lead_generation.historical_prequals_all
			(first_name,last_name,email,home_phone,work_phone,best_call_time,address_1,address_city,address_state,
			address_zip,income_direct_deposit,income_net_pay,income_frequency,income_next_paydate_1,income_next_paydate_2,
			is_citizen,ip_address,created_date)
		SELECT
			p.first_name,
			p.last_name,
			p.email,
			p.home_phone,
			p.home_phone as work_phone,
			best_call_time,
			r.address_1,
			r.city,
			r.state,
			r.zip,
			b.direct_deposit,
			i.net_pay,
			i.pay_frequency,
			i.paid_on_day_1,
			i.paid_on_day_2,
			'TRUE',
			c.ip_address,
			p.modified_date
		FROM	campaign_info c
		LEFT JOIN	personal p USING (application_id)
		LEFT JOIN	residence r USING (application_id)
		LEFT JOIN	bank_info b USING (application_id)
		LEFT JOIN	income i USING (application_id)
		WHERE c.modified_date BETWEEN '%start%' AND '%end%'";
				
	$query_d1 = "
		-- D1 -----------------------------------------------------------------------
		INSERT IGNORE INTO lead_generation.historical_completes_all
			(first_name,middle_name,last_name,email,home_phone,work_phone,best_call_time,address_1,address_city,address_state,
			address_zip,direct_deposit,income_net_pay,income_frequency,income_next_paydate_1,income_next_paydate_2,
			employer_name,employer_length,employer_type,social_security_number,driver_license_number,driver_license_state,
			bank_name,bank_routing,bank_account,is_citizen,ip_address
			,ref_01_name,ref_01_phone,ref_01_relationship
			,ref_02_name,ref_02_phone,ref_02_relationship
			,created_date)
		SELECT
			p.first_name,
			p.middle_name,
			p.last_name,
			p.email,
			p.home_phone,
			e.work_phone,
			p.best_call_time,
			r.address_1,
			r.city,
			r.state,
			r.zip,
			b.direct_deposit,
			i.net_pay,
			i.pay_frequency,
			i.paid_on_day_1,
			i.paid_on_day_2,
			e.employer,
			e.date_of_hire,
			e.income_type,
			p.social_security_number,
			p.drivers_license_number,
			r.state AS driver_license_state,
			b.bank_name,
			b.routing_number,
			b.account_number,
			'TRUE' AS is_citizen,
			c.ip_address AS ip_address,
			pc1.full_name,
			pc1.phone,
			pc1.relationship,
			pc2.full_name,
			pc2.phone,
			pc2.relationship,
			a.created_date
		FROM application a
		LEFT JOIN campaign_info c USING (application_id)
		LEFT JOIN	personal p USING (application_id)
		LEFT JOIN	residence r USING (application_id)
		LEFT JOIN	employment e USING (application_id)
		LEFT JOIN	bank_info b USING (application_id)
		LEFT JOIN	income i USING (application_id)
		LEFT JOIN	personal_contact pc1 ON (p.contact_id_1=pc1.contact_id)
		LEFT JOIN	personal_contact pc2 ON (p.contact_id_2=pc2.contact_id)
		WHERE 
			a.created_date BETWEEN '%start%' AND '%end%'";
	$query_d1_partial = "
		-- D1 --------------------------------------------------------------------------
		INSERT IGNORE INTO lead_generation.historical_prequals_all
			(first_name,last_name,email,home_phone,work_phone,best_call_time,address_1,address_city,address_state,
			address_zip,income_direct_deposit,income_net_pay,income_frequency,income_next_paydate_1,income_next_paydate_2,
			is_citizen,ip_address,created_date)
		SELECT
			p.first_name,
			p.last_name,
			p.email,
			p.home_phone,
			e.work_phone,
			'EVENING',
			r.address_1,
			r.city,
			r.state,
			r.zip,
			b.direct_deposit,
			i.net_pay,
			i.pay_frequency,
			i.paid_on_day_1,
			i.paid_on_day_2,
			'TRUE',
			'0.0.0.0',
			p.modified_date
		FROM campaign_info c
		LEFT JOIN personal p USING (application_id)
		LEFT JOIN	residence r USING (application_id)
		LEFT JOIN	employment e USING (application_id)
		LEFT JOIN bank_info b USING (application_id)
		LEFT JOIN	income i USING (application_id)
		WHERE 
			c.modified_date BETWEEN '%start%' AND '%end%'";		
	$query_ufc = "
		-- UFC -------------------------------------------------------------------
		INSERT IGNORE INTO lead_generation.historical_completes_all
			(first_name,middle_name,last_name,email,home_phone,work_phone,best_call_time,address_1,address_city,address_state,
			address_zip,direct_deposit,income_net_pay,income_frequency,income_next_paydate_1,income_next_paydate_2,
			employer_name,employer_length,employer_type,social_security_number,driver_license_number,driver_license_state,
			bank_name,bank_routing,bank_account,is_citizen,ip_address
			,ref_01_name,ref_01_phone,ref_01_relationship
			,ref_02_name,ref_02_phone,ref_02_relationship
			,created_date)
		SELECT
			p.first_name,
			p.middle_name,
			p.last_name,
			p.email,
			p.home_phone,
			e.work_phone,
			p.best_call_time,
			r.address_1,
			r.city,
			r.state,
			r.zip,
			b.direct_deposit,
			i.net_pay,
			i.pay_frequency,
			i.paid_on_day_1,
			i.paid_on_day_2,
			e.employer,
			e.date_of_hire,
			e.income_type,
			p.social_security_number,
			p.drivers_license_number,
			r.state AS driver_license_state,
			b.bank_name,
			b.routing_number,
			b.account_number,
			'TRUE' AS is_citizen,
			c.ip_address AS ip_address,
			pc1.full_name,
			pc1.phone,
			pc1.relationship,
			pc2.full_name,
			pc2.phone,
			pc2.relationship,
			a.created_date
		FROM application a
		LEFT JOIN campaign_info c USING (application_id)
		LEFT JOIN	personal p USING (application_id)
		LEFT JOIN	residence r USING (application_id)
		LEFT JOIN	employment e USING (application_id)
		LEFT JOIN	bank_info b USING (application_id)
		LEFT JOIN	income i USING (application_id)
		LEFT JOIN	personal_contact pc1 ON (p.contact_id_1=pc1.contact_id)
		LEFT JOIN	personal_contact pc2 ON (p.contact_id_2=pc2.contact_id)
		WHERE 
			a.created_date BETWEEN '%start%' AND '%end%'";
		

		
		$dbs = array(
			"olp_bb_visitor" => $query_bb,
			"olp_bb_partial" => $query_bb_partial,
			"olp_ca_visitor" => $query_ca,
			"olp_ca_partial" => $query_ca_partial,
			"olp_ucl_visitor" => $query_ucl,
			"olp_ucl_partial" => $query_ucl_partial,
			"olp_ufc_visitor" => $query_ufc,
			"olp_pcl_visitor" => $query_pcl,
			"olp_pcl_partial" => $query_pcl_partial,
			"olp_d1_visitor" => $query_d1,
			"olp_d1_partial" => $query_d1_partial,
			);
					
		print "\n Grabbing historical data for the time period: " . $date_start . " -> " . $date_end;
		
		foreach($dbs as $db=>$query)
		{
			// 3 runs
			$lmonth = strtotime("-1 month");
			$dates=
				array(
					array(
						date("Y-m-01",$lmonth),
						date("Y-m-10",$lmonth),
					),					
					array(
						date("Y-m-10",$lmonth),
						date("Y-m-20",$lmonth),
					),
					array(
						date("Y-m-20",$lmonth),
						date("Y-m-01"),
					),
				);
	
			foreach($dates as $range)
			{
				list($start,$end) = $range;
				
				print "\n ... $db ($start -> $end) - ";
				$qry = str_replace(array("%start%","%end%"),array($start,$end),$query);
				$res = $sql->query($db, $qry, Debug_1::Trace_Code(__FILE__,__LINE__));
				
				Error_2::Error_Test($res,TRUE);
				
				print $sql->Affected_Row_Count($res);		
			}
		}
		
		print "\n\n.. finished!\n\n";
?>