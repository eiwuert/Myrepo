<?php
/**
 * Wraps the php PDO functions
 *
 */

// may already be loaded for remote administration
if (!class_exists('PearWrapper')) :
	
	ini_set('include_path',ROOT_PATH.'cms-includes:'.ROOT_PATH.'cms-includes/pear:'.ini_get('include_path'));	
	
	require_once("DB.php");
	class PearWrapper
	{
		private $dbh;
		private $dsn;
		private $error;
		private $num_queries;
		private $failed_queries;
		
		/**
		 * Expects an array like this:
		 * 
		 * 	
		 * $dsn = array(
			    'phptype'  => "mysql",
			    'hostspec' => DB_HOST,
			    'database' => DB_NAME,
			    'username' => DB_USER,
			    'password' => DB_PASSWORD
			);	
		 *
		 * @param array $dsn
		 */
		public function __construct($dsn)
		{
			$this->dsn = $dsn;
		}
		
		/**
		 * destroys the object
		 *
		 */
		function __destruct()
		{
			$this->dsn = null;
		}
		
		/**
		 * Commit a transaction
		 *
		 */
		public function commit()
		{
			$this->dbh->commit();
		}
		
		/**
		 * Begin a transaction
		 *
		 */
		public function begin()
		{
			$this->dbh->autoCommit(false);
		}
		
		/**
		 * Rollback a transaction
		 *
		 * @param string $line
		 * @param string $msg
		 */
		public function rollback($line = null, $msg = null)
		{
			if ($line)
				lum_logMe($line);
			if ($msg)
				lum_logMe($msg);
			$this->dbh->rollback();
		}
		
		/**
		 * connect to the database
		 *
		 * @return bool
		 */
	    public function connect()
	    {
		$db = new DB();
		$this->dbh = $db->connect($this->dsn);
		
//		var_dump($this->dbh);
		
		$pear = new PEAR();
		if ($pear->isError($this->dbh)) 
		{
			$this->setError($this->dbh->getMessage());
			return false;
		}
			else
			{
				return true;
			}
	    }
		
	    /**
	     * Returns a single row from a table
	     *
	     * @param string $sql
	     * @param array $value_array
	     * @param bool $asArray
	     * @return a single from from the table or false
	     */
	    public function getRow($sql, $value_array = null, $asArray = false)
	    {
		$rows = $this->getRows($sql, $value_array, $asArray);
		if (is_array($rows))
		{
				if (count($rows) > 0)
					return $rows[0];
					
				return array();
		}
		return false;
	    }
	    
	    /**
	     * Returns multiple rows from a table
	     *
	     * @param string $sql
	     * @param array $value_array
	     * @param bool $asArray
	     * @return array of rows or false
	     */
	    public function getRows($sql, $value_array = null, $asArray = false)
	    {
		$pear = new PEAR();
		$fetchMode = DB_FETCHMODE_OBJECT; 		    	
		if ($asArray)
			$fetchMode = DB_FETCHMODE_ASSOC;
			
		if ($value_array != null)
		{
			$sql = $this->dbh->prepare($sql);
				$res = &$this->dbh->execute($sql, $value_array);
		}
		else
		{
			$res = &$this->dbh->query($sql);
		}
			if ($pear->isError($res))
			{
				$this->setError($res->getMessage(). " ".$res->getDebugInfo());
				lum_logMe("SQL Error Message: " . $res->getMessage()."\r\n");
				lum_logMe("SQL Error Debug Info: " . $res->getDebugInfo()."\r\n");
				lum_logMe("SQL Query: " . $sql."\r\n\r\n");
				return false;
			}
	
			$rows = array();
			$obj = null;
			while ($obj =& $res->fetchRow($fetchMode)) 
			{
				if (isset($use_key) && isset($use_val))
				{
					$key = '';
					$val = '';
					if (is_array($obj))
					{
						$key = $obj[$use_key];
						$val = $obj[$use_val];
					}
					else 
					{
						$key = $obj->$use_key;
						$val = $obj->$use_val;
					}
					$rows[$key] = $val;
				}
			    $rows[] = $obj;
			}
	
			return $rows;    	
	    }
	
	    /**
	     * Inserts a row into a table
	     *
	     * @param string $sql
	     * @param array $value_array
	     * @return autoincrement id or false
	     */
	    public function doInsert($sql, $value_array = null)
	    {
		$pear = new PEAR();
		if ($value_array != null)
		{
			$sql = $this->dbh->prepare($sql);
				$res = &$this->dbh->execute($sql, $value_array);
		}
		else
		{
			$res = &$this->dbh->query($sql);
		}
	
			if ($pear->isError($res))
			{
				$this->setError($res->getMessage(). " ".$res->getDebugInfo());
				lum_logMe("SQL Error Message: " . $res->getMessage()."\r\n");
				lum_logMe("SQL Error Debug Info: " . $res->getDebugInfo()."\r\n");
				lum_logMe("SQL Query: " . $sql."\r\n\r\n");
				return false;
			}
			return mysql_insert_id($this->dbh->connection);
	    }     
	
	    /**
	     * performs a query on the database
	     *
	     * @param string $sql
	     * @param array $value_array
	     * @return affected rows (int) or false on error
	     */
	    public function doQuery($sql, $value_array = null)
	    {
		$pear = new PEAR();
		if ($value_array != null)
		{
			$sql = $this->dbh->prepare($sql);
				$res = &$this->dbh->execute($sql, $value_array);
		}
		else
		{
			$res = &$this->dbh->query($sql);
		}
	
			if ($pear->isError($res))
			{
				$this->setError($res->getMessage(). " ".$res->getDebugInfo());
				lum_logMe("SQL Error Message: " . $res->getMessage()."\r\n");
				lum_logMe("SQL Error Debug Info: " . $res->getDebugInfo()."\r\n");
				lum_logMe("SQL Query: " . $sql."\r\n\r\n");
				return false;
			}
			return $this->dbh->affectedRows();
	    }     
	    
	    /**
	     * sets the error string if a SQL error occurred
	     *
	     * @param array or string $err
	     */
	    private function setError($err)
	    {
		$msg = $err;
		if (is_array($err))
		{
			$msg = "";
			foreach ($err as $n)
			{
				$msg .= "$n: $err[$n]";
			}
		}
		$this->error = $msg;
		lum_logMe('SQL Error: '.$msg);
	    }
	      
	    /**
	     * returns the error
	     *
	     * @return string
	     */
	    public function getError()
	    {
		return $this->error;
	    }
	      
	    /**
	     * just to keep track of the number of queries
	     * used in a page load. Used for performace
	     * optimization
	     *
	     */
	    private function incremetQueries()
	    {
		$this->num_queries++;
	    }
	    
	    private function resetQueries()
	    {
		$this->num_queries = 0;
	    }
	    
	    public function getNumQueries()
	    {
		return $this->num_queries;
	    }
	    
	    private function incremetFailedQueries()
	    {
		$this->num_queries++;
	    }
	    
	    private function resetFailedQueries()
	    {
		$this->failed_queries = 0;
	    }
	    
	    public function getNumFailedQueries()
	    {
		return $this->failed_queries;
	    }  
	}

endif;

?>