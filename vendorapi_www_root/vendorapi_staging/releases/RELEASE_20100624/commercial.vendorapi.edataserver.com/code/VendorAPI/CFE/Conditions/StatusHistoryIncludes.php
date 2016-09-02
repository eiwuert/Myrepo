<?php
/**
 * CFE condition/expression that determines is a particular status
 * exists in the status history for the application in the context
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class VendorAPI_CFE_Conditions_StatusHistoryIncludes
		implements ECash_CFE_ICondition, ECash_CFE_IExpression
{
	/**
	 * @var string
	 */
	protected $status;

	/**
	 * Constructor sets teh status to evaluate 
	 * @param string $status Status chain (e.g.: pending::prospect::*root)
	 */
	public function __construct($status)
	{
		$this->status = $status;
	}

	/**
	 * @see ECash_CFE_ICondition#isValid($c)
	 */
	public function isValid(ECash_CFE_IContext $c)
	{
		return $this->evaluate($c);
	}

	/**
	 * Determines if the status_history in the supplied context contains
	 * the status from thh constructor
	 * @see ECash_CFE_IExpression#evaluate($c)
	 */
	public function evaluate(ECash_CFE_IContext $c)
	{
		$in_history = FALSE;
		$status = ($this->status instanceof ECash_CFE_IExpression)
			? $this->status->evaluate($c)
			: $this->status;

		// Get the status history from the context
		$history = $c->getAttribute('status_history');

		// Make sure we can iterate/traverse over the history
		if (is_array($history) || $history instanceof iterator || $history instanceof traversable)
		{
			foreach ($history as $row)
			{
				// If the row allows array access, get the name that way
				if ((is_array($row) || $row instanceof ArrayAccess) && isset($row['name']))
				{
					$name = $row['name'];
				}
				// If the row is an object and has a name value, use it
				elseif (is_object($row) && isset($row->name))
				{
					$name = $row->name;
				}
				else
				{
					$name = NULL;
				}
				// If the name mathches the stats, then set in_history and stop
				// processing
				if ($name == $status)
				{
					$in_history = TRUE;
					break;
				}
			}
		}
		return $in_history;
	}
}
?>