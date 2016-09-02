<?PHP
/** 			
	@version:
			4.0.0 2004-09-27 - A PHP5 wrapper for MySQL
				
	@author:	
			Nick White - version 4.0.0
				
	@Updates:
			1.3	2005-03-03	Andy H
				Get_Table_List function
				replace if($result = $this->Query($database, $query) === FALSE)
				with    if(($result = $this->Query($database, $query)) === FALSE)
				
	@Usage Example:
		- Connect
			$sql = new MySQL_4($host,$login,$password,TRUE);
			try{ $sql->Connect(); }
			catch(MySQL_Exception $e){ Catch Code Here }
		
		- Query
			$query = 'Select * From TABLE';
			$sql->Query($database,$query);
			
			
*/

if(!defined('MYSQL4_LOG'))
{
    define('MYSQL4_LOG',false);
}

require_once('timer.1.php');
require_once('general_exception.1.php');
require_once('mysql_exception.php');
require_once 'applog.singleton.class.php';

class MySQL_4
{
	/*
	* @param $link_id 		int:		Connection internal link id
	* @param $connected_time float: 
	* @param $query_time 	float: 	Time it takes to run a query
	* @param $query_count 	int: 	How many queries ran
	* @param $host 		string: 	Host to connect to
	* @param $user			string:	Database user name
	* @param $password 		string: 	Database password
	* @param debug 		bool: 	Debug mode (true/false)
	*/
	
	protected $link_id;
	protected $connect_time = 0;
	protected $query_time = 0;
	protected $query_count = 0;
	protected $host;
	protected $user;
	protected $password;
	protected $database = NULL;
	protected $debug;
    private $timer = null;
    private $applog = null;
    
	/**
	* @return 		bool
	* @param $host 	string
	* @param $user 	string
	* @param $password 	string
	* @param $debug 	bool
	* @desc Setup the values used in the MySQL_4 class, if you need to pass a port do so in host as host:port
	*/
	function __construct($host = NULL, $user = NULL, $password = NULL, $debug = TRUE)
	{
		
		$this->host = $host;
		$this->user = $user;
		$this->password = $password;
		$this->debug = $debug;
		
        if(MYSQL4_LOG)
        {
            $this->applog = Applog_Singleton::Get_Instance("mysql4", "1000000000", 20, "all", true);
        }
        
		return TRUE;
		
	}
	
	/**
	* @return int
	* @desc Make the connection to the database
 	*/
	public function Connect($use_pconnect = FALSE)
	{
        if(MYSQL4_LOG)
        {
            // Start the timer.
            $this->timer = new Code_Timer();
        }
        
		if(!$this->link_id)
		{
			switch( $use_pconnect )
			{
				case TRUE:
				
					if(($this->link_id = @mysql_connect($this->host, $this->user, $this->password, TRUE)) === FALSE)
					{
						throw new MySQL_Exception('Database connection failed to '.$this->host.' as '.$this->user.'.',$this->debug);
					}
				break;
				
				case FALSE:
					if(($this->link_id = @mysql_connect($this->host, $this->user, $this->password)) === FALSE)
					{
						throw new MySQL_Exception('Database connection failed to '.$this->host.' as '.$this->user.'.',$this->debug);
					}
				break;
			}
			
			if ($this->database)
			{
				$this->Select($this->database, TRUE);
			}
			
		}
	     
        if(MYSQL4_LOG)
        {
    		// End the timer.
    		$this->timer->Stop_Timer();
            //Get Backtrace
            $backtrace = debug_backtrace();
            $caller = $backtrace[0]["file"] . ":" . $backtrace[0]["line"];     
            //Write to Applog
            $this->applog->Write("Elapsed time for [connect:" . $this->host . ":" . $caller .  "]  is " . 
                                 $this->timer->Get_Time(4) . " seconds.");            
            $_SESSION["mysql4_timer"] += (float)$this->timer->Get_Time(4);
        }
        
		// Return the resource
		return $this->link_id;
	}

	/**
	* @return bool
	* @desc Check to see if a connection to the database currently exists
 	*/
	public function Is_Connected()
	{
		return (isset($this->link_id));
	}
	
