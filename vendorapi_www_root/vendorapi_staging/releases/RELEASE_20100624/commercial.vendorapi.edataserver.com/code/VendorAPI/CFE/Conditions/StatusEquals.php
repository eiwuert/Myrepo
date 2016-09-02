<?php

class VendorAPI_CFE_Conditions_StatusEquals implements ECash_CFE_ICondition, ECash_CFE_IExpression
{
	protected $status;

	public function __construct($status)
	{
		$this->status = $status;
	}

	public function evaluate(ECash_CFE_IContext $c)
	{
		return $this->isValid($c);
	}

	public function isValid(ECash_CFE_IContext $c)
	{
		$st = ($this->status instanceof ECash_CFE_IExpression)
			? $this->status->evaluate($c)
			: $this->status;

		return ($c->getAttribute('application_status') == $st);
	}
}

?>