<?php

class VendorAPI_CFE_Expressions_Value implements ECash_CFE_IExpression {
	private $value;

	public function __construct($value) {
		$this->value = $value;
	}

	public function evaluate(ECash_CFE_IContext $c) {
		$value = ($this->value instanceof ECash_CFE_IExpression)
			? $this->value->evaluated($value)
			: $this->value;
			
		$return = preg_replace('#\$\{([a-zA-Z]+)\}#e', '$c->getAttribute($1)', $value);
		return $return;
	}	
}
?>