<?php
/**
 * @package Rpc
 */

/**
 * Container for multiple method invocation results
 *  
 */
class Rpc_Result_1 extends Collections_List_1  
{
	/**
	 * Flag bitfield
	 *
	 * @var int
	 */
	protected $bf;
	
	/**
	 * Output buffer
	 *
	 * @var string
	 */
	protected $ob;
	
	/**
	 * Constructor
	 *
	 * @param int $flag
	 */
	public function __construct($flag = NULL)
	{
		$this->bf = $flag === NULL ? 0 : $flag;
	}
	
	/**
	 * Get the flag bitfield
	 *
	 * @return int
	 */
	public function getFlag()
	{
		return $this->bf;
	}
	
	/**
	 * Set the flag bitfield
	 *
	 * @param int $flag
	 */
	public function setFlag($flag)
	{
		$this->bf = $flag;
	}
	
	/**
	 * Add a flag to the bitfield
	 *
	 * @param int $flag
	 */
	public function addFlag($flag)
	{
		$this->bf |= $flag;
	}
	
	/**
	 * Get the output buffer
	 *
	 * @return string
	 */
	public function getOutput()
	{
		return $this->ob;
	}
	
	/**
	 * Set the output buffer
	 *
	 * @param set $ob
	 */
	public function setOutput($ob)
	{
		$this->ob = $ob;
	}
	
	/**
	 * Add a result to the set
	 *
	 * @param mixed $key
	 * @param int $type
	 * @param mixed $result
	 * @param string $output
	 */
	public function addResult($key, $type, $result, $output)
	{
		$this->offsetSet($key, array($type, $result, $output));
	}
	
	/**
	 * Get a result from the set
	 *
	 * @param mixed $key
	 * @return array
	 */
	public function getResult($key)
	{
		return $this->offsetGet($key);
	}
}

?>