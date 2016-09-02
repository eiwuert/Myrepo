<?php

class VendorAPI_CFE_TransitionCondition implements ECash_CFE_IExpression
{
	protected $key, $value;

	public function __construct($value)
	{
		$key = '';
		if (strpos($value, '.') !== FALSE)
		{
			list($key, $value) = explode('.', $value, 2);
		}

		$this->key = $key;
		$this->value = $value;
	}

	public function evaluate(ECash_CFE_IContext $c)
	{
		$value = $c->getAttribute($this->key);
		return ($value !== NULL
			&& $this->normalize($value) == $this->value);
	}

	protected function normalize($value)
	{
		if (is_bool($value))
		{
			return $value ? 'true' : 'false';
		}
		return $value;
	}
}