	public function Select($db, $force = FALSE)
	{
		
		// only run the select if the database has actually changed
		if (($db !== $this->database) || $force)
		{
			
			if (@mysql_select_db($db, $this->link_id) !== FALSE)
			{
				$this->database = $db;
			}
			else
			{
				throw new Exception('Could not select database '.$db, $this->debug);
			}
			
		}
		
		return TRUE;
		
	}

	/**
	* @return int
	* @param $database string
	* @param $query string
	* @desc Run the given query against the given database
	*/
	public function UnbufferedQuery($database, $query)
	{
		if(MYSQL4_LOG)
        {
            // Start the timer.
            $this->timer->Start_Timer();
        }
		
		// Set the temp link id
		$temp_link_id = $this->link_id;
		
		// Select the database
		if ($database !== NULL)
		{
			$this->Select($database);
		}
		
		// Query the database
		if(($result_id = @mysql_unbuffered_query($query, $temp_link_id)) === FALSE)
		{
			throw new Exception(mysql_error($temp_link_id)."\n".$query,$this->debug);
		}
		
		if(MYSQL4_LOG)
        {
            // End the timer.
            $this->timer->Stop_Timer();
            //Get Backtrace
            $backtrace = debug_backtrace();
            $caller = $backtrace[0]["file"] . ":" . $backtrace[0]["line"]; 
            //Write to Applog
            $this->applog->Write("Elapsed time for [unbufferedquery:" . $caller . "]  is " . $this->timer->Get_Time(4) . " seconds.");
            $_SESSION["mysql4_timer"] += (float)$this->timer->Get_Time(4);
            ++$_SESSION["mysql4_query_count"];
        }
		
		// Return the resource
		return $result_id;
	} 
	
	
	/**
	* @return 		int
	* @param $database 	string
	* @param $query 	string
	* @desc Run the given query against the given database
 	*/
	public function Query($database, $query)
	{
        if(MYSQL4_LOG)
        {
            // Start the timer.
            $this->timer->Start_Timer();
        }
        
        //Get Backtrace
        if ($this->debug || MYSQL4_LOG)
		{
			// add backtrace information
			$backtrace = debug_backtrace();
			
        }
        
        if ($this->debug) 
        {
			$log = '/* '.__CLASS__.'::'.__FUNCTION__.'() called from '.$backtrace[0]['file'].', line '.$backtrace[0]['line']." */\n";;
			
			// add to the query
			$query = $log.$query;
		}
		
		// Set the temp link id
		$temp_link_id = $this->link_id;
		
		// Select the database
		if ($database !== NULL)
		{
			$this->Select($database);
		}
		
		// Query the database
		if(($result_id = @mysql_query($query, $temp_link_id)) === FALSE)
		{
			throw new MySQL_Exception(mysql_error($temp_link_id)."\n".$query,$this->debug);
		}
		
		if(MYSQL4_LOG)
        {
            // End the timer.
            $this->timer->Stop_Timer();
            $caller = $backtrace[0]["file"] . ":" . $backtrace[0]["line"]; 
            //Write to Applog
            $this->applog->Write("Elapsed time for [query:" . $caller . "]  is " . $this->timer->Get_Time(4) . " seconds.");
            $_SESSION["mysql4_timer"] += (float)$this->timer->Get_Time(4);
            ++$_SESSION["mysql4_query_count"];
        }
        
		// Return the resource
		return $result_id;
	}

	/**
	* @return 		bool
	* @param $result_id int
	* @desc Free up the results
 	*/
	public function Free_Result($result_id)
	{
		return($result_id ? @mysql_free_result($result_id) : FALSE);
		//return(is_resource($result_id) ? @mysql_free_result($result_id) : FALSE);
	}
	
	/**
	* @return 		int
	* @param $result_id int
	* @desc get the number of rows effected by the given id
 	*/
	public function Row_Count($result_id)
	{
		return($result_id ? @mysql_num_rows($result_id) : 0);
		//return(is_resource($result_id) ? @mysql_num_rows($result_id) : 0);
	}

	/**
	* @return 		int
	* @param $result_id	int
	* @desc get the number of affected rows by the given id
 	*/
	public function Affected_Row_Count()
	{
		return @mysql_affected_rows($this->link_id);
	}

	/**
	* @return 		int
	* @param $result_id int
	* @desc Return the last inserted id for the given id
 	*/
	public function Insert_Id()
	{
		return @mysql_insert_id($this->link_id);
	}

