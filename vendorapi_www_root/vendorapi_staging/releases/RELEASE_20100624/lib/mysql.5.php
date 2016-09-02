<?php

require_once("mysql_exception.php");

/**
 * Database abstraction layer designed to work exactly like MySQLi_1, allowing
 * easy transition between mysql and mysqli
 *
 * Created  on Sep 21, 2005
 *
 * @access public
 *
 * @author Jason Belich <jason.belich@sellingsource.com>
 * @copyright Copyright &copy; 2005 The Selling Source, Inc.
 *
 * @version $Revision: 2744 $
 */


// omg doug u should no how mysql5 works dont say my name
class MySQL_5 {

	/**
	* The hostname of the MySQL server
	*
	* @access private
	* @var    string
	*/
	private $host;

	/**
	* The name of the default MySQL database to run queries on
	*
	* @access private
	* @var    string
	*/
	private $database;

	/**
	* The username to use when logging into MySQL
	*
	* @access private
	* @var    string
	*/
	private $username;

	/**
	* The password of the username to login to MySQL with
	*
	* @access private
	* @var    string
	*/
	private $password;

	/**
	* Holds the mysql resource for performing queries
	*
	* @access private
	* @var    resource
	*/
	private $link_id;

	/**
	* Whether or not MySQL is currently within a query transaction.
	*
	* @access private
	* @var    boolean
	*/
	private $in_query = FALSE;

	/**
	* Sets the class default commit mode
	*
	* @access private
	* @var    boolean
	*/
	private $commit_mode;

	/**
	* constructor for setting up the initial connection to MySQL.
	*
	* @param string $host  Host of the MySQL server
	* @param string $db    Database name to use for queries
	* @param string $user  Username to use to login to MySQL
	* @param string $pass  Password of the username set above
	*
	* @access public
	* @throws MySQL_Exception
	* @return void
	*/
	public function __construct ($host = NULL, $user = NULL, $pass = NULL, $db = NULL, $port = NULL)
	{
		if($host == NULL && !$this->parseDSN(DB_DSN)) {
			$this->host     = DB_HOST;
			$this->database = DB_NAME;
			$this->username = DB_USER;
			$this->password = DB_PASS;

		} elseif ($host != NULL && !$this->parseDSN($host)) {
			$this->host     = $host;
			$this->database = $db;
			$this->username = $user;
			$this->password = $pass;

		}

		if (!$this->username && isset($user)) $this->username  = $user;
		if (!$this->password && isset($pass)) $this->password  = $pass;

		$this->link_id = mysql_connect(	$this->host . (isset($port) ? ":" . $port : NULL) ,
		                           		$this->username,
		                           		$this->password,
		                           		true );

		if (!$this->link_id)
		{
			throw new MySQL_Exception("MySQL Connection Error: " . mysql_error());
		}

		$this->Change_Database($this->database);

		// Default auto commit to true on construct
		$this->Auto_Commit();
	}

	private function parseDSN($dsn)
	{
		if (!preg_match("/^mysql:/", $dsn)) {
			return false;
		}

		if(!preg_match_all("/(\w+)=([^;]*)/",$dsn,$matches,PREG_PATTERN_ORDER)) {
			return false;
		}
		$new  = array_combine($matches[1],$matches[2]);

		$this->host = $new['host'];
		$this->database = $new['dbname'];
		if($new['username']) $this->username = $new['username'];
		if($new['password']) $this->password = $new['password'];

		return true;
	}

	public function Auto_Commit($mode = TRUE)
	{
		$this->commit_mode = ($mode) ? TRUE: FALSE;

		mysql_query("SET AUTOCOMMIT = " . (int) $this->commit_mode, $this->Get_Link());

		return TRUE;
	}

	/**
	* Returns a boolean indicating whether or not the database change worked
	*
	* @param string $db		The database to change to
	*
	* @access public
	* @return boolean
	*/
	public function Change_Database($db)
	{
		$this->database = $db;

		if (mysql_select_db($this->database, $this->Get_Link()) === false) {
			throw new MySQL_Exception("MySQL DB Selection Error: " . mysql_error());
		}

		return true;

	}

