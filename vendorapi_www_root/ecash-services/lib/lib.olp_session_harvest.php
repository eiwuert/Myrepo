<?php

/****

a library for copying partial sessions from an olp_??_visitor.session table into our own db for our own uses

CULPRIT:
	pizza

TODO:
	does not correctly handle the unlikely yet possible circumstance of seeing the same
	session again... if someone goes away for 61 mins then comes back and we catch them before they
	complete or they never do...

****/

require_once("diag.1.php");
require_once("mysql.3.php");

class OLP_Harvest
{
	var $sql;
	var $db_from;
	var $db_to;
	var $ts_from;
	var $ts_until;

	var $_sess = array(); # grrrr, design of olp db makes it very difficult to optimize sql statements
	var $_min_age = 3600; # one hour age
	var $_max_age = 86400; # from within the last 24 hours
	var $_logfile = "/tmp/diag.olp_harvest.log";

	function OLP_Harvest(&$sql)
	{
		assert(is_object($sql) && is_a($sql, "MySQL_3"));

		$this->sql = $sql;
	}

	function Harvest($db_from, $db_to, $ts_from=NULL, $ts_until=NULL)
	{
		assert(is_string($db_from) && "" != $db_from);
		assert(is_string($db_to) && "" != $db_to);
		assert(is_numeric($ts_from) || NULL === $ts_from);
		assert(is_numeric($ts_until) || NULL === $ts_until);

		# reset qualified count
		$this->_qualified = 0;

		# turn Diag:: off for the whole harvesting process
		Diag::Push(true);

		if (NULL === $ts_from)
		{
			$ts_from = time() - $this->_max_age;
		}

		if (NULL === $ts_until)
		{
			$ts_until = time() - $this->_min_age;
		}

		$this->db_from = $db_from;
		$this->db_to = $db_to;
		$this->ts_from = $ts_from;
		$this->ts_until = $ts_until;

		Diag::Out("this->db_from: {$this->db_from}, this->db_to: {$this->db_to}, this->ts_from:{$this->ts_from} (" . 
			date("Y-m-d H:i:s T", $this->ts_from) . ") this->ts_until: {$this->ts_until} (" .
			date("Y-m-d H:i:s T", $this->ts_until) . ")"
		);

		Diag::Pop();

		$this->_Transfer_Sessions();

	}

	/******************* internal methods **************************/

	# figure out what the latest session we already fetched from this table was
	# to minimize the data we return in the query
	function _Latest_Session_Fetched($table)
	{
		$ts = 0;
		$db_from = mysql_escape_string($this->db_from);
		$table_from = mysql_escape_string($table);

		$query = <<<SQL
		SELECT
			ts_until
		FROM
			harvest_log
		WHERE
			db_from = '{$db_from}'
		AND
			table_from = '{$table_from}'
		ORDER BY
			id DESC
SQL;

		$rs = $this->sql->Query($this->db_to, $query);

		Error_2::Error_Test($rs, true);

		if (0 == $this->sql->Row_Count($rs))
		{
			# return lowest legal mysql timestamp
			$ts = '000000000000';
		}
		else
		{
			$row = $this->sql->Fetch_Array_Row($rs);
			$ts = $row["ts_until"];
		}

		Diag::Out("_Last_Session_Fetched: $ts");
		
		return $ts;
	}

