<?php
	// clicktrack.class.php
	// A class to handle click tracking.
	class Click_Track_1
	{
		var $_sql;
		
		function Click_Track_1 ($sql)
		{
			$this->_sql = $sql;

			return TRUE;
		}

		function Click_Count ($site_id, $group_id, $column, $promo_id = NULL, $promo_sub_code = NULL, $unique_id = NULL, $value = 1)
		{
			$promo_id = preg_replace ("/[^\d]/", "", $promo_id);
			$promo_id = $promo_id < 10000 ? NULL : $promo_id;
			
			if (! is_null ($unique_id))
			{
				if ($this->_Unique_Click($unique_id, $site_id, $column))
				{
					return $this->_Click_Count (time(), $site_id, $group_id, $column, $promo_id, $promo_sub_code, $value);
				}
				else
				{
					return TRUE;
				}
			}
			else
			{
				return $this->_Click_Count (time(), $site_id, $group_id, $column, $promo_id, $promo_sub_code, $value);
			}
		}

		function Drop_Report_Table ()
		{
			// Drop the temp table.
			$this->_sql->Wrapper ("drop table ".$this->_report_table_name, "", "\t".__FILE__."->".__LINE__."\n");

			// Kill the cookie.
			$this->_Delete_Cookie ();
		}
		

		function Unique_Nuke ($unique_id, $site_id)
		{
			$key_fields = array ("unique_id", "site_id");

			$query = "delete from unique_click ";

			$where_clause = "where ";
			foreach ($key_fields as $field)
			{
				$where_clause .= $field." = '".mysql_escape_string(${$field})."' AND ";
			}
			$where_clause = substr ($where_clause, 0, -5);

			$this->_sql->Query ($query.$where_clause, "\t".__FILE__."->".__LINE__."\n");
			
			return TRUE;
		}

		function _Click_Count ($today, $site_id, $group_id, $column, $promo_id = NULL, $promo_sub_code = NULL, $value = 1)
		{
			// Setup the values that we'll need.
			$day = date ("Y-m-d", $today);
			$hour = date ("H:00:00", $today);
			$promo_id = is_null ($promo_id) ? "10000" : $promo_id;

			$table_name = "stats_".date ("Y", $today)."_".date ("m", $today);

			if (!$this->_Sanity_Check ($table_name, $column))
			{
				return FALSE;
			}

			// Construct the set clauses
			if (is_object ($column))
			{
				$column_names = array_keys (get_object_vars ($column));
			}
			elseif (is_array ($column))
			{
				$column_names = $column;
			}
			elseif (is_string ($column))
			{
				$column_names = array ($column);
			}
			else
			{
				return FALSE;
			}

			$column_set_update = $column_set_insert = "";
			foreach ($column_names as $column)
			{
				$column_set_update .= $column."=".$column."+".$value.",";
				$column_set_insert .= $column."=".$value.",";
			}
			$column_set_update = substr($column_set_update, 0, -1);
			$column_set_insert = substr($column_set_insert, 0, -1);

			// Hour row?
			$query = "select * from ".$table_name." where site_id='".$site_id."' and group_id='".$group_id."' and promo_id='".$promo_id."' and promo_sub_code='".$promo_sub_code."' and date='".$day."' and time='".$hour."'";
			if ($this->_sql->Wrapper ($query, "", "\t".__FILE__."->".__LINE__."\n"))
			{
				// Increment hour hit count.
				// Increment day hit count.
				$query = "update ".$table_name." set ".$column_set_update." where site_id='".$site_id."' and group_id='".$group_id."' and promo_id='".$promo_id."' and promo_sub_code='".$promo_sub_code."' and date='".$day."' and (time='".$hour."' or time='-1:00:00')";
				
				$this->_sql->Wrapper ($query, "", "\t".__FILE__."->".__LINE__."\n");
			}
			else // There was no row for this hour.
			{
				// Create new hour record in the DB.
				$query = "insert into ".$table_name." set site_id='".$site_id."', group_id='".$group_id."', promo_id='".$promo_id."', promo_sub_code='".$promo_sub_code."', date='".$day."', time='".$hour."', ".$column_set_insert;
				$this->_sql->Wrapper ($query, "", "\t".__FILE__."->".__LINE__."\n");
	
				// Day row?
				$query = "select * from ".$table_name." where site_id='".$site_id."' and group_id='".$group_id."' and promo_id='".$promo_id."' and promo_sub_code='".$promo_sub_code."' and date='".$day."' and time='-1:00:00'";
				if ($this->_sql->Wrapper ($query, "", "\t".__FILE__."->".__LINE__."\n"))
				{
					// Increment day hit count.
					$query = "update ".$table_name." set ".$column_set_update." where site_id='".$site_id."' and group_id='".$group_id."' and promo_sub_code='".$promo_sub_code."' and promo_id='".$promo_id."' and date='".$day."' and time='-1:00:00'";
					$this->_sql->Wrapper ($query, "", "\t".__FILE__."->".__LINE__."\n");
				}
				else // There was no row for this day.
				{
					// Create new day record in the DB.
					$query = "insert into ".$table_name." set site_id='".$site_id."', group_id='".$group_id."', promo_id='".$promo_id."', promo_sub_code='".$promo_sub_code."', date='".$day."', time='-1:00:00', ".$column_set_insert;
					$this->_sql->Wrapper ($query, "", "\t".__FILE__."->".__LINE__."\n");
				}
			}

			return TRUE;
		}

		function _Delete_Cookie ()
		{
			// Kill the cookie.
			setcookie (REPORT_TABLE_COOKIE);

			// Make the cookie immediately unavailable.
			unset ($_COOKIE[REPORT_TABLE_COOKIE]);		
		}

		function _Sanity_Check ($table, $column = NULL)
		{
			// Column is_null
			if (is_null ($column))
			{
				// return Is_Table
				return ($this->_sql->Is_Table ($table, "\t".__FILE__."->".__LINE__."\n"));
			}
			else
			{
				// return !(!Table || !Column)
				return (!(!$this->_sql->Is_Table ($table, "\t".__FILE__."->".__LINE__."\n")) || (!$this->_sql->Is_Column ($table, $column)));
			}

			return FALSE;
		}

		function _Unique_Click ($unique_id, $site_id, $column)
		{
			$key_fields = array ("unique_id", "site_id");
	
			if (is_object ($column))
			{
				$column = array_shift (array_keys (get_object_vars ($column)));
			}
			elseif (is_array ($column))
			{
				$column = array_shift ($column);
			}

			// Check for a unique click record
			$query = "select ".$column." from unique_click ";
	
			$where_clause = "where ";
			foreach ($key_fields as $field)
			{
				$where_clause .= $field." = '".mysql_escape_string(${$field})."' AND ";
			}
			$where_clause = substr ($where_clause, 0, -5);
	
			$unique = $this->_sql->Wrapper ($query.$where_clause, "", "\t".__FILE__."->".__LINE__."\n");
	
			// If we have one
			if (count($unique))
			{
				// Havent hit it yet
				if (! $unique[0]->$column)
				{
					$query = "update unique_click set ".$column." = 1 ".$where_clause." limit 1";
					$this->_sql->Query ($query, "\t".__FILE__."->".__LINE__."\n");
					
					return TRUE;
				}
				else // Already hit it
				{
					return FALSE;
				}
			}
			else // Create a new record
			{
				$query = "insert into unique_click (unique_id, site_id, ".$column.") values (";
				foreach ($key_fields as $field)
				{
					$query .= "'".${$field}."',";
				}
				$query .= "1)";
				
				$this->_sql->Query ($query,"\t".__FILE__."->".__LINE__."\n");
	
				return TRUE;
			}
		}
		
	}
		
?>