	/**
	* Returns a boolean indicating whether or not the database is currently
	* in a query transaction.
	*
	* @access public
	* @return boolean
	*/
	public function In_Query()
	{
		return $this->in_query;
	}

	/**
	* Queries the connected database. A result set (array) is only returned
	* if you specify to return one.
	*
	* @param string $query		query to send to run
	* @param string $database	optional database to run the query on
	*
	* @example   Returning a result
	*            $db     = new MySQLi_1(...);
	*            $result = $db->Query("SELECT * FROM table");
	*            $row    = $result->Fetch_Object_Row();
	*
	* @access public
	* @throws MySQL_Exception
	* @return MySQL_Result_5	The MySQLi result object for the query
	*
	*/
	public function Query($query, $database = NULL)
	{
		//change databases, just for this query
		if($database)
		{
			$this->Change_Database($database);
		}
		$GLOBALS['queries'][] = $query;
		try
		{
			$result = new MySQL_Result_5($this, mysql_query($query, $this->Get_Link()));
		}
		catch(Exception $e)
		{

			if($database)
			{
				$this->Change_Database($database);
			}

			$error = mysql_error($this->Get_Link());

			$backtrace = debug_backtrace();

			$message = "{$error} in query: \"{$query}\" executed from {$backtrace[0]['file']} at line {$backtrace[0]['line']}";

			throw new MySQL_Exception($message, mysql_errno($this->Get_Link()));
		}

		//change the db back
		if($database)
		{
			$this->Change_Database($database);

		}

		return $result;
	}


	/**
	* Gets affected row count for last MySQL operation
	*
	* @access public
	* @return integer
	*/
	public function Affected_Row_Count()
	{
		return mysql_affected_rows($this->Get_Link());
	}

	/**
	* Gets the last insert id
	*
	* @access public
	* @return integer
	*/
	public function Insert_Id()
	{
		return mysql_insert_id($this->Get_Link());
	}

	/**
	* Gets a one-dimensional array for a select with one column
	*
	* @param string $query		one column select statement
	* @param string $database	optional database to run the select on
	*
	* @access public
	* @throws MySQL_Exception
	* @return array
	*/
	public function Get_Column($query, $database = NULL)
	{
		$result = $this->Query($query, $database);

		if( $result->Field_Count() > 1 )
		{
			$result->Close();
			throw new MySQL_Exception( "Too many columns in the result set for Get_Column to deal with" );
		}

		$return_array = array();

		while ($row = $result->Fetch_Row())
		{
			array_push($return_array, $row[0]);
		}

		$result->Close();
		return $return_array;

	}

   /**
	* Gets an object of table information
	*
	* @param string $table		table name
	* @param string $database	optional database to look for table in
	*
	* @access public
	* @throws MySQL_Exception
	* @return object
	*/
	public function Get_Table_Info($table, $database = NULL)
	{
		// Get the table information
		$query = "describe ".$table;
		$result = $this->Query($query, $database);

		// Create the object
		$field_list = (object)array();

		// Walk the results and build the return object
		while( $row_data = $result->Fetch_Object_Row() )
		{
			$field_list->{$row_data->Field} = $row_data;
		}

		// Return the fields as an object
		return $field_list;

	}

	/**
	* Gets an object with a table list
	*
	* @param string $database	optional database to look for tables in
	*
	* @access public
	* @throws MySQL_Exception
	* @return object
	*/
	public function Get_Table_List($database = NULL)
	{
		// Get the list of tables
		$query = "show tables";
		$result = $this->Query($query, $database);

		// Create the name of default element
		$element = "Tables_in_".$this->database;

		// Create the object to return
		$table_list = (object)array();

		// Walk the result set and push into the return object
		while( $row_data = $result->Fetch_Object_Row() )
		{
			$table_list->{$row_data->$element} = TRUE;
		}

		// Return the tables as an object
		return $table_list;
	}