	# record the query and result of a single session table
	function _Write_Log($table_from, $qualified=0, $new=0, $updated=0)
	{
		assert(is_string($table_from) && "" != $table_from);
		assert($qualified == $new + $updated);

		# prepare values
		$ts_run = mysql_escape_string(date("YmdHis"));
		$ts_from = mysql_escape_string(date("YmdHis", $this->ts_from));
		$ts_until = mysql_escape_string(date("YmdHis", $this->ts_until));
		$db_from = mysql_escape_string($this->db_from);
		$table_from = mysql_escape_string($table_from);
		$qualified = intval($qualified);
		$new = intval($new);
		$updated = intval($updated);

		# secksie query
		$query = <<<SQL
		INSERT INTO harvest_log (
			id
			,ts_run
			,ts_from
			,ts_until
			,db_from
			,table_from
			,sessions_qualified
			,sessions_new
			,sessions_updated
		) VALUES (
			NULL
			,NOW()
			,'$ts_from'
			,'$ts_until'
			,'$db_from'
			,'$table_from'
			,$qualified
			,$new
			,$updated
		)
SQL;

		Diag::Out("log insert:\n$query");
		#Diag::DumpToFile($query, "log insert");

		Error_2::Error_Test(
			$this->sql->Query($this->db_to, $query),
			true
		);

		return $this->sql->Insert_Id();
	}

	# record a session id so we don't fetch it again
	function _Record_Session_Id($sess_id)
	{
		assert(is_string($sess_id));

		if (NULL === $sess_id || "" == $sess_id)
		{
			# invalid
			return FALSE;
		}

		# prepare values
		$sess_id = mysql_escape_string($sess_id);

		# secksie query
		$query = <<<SQL
		INSERT INTO harvest_session_log (
			session_id
		) VALUES (
			'$sess_id'
		)
SQL;

		Diag::Out("record session id:\n$query");
		#Diag::DumpToFile($query, "record session id");

		$rs = $this->sql->Query($this->db_to, $query);

		# whoops, we've already seen that session before!
		# we don't want to die, we just want to signal that this session is a dupe
		if (is_a($rs, "Error_2") && 1062 == @$rs->sql_errno)
		{
			return false;
		}

		# if we get any errors other than a 'duplicate key' we do want to die
		Error_2::Error_Test(
			$rs,
			true
		);

		# the session has not been seen and the insert was a success
		return true;
	}

	# record a session id so we don't fetch it again
	function _Record_Application_Id($app_id)
	{
		if (NULL === $app_id || "" == $app_id)
		{
			# invalid
			return FALSE;
		}

		# prepare values
		$app_id = mysql_escape_string($app_id);

		# secksie query
		$query = <<<SQL
		INSERT INTO harvest_application_log (
			application_id
		) VALUES (
			'$app_id'
		)
SQL;

		Diag::Out("record application id:\n$query");
		#Diag::DumpToFile($query, "record application id");

		$rs = $this->sql->Query($this->db_to, $query);

		# whoops, we've already seen that application before!
		# we don't want to die, we just want to signal that this application is a dupe
		if (is_a($rs, "Error_2") && 1062 == @$rs->sql_errno)
		{
			return false;
		}

		# if we get any errors other than a 'duplicate key' we do want to die
		Error_2::Error_Test(
			$rs,
			true
		);

		# the application has not been seen and the insert was a success
		return true;
	}

	function _Fetch_Session_Tables()
	{
		Diag::Push(true);

		$tables = $this->sql->Get_Table_List($this->db_from, Debug_1::Trace_Code(__FILE__, __LINE__));
		$session_tables = array();
		reset($tables);
		while (list($table, $true) = each($tables))
		{
			if (preg_match('/^session_(site|[[:xdigit:]]{1})$/', strtolower($table), $m))
			{
				# TRUE if compression (blackbox uses session_[hex], others use regular sesison), false  for a regular session table
				$session_tables[$table] = ($m[1] != "site");
			}
		}
		Diag::Dump($session_tables, "session tables from db '{$this->db_from}'");
		#Diag::DumpToFile($session_tables, "session tables from {$this->db_from}");

		Diag::Pop();

		return $session_tables;
	}

