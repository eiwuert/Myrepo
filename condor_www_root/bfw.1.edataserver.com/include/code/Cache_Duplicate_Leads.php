<?php


class CacheDuplicateLeads
{
	private $sql;
	private $config;
	private $application_id;
	private $collected_data;
	private $dupe_global_expire;
	private $last_recorded_timestamp;
	
	public function __construct($sql)
	{
		$this->sql = $sql;
		$this->dupe_global_expire = 48 * 60 * 60; // Global Expire time of 12 hours
	}
	
	// This should be set in the failover_config.php
	//const DUPLICATE_LEAD_EXPIRE = 30; 
	private function getDefaultExpireTime()
	{
		/**
		 * DUPLICATE_LEAD_EXPIRE is set from the limits section of webadmin2. This value is stored
		 * inside the failover_data table. Somewhere in the begining of this code there is a module that pulls
		 * all the rows from this table and turns them into defines.
		 */
		
		/**
		 * 10/30/2007 Rather than a global expire, this is now used a default expire, a global expire time
		 * will be a fixed time defined in $this->dupe_global_expire in the constructor.
		 */
		return (DUPLICATE_LEAD_EXPIRE) ? DUPLICATE_LEAD_EXPIRE*60 : 30*60;
	}

	private function isDuplicateOnPromo($promo_id = "default",$expire_time_in_seconds)
	{
		
		//Memcache Key used to track duplicate leads.
		$memcache_key = "DUPLICATE_LEAD";
		$memcache_key .= ":".$this->collected_data['email_primary'];	
		$memcache_key .= ":$promo_id";	
		$memcache_key = strtoupper($memcache_key);
		
		$memcache_result = Memcache_Singleton::Get_Instance()->get($memcache_key);
		
		if($promo_id == "default")
		{
			$this->last_recorded_timestamp = $memcache_result['timestamp'];
		}
		
		$memcache_data_to_be_stored = array(
			'application_id' => $this->application_id,
			'timestamp' => time(),
		);
		
		//** IMPORTANT TO CHECK THE DATATYPE BECAUSE MEMCACHE COULD HAVE OLD DATA AND A NEW 
		// DATA TYPE WILL CAUSE FATAL EXCEPTIONS! **
		
		//Is there some default duplicate_leads data?
		if(is_array($memcache_result))
		{
			
			//If we are on the same application then not a dupe.
			if($memcache_result['application_id'] == $application_id) 
			{
				$duplicate_lead = "FALSE";
			}
			else 
			{
				$delta = $memcache_data_to_be_stored['timestamp'] - $memcache_result['timestamp'];
				
				//Delta time has surpassed expire time, so allow this one through as not a dupe.
				if($delta > $expire_time_in_seconds)
				{
					$duplicate_lead = "FALSE";	
				}
				else 
				{
					$duplicate_lead = "TRUE";
				}
			}
	
			//Update the memcache
			Memcache_Singleton::Get_Instance()->set($memcache_key,$memcache_data_to_be_stored,$this->dupe_global_expire);
		}
		else 
		{
			//Nothing in memcache, this isn't a duplicate
			$duplicate_lead = "DOES NOT EXIST";
			
			//Add this new lead to the memcache
			Memcache_Singleton::Get_Instance()->add($memcache_key,$memcache_data_to_be_stored,$this->dupe_global_expire);
		}
		
		return $duplicate_lead;
	}
	
	//Checks to see if a lead given an application id already exhists inside the memcache.
	private function isDuplicate()
	{
		// Default duplicate to FALSE for safety purposes
		$return_value = FALSE;
		
		//Regardless of Promo, always check against the default.
		$defaultDupe = $this->isDuplicateOnPromo("default",$this->getDefaultExpireTime());
		
		//Specific Promo ID's can override the default expire time by adding the runtime field duplicate_leads_expire_in_minutes
		//into webadmin1 options.
		if(isset($this->config->duplicate_leads_expire_in_minutes))
		{
			if($this->config->duplicate_leads_expire_in_minutes >= 1)
			{
				//Memcache expire time unit is seconds
				$promo_expire_time = $this->config->duplicate_leads_expire_in_minutes*60;
				$promoDupe = $this->isDuplicateOnPromo($this->config->promo_id,$promo_expire_time);
				
				if($promoDupe == "DOES NOT EXIST")
				{
					return ((time() - $this->last_recorded_timestamp) <= $promo_expire_time) ? 
						TRUE : FALSE;
				}
			}
			else 
			{
				return FALSE;
			}
			
			
			return ($promoDupe == "TRUE") ? TRUE : FALSE;
		}
		
		return ($defaultDupe == "TRUE") ? TRUE : FALSE;

	}
	
	
	// Run function that uses email to record duplicate leads
	public function run_for_email($application_id,$event,&$next_page,&$config,$collected_data)
	{
		$this->application_id = $application_id;
		$this->config = $config;
		$this->collected_data = $collected_data;

		// If any of the following fields are not available then we can not run a the memcache duplicate leads check
		if($collected_data['email_primary'] && is_numeric($application_id))
		{
			if(isset($_SESSION['data']['no_checks']))
			{
				$event->Log_Event('DUPLICATE_LEAD_CHECK', 'debug_skip',NULL,$application_id);
				return;
			}

			
			if($this->isDuplicate())
			{
				// Mark as a duplicate Lead - this session variable will be later used to
				// ensure we no longer enter this module as well as skip blackbox_prequal().
				$_SESSION['duplicate_lead'] = TRUE;
				$event->Log_Event('DUPLICATE_LEAD_CHECK', 'FAIL',NULL,$application_id);
				
				// If application comes from soap,
				if(in_array(strtoupper($_SESSION['data']['site_type']),array('SOAP','SOAP_OC','BLACKBOX.ONE.PAGE')))
				{
					// This is set ensure that we do not sell a duplicate lead to CLK (tier1)
					if($config->limits->accept_level < 2 || !isset($config->limits->accept_level)) 
						$config->limits->accept_level = 2;
				}
			} 
			else
			{
				$event->Log_Event('DUPLICATE_LEAD_CHECK','PASS',NULL,$application_id);
				$_SESSION['duplicate_lead'] = FALSE;
			}
		}
	}	
}
	
?>
