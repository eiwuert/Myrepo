<?php

class VendorAPI_CFE_Factory implements VendorAPI_CFE_IFactory
{
	/**
	 * Creates an expression
	 *
	 * Note: this is not used for literal values.
	 *
	 * @param string $type
	 * @param array $params
	 * @return ECash_CFE_IExpression
	 */
	public function getExpression($type, array $params)
	{
		$rc = new ReflectionClass($type);

		/* @var $c ReflectionMethod */
		if (($c = $rc->getConstructor()) != false
			&& count($p = $c->getParameters()) == 1
			&& $p[0]->isArray())
		{
			return $rc->newInstance($params);
		}

		return $rc->newInstanceArgs($params);
	}
}

?>