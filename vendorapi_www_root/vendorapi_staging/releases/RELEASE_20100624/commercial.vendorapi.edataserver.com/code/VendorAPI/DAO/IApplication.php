<?php

/**
 * DAO Interface for Application management within eCash
 *
 * @author Raymond Lopez <raymond.lopez@selingsource.com>
 */
interface VendorAPI_DAO_IApplication
{
	/**
	 * Save the application
	 * @param VendorAPI_StateObject $state
	 */
	public function save(VendorAPI_StateObject $state);
}

?>