	public function Seek_Row($result_id,$row_id)
	{
		if( $result_id )
		{
			$n = @mysql_num_rows ($result_id);
			$row_id = ($row_id < 0) ? 0 : ($row_id > $n) ? $n : $row_id;
			return @mysql_data_seek($result_id,$row_id);
		} 
		else
		{
			return FALSE;
		}
	}

	public function Fetch_Object_Row($result_id)
	{
		return($result_id ? @mysql_fetch_object($result_id) : FALSE);
		//return(is_resource($result_id) ? @mysql_fetch_object($result_id) : FALSE);
	}

	public function Fetch_Array_Row($result_id)
	{
		return($result_id ? @mysql_fetch_assoc($result_id) : FALSE);
		//return(is_resource($result_id) ? @mysql_fetch_assoc($result_id) : FALSE);
	}

	public function Fetch_Row($result_id)
	{
		return($result_id ? @mysql_fetch_row($result_id) : FALSE);
		//return(is_resource($result_id) ? @mysql_fetch_row($result_id) : FALSE);
	}

	public function Fetch_Column($result_id, $column)
	{
		return($result_id ? @mysql_result($result_id, $column) : FALSE);
		//return(is_resource($result_id) ? @mysql_result($result_id, $column) : FALSE);
	}

	/**
	* @return 		obj
	* @param $database 	string
	* @param $table 	string
	* @desc Return the information about the spcified table
 	*/
	public function Get_Table_Info($database, $table)
	{
		// Get the table information
		$query = "describe ".$table;
		$resource_id = $this->Query($database, $query);

		// Create the object
		$field_list = new stdClass ();

		// Walk the results and build the return object
		while(FALSE !== ($row_data = $this->Fetch_Object_Row ($resource_id)))
		{
			$field_list->{$row_data->Field} = $row_data;
		}
		
		// Return the fields as an object
		return $field_list;
	}

	public function Get_Table_List($database)
	{
		// Get the list of tables
		$query = "show tables";
		if(($result = $this->Query($database, $query)) === FALSE)
		{
			throw new MySQL_Exception(mysql_error($this->link_id),$this->debug);	
		}
		
		// Create the name of default element
		$element = "Tables_in_".$database;

		// Create the object to return
		$table_list = new stdClass();

		// Walk the result set and push into the return object
		while(FALSE !== ($row_data = $this->Fetch_Object_Row($result)))
		{
			$table_list->{$row_data->$element} = TRUE;
		}

		// Return the tables as an object
		return $table_list;
	}
	
	/**
	* @return obj
	* @desc Return a list of databases
 	*/
	public function Get_Database_List()
	{
		// Get the list
		$query = "show databases";
		if(($result = $this->Query("mysql", $query)) === FALSE)
		{
			throw new MySQL_Exception(mysql_error($this->link_id)."\n".$query,$this->debug);
		}
			
		$db_list = new stdClass();
			
		while($row = $this->Fetch_Object_Row($result))
		{
			$db_list->{$row->Database} = TRUE;
		}
				
		// Return the list of Databases as an object
		return $db_list;
	}
	
	/**
	 * Creates a new table like another for versions of MySQL that don't have
	 * the CREATE TABLE LIKE syntax.
	 *
	 * @param string $database
	 * @param string $source
	 * @param string $target
	 * @return bool
	 */
	public function Create_Table_Like($database, $source, $target)
	{
		// Get the table schema of the source
		$query = "SHOW CREATE TABLE `$source`";
		$result = $this->Query($database, $query);
		
		if(($row = $this->Fetch_Array_Row($result)))
		{
			// Get the create table query and replace the source name with the target table name
			$query = $row['Create Table'];
			$query = preg_replace('/`'.preg_quote($source).'`/', $target, $query, 1);
			
			// Create the new table
			$result = $this->Query($database, $query);
		}
		
		return $result;
	}

	private function Get_Total_Time()
	{
		return $this->connect_time + $this->query_time;
	}

	public function Get_Error()
	{
		return @mysql_error($this->link_id);
	}

	public function Get_Errno()
	{
		return @mysql_errno($this->link_id);
	}
	
	public function Close_Connection()
	{
		$this->__destruct();
		return TRUE;
	}
	
	function __destruct()
	{
		@mysql_close($this->link_id);
		$this->link_id = NULL;
	}
}
?>
