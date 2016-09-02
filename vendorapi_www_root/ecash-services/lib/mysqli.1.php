<?php

require_once("mysql_exception.php");

define('MYSQLI_CACHE_TIME_ONE_SECOND' , 1                                  );
define('MYSQLI_CACHE_TIME_ONE_MINUTE' , MYSQLI_CACHE_TIME_ONE_SECOND * 60  );
define('MYSQLI_CACHE_TIME_ONE_HOUR'   , MYSQLI_CACHE_TIME_ONE_MINUTE * 60  );
define('MYSQLI_CACHE_TIME_ONE_DAY'    , MYSQLI_CACHE_TIME_ONE_HOUR   * 24  );
define('MYSQLI_CACHE_TIME_ONE_WEEK'   , MYSQLI_CACHE_TIME_ONE_DAY    * 7   );
define('MYSQLI_CACHE_TIME_ONE_MONTH'  , MYSQLI_CACHE_TIME_ONE_DAY    * 30  );
define('MYSQLI_CACHE_TIME_ONE_YEAR'   , MYSQLI_CACHE_TIME_ONE_DAY    * 365 );

/**
* Database abstraction layer based on
* Partner Weekly's MySQL functionality.
*
* @access public
*/
class MySQLi_1
{
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
	 * Port of the MySQL server
	 * @var int
	 */
	private $port;

	/**
	 * UNIX socket of the MySQL server
	 * @var string
	 */
	private $socket;

	/**
	* Holds the mysqli object for performing queries
	*
	* @access private
	* @var    mysqli
	*/
	private $mysqli;

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
	 * Determines if the query should use a disk-based cache
	 *
	 * @access private
	 * @var    boolean
	 */
	private $disk_cache_enabled;

	/**
	 * Defines the location of the disk-based cache
	 *
	 * @access private
	 * @var    string
	 */
	private $disk_cache_location;
	
	/**
	 * Time it takes for the connection to timeout in seconds.
	 *
	 * @var int
	 */
	protected $connection_timeout;

	/**
	* constructor for setting up the initial connection to MySQL.
	*
	* @param string $host  Host of the MySQL server
	* @param string $db    Database name to use for queries
	* @param string $user  Username to use to login to MySQL
	* @param string $pass  Password of the username set above
	* @param int $connection_timeout an integer of seconds to wait for connection timeout
	*
	* @access public
	* @throws MySQL_Exception
	* @return void
	*/
	public function __construct(
		$host = NULL,
		$user = NULL,
		$pass = NULL,
		$db = NULL,
		$port = NULL,
		$socket = NULL,
		$connection_timeout = NULL
	)
	{
		if ($host === NULL && (!defined('DB_DSN') || !$this->parseDSN(DB_DSN)))
		{
			$this->host = @DB_HOST;
			$this->database = @DB_NAME;
			$this->username = @DB_USER;
			$this->password = @DB_PASS;
			$this->port = @DB_PORT;
		}
		elseif (!$this->parseDSN($host))
		{
			$this->host = $host;
			$this->database = $db;
			$this->username = $user;
			$this->password = $pass;
			$this->port = $port;
			$this->socket = $socket;
		}
		else
		{
			// the DSN may or may not contain these..?
			if (!$this->username) $this->username  = $user;
			if (!$this->password) $this->password  = $pass;
		}

		// allow them to append the port in both the DSN and params
		if(strpos($this->host, ":") !== FALSE)
		{
			list($this->host, $this->port) = explode(":", $this->host);
		}
		
		if ($connection_timeout != NULL)
		{
			$this->connection_timeout = (int)$connection_timeout;
		} 

		$this->Connect();

		// Default auto commit to true on construct
		$this->Auto_Commit();

		$this->disk_cache_enabled = NULL;
		$this->disk_cache_location = '/tmp/mysqli_cache';
	}

	public function Get_Thread_Id()
	{
		if ($this->mysqli)
		{
			return @$this->mysqli->thread_id;
		}
		throw new MySQL_Exception("MySQLi thread_id not set because no connection exists.");
	}
	
	/**
	 * Connects to the database.
	 *
	 * @return void
	 */
	public function Connect()
	{
		$this->mysqli = mysqli_init();
		
		if ($this->connection_timeout != NULL)
		{
			$this->mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, $this->connection_timeout);
		}
		
		if ($this->socket == NULL)
		{
			$this->mysqli->real_connect(
				$this->host,
				$this->username,
				$this->password,
				$this->database,
				$this->port
			);
		}
		else
		{
			$this->mysqli->real_connect(
				$this->host,
				$this->username,
				$this->password,
				$this->database,
				$this->port,
				$this->socket
			);
		}