	function _Fetch_Sessions_Compressed($table)
	{

		$ts_early = max($this->_Latest_Session_Fetched($table), date('YmdHis', $this->ts_from));
		$ts_latest = date('YmdHis', $this->ts_until);

		Diag::Out("_Fetch_Sessions_Compressed() ts_early: '$ts_early', ts_latest: '$ts_latest'");

		$query = <<<SQL
		SELECT
			{$table}.session_id
			,compression
			,session_info
		FROM
			{$table}
		LEFT JOIN
			{$this->db_to}.harvest_session_log l
		ON
			$table.session_id = l.session_id
		WHERE
		(
			date_modified > '{$ts_early}'
			AND
			date_modified <= '{$ts_latest}'
		)
		AND
			l.session_id IS NULL
SQL;

		Diag::Out("fetch sessions compressed:\n$query");
		#Diag::DumpToFile($query, "fetch sessions compressed");

		$rs = $this->sql->Query($this->db_from, $query);

		Error_2::Error_Test($rs, true);

		$rows = array();

		while (false !== ($row = $this->sql->Fetch_Array_Row($rs)))
		{
			switch ($row["compression"])
			{
			case "gz":
				$row["session_info"] = gzuncompress($row["session_info"]);
				break;
			case "bz":
				$row["session_info"] = bzdecompress($row["session_info"]);
				break;
			}
			# key value pair
			$rows[$row["session_id"]] = $row["session_info"];
		}

		$this->sql->Free_Result($rs);

		return $rows;
	}

	# fetch qualifying sessions from a single table
	# have to account for 2 types of tables, standard and blackbox's compressed sessions
	function _Fetch_Sessions($table, $compressed=false)
	{

		Diag::Push(true);

		if ($compressed)
		{
			return $this->_Fetch_Sessions_Compressed($table);
		}

		$ts_early = max($this->_Latest_Session_Fetched($table), date('YmdHis', $this->ts_from));
		$ts_latest = date('YmdHis', $this->ts_until);

		Diag::Out("_Fetch_Sessions() ts_early: '$ts_early', ts_latest: '$ts_latest'");

		$query = <<<SQL
		SELECT
			{$table}.session_id
			,session_info
		FROM
			{$table}
		LEFT JOIN
			{$this->db_to}.harvest_session_log l
		ON
			{$table}.session_id = l.session_id
		WHERE
			l.session_id IS NULL
		AND
		(
			modifed_date > '{$ts_early}'
			AND
			modifed_date <= '{$ts_latest}'
		)
SQL;

		Diag::Out("fetch sessions:\n$query");

		#Diag::DumpToFile($query, "fetch sessions");

		$rs = $this->sql->Query($this->db_from, $query, MYSQL_3_QUERY_MASTER, false);

		Error_2::Error_Test($rs, true);

		$rows = array();

		while (false !== ($row = $this->sql->Fetch_Array_Row($rs)))
		{
			# key value pair
			$rows[$row["session_id"]] = $row["session_info"];
		}


		Diag::Out("number of rows returned: '" . count($rows) . "'");
		Diag::Pop();

		return $rows;
	}

