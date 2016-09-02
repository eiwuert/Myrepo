<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @author Asa Ayers <Asa.Ayers@SellingSource.com>
 */
interface WebServices_ICache {
	
    public function hasCache($function, $id);

	public function getCache($function, $id);

	/**
	 * Stores a call in the cach
	 *
	 * @param string $function
	 * @param string $id
	 * @param object $value
	 * @return void
	 */
	public function storeCache($function, $id, $value);

	/**
	 * Removes value from the cache
	 *
	 * @param string $function
	 * @param string $id
	 * @return void
	 */
	public function removeCache($function, $id);

}
?>