	/**
	* Gets an object with a database list
	*
	* @access public
	* @throws MySQL_Exception
	* @return object
	*/
	public function Get_Database_List()
	{
		// Get the list
		$query = "show databases";
		$result = $this->Query($query,"mysql");

		$db_list = (object)array();

		while($row = $result->Fetch_Object_Row())
		{
			$db_list->{$row->Database} = TRUE;
		}

		// Return the list of Databases as an object
		return $db_list;
	}


	/**
	* Begin a MySQL query transaction.
	*
	* @access public
	* @throws MySQL_Exception
	* @return boolean
	*/
	public function Start_Transaction ()
	{
		if (!$this->in_query)
		{
			$result = $this->Auto_Commit(false);

			if(!$result)
			{
				throw new MySQL_Exception(mysql_error());
			}

			$this->in_query = $result;

		}
		else
		{
			throw new MySQL_Exception("Already in a transaction");
		}
	}

	/**
	* Commit the previously-started query transaction and return any
	* data MySQL spits out.
	*
	* @access public
	* @throws MySQL_Exception
	* @return boolean
	*/
	public function Commit()
	{
		if ($this->in_query)
		{
			$result = mysql_query("COMMIT", $this->Get_Link());

			$this->in_query = FALSE;

			if( $this->commit_mode )
				$this->Auto_Commit(TRUE);

			return (bool) is_resource($result);
		}
		else
		{
			throw new MySQL_Exception("No transaction has been started - no commit performed.");
		}
	}

	/**
	* Roll back the previously-started query transaction so that no
	* changes are made and return any data MySQL spits out.
	*
	* @access public
	* @return boolean
	*/
	public function Rollback ()
	{

		if ($this->in_query)
		{
			$result = mysql_query("ROLLBACK", $this->Get_Link());

			$this->in_query = FALSE;

			if( $this->commit_mode )
				$this->Auto_Commit(TRUE);

			return (bool) is_resource($result);
		}
		else
		{
			throw new MySQL_Exception("No transaction has been started - no rollback performed.");
		}
	}

	/**
	* Prepares a query
	*
	* @access public
	* @throws MySQL_Exception
	* @return mysqli_stmt
	*/
	public function Prepare ($query)
	{
		throw new MySQL_Exception ("__METHOD__ not implemented");
	}

	public function Get_Link ()
	{
		if(is_resource($this->link_id))
			return $this->link_id;

		throw new MySQL_Exception(__METHOD__ . " Error");
	}

	public function Escape_String ($string)
	{
		return mysql_real_escape_string($string, $this->Get_Link());
	}

	private function Reset ()
	{
		if ($this->in_query)
		{
			$this->Rollback();
			$this->in_query = FALSE;
		}
	}

	public function Get_Error()
	{
		return mysql_error($this->Get_Link());
	}

	public function Get_Errno()
	{
		return mysql_errno($this->Get_Link());
	}

	public function Close()
	{
		return mysql_close($this->Get_Link());
	}

	public function __destruct()
	{
		$this->Close();
	}

}

class MySQL_Result_5
{
	private $result;

   	public function __construct(MySQL_5 $mysql, $result)
	{
		if(!is_resource($result) && $result !== true)
		{
			throw new MySQL_Exception("Query Error: " . mysql_error(), mysql_errno());
		}

		$this->result = $result;
	}

	public function Fetch_Row()
	{
		return mysql_fetch_row($this->result);
	}

	public function Fetch_Array_Row($result_type = NULL)
	{
		if($result_type === NULL)
		{
			return mysql_fetch_array($this->result);
		}
		else
		{
			return mysql_fetch_array($this->result, $result_type);
		}
	}

	public function Row_Count()
	{
		return mysql_num_rows($this->result);
	}

	public function Seek_Row($row_num)
	{
			$n = $this->Row_Count() - 1;
			$row_num = ($row_num < 0) ? 0 : ($row_num > $n) ? $n : $row_num;
			mysql_data_seek($row_num);
			return $this->Fetch_Object_Row();
	}

	public function Fetch_Object_Row()
	{
		return mysql_fetch_object($this->result);
	}

	public function Field_Count()
	{
		return mysql_num_fields($this->result);
	}

	public function Close()
	{
		mysql_free_result($this->result);
	}
}