	# decode session and build 
	function _Process_Session($id, &$data)
	{
		@session_destroy();
		@session_start(); # warns about failure to set cookies
		assert(session_decode($data)); # create $_SESSION

		# bail if they've completed the application
		#Diag::Dump($_SESSION, "_SESSION");

		# if this session doesn't contain basic elements of an olp session, bail now
		if ("" == $id || "" == @$_SESSION["application_id"])
		{
			return false;
		}

		# save data from session to internal buffer so we
		# can insert it into the db later
		$this->_sess = array(
			"session_id" => $id
			,"application_id" => @$_SESSION["application_id"]
			##### bank_info
			,"bank_name" => @$_SESSION["data"]["bank_name"]
			,"account_number" => @$_SESSION["data"]["bank_account"]
			,"routing_number" => @$_SESSION["data"]["bank_aba"]
			,"check_number" => @$_SESSION["data"]["check_number"]
			,"direct_deposit" => (@$_SESSION["data"]["income_direct_deposit"] == 'TRUE' ? 'TRUE' : 'FALSE')
			##### campaign_info
			,"promo_id" => @$_SESSION["config"]->promo_id
			,"pro_sub_code" => @$_SESSION["config"]->promo_sub_code
			,"license_key" => @$_SESSION["config"]->license
			,"ip_address" => @$_SESSION["data"]["client_ip_address"]
			,"url" => @$_SESSION["config"]->site_name
			##### employment
			#### address_id ?!?!?!
			,"employer" => @$_SESSION["data"]["employer_name"]
			,"work_phone" => @$_SESSION["data"]["phone_work"]
			,"work_ext" => @$_SESSION["data"]["work_ext"]
			,"title" => @$_SESSION["data"]["title"]
			,"shift" => @$_SESSION["data"]["shift"]
			,"date_of_hire" => @$_SESSION["data"]["date_of_hire"]
			,"income_type" => @$_SESSION["data"]["income_type"]
			##### income
			,"net_pay" => @$_SESSION["data"]["income_monthly_net"]
			,"pay_frequency" => @$_SESSION["data"]["paydate"]["frequency"]
			#,"paid_on_day_1" => @$_SESSION["data"][""]
			#,"paid_on_day_2" => @$_SESSION["data"][""]
			# mysql timestamps: YYYYmmddHHiiss
			//,"pay_date_1" => @$_SESSION["data"]["income_date1_y"] . @$_SESSION["data"]["income_date1_m"] . @$_SESSION["data"]["income_date1_d"] . "000000"
			//,"pay_date_2" => @$_SESSION["data"]["income_date2_y"] . @$_SESSION["data"]["income_date2_m"] . @$_SESSION["data"]["income_date2_d"] . "000000"
			//,"pay_date_3" => @$_SESSION["data"]["income_date3_y"] . @$_SESSION["data"]["income_date3_m"] . @$_SESSION["data"]["income_date3_d"] . "000000"
			//,"pay_date_4" => @$_SESSION["data"]["income_date4_y"] . @$_SESSION["data"]["income_date4_m"] . @$_SESSION["data"]["income_date4_d"] . "000000"
			,"pay_date_1" => str_replace("-", "", @$_SESSION["data"]["paydates"]["0"]) . "000000"						
			,"pay_date_2" => str_replace("-", "", @$_SESSION["data"]["paydates"]["1"]) . "000000"	
			,"pay_date_3" => str_replace("-", "", @$_SESSION["data"]["paydates"]["2"]) . "000000"	
			,"pay_date_4" => str_replace("-", "", @$_SESSION["data"]["paydates"]["3"]) . "000000"	
			##### paydate 
			,"paydate_model_id" => @$_SESSION["paydate_model"]["model_name"]
			,"day_of_week" => @$_SESSION["paydate_model"]["day_of_week"]
			,"next_paydate" => @$_SESSION["paydate_model"]["next_pay_date"]
			,"day_of_month_1" => @$_SESSION["paydate_model"]["day_int_one"]
			,"day_of_month_2" => @$_SESSION["paydate_model"]["day_int_two"]
			,"week_1" => @$_SESSION["paydate_model"]["week_one"]
			,"week_2" => @$_SESSION["paydate_model"]["week_two"]
			##### personal
			,"first_name" => @$_SESSION["data"]["name_first"]
			,"middle_name" => @$_SESSION["data"]["name_middle"]
			,"last_name" => @$_SESSION["data"]["name_last"]
			,"home_phone" => @$_SESSION["data"]["phone_home"]
			,"cell_phone" => @$_SESSION["data"]["phone_cell"]
			,"fax_phone" => @$_SESSION["data"]["phone_fax"]
			,"email" => @$_SESSION["data"]["email_primary"]
			,"alt_email" => @$_SESSION["data"]["email_alternate"]
			,"date_of_birth" => @$_SESSION["data"]["date_dob_y"]."-".@$_SESSION["data"]["date_dob_m"]."-".@$_SESSION["data"]["date_dob_d"]
			#,"contact_id_1" => NULL
			#,"contact_id_2" => NULL
			,"social_security_number" => @$_SESSION["data"]["social_security_number"]
			,"drivers_license_number" => @$_SESSION["data"]["state_id_number"]
			,"best_call_time" => @$_SESSION["data"]["best_call_time"]
			##### personal_contact
			,"full_name_1" => @$_SESSION["data"]["ref_01_name_full"]
			,"phone_1" => @$_SESSION["data"]["ref_01_phone_home"]
			,"relationship_1" => @$_SESSION["data"]["ref_01_relationship"]
			,"full_name_2" => @$_SESSION["data"]["ref_02_name_full"]
			,"phone_2" => @$_SESSION["data"]["ref_02_phone_home"]
			,"relationship_2" => @$_SESSION["data"]["ref_02_relationship"]
			##### residence
			,"residence_type" => @$_SESSION["data"]["residence_type"]
			,"length_of_residence" => @$_SESSION["data"]["residence_length"]
			,"address_1" => @$_SESSION["data"]["home_street"]
			,"address_2" => ""
			,"apartment" => @$_SESSION["data"]["home_unit"]
			,"city" => @$_SESSION["data"]["home_city"]
			,"state" => @$_SESSION["data"]["home_state"]
			,"zip" => @$_SESSION["data"]["home_zip"]
		);

		# session passes basic tests and is now in $this->_sess
		return true;

	}

