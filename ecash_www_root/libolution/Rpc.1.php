<?php
/**
 * @package Rpc
 */

/**
 * Common static funcs and constants
 *
 */
class Rpc_1
{
	const T_RETURN = 1;
	const T_THROW = 2;

	const E_INIT = 1;

	/**
	 * Encode for transport
	 *
	 * @param mixed $v
	 * @return string
	 */
	public static function encode($v)
	{
		return gzcompress(serialize($v));
	}

	/**
	 * Decode for processing
	 *
	 * @param string $v
	 * @return mixed
	 */
	public static function decode($v)
	{
		return unserialize(gzuncompress($v));
	}
}

?>