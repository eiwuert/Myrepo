<?php
/**
 * @package Rpc
 */

/**
 * Container for multiple method invocations
 *  
 */
class Rpc_Call_1 implements Countable 
{
	protected $name = array();
	protected $invoke = array();
	
	/**
	 * Add a method invocation record
	 *
	 * @param mixed $key
	 * @param string $method
	 * @param array $args
	 */
	public function addMethod($key, $method, array $args = array(), $service = NULL)
	{
		if(!isset($this->name[$method]))
			$this->name[$method] = count($this->name);
		
		if($key === NULL)
			$this->invoke[] = array($this->name[$method], $args, $service);
		else
			$this->invoke[$key] = array($this->name[$method], $args, $service);
	}
	
	/**
	 * Get a method invocation record
	 *
	 * @param mixed $key
	 * @return array
	 */
	public function getMethod($key)
	{
		if(isset($this->invoke[$key]))
		{
			$map = array_flip($this->name);
			$re = $this->invoke[$key];
			$re[0] = $map[$re[0]];
			return $re;
		}
		return NULL;
	}
	
	/**
	 * Get the number of method calls
	 *
	 * @return int
	 */
	public function count()
	{
		return count($this->invoke);
	}
	
	/**
	 * Invoke the methods on the service object(s)
	 *
	 * @param mixed $obj
	 * @return Rpc_Result_1
	 */
	public function invoke($service)
	{
		$res = new Rpc_Result_1();
		$map = array_flip($this->name);
		foreach($this->invoke as $k => $v)
		{
			ob_start();
			try
			{
				$result = call_user_func_array(array($service[$v[2]],$map[$v[0]]), $v[1]);
				$ob = ob_get_clean();	
				$res[$k] = array(Rpc_1::T_RETURN, $result, $ob);
			}
			catch (Exception $e)
			{
				$ob = ob_get_clean();
				$res[$k] = array(Rpc_1::T_THROW, $e, $ob);
			}
		}
		return $res;
	}
}

?>
