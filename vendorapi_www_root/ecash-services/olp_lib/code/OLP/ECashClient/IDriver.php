<?php

/**
 * Interface for OLP_ECashClient.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
interface OLP_ECashClient_IDriver
{
	/**
	 * The constructor for the possible adapters should only take the
	 * same requirements of the facade.
	 *
	 * @param string $mode
	 * @param string $property_short
	 */
	public function __construct($mode, $property_short);
	
	/**
	 * Returns a verbose description of the driver in human readable form.
	 *
	 * @return string
	 */
	public function getDriverDescription();
	
	/**
	 * Gets a simple listing of all methods that this class will handle. The
	 * returned array will just be a listing of method names.
	 *
	 * @return array
	 */
	public function getMethodList();
	
	/**
	 * Gets a more verbose listing of all methods that can fully describe the
	 * API. The returned array will be a listing of methods that contain a
	 * subarray that fully describe each method in human readable form.
	 *
	 * array()
	 *   array()
	 *     name => string
	 *     parameters => string
	 *     comments => string
	 *
	 * @return array
	 */
	public function getVerboseMethodList();
}

?>
