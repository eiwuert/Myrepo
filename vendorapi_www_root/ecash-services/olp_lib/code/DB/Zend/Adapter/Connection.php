<?php
/**
 * Zend_Db connection adapter for DB_Database_1 provides
 * a wrapper for using our existing PDO wrapper within
 * the Zend framework
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class DB_Zend_Adapter_Connection extends Zend_Db_Adapter_Pdo_Mysql
{
	/**
	 * Constructor
	 * 
	 * @param DB_Database_1 $db Connection object to use for target
	 * @param  array|Zend_Config $config An array or instance of Zend_Config having configuration data
     * @throws Zend_Db_Adapter_Exception
     */
    public function __construct(DB_Database_1 $db, $config = array())
    {
    	$this->_connection = $db;
    	parent::__construct($config);
    }
    
    /**
     * Creates a connection to the database.
     *
     * @return void
     */
    protected function _connect()
    {
    	if (!$this->_connection->getIsConnected())
    	{
    		$this->_connection->connect();
    	}
    }

    /**
     * Force the connection to close.
     *
     * @return void
     */
    public function closeConnection()
    {
        $this->_connection->disconnect();
    }

    /**
     * Gets the PDO DSN for the adapter
     *
     * @return string
     */
    protected function _dsn()
    {
        return $this->_connection->getDSN();
    }

    /**
     * Check for config options that are mandatory.
     * Throw exceptions if any are missing.
     *
     * @param array $config
     * @throws Zend_Db_Adapter_Exception
     */
    protected function _checkRequiredOptions(array $config)
    {
    	// Intentionally blank as there is no config necessary
    }
}

?>