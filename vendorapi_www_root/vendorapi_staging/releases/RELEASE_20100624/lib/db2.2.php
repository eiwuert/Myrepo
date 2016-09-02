<?PHP
/**
	@version:
			2.0.0 2004-09-29 - A PHP5 wrapper for DB2

	@author:
			Nick White - version 2.0.0

	@Updates:

	@Usage Example:
*/

require_once('db2_exception.php');
require_once('error.2.php');

class Db2_2
{
	/*
	* @param $db2 			int: 	db2 resource id
	* @param $base 		string:	database alias
	* @param $user 		string: 	username
	* @param $password 		string: 	password
	* @param $use_pconnect 	bool: 	use persistant connection True / False
	* @param $use_autocommit bool: 	use autocommit True / False
	* @param $debug 		bool:	use debug True / False
	*/
	public $db2;
	protected $base;
	protected $user;
	protected $password;
	protected $use_pconnect;
	protected $use_autocommit = TRUE;
	protected $debug;

	function __construct($base = NULL, $user = NULL, $pass = NULL, $use_pconnect = FALSE, $debug = TRUE)
	{
		$this->base = $base;
		$this->user = $user;
		$this->pass = $pass;
		$this->debug = $debug;
		$this->use_pconnect = $use_pconnect;

		if(isset($this->base) && isset($this->user) && isset($this->pass))
		{
			$this->db2 = $this->Connect();
		}
	}

	/**
	* @return void(0)
	* @desc Create the connection to the db2 database, by default we use pconnect
	*/
	public function Connect()
	{
		$connect_func = $this->use_pconnect ? "odbc_pconnect" : "odbc_connect";

		if(!($this->db2 = @$connect_func($this->base, $this->user, $this->pass)))
		{
			throw new Db2_Exception('DB2 Connect to '.$this->base.' as '.$this->user.' failed.',LOG_EMERG);
		}

		return $this->db2;
	}

	/**
	* @return 		bool
	* @param $use	 	bool
	* @desc Use autocommit for you queries
	*/
	public function Autocommit($use)
	{
		if(!odbc_autocommit($this->db2, $use))
		{
			throw new Db2_Exception('DB2 Autocommit failed');
		}

		$this->use_autocommit = $use;
	}

	/**
	* @return 		bool
	* @param $query 	string
	* @desc Run a query against the database, calls Db2_Query_2
	*/
	public function &Query($query)
	{
		$q = new Db2_Query_2($this, $query, $this->debug);
						
		if (!$q->Prepare())
		{
			throw new Db2_Exception('DB2 query prepare failed :' . @odbc_errormsg($this->db2) . "\n".$query."\n");
		}

		return $q;
	}

	public function &Execute($query)
	{
		$q =& $this->Query($query);
		if (Error_2::Check($q))
		{
			return $q;
		}

		$rc = $q->Execute();

		return is_a($rc, "Error_2") ? $rc : $q;
	}

	function Commit()
	{
		if (!@odbc_commit($this->db2))
		{
			throw new Db2_Exception('DB2 Commit failed =>'.@odbc_errormsg($this->db2));
		}

		return TRUE;
	}

	function Rollback()
	{

		if(!@odbc_rollback($this->db2))
		{
			throw new Db2_Exception('DB2 Rollback failed =>'.@odbc_errormsg($this->db2));
		}

		return TRUE;
	}

	function Insert_Id()
	{
		$rc = $this->Execute("values identity_val_local()");
		Error_2::Error_Test ($rc, TRUE);

		$row = $rc->Fetch_Array();
		$id = array_pop($row);
		return $id;
	}

	function Get_Field_Names()
	{
		$field_count = $this->sql = @odbc_num_fields($this->db2);
		return $field_count;
	}
}

class Db2_Query_2
{
	protected $db2;		// parent db2 object
	protected $sql;		// db2 pointer
	protected $query;		// sql statement
	protected $debug;

	function __construct(&$db2, $query, $debug = TRUE)
	{
		$this->db2 =& $db2;
		$this->query = $query;
		$this->debug = $debug;
	}

	function Prepare()
	{
		return $this->sql = @odbc_prepare($this->db2->db2, $this->query);
						
		//return($this->sql = @odbc_prepare($this->db2->db2, $this->query)) ? TRUE : FALSE;
	}

	function Execute()
	{
		if(!is_resource($this->sql))
		{
			throw new Db2_Exception('DB2 query execute failed => Query is not prepared.');
		}

		if(func_num_args())
		{
			$args = func_get_args();
			$rc = @odbc_execute($this->sql, $args);
		}
		else
		{
			$rc = @odbc_execute($this->sql);
		}

		if(!$rc)
		{
			throw new Db2_Exception('DB2 query Execute failed => ' . @odbc_errormsg($this->db2->db2) . "\n".$this->query."\n");
		}

		return TRUE;
	}

	/**
	 * @return int
	 * @desc Returns the number of rows from the query except select statments.
	 * @desc odbc_num_rows is flaky at best with a select statment.

	 * switch the num_rows functions call because of the flaky issues and the need to change
	   the cursor type for performance reasons - eCash needs this result to function - 06/11/04 - swarren
	*/

	function Num_Rows()
	{
		return @odbc_num_rows($this->sql);
	}

	function Num_Fields()
	{
		return @odbc_num_fields($this->sql);
	}

	function Field_Name($pos)
	{
		return @odbc_field_name($this->sql,$pos);
	}

	public function Fetch_Array($rownum = NULL)
	{
		if(is_null($rownum))
			return @odbc_fetch_array($this->sql);
		else
			return @odbc_fetch_array($this->sql, $rownum);
	}

	function Fetch_Object($rownum = NULL)
	{
		if(is_null($rownum))
			return @odbc_fetch_object($this->sql);
		else
			return @odbc_fetch_object($this->sql, $rownum);
	}


	/**
	 * @return int
	 * @desc Returns the id of the last inserted sql query.
	*/
	function Insert_Id()
	{
		$new_id = odbc_result($this->sql, 1);
		return $new_id;
	}
}
?>
