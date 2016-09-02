<?php

/**
 * determines which UW and inquiry tyoe to use based off of the source campaign and the lookup map in the database 
 *
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */
class VendorAPI_Capaign2UW_Chooser
{
	protected $config;

	protected $db;

	public function __construct()
	{
        $this->config = ECash::getConfig();
		$this->db = $this->getDatabase();
	}

	public function chooseUWinquiry($campaign)
	{
	$campaign = strtoupper($campaign);
	// build the basic UW and inquiry type query
        $sql_str_prem = "SELECT up.uw_name_short, ui.uw_inquiry_name, us.store_id, ci.count, ci.count/cis.cnt_sum*100 AS percentage ".
            "FROM campaigns AS cm ".
            "JOIN campaign_inquiry AS ci USING (campaign_id) ".
            "JOIN uw_inquiries AS ui USING (uw_inquiry_id) ".
            "JOIN uw_providers AS up USING (uw_provider_id) ".
            "LEFT JOIN uw_store AS us ON (us.uw_store_id = ui.uw_store_id) ".
            "JOIN ( ".
                "SELECT campaign_id, sum(count) AS cnt_sum ".
                "FROM campaign_inquiry GROUP BY campaign_id) ".
            "AS cis USING (campaign_id) ".
	    "WHERE ci.count>0 ";
	// get the rows associated with the campaign
        $sql_str = $sql_str_prem . " AND cm.campaign_name = '".$campaign."'";
	$result = $this->db->query($sql_str);
	$rows = $result->fetchAll();
	// if no rows found get the default row
	if (!($rows) || ($result->rowCount()<1)){
            $sql_str = $sql_str_prem . " AND cm.campaign_name = '*default campaign*'";
	    $result = $this->db->query($sql_str);
	    $rows = $result->fetchAll();
        }
        // use a random 1-100 number to select which row is used based on the percentage of the row count
        $rand = rand(1,100);
        foreach($rows as $row){
            if ($row['percentage'] >= $rand) {
                return array($row['uw_name_short'],$row['uw_inquiry_name'],$row['store_id']);
            }
            $rand -= $row['percentage'];
        }
        return array($row['uw_name_short'],$row['uw_inquiry_name'],$row['store_id']);
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
