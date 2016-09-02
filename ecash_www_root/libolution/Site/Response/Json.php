<?php

/**
 * Provides a json response based on a given variable
 * 
 * @author Mike Lively <mike.lively@sellingsource.com>
 * @package Site
 */
class Site_Response_Json implements Site_IResponse
{

	/**
	 * @var mixed
	 */
	protected $data;

	/**
	 * Creates a new response using the specified variable
	 *
	 * @param mixed $data
	 */
	public function __construct($data)
	{
		$this->data = $data;
	}

	/**
	 * Renders the json object.
	 */
	public function render()
	{
		echo json_encode($this->data);
	}
}

?>