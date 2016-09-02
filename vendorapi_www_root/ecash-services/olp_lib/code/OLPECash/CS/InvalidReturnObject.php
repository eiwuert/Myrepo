<?php
/**
 * Invalid return object extends the Exception class to attach the object
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class OLPECash_CS_InvalidReturnObject extends Exception
{
	/**
	 * Object holder
	 *
	 * @var object
	 */
	protected $object;
	
	/**
	 * Get the object for the exception
	 *
	 * @return object
	 */
	public function getObject()
	{
		return $this->object;
	}
	
	/**
	 * Set the object for the exception
	 *
	 * @param object $object
	 * @return void
	 */
	public function setObject($object)
	{
		$this->object = $object;
	}
}

?>