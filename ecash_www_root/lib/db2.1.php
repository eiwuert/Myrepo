<?php

require_once ("error.2.php");

// DB2 database
class Db2_1
{
	var $db2;		// db2 resource id

	var $base;		// database alias
	var $user;		// username
	var $pass;		// password

	var $use_pconnect = FALSE;		// gentoo? the use flag concept is nice so we use it
	var $use_autocommit = TRUE;

	function Db2_1 ($base = NULL, $user = NULL, $pass = NULL)
	{
		$this->Config ($base, $user, $pass);
		/* Autoconnect would be nice but we cant return an error here -- so we wait for exceptions in php5
		if (! is_null ($this->base))	$this->Connect ();
		*/
	}

	function Config ($base, $user, $pass)
	{
		$this->base = $base;
		$this->user = $user;
		$this->pass = $pass;
	}

	function Connect ()
	{
		$connect_func = $this->use_pconnect ? "odbc_pconnect" : "odbc_connect";
		if (! ($this->db2 = @$connect_func ($this->base, $this->user, $this->pass)))
		{
			return new Error_2 ("DB2 Connect to ".$this->base." as ".$this->user." failed.");
		}
	}

	function Autocommit ($use)
	{
		if (! odbc_autocommit ($this->db2, $use))
			return new Error_2 (array (
				"type" => "DB2 Autocommit failed",
				"message" => @odbc_errormsg ($this->db2)
				));

		$this->use_autocommit = $use;
	}

	function & Query ($query)
	{
		$q = new Db2_Query_1 ($this, $query);
		if (! $q->Prepare ())
			return new Error_2 (array (
				"type" => "DB2 Query Prepare failed",
				"message" => @odbc_errormsg ($this->db2)
				));

		return $q;
	}

	function & Execute ($query)
	{
		$q =& $this->Query ($query);
		if (Error_2::Check ($q))
		{
			return $q;
		}

		$rc = $q->Execute ();

		return is_a($rc, "Error_2") ? $rc : $q;
	}

	function Commit ()
	{
		if (! @odbc_commit ($this->db2))
			return new Error_2 (array (
				"type" => "DB2 Commit failed",
				"message" => @odbc_errormsg ($this->db2)
				));

		return TRUE;
	}

	function Rollback ()
	{
		if (! @odbc_rollback ($this->db2))
			return new Error_2 (array (
				"type" => "DB2 Rollback failed",
				"message" => @odbc_errormsg ($this->db2)
				));

		return TRUE;
	}

	function Insert_Id ()
	{
		$rc = $this->Execute ("values identity_val_local()");
		Error_2::Error_Test ($rc, TRUE);

		$row = $rc->Fetch_Array ();
		$id = array_pop ($row);
		return $id;
	}

	function Get_Field_Names()
	{
		$field_count = $this->sql = @odbc_num_fields($this->db2);
		return $field_count;
	}
}

class Db2_Query_1
{
	var $db2;		// parent db2 object
	var $sql;		// sql statement resource id
	var $query;		// sql statement

	function Db2_Query_1 (&$db2, $query)
	{
		$this->db2 =& $db2;
		$this->query = $query;
	}

	function Prepare ()
	{
		return ($this->sql = @odbc_prepare ($this->db2->db2, $this->query)) ? TRUE : FALSE;
	}

	function Execute ()
	{
		if (! is_resource ($this->sql))
			return new Error_2 (array (
				"type" => "DB2 Query Execute failed",
				"message" => "Query is not prepared"
				));

		if (func_num_args ())
		{
			$args = func_get_args();
			$rc = @odbc_execute ($this->sql, $args);
		}
		else
		{
			$rc = @odbc_execute ($this->sql);
		}

		if (! $rc)
			return new Error_2 (array (
				"type" => "DB2 Query Execute failed",
				"message" => @odbc_errormsg ($this->db2->db2)
				));

		return TRUE;
	}

	/**
	 * @return int
	 * @desc Returns the number of rows from the query except select statments.
	 * @desc odbc_num_rows is flaky at best with a select statment.

	 * switch the num_rows functions call because of the flaky issues and the need to change
	   the cursor type for performance reasons - eCash needs this result to function - 06/11/04 - swarren
	*/

	function Num_Rows ()
	{
		return @odbc_num_rows ($this->sql);
	}

	function Num_Fields ()
	{
		return @odbc_num_fields ($this->sql);
	}

	function Field_Name ($pos)
	{
		return @odbc_field_name($this->sql,$pos);
	}

	function Fetch_Array ($rownum = NULL)
	{
		if (is_null ($rownum))
			return @odbc_fetch_array ($this->sql);
		else
			return @odbc_fetch_array ($this->sql, $rownum);
	}

	function Fetch_Object ($rownum = NULL)
	{
		if (is_null ($rownum))
			return @odbc_fetch_object ($this->sql);
		else
			return @odbc_fetch_object ($this->sql, $rownum);
	}


	/**
	 * @return int
	 * @desc Returns the id of the last inserted sql query.
	*/
	function Insert_Id ()
	{
		$new_id = odbc_result($this->sql, 1);
		return $new_id;
	}
}

?>
