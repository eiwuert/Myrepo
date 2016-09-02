<?php

/**
 * StateData for CFE data
 *
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
class OLPBlackbox_CFE_StateData extends BlackBox_StateData
{

	/**
	 * Setup keys, and clal the parent constructor
	 *
	 * @param mixed $data Data to initialize with.
	 */
	function __construct($data = NULL)
	{
		
		$this->immutable_keys[] = 'asynch_object';
		
		parent::__construct($data);
	}
}
?>