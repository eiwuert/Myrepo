<?php
	// Version 1.2.0

	/*
		2003-03-02 rodricg - added _Setup_Stats and _Abs_Set_Stat for import
		2003-03-18 rodricg - optimized _Abs_Set_Stat and Set_Stat
	*/

	//require_once('statpro_ext.1.php');
	require_once('statpro_client.php');
	include_once('lib_mode.1.php');




	class Set_Stat_2
	{
		// A constructor just to be complete
		function __constuct ()
		{
			return TRUE;
		}

		// Gets the initial block_id to use
		private function _Get_Block($date, $site_id, $vendor_id, $page_id, $promo_id, $promo_sub_code, $sql_object, $database_name, $promo_status, $batch_id = NULL)
		{
			$status = $promo_status->valid;
			$status_message = ($promo_status->valid == "valid") ? NULL : serialize($promo_status);

			// Adjust Vars
			//$date = date("Y-m-d");

			switch (strtolower($batch_id))
			{
				case 'week':
					$batch_id_field = ', batch_id';
					$batch_id_value = ', YEARWEEK(\''.$date.'\', 0)';
					break;

				default:
					$batch_id_field = $batch_id_value = '';
					break;
			}

			try
			{
				// See if the ID Block already exists in the table
				$sql = "SELECT block_id FROM id_blocks WHERE site_id='".$site_id."' AND page_id='".$page_id."' AND promo_id='".$promo_id."' AND promo_sub_code='".$promo_sub_code."' AND stat_date='".$date."'";
				$query = $sql_object->Query($database_name,$sql);

				if ($sql_object->Row_Count($query) == 0)
				{
					// If the block does not exist, create and insert it into id_blocks and return id_block
					$sql = "INSERT INTO id_blocks (site_id, vendor_id, page_id, promo_id, promo_sub_code, status, status_message, stat_date".$batch_id_field.") VALUES('".$site_id."','".$vendor_id."','".$page_id."', '".$promo_id."', '".$promo_sub_code."','".$status."', '".$status_message."','".$date."'".$batch_id_value.")";
					$query = $sql_object->Query($database_name, $sql);

					// Build the return value and send back.
					$id_block_response = $sql_object->Insert_id();

					return $id_block_response;
				}
				else
				{
					//  Build the return value and send back.
					$result = $sql_object->Fetch_Object_Row ($query);

					$id_block_response = $result->block_id;

					return $id_block_response;
				}
			}

			catch( MySQL_Exception $e )
			{
				throw $e;
			}
		}

		// Get the table name to use or create one if needed.
		private function _Get_Table($date, $block_id, $page_id, $sql_object, $database_name)
		{
			// Template table name
			$template_table_name = "stats".$page_id;

			// Build proper table name
			$stats_table_name = $template_table_name.date ("_Y_m", strtotime($date));

			try
			{
				// Get a list of tables in db
	   			$table_list = $sql_object->Get_Table_List ($database_name, Debug_1::Trace_Code (__FILE__, __LINE__));

				// Check if the table exists
	   			if (!$table_list->$stats_table_name)
				{
					// Build the a new table for this month/year

					// Check for the template table
					if (!$table_list->$template_table_name)
					{
						$msg = "Template table (" . $template_table_name . ") does not exist in the database (" . $database_name . ")";

						throw new Exception ($msg, LOG_CRIT);
					}

					// Template exists build a new one
					$table_structure = $sql_object->Get_Table_Info ($database_name, $template_table_name, Debug_1::Trace_Code (__FILE__, __LINE__));

					foreach ($table_structure as $field=>$data)
					{
						switch($data->NULL)
						{
							default:
							$null = "NOT NULL";
						}
						$statement .= "`".$data->Field."` ".$data->Type." ".$null." default '".$data->Default."',";
					}

					// Create the table using template structure
					$sql = "CREATE TABLE $stats_table_name
					(".$statement." PRIMARY KEY `block_id` ( `block_id` ))
					TYPE = MYISAM";

					// Run Query
					$create = $sql_object->Query($database_name, $sql);

				}
			}

			catch( Exception $e )
			{
				throw $e;
			}



			return $stats_table_name;
		}

		// Write the requested record to the database
		public function Set_Stat ($block_id, $table_name, $sql_object, $database_name, $column, $increment='1', $stat_model='BOTH', $timestamp = NULL, $use_enterprisepro = TRUE)
		{

			// tablename may be an Error_2 object coming in
			Error_2::Error_Test($table_name, TRUE);

			// NOTE: When $stat_model == 'BOTH', there are, in fact,
			// three different versions of stat_pro being updated.
			$stat_model = strtoupper($stat_model);

			if ($stat_model=='OLD' || $stat_model=='BOTH')
			{

				try
				{

					$sql = "SELECT COUNT(*) FROM $table_name WHERE block_id='".$block_id."'";
					$query = $sql_object->Query($database_name, $sql);

					// if it does not exist, insert it and start at $incrment else add $increment and continue.
					if ($sql_object->Fetch_Column($query, 0) == 0)
					{
						$sql = "INSERT INTO $table_name (block_id, $column) VALUES('".$block_id."','".$increment."')";
					}
					else
					{
						$sql = "UPDATE $table_name SET ".$column."=".$column." + (".$increment.") WHERE block_id='".$block_id."'";
					}

					// run the query
					$query = $sql_object->Query($database_name, $sql);

				}
				catch( MySQL_Exception $e )
				{
					throw $e;
				}

			}

			// If we don't have a mode in the session, get it
			if (!$_SESSION['config']->mode)
			{

				switch (Lib_Mode::Get_Mode())
				{

					case 1:
						$_SESSION['config']->mode = "LOCAL";
					break;

					case 2:
						$_SESSION['config']->mode = "RC";
					break;

					case 3:
						$_SESSION['config']->mode = "LIVE";
					break;

				}

			}

			if (($stat_model == 'NEW') || ($stat_model == 'BOTH'))
			{

				// Set the customer key and customer pass based on the property_id
				switch ($_SESSION['config']->property_id) {

					case 9278:
						$statpro_key = 'equityone';
						$statpro_pass = '3337b7d5b3321b075c8582540';
					break;

					case 34676:
						$statpro_key = 'emv';
						$statpro_pass = 'a51a5c87c5f2c030de8dee2da';
					break;

					case 28400:
						$statpro_key = 'leadgen';
						$statpro_pass = '04b650f6350a863089a015164';
					break;

					case 4967:
						$statpro_key = 'ge';
						$statpro_pass = '3818ca3aab5960549fb32d4c5';
					break;

					case 35459:
						// For LeadGen partner=PW, DO NOT RECORD STATS ON NEW MODEL
						$statpro_key = 'pwsites';
						$statpro_pass = 'bfa657d3633';
					break;

					case 55459:
						$statpro_key = 'smt';
						$statpro_pass = 'moosow1U';
						break;

					case 1571: // Express Gold Card
					case 44024: //Cubis Financial Cards
						$statpro_key = 'cubis';
						$statpro_pass = 'FtT7CYMFMyrC0';
					break;

					case 48204: // Impact
					case 48206: // Impact
						$statpro_key = 'imp';
						$statpro_pass = 'h0l3iny0urp4nts';
					break;

					case 57458:
						$statpro_key = 'ocp';
						$statpro_pass = 'raic9Cei';
						break;

					default:
						$statpro_key = 'catch';//'catch_all';
						$statpro_pass = 'bd27d44eb515d550d43150b9b';
					break;

				}

				// This is a special case, easier done this way than a bunch
				// of case statements
				if (in_array($_SESSION['config']->property_id, array(31631, 3018, 9751, 1583, 1581, 1579, 1720, 17208, 10985))) {
					$statpro_key = 'clk';
					$statpro_pass = 'dfbb7d578d6ca1c136304c845';
				}

				if ($statpro_key && $statpro_pass) {

					$mode = ($_SESSION['config']->mode == 'LIVE') ? 'live' : 'test';

					define('STATPRO_2_BIN', '/opt/statpro/bin/spc_'.$statpro_key.'_'.$mode);
					define('STATPRO_2_OPT', '-v');

					$session_key = $_SESSION['statpro']['session_key'];

					// STAT PRO v.1
					// lets capture the data that gets thrown when we can't connect to statpro
					ob_start();

					if ($timestamp === NULL && @$_SESSION['stat_info']->stat_time)
					{
						$timestamp = $_SESSION['stat_info']->stat_time;
					}

					// STAT PRO v.2
					// Andrew Minerd, 3/30/05a
					$statpro2 = new StatPro_Client(STATPRO_2_BIN, STATPRO_2_OPT, $statpro_key, $statpro_pass, $use_enterprisepro);
					$statpro2->Record_Event($column, $timestamp);

					// save objects
					//$_SESSION['statpro']['statpro_obj'] = &$statpro;
					$_SESSION['statpro']['statpro_obj'] = &$statpro2;
					//$statpro = new StatPro_Ext($statpro_key,$statpro_pass);
					//$_SESSION['statpro']['statpro_obj'] = &$statpro;

					// Record an event
					//$statpro->Call('Record_Event',array($_SESSION['statpro']['session_key'],$column));
					$crap = ob_get_contents();
					ob_end_clean();
				}


				return TRUE;

			}

			return TRUE;


		}

		// Write the requested record to the database
		public function _Abs_Set_Stat ($block_id, $table_name, $sql_object, $database_name, $data)
		{
			$data = is_object ($data) ? get_object_vars ($data) : $data;

			try
			{

				$sql = "SELECT COUNT(*) FROM $table_name WHERE block_id = $block_id";
				$result = $sql_object->Query($database_name, $sql);

				$count = $sql_object->Fetch_Column ($result, 0);

				if ($count)
				{
					$sql = "UPDATE $table_name SET ";
					foreach ($data as $col => $val)
					{
						$sql .= $col."='".$val."',";
					}
					$sql = substr($sql,0,-1)." WHERE block_id='".$block_id."'";
					$result = $sql_object->Query($database_name, $sql);

				}
				else
				{
					$sql = "INSERT INTO $table_name SET ";
					foreach ($data as $col => $val)
					{
						$sql .= $col."='".$val."',";
					}

					$sql = $sql."block_id='".$block_id."'";
					$result = $sql_object->Query($database_name, $sql);

				}
			}
			catch( MySQL_Exception $e )
			{
				throw $e;
			}

			return TRUE;
		}

		public function _Replace_Stat ($block_id, $table_name, $sql_object, $database_name, $data)
		{
			$data = is_object ($data) ? get_object_vars ($data) : $data;

			$sql = "REPLACE $table_name SET ";
			foreach ($data as $col => $val)
			{
				$sql .= $col."='".$val."',";
			}
			$sql = $sql."block_id='".$block_id."'";

			try
			{
				$result = $sql_object->Query($database_name, $sql, Debug_1::Trace_Code (__FILE__, __LINE__));
			}
			catch( MySQL_Exception $e )
			{
				throw $e;
			}

			return TRUE;
		}

		// Wrapper function to run on session start to set initial settings.
		static public function Setup_Stats ($site_id, $vendor_id, $page_id, $promo_id, $promo_sub_code, $sql_object, $database_name, $promo_status, $batch_id = NULL)
		{
      		try
      		{
      			return Set_Stat_2::_Setup_Stats (NULL, $site_id, $vendor_id, $page_id, $promo_id, $promo_sub_code, $sql_object, $database_name, $promo_status, $batch_id);
      		}
      		catch( Exception $e )
      		{
      			throw $e;
      		}
		}

		// Wrapper function to run on session start to set initial settings.
		public function _Setup_Stats ($date, $site_id, $vendor_id, $page_id, $promo_id, $promo_sub_code, $sql_object, $database_name, $promo_status, $batch_id = NULL)
		{
			if (is_null($date))
			{
				$time = time();
				$date = date('Y-m-d', $time);
			}
			elseif (is_numeric($date))	// date is a unix timestamp
			{
				$time = $date;
				$date = date('Y-m-d', $time);
			}
			else	// date is Y-m-d
			{
				$time = NULL;
			}

			try
			{
				//Get Block ID
				$block_id = Set_Stat_2::_Get_Block($date, $site_id, $vendor_id, $page_id, $promo_id, $promo_sub_code, $sql_object, $database_name, $promo_status, $batch_id);

				//Get Table Name
				$table_name = Set_Stat_2::_Get_Table($date, $block_id, $page_id, $sql_object, $database_name);
			}
			catch( Exception $e )
			{
				throw $e;
			}
			// Prep the return values
			$stat_info = new stdClass ();
			$stat_info->block_id = $block_id;
			$stat_info->tablename = $table_name; // things depend on this
			$stat_info->table_name = $table_name; // but they should use this
			$stat_info->stat_date = $date;
			$stat_info->stat_time = $time;

			// Return our arguments so the session can re-call us with the same args when the date changes
			$stat_info->cache = new stdClass();
			$stat_info->cache->site_id = $site_id;
			$stat_info->cache->vendor_id = $vendor_id;
			$stat_info->cache->page_id = $page_id;
			$stat_info->cache->promo_id = $promo_id;
			$stat_info->cache->promo_sub_code = $promo_sub_code;
			$stat_info->cache->database_name = $database_name;
			//$stat_info->cache->promo_status = $promo_status; // putting this in the session breaks it :/
			$stat_info->cache->batch_id = $batch_id;

			return $stat_info;

		}
	}
?>