	function _Insert_Personal_Contact($app_id, $name, $phone, $rel)
	{
		$app_id = mysql_escape_string($app_id); # problem with NULL... blah
		$name = mysql_escape_string($name);
		$phone = mysql_escape_string($phone);
		$rel = mysql_escape_string($rel);

		$query = <<<SQL
		INSERT IGNORE INTO personal_contact (
			contact_id
			,application_id
			,full_name
			,phone
			,relationship
		) VALUES (
			NULL
			,'$app_id'
			,'$name'
			,'$phone'
			,'$rel'
		)
SQL;

		Diag::Out("insert into personal_contact:\n$query");
		#Diag::DumpToFile($query, "insert into ");

		$rs = $this->sql->Query($this->db_to, $query);

		Error_2::Error_Test($rs, true);

		return $this->sql->Insert_Id();

	}


	function _Insert_Bank_Info()
	{

		$application_id = mysql_escape_string($this->_sess["application_id"]);
		$bank_name = mysql_escape_string($this->_sess["bank_name"]);
		$account_number = mysql_escape_string($this->_sess["account_number"]);
		$routing_number = mysql_escape_string($this->_sess["routing_number"]);
		$check_number = mysql_escape_string($this->_sess["check_number"]);
		$direct_deposit = mysql_escape_string($this->_sess["direct_deposit"]);

		$query = <<<SQL
		INSERT IGNORE INTO bank_info (
			application_id
			,bank_name
			,account_number
			,routing_number
			,check_number
			,direct_deposit
		) VALUES (
			'$application_id'
			,'$bank_name'
			,'$account_number'
			,'$routing_number'
			,'$check_number'
			,'$direct_deposit'
		)
SQL;

		Diag::Out("insert into bank_info:\n$query");
		#Diag::DumpToFile($query, "insert into bank_info");

		$rs = $this->sql->Query($this->db_to, $query);

		if ($this->sql->Get_Errno() == 1062)
		{
			Diag::Out("duplicate inserting into bank...");
			return;
		}

		Error_2::Error_Test($rs, true);

		return $this->sql->Insert_Id();
	}

	function _Insert_Campaign_Info()
	{

		$application_id = mysql_escape_string($this->_sess["application_id"]);
		$promo_id = mysql_escape_string($this->_sess["promo_id"]);
		$promo_sub_code = mysql_escape_string($this->_sess["promo_sub_code"]);
		$license_key = mysql_escape_string($this->_sess["license_key"]);
		$ip_address = mysql_escape_string($this->_sess["ip_address"]);
		$url = mysql_escape_string($this->_sess["url"]);

		$query = <<<SQL
		INSERT IGNORE INTO campaign_info (
			campaign_info_id
			,application_id
			,modified_date
			,promo_id
			,promo_sub_code
			,license_key
			,created_date
			,active
			,ip_address
			,url
		) VALUES (
			NULL
			,'$application_id'
			,NOW()
			,'$promo_id'
			,'$promo_sub_code'
			,'$license_key'
			,'000000000000'
			,'FALSE'
			,'$ip_address'
			,'$url'
		)
SQL;

		Diag::Out("insert into campaign_info:\n$query");
		#Diag::DumpToFile($query, "insert into campaign_info");

		$rs = $this->sql->Query($this->db_to, $query);

		Error_2::Error_Test($rs, true);

		return $this->sql->Insert_Id();
	}

