<?php
	class Get_Stat_1
	{
		// Incoming Statics
		var $sql_object;
		var $database_name;
		var $level;
		var $current_date;
		var $data_range;
		var $start_range;
		var $end_range;

		// Usable data lists
		var $stat_tables;
		var $stat_columns;
		var $stat_blockids;
		var $stat_vendors;
		var $stat_url_list;

		// Misc.
		var $vendor_id;
		var $promo_id;
		var $sub_codes;

		// Objects To Store In A Session And Return
		var $stat_group_data;
		var $stat_lp_data;
		var $stat_vendor_data;
		var $stat_promo_id_data;
		var $stat_promo_sub_data;

		// Constructor
		function Get_Stat_1($sql_object, $database_name, $level, $data_range, $start_range=NULL, $end_range=NULL)
		{
			$this->sql_object = $sql_object;
			$this->database_name = $database_name;
			$this->level = $level;
			$this->current_date = date("Y-m-d");
			$this->request_date = $request_date;
			$this->data_range = $data_range;

			if($this->data_range == "custom")
			{
				$this->start_range = $start_range;
				$this->end_range = $end_range;
			}

			// Get a usable data lists
			$this->Get_Stat_Tables();
			$this->Get_Stat_Columns();
			$this->Get_Stat_Block();

			return TRUE;

		}

		// Get the tables to pull data from
		function Get_Stat_Tables()
		{
			$table_list = $this->sql_object->Get_Table_List($this->database_name);

			foreach ($table_list as $key=>$value)
			{
				// Get only stat tables
				if(preg_match("/^stats\d+_\d+_\d+/",$key))
				{
					// Break out to tables within the date range
					switch($this->data_range)
					{
						case "today":
						case "month_to_date":
						$value = date("Y_m");
						if(preg_match("/$value/", $key))
						{
							$this->stat_tables[]=$key;
						}

						break;

						default:
						$this->stat_tables[]=$key;
					}
				}
			}
			return TRUE;
   		}
		
		// Get the valid column for this database
		function Get_Stat_Columns()
		{
			$sql = "SELECT view FROM stat_view WHERE db_name='".$this->database_name."' AND level='".$this->level."'";
			$query = $this->sql_object->Query('management_db_temp',$sql, Debug_1::Trace_Code (__FILE__, __LINE__));
   			Error_2::Error_Test ($query);

			$result = $this->sql_object->Fetch_Object_Row ($query);
			$this->stat_columns = explode(",", $result->view);

			return TRUE;
		}

		// Get vendor data list
		function Get_Stat_Vendors()
		{
			$sql = "SELECT DISTINCT promo_data_map.vendor_id FROM promo_data_map";
			$query = $this->sql_object->Query('management_db_temp',$sql, Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test ($query);

			while (FALSE !== ($result = $this->sql_object->Fetch_Object_Row ($query)))
			{
				$this->stat_vendors->{$result->vendor_id} = $result;
			}

			return TRUE;

		}
		
		// Get Site List
		function Get_Site_List()
		{
			$sql = "SELECT DISTINCT site_id FROM id_blocks";
			$query = $this->sql_object->Query($this->database_name,$sql, Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test ($query);
			
			while (FALSE !== ($result = $this->sql_object->Fetch_Object_Row ($query)))
			{
				$this->stat_url_list->{$result->site_id} = $result;
			}
			return TRUE;

		}
		
		// Get promo Id list
		function Get_Promo_Id_List($vendor_id, $page_id)
		{
			$sql = "SELECT DISTINCT promo_data_map.promo_id FROM promo_data_map WHERE vendor_id='".$vendor_id."' AND page_id='".$page_id."'";
			$query = $this->sql_object->Query('management_db_temp',$sql, Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test ($query);

			while (FALSE !== ($result = $this->sql_object->Fetch_Object_Row ($query)))
			{
				$this->promo_id->{$result->promo_id} = $result;
			}

			return TRUE;

		}
		
		// Get promo sub list
		function Get_Sub_Code_List($promo_id)
		{
			$sql = "SELECT promo_sub_code FROM id_blocks WHERE promo_id='".$promo_id."' AND promo_sub_code !=''";
			$query = $this->sql_object->Query($this->database_name,$sql, Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test ($query);

			while (FALSE !== ($result = $this->sql_object->Fetch_Object_Row ($query)))
			{
				$this->sub_codes->{$result->promo_sub_code} = $result;
			}

			return TRUE;

		}
		
		// Get the valid block_id's for this query
		function Get_Stat_Block()
		{	
			
			if ($this->data_range == "today")
			{
				$stat_blockids = new stdClass;
				$sql = "SELECT block_id FROM id_blocks WHERE stat_date='".$this->current_date."'";
				$query = $this->sql_object->Query($this->database_name,$sql, Debug_1::Trace_Code (__FILE__, __LINE__));
				Error_2::Error_Test ($query);

				while (FALSE !== ($result = $this->sql_object->Fetch_Object_Row ($query)))
				{
				$this->stat_blockids->{$result->block_id} = $result->block_id;
				}
			}
			elseif ($this->data_range == "month_to_date")
			{
				$start = date("Y-m-01");
				$stat_blockids = new stdClass;
				$sql = "SELECT block_id FROM id_blocks WHERE stat_date BETWEEN '".$start."' AND '".$this->current_date."'";
				$query = $this->sql_object->Query($this->database_name,$sql, Debug_1::Trace_Code (__FILE__, __LINE__));
				Error_2::Error_Test ($query);

				while (FALSE !== ($result = $this->sql_object->Fetch_Object_Row ($query)))
				{
				$this->stat_blockids->{$result->block_id} = $result->block_id;
				}
			}
			else
			{
				$stat_blockids = new stdClass;
				$sql = "SELECT block_id FROM id_blocks WHERE stat_date BETWEEN '".$this->start_range."' AND '".$this->end_range."'";
				$query = $this->sql_object->Query($this->database_name,$sql, Debug_1::Trace_Code (__FILE__, __LINE__));
				Error_2::Error_Test ($query);

				while (FALSE !== ($result = $this->sql_object->Fetch_Object_Row ($query)))
				{
				$this->stat_blockids->{$result->block_id} = $result->block_id;
				}
			}

			return TRUE;
		}

		// Get the group data
		function Get_Group_Data()
		{
			// list blockids for sql statement
			foreach ($this->stat_blockids as $blockids)
			{
				$blockid_list .= $blockids.",";
			}
			$blockid_list = substr($blockid_list, 0, -1);
			
			//exit func if blockid list is empty
			if (!strlen ($blockid_list))
			{
				return $this->stat_group_data;
			}
			
			// list tables & where clause for sql statement
			$from_clause = "id_blocks ";
			foreach ($this->stat_tables as $table)
			{
				$from_clause .= "LEFT JOIN ".$table." ON (id_blocks.block_id=".$table.".block_id) ";
				$table_list .= $table.",";
				$where_clause .= $table.".".block_id." IN (".$blockid_list.") OR ";

				foreach($this->stat_columns as $column)
				{
					$temp_select_clause->$column .= "sum(".$table.".".$column.") + ";
				}
			}
			$table_list = substr($table_list, 0, -1);
			$where_clause = "id_blocks.block_id IN (".$blockid_list.")";

			// Build the sum response and kick object back with totals per column
			foreach ($temp_select_clause as $column => $summing)
			{
				$almost_select_clause .= "(".substr ($summing, 0, -3).") as ".$column.", ";
			}
			$select_clause = substr($almost_select_clause, 0, -2);

			// Run the query and return the result
			$sql = "SELECT ".$select_clause." FROM ".substr($from_clause, 0, -1)." WHERE ".$where_clause."";

			$query = $this->sql_object->Query($this->database_name,$sql, Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test ($query);

			$this->stat_group_data = new stdClass;

			while (FALSE !== ($result = $this->sql_object->Fetch_Object_Row ($query)))
			{
			$this->stat_group_data->{$this->database_name} = $result;
			}

			return $this->stat_group_data;
		}
		
		// Get the URL data
		function Get_Url_Data()
		{
		
			// Get site Id list
			$this->Get_Site_List($this->database_name);

			// list blockids for sql statement
			foreach ($this->stat_blockids as $blockids)
			{
				$blockid_list .= $blockids.",";
			}
			$blockid_list = substr($blockid_list, 0, -1);
			
			//exit func if blockid list is empty
			if (!strlen ($blockid_list))
			{
				return $this->stat_url_data;
			}

			// list tables & where clause for sql statement
			$from_clause = "id_blocks ";
			foreach ($this->stat_tables as $table)
			{
				$from_clause .= "LEFT JOIN ".$table." ON (id_blocks.block_id=".$table.".block_id) ";
				$table_list .= $table.",";
				$where_clause .= $table.".".block_id." IN (".$blockid_list.") OR ";

				foreach($this->stat_columns as $column)
				{
					$temp_select_clause->$column .= "sum(".$table.".".$column.") + ";
				}

			}
			$this->stat_url_data = new stdClass;
			foreach ($this->stat_url_list as $key=>$value)
			{
				$site_id_list = substr($site_id_list, 0, -1);
				$table_list = substr($table_list, 0, -1);
				$where_clause = "id_blocks.block_id IN (".$blockid_list.") AND id_blocks.site_id='".$key."'";

				// Build the sum response and kick object back with totals per column
				foreach ($temp_select_clause as $column => $summing)
				{
					$almost_select_clause .= "(".substr ($summing, 0, -3).") as ".$column.", ";
				}
				$select_clause = substr($almost_select_clause, 0, -2);

				// Run the query and return the result
				$sql = "SELECT ".$select_clause." FROM ".substr($from_clause, 0, -1)." WHERE ".$where_clause."";

				$query = $this->sql_object->Query($this->database_name,$sql, Debug_1::Trace_Code (__FILE__, __LINE__));
				Error_2::Error_Test ($query);

				while (FALSE !== ($result = $this->sql_object->Fetch_Object_Row ($query)))
				{
				$this->stat_url_data->{$key} = $result;
				}
			}
			
			return $this->stat_url_data;
		}


		// Get the landing page data
		function Get_LP_Data()
		{
			// list blockids for sql statement
			foreach ($this->stat_blockids as $blockids)
			{
				$blockid_list .= $blockids.",";
			}
			$blockid_list = substr($blockid_list, 0, -1);

			//exit func if blockid list is empty
			if (!strlen ($blockid_list))
			{
				return $this->stat_lp_data;
			}
			
			// list tables & where clause for sql statement
			$from_clause = "id_blocks ";
			$this->stat_lp_data = new stdClass;
			foreach ($this->stat_tables as $table)
			{
			
				// Get Landing Page Name
				$lp_name = explode("_",$table);
				$lp_name = preg_replace("/stats/", "", $lp_name[0]);
				$sql = "SELECT name FROM landing_pages WHERE page_id='".$lp_name."'";
				$query = $this->sql_object->Query($this->database_name,$sql, Debug_1::Trace_Code (__FILE__, __LINE__));
				Error_2::Error_Test ($query);
				
				$lp_name = $this->sql_object->Fetch_Object_Row ($query);

				$from_clause = "id_blocks ";
				$from_clause .= "LEFT JOIN ".$table." ON (id_blocks.block_id=".$table.".block_id) ";

				foreach ($this->stat_columns as $columns)
				{
					$select_clause .= "sum(".$table.".".$columns.") as ".$columns.", ";
				}
				$select_clause = substr($select_clause, 0, -2);
				$where_clause = "id_blocks.block_id IN (".$blockid_list.")";
				
				$sql = "SELECT ".$select_clause." FROM ".substr($from_clause, 0, -1)." WHERE ".$where_clause."";
				$query = $this->sql_object->Query($this->database_name,$sql, Debug_1::Trace_Code (__FILE__, __LINE__));
				Error_2::Error_Test ($query);

				$result = $this->sql_object->Fetch_Object_Row ($query);
				$this->stat_lp_data->{$lp_name->name} = $result;
				
				// Unset to avoid the " .= " loop
				unset ($from_clause);
				unset ($select_clause);

			}
			return $this->stat_lp_data;
		}

		// Get the vendor data
		function Get_Vendor_Data($landing_page)
		{
			$this->Get_Stat_Vendors();
			$this->stat_vendor_data = new stdClass;
			
			// Get the page id for the named landing page
			$sql = "SELECT page_id FROM landing_pages WHERE name='".$landing_page."' ";
			$query = $this->sql_object->Query($this->database_name,$sql, Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test ($query);
			$landing_page_id = $this->sql_object->Fetch_Object_Row ($query);

			foreach ($this->stat_tables as $table)
			{
				echo "<br>";
				if(preg_match("/stats".$landing_page_id->page_id."/i",$table))
				{
					$valid_table = $table;
				}
			}
			
			// Build the clauses for the sql statment
			foreach ($this->stat_columns as $columns)
			{
				$select_clause .= "sum(".$columns.") as ".$columns.", ";
			}
			$select_clause = substr($select_clause, 0, -2);

			$from_clause = "id_blocks, ".$valid_table."";

			$this->stat_vendor_data = new stdClass;
			foreach ($this->stat_vendors as $key=>$value)
			{
				$where_clause = "id_blocks.block_id=".$valid_table.".block_id AND id_blocks.vendor_id='".$key."'";

				$sql = "SELECT DISTINCT vendor_name FROM promo_data_map WHERE vendor_id='".$key."'";
				$query = $this->sql_object->Query("management_db_temp",$sql, Debug_1::Trace_Code (__FILE__, __LINE__));
				Error_2::Error_Test ($query);
				$vendor_name = $this->sql_object->Fetch_Object_Row ($query);

				$sql = "SELECT ".$select_clause." FROM ".$from_clause." WHERE ".$where_clause."";
				$query = $this->sql_object->Query($this->database_name,$sql, Debug_1::Trace_Code (__FILE__, __LINE__));
				Error_2::Error_Test ($query);
				while (FALSE !== ($result = $this->sql_object->Fetch_Object_Row ($query)))
				{
				$this->stat_vendor_data->{$vendor_name->vendor_name} = $result;
				}
			}

			return $this->stat_vendor_data;
		}

		// Get Stats By Promo ID...
		function Get_Promo_Id_Data($landing_page, $vendor_name)
		{
			// Get the page_id for the named landing_page
			$sql = "SELECT page_id FROM landing_pages WHERE name='".$landing_page."' ";
			$query = $this->sql_object->Query($this->database_name,$sql, Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test ($query);
			$landing_page_id = $this->sql_object->Fetch_Object_Row ($query);
			
			// Get the vendor_id for the named vendor
			$sql = "SELECT DISTINCT vendor_id FROM promo_data_map WHERE vendor_name='".$vendor_name."' ";
			$query = $this->sql_object->Query("management_db_temp",$sql, Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test ($query);
			$vendor_id = $this->sql_object->Fetch_Object_Row ($query);
			
			$this->Get_Promo_Id_List($vendor_id->vendor_id, $landing_page_id->page_id);

			foreach ($this->stat_tables as $table)
			{
				// Get the exact matching table to avoid a long loop through useless tables
				if(preg_match("/stats".$landing_page_id->page_id."/i",$table))
				{
					$valid_table = $table;
				}
			}
			
			// Build the clauses for the sql statment
			foreach ($this->stat_columns as $columns)
			{
				$select_clause .= "sum(".$columns.") as ".$columns.", ";
			}
			$select_clause = substr($select_clause, 0, -2);

			$from_clause = "id_blocks, ".$valid_table."";

			$this->stat_promo_id_data = new stdClass;

			foreach ($this->promo_id as $key => $value)
			{
				$where_clause = "id_blocks.block_id=".$valid_table.".block_id AND id_blocks.vendor_id='".$vendor_id->vendor_id."' AND id_blocks.promo_id='".$key."'";

				$sql = "SELECT ".$select_clause." FROM ".$from_clause." WHERE ".$where_clause."";
				$query = $this->sql_object->Query($this->database_name,$sql, Debug_1::Trace_Code (__FILE__, __LINE__));
				Error_2::Error_Test ($query);
				while (FALSE !== ($result = $this->sql_object->Fetch_Object_Row ($query)))
				{
					$this->stat_promo_id_data->{$key} = $result;
				}
			}
			
			return $this->stat_promo_id_data;
		}
		
		// Get the Promo Sub Code Data
		function Get_Promo_Sub_Data($landing_page, $promo_id)
		{
			$this->Get_Sub_Code_List($promo_id);
			
			// Get the page_id for the named landing page
			$sql = "SELECT page_id FROM landing_pages WHERE name='".$landing_page."' ";
			$query = $this->sql_object->Query($this->database_name,$sql, Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test ($query);
			$landing_page_id = $this->sql_object->Fetch_Object_Row ($query);
			
			foreach ($this->stat_tables as $table)
			{
				// Get the exact matching table to avoid a long loop through useless tables
				if(preg_match("/stats".$landing_page_id->page_id."/i",$table))
				{
					$valid_table = $table;
				}
			}
			
			foreach ($this->stat_columns as $columns)
			{
				$select_clause .= "sum(".$valid_table.".".$columns.") as ".$columns.", ";
			}
			$select_clause = substr($select_clause, 0, -2);

			$from_clause = "id_blocks, ".$valid_table."";

			$this->stat_promo_sub_data = new stdClass;
			
			// Build the clauses for the sql statement
			foreach ($this->sub_codes as $key => $value)
			{
				$where_clause = "id_blocks.block_id=".$valid_table.".block_id AND id_blocks.promo_sub_code='".$key."'";

				$sql = "SELECT ".$select_clause." FROM ".$from_clause." WHERE ".$where_clause."";
				$query = $this->sql_object->Query($this->database_name,$sql, Debug_1::Trace_Code (__FILE__, __LINE__));
				Error_2::Error_Test ($query);
				while (FALSE !== ($result = $this->sql_object->Fetch_Object_Row ($query)))
				{
					$this->stat_promo_sub_data->{$key} = $result;
				}
			}
			
			return $this->stat_promo_sub_data;

		}
	}
?>