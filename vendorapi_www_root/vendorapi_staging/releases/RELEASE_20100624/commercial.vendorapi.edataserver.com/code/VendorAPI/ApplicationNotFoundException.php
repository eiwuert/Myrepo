<?php

/**
 * Thrown to indicate that an application that was requested could not be found.
 * @author Andrew Minerd
 */
class VendorAPI_ApplicationNotFoundException extends Exception
{
	/**
	 * @var int The nonexistent application ID that was requested
	 */
	private $application_id;
	
	public function __construct($application_id)
	{
		parent::__construct("Application ID {$application_id} could not be found");
		$this->application_id = $application_id;
	}
	
	/**
	 * Returns the ID that was not found.
	 * @return int
	 */
	public function getApplicationId()
	{
		return $application_id;
	}
}

?>