	function _Insert_Employment($address_id)
	{

		$application_id = mysql_escape_string($this->_sess["application_id"]);
		$employer = mysql_escape_string($this->_sess["employer"]);
		$work_phone = mysql_escape_string($this->_sess["work_phone"]);
		$work_ext = mysql_escape_string($this->_sess["work_ext"]);
		$title = mysql_escape_string($this->_sess["title"]);
		$shift = mysql_escape_string($this->_sess["shift"]);
		$date_of_hire = mysql_escape_string($this->_sess["date_of_hire"]);
		$income_type = mysql_escape_string($this->_sess["income_type"]);

		$query = <<<SQL
		INSERT IGNORE INTO employment (
			application_id
			,employer
			,address_id
			,work_phone
			,work_ext
			,title
			,shift
			,date_of_hire
			,income_type
		) VALUES (
			'$application_id'
			,'$employer'
			,$address_id
			,'$work_phone'
			,'$work_ext'
			,'$title'
			,'$shift'
			,'$date_of_hire'
			,'$income_type'
		)
SQL;

		Diag::Out("insert into employment:\n$query");
		#Diag::DumpToFile($query, "insert into employment");

		$rs = $this->sql->Query($this->db_to, $query);

		Error_2::Error_Test($rs, true);

		return $this->sql->Insert_Id();
	}

	function _Insert_Income()
	{

		$application_id = mysql_escape_string($this->_sess["application_id"]);
		$net_pay = mysql_escape_string($this->_sess["net_pay"]);
		$pay_frequency = mysql_escape_string($this->_sess["pay_frequency"]);
		$paid_on_day_1 = mysql_escape_string($this->_sess["paid_on_day_1"]);
		$paid_on_day_2 = mysql_escape_string($this->_sess["paid_on_day_2"]);
		$pay_date_1 = mysql_escape_string($this->_sess["pay_date_1"]);
		$pay_date_2 = mysql_escape_string($this->_sess["pay_date_2"]);
		$pay_date_3 = mysql_escape_string($this->_sess["pay_date_3"]);
		$pay_date_4 = mysql_escape_string($this->_sess["pay_date_4"]);

		$query = <<<SQL
		INSERT IGNORE INTO income (
			application_id
			,modified_date
			,net_pay
			,pay_frequency
			,paid_on_day_1
			,paid_on_day_2
			,pay_date_1
			,pay_date_2
			,pay_date_3
			,pay_date_4
		) VALUES (
			'$application_id'
			,NOW()
			,'$net_pay'
			,'$pay_frequency'
			,'$paid_on_day_1'
			,'$paid_on_day_2'
			,'$pay_date_1'
			,'$pay_date_2'
			,'$pay_date_3'
			,'$pay_date_4'
		)
SQL;

		Diag::Out("insert into income:\n$query");
		#Diag::DumpToFile($query, "insert into income");

		$rs = $this->sql->Query($this->db_to, $query);

		Error_2::Error_Test($rs, true);

		return $this->sql->Insert_Id();
	}

