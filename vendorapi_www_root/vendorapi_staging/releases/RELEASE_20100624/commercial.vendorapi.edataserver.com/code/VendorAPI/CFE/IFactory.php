<?php

/**
 * Interface for a factory to create actions, conditions, and expressions
 *
 * NOTE: this is a temporary location until it can be moved into CFE core.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
interface VendorAPI_CFE_IFactory
{
	/**
	 * Creates an expression
	 *
	 * @param string $type
	 * @param array $params
	 * @return ECash_CFE_IExpression
	 */
	public function getExpression($type, array $params);
}

?>