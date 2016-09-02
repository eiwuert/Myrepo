<?php
require_once('site_type_manager.php');
require_once('Memcache_Singleton.php');

class Cache_Site_Type_Manager extends Site_Type_Manager
{
	/**
	 * Returns the site type object.
	 *
	 * @param string $site_type
	 * @return object
	 */
	public function Get_Site_Type($site_type)
	{
		$key = 'ST:' . md5($site_type);
		if(!($site_type_obj = Memcache_Singleton::Get_Instance()->get($key)))
		{
			$site_type_obj = parent::Get_Site_Type($site_type);
			Memcache_Singleton::Get_Instance()->add($key, $site_type_obj);
		}
		return $site_type_obj;
	}
}
?>
