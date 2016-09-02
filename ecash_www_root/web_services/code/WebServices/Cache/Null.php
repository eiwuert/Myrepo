<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Null
 *
 * @author Asa Ayers <Asa.Ayers@SellingSource.com>
 */
class WebServices_Cache_Null implements WebServices_ICache {

	public function getCache($function, $id)
	{
		return NULL;
	}

	public function hasCache($function, $id)
	{
		return FALSE;
	}

	/**
	 * Stores a call in the cach
	 *
	 * @param string $function
	 * @param string $id
	 * @param object $value
	 * @return void
	 */
	public function storeCache($function, $id, $value)
	{

	}

	/**
	 * Removes value from the cache
	 *
	 * @param string $function
	 * @param string $id
	 * @return void
	 */
	public function removeCache($function, $id)
	{

	}

}
?>
