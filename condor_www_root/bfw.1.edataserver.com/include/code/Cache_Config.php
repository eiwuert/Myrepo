<?php
require_once(BFW_CODE_DIR.'Memcache_Singleton.php');
require_once('config.6.php');

/**
 * This class implements memcache for site configs. It uses Config_6 as a base. [BF]
 */
class Cache_Config extends Config_6
{
	/**
	 * Overloaded constructor for Config_6.
	 * 
	 * We want to force the database to be the management database, since we can pass the OLP
	 * connection to the object and the database for that may be set to OLP.
	 *
	 * @param MySQL_Wrapper $sql
	 */
	public function __construct($sql)
	{
		parent::__construct($sql);
		$this->database = 'management';
	}
	
	/**
	 * Returns the site config based on the license key and promo ID. This version of the function
	 * will attempt to read from the memcache servers first.
	 *
	 * @param string $license
	 * @param int $promo_id
	 * @param string $promo_sub_code
	 * @return object
	 */
	public function Get_Site_Config ($license, $promo_id = NULL, $promo_sub_code = NULL)
	{
		// Simple validation on license key.
		if( !is_string($license) )
		{
			throw new Exception("License key should be a string 32 characters or longer.");
		}
		
		// If we do not have a proper promo_id change to the default of 10000
		if( is_null($promo_id) || !is_numeric($promo_id) )
		{
			$promo_id = 10000;
		}
		
		// If sub_code is null change to default of a blank string
		if( is_null($promo_sub_code) )
		{
			$promo_sub_code = "";
		}

		// Attempt to get this from memcache servers first
		$key = 'CN:' . md5("{$license}:{$promo_id}:{$promo_sub_code}");
		$config = Memcache_Singleton::Get_Instance()->get($key);
		
		// If we missed it in cache, call it as normal and store it in cache
		if(!$config)
		{
			$config = parent::Get_Site_Config($license, $promo_id, $promo_sub_code);
			Memcache_Singleton::Get_Instance()->add($key, $config);
		}

		return $config;
	}
}
?>