		if (mysqli_connect_errno())
		{
			throw new MySQL_Exception("MySQLi Connection Error: ".mysqli_connect_error());
		}
	}

	/**
	 * @brief Attempts to enable query caching to disk
	 *
	 * This attempts to find/create the base disk cache directory,
	 * and confirm its permissions.  If successful, it will also
	 * internally flag the success and allow caching of results to
	 * begin.
	 *
	 * @return BOOLEAN - success
	 */
	public function disk_cache_enable($new_location = NULL)
	{
		if(NULL !== $new_location)
		{
			$this->disk_cache_enabled = NULL;
			$this->disk_cache_location = $new_location;
		}

		if(NULL !== $this->disk_cache_enabled)
		{
			return($this->disk_cache_enabled);
		}

		try
		{
			if(!@file_exists($this->disk_cache_location))
			{
				@mkdir($this->disk_cache_location);
			}
			if(!@is_dir($this->disk_cache_location))
			{
				throw(new Exception("Cache location is not a directory"));
			}
			if(!@is_writeable($this->disk_cache_location))
			{
				throw(new Exception("Cache location is not writeable"));
			}
			if(!@is_readable($this->disk_cache_location))
			{
				throw(new Exception("Cache location is not readable"));
			}
		}
		catch(Exception $e)
		{
			$this->disk_cache_enabled = FALSE;
			return(FALSE);
		}

		$this->disk_cache_enabled = TRUE;
		return(TRUE);
	}

	public function Intelligent_Escape($value)
	{
		if(is_null($value))
		{
			return('NULL');
		}
		if(is_int($value) || is_float($value))
		{
			return($value);
		}
		if(is_bool($value))
		{
			return($value ? 1 : 0);
		}
		if(is_string($value))
		{
			return("'".$this->Escape_String($value)."'");
		}
		return(NULL);
	}

	/**
	 * @brief Wraps the Query() method and always returns the full
	 * result set as a nested array instead of result resources
	 *
	 * @param $query Passed through to Query()
	 *
	 * @param $cache_seconds INTEGER - How many seconds a cached
	 * query should be considered valid
	 *
	 * @param $database Passed through to Query()
	 *
	 * @return array(row_number => array(column_name => value))
	 */
	public function Cache_Query($query, $cache_seconds, $database = NULL)
	{
		$filename = $this->disk_cache_location . "/" . md5($query) . ".serialized.txt";

		if($this->disk_cache_enable())
		{
			if(file_exists($filename) && is_readable($filename) && (time()-$cache_seconds) <= filemtime($filename))
			{
				try
				{
					$contents = file_get_contents($filename);
					$return = unserialize($contents);
					return($return);
				}
				catch(Exception $e)
				{
				}
			}
		}

		$result = $this->Query($query, $database);
		if(NULL === $result)
		{
			return($result);
		}

		$fields = $result->Get_Fields();

		$results = array();
		while(NULL !== ($row = $result->Fetch_Array_Row(MYSQLI_ASSOC)))
		{
			$results[] = $row;
		}

		$return = new MySQLi_Cached_Result($query, $fields, $results);

		if($this->disk_cache_enable())
		{
			file_put_contents($filename, serialize($return));
		}

		return($return);
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
		$this->mysqli->autocommit($this->commit_mode);

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
		return $this->mysqli->select_db($this->database);
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
	* @return MySQLi_Result_1	The MySQLi result object for the query
	*
	*/
	public function Query($query, $database = NULL)
	{
		try
		{
			//change databases, just for this query
			if($database)
			{
				$this->mysqli->select_db($database);
			}

			$result = new MySQLi_Result_1($this, $this->mysqli->query($query));
		}
		catch(Exception $e)
		{
			if($database)
			{
				$this->mysqli->select_db($this->database);
			}

			$error = $this->Get_Error();

			$backtrace = debug_backtrace();

			$message = "{$error} in query: \"{$query}\" executed from {$backtrace[0]['file']} at line {$backtrace[0]['line']}";

			throw new MySQL_Exception($message);
		}

		//change the db back
		if($database)
		{
			$this->mysqli->select_db($this->database);
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
		return $this->mysqli->affected_rows;
	}

	/**
	* Gets the last insert id
	*
	* @access public
	* @return integer
	*/
	public function Insert_Id()
	{
		return $this->mysqli->insert_id;
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
		$element = ($database) ? "Tables_in_".$database : "Tables_in_" . $this->database;

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
			$result = $this->mysqli->autocommit(FALSE);

			if(!$result)
			{
				throw new MySQL_Exception($this->mysqli->error);
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
			$result = $this->mysqli->commit();

			$this->in_query = FALSE;

			if( $this->commit_mode )
				$this->mysqli->autocommit(TRUE);

			return $result;
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
			$result = $this->mysqli->rollback();

			$this->in_query = FALSE;

			if( $this->commit_mode )
				$this->mysqli->autocommit(TRUE);

			return $result;
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

		$stmt = $this->mysqli->prepare($query);

		if(!$stmt)
		{
			if($this->mysqli->error)
			{
				throw new MySQL_Exception($this->mysqli->error);
			}
			else
			{
				throw new MySQL_Exception("Error preparing query -- possibly syntax or table/column identifiers");
			}
		}

		return $stmt;
	}

	public function Get_Link ()
	{
		return $this->mysqli;
	}

	public function Escape_String ($string)
	{
		return $this->mysqli->real_escape_string($string);
	}

	private function Reset ()
	{
		if ($this->in_query)
		{
			$this->Rollback();
			$this->in_query = FALSE;
		}
	}

	public function Ping()
	{
		if(! ($this->mysqli instanceof mysqli))
		{
			$this->Reset_Connection();
		}

		if(! @$this->mysqli->ping())
		{
			$this->Reset_Connection();
			return $this->mysqli->ping();
		}
		else
			return true;
	}

	public function Reset_Connection()
	{
		if($this->in_query)
		{
			throw new MySQL_Exception("Lost connection in the middle of a transaction, will not attempt a reconnection.");
		}

		unset ($this->mysqli);
		$this->Connect();
	}

	public function Get_Error()
	{
		return $this->mysqli->error;
	}

	public function Get_Errno()
	{
		return $this->mysqli->errno;
	}

	/**
	 * Accessor for commit mode
	 *
	 * Added for Jason Schmidt by Justin Foell
	 */
	public function Get_Commit_Mode()
	{
		return($this->commit_mode);
	}

	public function Close()
	{
		return $this->mysqli->close();
	}

	public function __destruct()
	{
		@$this->Close();
	}

}

class MySQLi_Result_1
{
	private $result;

   	public function __construct(MySQLi_1 $mysqli, $result)
	{
		if($result !== TRUE && !$result instanceof mysqli_result)
		{
			throw new MySQL_Exception($mysqli->Get_Error() . $mysqli->Get_Errno());
		}

		$this->result = $result;
	}

	public function Fetch_Row()
	{
		return $this->result->fetch_row();
	}

	public function Fetch_Array_Row($result_type = NULL)
	{
		if($result_type === NULL)
		{
			return $this->result->fetch_array();
		}
		else
		{
			return $this->result->fetch_array($result_type);
		}
	}

	public function Row_Count()
	{
		return $this->result->num_rows;
	}

	public function Seek_Row($row_num)
	{
			$n = $this->result->num_rows - 1;
			$row_num = ($row_num < 0) ? 0 : ($row_num > $n) ? $n : $row_num;
			$this->result->data_seek($row_num);
			return $this->result->fetch_object();
	}

	public function Fetch_Object_Row()
	{
		return $this->result->fetch_object();
	}

	public function Field_Count()
	{
		return $this->result->field_count;
	}

	public function Get_Fields()
	{
		return $this->result->fetch_fields();
	}

	public function Close()
	{
		if ($this->result instanceof mysqli_result)
		{
			$this->result->close();
		}
	}
}

class MySQLi_Cached_Result
{
	public  $query;
	private $result_fields;
	private $result_data;
	private $row_number;

	public function __construct($query, $fields, $result)
	{
		$this->query = $query;
		$this->result_fields = $fields;
		$this->result_data = $result;
		$this->row_number = 0;
	}

	private function Numbered_Columns($row)
	{
		if(!is_array($row))
		{
			return($row);
		}

		$return = array();
		foreach($row as $val)
		{
			$return[] = $val;
		}
		return($return);
	}

	public function Fetch_Row()
	{
		if(!isset($this->result_data[$this->row_number]))
		{
			return(FALSE);
		}

		return($this->Numbered_Columns($this->result_data[$this->row_number ++]));
	}

	public function Fetch_Array_Row($result_type = NULL)
	{
		if(!isset($this->result_data[$this->row_number]))
		{
			return(NULL);
		}

		if(MYSQLI_ASSOC === $result_type)
		{
			return($this->result_data[$this->row_number ++]);
		}
		elseif(MYSQLI_NUM === $result_type)
		{
			return($this->Numbered_Columns($this->result_data[$this->row_number ++]));
		}
		elseif(MYSQLI_BOTH === $result_type || NULL === $result_type)
		{
			$return = $this->result_data[$this->row_number ++];
			$return = array_merge($return, $this->Numbered_Columns($return));
			return($return);
		}

		return(NULL);
	}

	public function Row_Count()
	{
		return(count($this->result_data));
	}

	public function Seek_Row($row_num)
	{
		$n = $this->Row_Count() - 1;
		$row_num = ($row_num < 0) ? 0 : ($row_num > $n) ? $n : $row_num;
		$this->row_number = $row_num;
		return($this->Fetch_Object_Row());
	}

	public function Fetch_Object_Row()
	{
		if(!isset($this->result_data[$this->row_number]))
		{
			return(NULL);
		}

		$return = new stdClass();

		foreach($this->result_data[$this->row_number ++] as $col => $value)
		{
			$return->$col = $value;
		}

		return($return);
	}

	public function Field_Count()
	{
		return(count($this->result_fields));
	}

	public function Get_Fields()
	{
		return($this->result_fields);
	}

	public function Close()
	{
		// Cached object, nothing to do really
	}
}

?>
