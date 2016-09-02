<?php

/**
 * Validates whether or not a customer has loan actions.
 *
 * @author Mike Lively <Mike.Lively@sellingsource.com>
 *
 */
class VendorAPI_CFE_Expressions_HasLoanActions implements ECash_CFE_IExpression
{
	/**
	 * @var VendorAPI_IApplication
	 */
	private $application;

	/**
	 * @var VendorAPI_Blackbox_Winner
	 */
	private $winner;

	public function __construct(array $params)
	{
		$this->application = $params['application'];
		$this->winner = $params['winner'];
	}

	/**
	 * Evaluates an applications
	 */
	public function evaluate(ECash_CFE_IContext $c)
	{
		if ($this->application instanceof ECash_CFE_IExpression)
		{
			$this->application = $this->application->evaluate($c);
		}

		if ($this->winner instanceof ECash_CFE_IExpression)
		{
			$this->winner = $this->winner->evaluate($c);
		}
		
		$call_context = $c->getAttribute('call_context');

		return $this->application->hasLoanActions($call_context->getApiAgentId())
			|| count($this->winner->getLoanActions());
	}
}