	function _Insert_Paydate()
	{

		$application_id = mysql_escape_string($this->_sess["application_id"]);
		$paydate_model_id = mysql_escape_string($this->_sess["paydate_model_id"]);
		$day_of_week = mysql_escape_string($this->_sess["day_of_week"]);
		$next_paydate = mysql_escape_string($this->_sess["next_paydate"]);
		$day_of_month_1 = mysql_escape_string($this->_sess["day_of_month_1"]);
		$day_of_month_2 = mysql_escape_string($this->_sess["day_of_month_2"]);
		$week_1 = mysql_escape_string($this->_sess["week_1"]);
		$week_2 = mysql_escape_string($this->_sess["week_2"]);

		$query = <<<SQL
		INSERT IGNORE INTO paydate (
			paydate_id
			,application_id
			,date_modified
			,date_created
			,paydate_model_id
			,day_of_week
			,next_paydate
			,day_of_month_1
			,day_of_month_2
			,week_1
			,week_2
		) VALUES (
			NULL
			,'$application_id'
			,NOW()
			,NOW()
			,'$paydate_model_id'
			,'$day_of_week'
			,'$next_paydate'
			,'$day_of_month_1'
			,'$day_of_month_2'
			,'$week_1'
			,'$week_2'
		)
SQL;

		Diag::Out("insert into paydate:\n$query");
		#Diag::DumpToFile($query, "insert into paydate");

		$rs = $this->sql->Query($this->db_to, $query);

		Error_2::Error_Test($rs, true);

		return $this->sql->Insert_Id();
	}

	function _Insert_Personal($contact_id_1, $contact_id_2)
	{

		$application_id = mysql_escape_string($this->_sess["application_id"]);
		$first_name = mysql_escape_string($this->_sess["first_name"]);
		$middle_name = mysql_escape_string($this->_sess["middle_name"]);
		$last_name = mysql_escape_string($this->_sess["last_name"]);
		$home_phone = mysql_escape_string($this->_sess["home_phone"]);
		$cell_phone = mysql_escape_string($this->_sess["cell_phone"]);
		$fax_phone = mysql_escape_string($this->_sess["fax_phone"]);
		$email = mysql_escape_string($this->_sess["email"]);
		$alt_email = mysql_escape_string($this->_sess["alt_email"]);
		$date_of_birth = mysql_escape_string($this->_sess["date_of_birth"]);
		$social_security_number = mysql_escape_string($this->_sess["social_security_number"]);
		$drivers_license_number = mysql_escape_string($this->_sess["drivers_license_number"]);
		$best_call_time = mysql_escape_string($this->_sess["best_call_time"]);

		$query = <<<SQL
		INSERT IGNORE INTO personal (
			application_id
			,modified_date
			,first_name
			,middle_name
			,last_name
			,home_phone
			,cell_phone
			,fax_phone
			,email
			,alt_email
			,date_of_birth
			,contact_id_1
			,contact_id_2
			,social_security_number
			,drivers_license_number
			,best_call_time
		) VALUES (
			'$application_id'
			,NOW()
			,'$first_name'
			,'$middle_name'
			,'$last_name'
			,'$home_phone'
			,'$cell_phone'
			,'$fax_phone'
			,'$email'
			,'$alt_email'
			,'$date_of_birth'
			,'$contact_id_1'
			,'$contact_id_2'
			,'$social_security_number'
			,'$drivers_license_number'
			,'$best_call_time'
		)
SQL;

		Diag::Out("insert into personal:\n$query");
		#Diag::DumpToFile($query, "insert into personal");

		$rs = $this->sql->Query($this->db_to, $query);

		Error_2::Error_Test($rs, true);

		return $this->sql->Insert_Id();
	}

	function _Insert_Residence()
	{

		$application_id = mysql_escape_string($this->_sess["application_id"]);
		$residence_type = mysql_escape_string($this->_sess["residence_type"]);
		$length_of_residence = mysql_escape_string($this->_sess["length_of_residence"]);
		$address_1 = mysql_escape_string($this->_sess["address_1"]);
		$apartment = mysql_escape_string($this->_sess["apartment"]);
		$city = mysql_escape_string($this->_sess["city"]);
		$state = mysql_escape_string($this->_sess["state"]);
		$zip = mysql_escape_string($this->_sess["zip"]);
		$address_2 = mysql_escape_string($this->_sess["address_2"]);

		$query = <<<SQL
		INSERT IGNORE INTO residence (
			application_id
			,residence_type
			,length_of_residence
			,address_1
			,apartment
			,city
			,state
			,zip
			,address_2
		) VALUES (
			'$application_id'
			,'$residence_type'
			,'$length_of_residence'
			,'$address_1'
			,'$apartment'
			,'$city'
			,'$state'
			,'$zip'
			,'$address_2'
		)
SQL;

		Diag::Out("insert into residence:\n$query");
		#Diag::DumpToFile($query, "insert into residence");

		$rs = $this->sql->Query($this->db_to, $query);

		Error_2::Error_Test($rs, true);

		return $this->sql->Insert_Id();
	}


