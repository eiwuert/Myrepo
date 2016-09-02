<?php

/**
 * An action that does nothing ("No Operation")
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_Actions_Noop extends VendorAPI_Actions_Base
{
	/**
	 * Executes the Noop action
	 *
	 * @param mixed $input
	 * @return VendorAPI_Response
	 */
	public function execute($input = NULL)
	{
		if (!$input)
		{
			$input = 'Hello world';
		}

		return new VendorAPI_Response(
			new VendorAPI_StateObject(),
			VendorAPI_Response::SUCCESS,
			array($input)
		);
	}
}

?>