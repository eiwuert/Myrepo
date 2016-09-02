<?php

/**
 * Failure group target
 *
 * A failure group invalidates all targets in the group on a fail exception (DataX
 * failure, really). The target that received the failure is left open to be
 * tried on another price point.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Generic_FailGroupTarget extends OLPBlackbox_Enterprise_Target
{
	/**
	 * @param string $name
	 * @param int $id
	 * @param OLPBlackbox_Enterprise_Generic_FailGroup $group
	 * @param Blackbox_IStateData $state_data
	 */
	public function __construct($name, $id, OLPBlackbox_Enterprise_Generic_FailGroup $group, Blackbox_IStateData $state_data = NULL)
	{
		parent::__construct($name, $id, $state_data);
		$this->group = $group;
	}

	/**
	 * Overloaded to check and see if the failure group has been tripped
	 * (non-PHPdoc)
	 * @see Blackbox/Blackbox_Target#pickTarget()
	 */
	public function pickTarget(Blackbox_Data $data)
	{
		$won = $this->group->getWinner();
		if ($won !== NULL
			&& $this->state_data->target_name != $won)
		{
			return FALSE;
		}

		try
		{
			return parent::pickTarget($data);
		}
		catch (OLPBlackbox_FailException $e)
		{
			$this->group->setWinner($this->state_data->target_name);
			return FALSE;
		}
	}
}
?>