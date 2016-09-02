<?php
interface VendorAPI_IValidator 
{
	/**
	 * Validates the provided data
	 * @param array $data
	 * @return boolean
	 */
	public function validate($request);
	
	/**
	 * 
	 * Return all of the errors 
	 * @return Iterable
	 */
	public function getErrors();
	
	/**
	 * Return the filtered data back 
	 * @return array
	 */
	public function getFilteredData();
	
}