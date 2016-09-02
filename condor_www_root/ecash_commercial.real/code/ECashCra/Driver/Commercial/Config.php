<?php

class ECashCra_Driver_Commercial_Config
{
	//protected $api_url = 'http://rc.cra.tss';
	protected $api_url = 'https://cra.verihub.com/';

	protected $updateable_statuses = array(
		'sent::external_collections::*root',
		'recovered::external_collections::*root',
		'paid::customer::*root',
		'chargeoff::collections::customer::*root',
	);
	protected $cancellation_statuses = array(
		'withdrawn::applicant::*root',
		'denied::applicant::*root',
		'declined::prospect::*root',
		'disagree::prospect::*root',
		'funding_failed::servicing::customer::*root'
	);
	
	protected $active_status = 'active::servicing::customer::*root';
	
	protected function setupCompanyDatabaseConfigs()
	{
		return array(
			'generic'  => new DB_MySQLConfig_1('reader.ecashgeneric.ept.tss', 'username', 'password', 'ldb_generic'),
		);
	}
	
	protected function setupCompanyCredentials()
	{
		return array(
	        "generic"  => array('username'=>'username' ,'password'=>'password'),
		);
	}
	
	////
	// END OF CONFIGURATION
	////
	
	protected $api_credentials;
	
	/**
	 * @var array
	 */
	protected $db_configs;
	
	/**
	 * @var string
	 */
	protected $company;
	
	protected $mode;
	
	public function __construct()
	{
		$this->db_configs = $this->setupCompanyDatabaseConfigs();
		$this->api_credentials = $this->setupCompanyCredentials();
		
		foreach ($this->db_configs as $alias => $config)
		{
			DB_DatabaseConfigPool_1::add($alias, $config);
		}
	}
	
	public function useArguments(array $arguments)
	{
		$company = array_shift($arguments);
		
		if (empty($this->db_configs[$company]))
		{
			throw new InvalidArgumentException('The specified company is not set up');
		}
		
		$this->company = $company;
		
		$mode = array_shift($arguments);
		
		$this->mode = $_ENV['ECASH_MODE'] = $mode;
	}
	
	public function getApiUrl()
	{
		return $this->api_url;
	}
	
	public function getApiUsername()
	{
		return $this->api_credentials[$this->company]['username'];
	}
	
	public function getApiPassword()
	{
		return $this->api_credentials[$this->company]['password'];
	}
	
	public function getCompany()
	{
		return $this->company;
	}
	
	public function getConnection()
	{
		return DB_DatabaseConfigPool_1::getConnection($this->company);
	}
	
	public function getActiveStatus()
	{
		return $this->active_status;
	}
	
	public function getUpdateableStatuses()
	{
		return $this->updateable_statuses;
	}

    public function getCancellationStatuses()
    {
        return $this->cancellation_statuses;
    }

}

?>
