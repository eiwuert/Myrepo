<?php

/**
 * Hits stats for the first 'hit' trigger with an associated stat found in the
 * state object.
 *
 * This api call will accept one parameter. The serialized version of the
 * state object.
 *
 *
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class VendorAPI_Actions_HandleTriggers extends VendorAPI_Actions_Base
{
	
	protected $application_factory;

	public function __construct(VendorAPI_IDriver $driver,
		VendorAPI_IApplicationFactory $application_factory
	)
	{
		parent::__construct($driver);
		$this->application_factory = $application_factory;

	}
	
	/**
	 * Executes the HandleTriggers Action
	 *
	 * @param string $state - serialized state object
	 * @return VendorAPI_Response
	 */
	public function execute($application_id, $state = NULL)
	{
		if ($state == NULL)
		{
			$state = $this->getStateObjectByApplicationID($application_id);
		}
		else
		{
			$state = $this->getStateObject($state);
		}
		
		$has_hit_triggers = FALSE;
		$stat = NULL;

		if (isset($state->triggers)
			&& $state->triggers instanceof VendorAPI_Blackbox_Triggers)
		{
			$stat = $state->triggers->getStatToHit();
			$has_hit_triggers = $state->triggers->hasHitTriggers();
		}
		elseif (!empty($state->has_triggers))
		{
			$stat = $state->trigger_stat;
			$has_hit_triggers = TRUE;
		}

		if (!empty($stat))
		{
			$this->hitStat($stat, $state->track_key, $state->space_key);
		}

		return new VendorAPI_Response($state, VendorAPI_Response::SUCCESS, array('has_triggers' => $has_hit_triggers));
	}
	
	/**
	 * Returns Application Factory.
	 *
	 * @return VendorAPI_IApplicationFactory
	 */
	protected function getApplicationFactory()
	{
		return $this->application_factory;
		
	}
}

?>