	# transfer internal session data from multiple sessions into one insert statement
	function _Insert_Session()
	{

		Diag::Out("_Insert_Session...");
		#Diag::Dump($this->_sess, "this->_sess");

		# if we've already seen this session, skip inserts
		# in a perfect world we'd now run inserts
		if (false === $this->_Record_Session_Id($this->_sess["session_id"]))
		{
			Diag::Out("end _Insert_Session, session dupe");
			return false;
		}

		/*

		# same for application id, if we've seen it before, we don't want to see it again
		if (false === $this->_Record_Application_Id($this->_sess["application_id"]))
		{
			Diag::Out("end _Insert_Session, app_id dupe");
			return false;
		}

		$contact_id_1 = $this->_Insert_Personal_Contact(
			$this->_sess["application_id"],
			$this->_sess["full_name_1"],
			$this->_sess["phone_1"],
			$this->_sess["relationship_1"]
		);

		$contact_id_2 = $this->_Insert_Personal_Contact(
			$this->_sess["application_id"],
			$this->_sess["full_name_2"],
			$this->_sess["phone_2"],
			$this->_sess["relationship_2"]
		);

		*/

		# dupe
		if ($this->_Insert_Bank_Info() == 0) /*return*/ ;
		$this->_Insert_Campaign_Info();
		$address_id = $this->_Insert_Residence();
		$this->_Insert_Employment($address_id);
		$this->_Insert_Income();
		$this->_Insert_Paydate();
		$this->_Insert_Personal($contact_id_1, $contact_id_2);

		Diag::Out("end _Insert_Session");

	}

	# read sessions from $db_from
	function _Transfer_Sessions()
	{

		Diag::Push(true); # enable for this scope

		Diag::Out("_Transfer_Sessions() has begunified");

		# ensure we've got what we need to accomplish the transfers
		assert(is_string($this->db_from) && "" != $this->db_from);
		assert(is_string($this->db_to) && "" != $this->db_to);
		assert(is_numeric($this->ts_until));

		# fetch a list of all applicable session tables
		$session_tables = $this->_Fetch_Session_Tables();
		Diag::Dump($session_tables, "_Transfer_Sessions() session tables");
		reset($session_tables);
		# with each session table...
		while (list($table, $compressed) = each($session_tables))
		{
			Diag::Out("_Transfer_Sessions() table '$table'");
			# fetch appropriate sessions from table
			$sessions = $this->_Fetch_Sessions($table, $compressed);
			Diag::Out("_Transfer_Sessions() fetched " . count($sessions) . " from '$table'");

			$qualified = 0;
			Diag::Push(false); # turn off Diag:: for the loop
			# for each session...
			reset($sessions);
			while (list($id, $data) = each($sessions))
			{
				#Diag::Out("_Transfer_Sessions() working on session '$id'");
				# add sessions' data to internal buffer
				if (true === $this->_Process_Session($id, $data))
				{
					$qualified++;
					$this->_Insert_Session();
					Diag::Out("_Transfer_Sessions() session '$id' inserted");
				}
				else
				{
					Diag::Out("_Transfer_Sessions() session '$id' did not qualify!");
				}
			}
			Diag::Pop(); # forget settings for this scope
			# record what we did
			$this->_Write_Log($table, $qualified, $qualified);
		}

		Diag::Out("_Transfer_Sessions() done.");

		Diag::Pop(); # forget settings for this scope
	}

}

?>