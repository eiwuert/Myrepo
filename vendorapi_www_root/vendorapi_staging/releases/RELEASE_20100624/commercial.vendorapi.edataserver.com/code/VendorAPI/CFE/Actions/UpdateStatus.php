<?php

class VendorAPI_CFE_Actions_UpdateStatus implements ECash_CFE_IExpression
{
	public function __construct($status)
	{
		$this->status = $status;
	}

	public function evaluate(ECash_CFE_IContext $c)
	{
		$status = ($this->status instanceof ECash_CFE_IExpression)
			? $this->status->evaluate($c)
			: $this->status;
		$c->setAttribute('application_status', $status);
	}
}