<?php

class VendorAPI_ServiceSoapWrapper
{
	/**
	 * @var VendorAPI_Service
	 */
	private $service;

	/**
	 * @param VendorAPI_Service $service
	 */
	public function __construct(VendorAPI_Service $service)
	{
		$this->service = $service;
	}

        /**
         * Default handler for action calls.
         *
         * @param string $name
         * @param array $arguments
         * @return mixed
         */
	public function __call($name, array $arguments)
	{
		$return = call_user_func_array(array($this->service, $name), $arguments);

		if (strtolower($name) == 'post')
		{
			//Check to see if the returned error is an array, if so we need to flatten it.
			if (is_array($return['error']))
			{
				$return['error'] = $this->flattenArray($return['error']);
			}
		}
		
		return $return;
	}

	private function flattenArray($arr, $indent = 0)
	{
		$flat = '';
		foreach ($arr as $key => $val)
		{
			if (!is_int($key))
			{
				$flat .= str_repeat(' ', $indent * 2) . $key . ': ';
			}

			if (is_array($val))
			{
				$flat .= "\n" . str_repeat(' ', $indent * 2) . $this->flattenArray($val, $indent + 1);
			}
			else
			{
				$flat .= str_repeat(' ', $indent * 2) . $val . "\n";
			}

			if (is_int($key))
			{
				$flat .= str_repeat(' ', $indent * 2) . "--\n";
			}
		}
		return $flat;
	}
}

?>
