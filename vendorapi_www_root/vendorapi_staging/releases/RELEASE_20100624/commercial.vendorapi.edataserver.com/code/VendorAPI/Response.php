<?php

/**
 * Vendor API response object
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_Response
{
	const SUCCESS = 1;
	const ERROR = 0;

	/**
	 * @var VendorAPI_StateObject
	 */
	protected $state;

	/**
	 * Either SUCCESS or ERROR
	 * @var int
	 */
	protected $outcome;

	/**
	 * @var array
	 */
	protected $result;

	/**
	 * @var string
	 */
	protected $error;

	/**
	 * @param VendorAPI_StateObject $state
	 * @param int $outcome self::SUCCESS or self::ERROR
	 * @param array $result
	 * @param null|string $error Only needed if an error  occured
	 */
	public function __construct(VendorAPI_StateObject $state, $outcome, array $result = array(), $error = NULL)
	{
		$this->state = $state;
		$this->outcome = $outcome;
		$this->result = $result;
		$this->error = $error;
	}

	/**
	 * Encodes the response as an array
	 * @return array
	 */
	public function toArray()
	{
		$result = $this->result;
		foreach ($this->result as $k => $v)
		{
			if ($v instanceof ArrayObject)
			{
				$result[$k] = $this->arrayObjectToArray($v);
			}
		}
		$response = array(
			'state_object' => serialize($this->state),
			'outcome' => $this->outcome,
			'result' => $result,
		);
		if ($this->outcome === self::ERROR)
		{
			$response['error'] = $this->error;
		}

		return $response;
	}

	/**
	 * Recursively convert an ArrayObject to an array.
	 * @param ArrayObject $obj
	 * @return array
	 */
	protected function arrayObjectToArray(ArrayObject $obj)
	{
		$return = (array)$obj;
		foreach ($obj as $k => $v)
		{
			if ($v instanceof ArrayObject)
			{
				$return[$k] = $this->arrayObjectToArray($v);
			}
		}
		return $return;
	}

	/**
	 * @return VendorAPI_StateObject
	 */
	public function getStateObject()
	{
		return $this->state;
	}

	/**
	 * @return int
	 */
	public function getOutcome()
	{
		return $this->outcome;
	}

	/**
	 * @return mixed
	 */
	public function getResult()
	{
		return $this->result;
	}

	/**
	 * @return null|string
	 */
	public function getError()
	{
		return $this->error;
	}
}

?>