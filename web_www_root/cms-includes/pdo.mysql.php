<?php
/**
 * Wraps the php PDO functions
 *
 */

// may already be loaded for remote administration
if (!class_exists('PdoWrapper')) :
	
	class PdoWrapper
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
			$this->dbh->beginTransaction();
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
			try 
			{
			$this->dbh = new PDO ($this->dsn['phptype'].':host='.$this->dsn['hostspec'].';dbname='.$this->dsn['database'], $this->dsn['username'], $this->dsn['password']);
			$this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
			$this->doQuery('SET NAMES utf8', null);
			return true;
		}
			catch(PDOException $e)
		{
			$this->setError($e->getMessage());
			return false;
		}  
			
		return false;  	
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
				if (count($rows) == 0)
					return $rows;
				
			return $rows[0];
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
		$stmt = null;
		
		    try 
		    {
			$stmt = $this->dbh->prepare($sql);
		    } 
		    catch(PDOExecption $e) 
		    {
				$this->setError($e->getMessage());
				return false;
		    } 
		
		    if ($stmt->execute($value_array))
		    {
				$rows = false;
				if ($asArray)
				{
					$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
				}
				else
				{
					$rows = $stmt->fetchAll(PDO::FETCH_OBJ);
				}
				
				$this->release($stmt);
				return $rows;
		    }
		    $this->setError( $stmt->errorInfo() );
			return false;
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
		if ($this->doQuery($sql, $value_array))
			return $this->dbh->lastInsertId();
		
		return false;
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
		$this->incremetQueries();
		$stmt = null;
		    try 
		    {
			$stmt = $this->dbh->prepare($sql);
		    } 
		    catch(PDOExecption $e) 
		    {
				$this->setError($e->getMessage());
				return false;
		    } 
		    if ($stmt->execute( $value_array ))
			return $stmt->rowCount();
			
			$this->setError( $stmt->errorInfo() );	    	
		    return false;
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
		lum_logError('SQL Error: '.$msg);
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
	    
	    /**
	     * resets the cursor after each query
	     *
	     * @param pdf statement object $stmt
	     */
	    function release($stmt)
	    {
			$stmt->closeCursor();
	    }
	}

endif;
?>
