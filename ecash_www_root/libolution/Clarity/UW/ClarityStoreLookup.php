<?php

/**
 * determines which UW and inquiry tyoe to use based off of the source campaign and the lookup map in the database 
 *
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */
class ClarityStore
{
    protected $config;

    protected $db;
    
    public $merchant;
    
    public $group;
    
    public $username;
    
    public $password;

    public $store;

	public function __construct($store_id)
	{
        $this->config = ECash::getConfig();
		$this->db = $this->getDatabase();

        // build the basic UW and inquiry type query
        $sql_str = "SELECT store_id, group_id, merchant, username, password ".
            "FROM uw_store AS us ".
	    "JOIN uw_inquiries ui USING(uw_store_id) ".
	    "WHERE ui.uw_inquiry_name = '".$store_id."'";
        $result = $this->db->query($sql_str);
        $row = $result->fetch();
        // if no rows found get the default row
        if (!($row) || ($result->rowCount()<1)){
            $this->store = '';
            $this->group = '';
            $this->merchant = '';
            $this->username = '';
            $this->password = '';
        } else {
            $this->store = $row['store_id'];
            $this->group = $row['group_id'];
            $this->merchant = $row['merchant'];
            $this->username = $row['username'];
            $this->password = $row['password'];
        }
    }
	/**
	 * Gets a database connection
	 *
	 * This will attempt to connect to each defined database in the failover order
	 *
	 * @return DB_IConnection_1
	 */
	public function getDatabase()
	{

		if (!$this->db)
		{
			$db = new DB_FailoverConfig_1();
			if (!$this->use_master)
			{
				$db->addConfig($this->config->DB_API_CONFIG);
				$db->addConfig($this->config->DB_SLAVE_CONFIG);
			}
			$db->addConfig($this->config->DB_MASTER_CONFIG);
			$this->db = $db->getConnection();
		}
		return $this->db;
	}
}

?>
