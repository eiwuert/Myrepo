<?php

/**
 * A collection of companies comprising an "enterprise" company (eg. CLK).
 * This collection adds additional behavior to the basic target collect, such
 * as decisions based on the outcome of the previous customer checks.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_Enterprise_TargetCollection extends OLPBlackbox_TargetCollection
{
	/**
	 * Option to invalidate targets held by this collection with active loans.
	 *
	 * This is used primarily for CLK where particular collection rules can
	 * invalidate targets (prev customer check stuff). If this option is true,
	 * then the collection will prune out targets which appear in the "customer
	 * history."
	 *
	 * @var bool
	 */
	protected $invalidate_active;

	/**
	 * Construct a OLPBlackbox_Enterprise_TargetCollection object.
	 *
	 * @param string $name Name of this target collection.
	 * @param Blackbox_IStateData $state Data about the parent collection.
	 * @param bool $invalidate_active Whether to remove targets based on prev
	 * customer collection rules. (used for CLK)
	 * @param array $tags List of target_tag entries (replaces submit level stats)
	 */
	public function __construct($name, Blackbox_IStateData $state = NULL, $invalidate_active = TRUE, $tags = NULL)
	{
		$this->invalidate_active = $invalidate_active;

		parent::__construct($name, $state, $tags);
	}

	/**
	 * Wraps the winner in an Enterprise_Winner
	 *
	 * @param Blackbox_Data $data the data to do further validation on
	 * @return OLPBlackbox_Enterprise_Winner
	 */
	public function pickTarget(Blackbox_Data $data)
	{
		try
		{
			if (($winner = parent::pickTarget($data)) !== FALSE)
			{
				// @todo not really sure I like unwrapping
				// this just to wrap it back up, but I don't
				// like double-wrapping it either...
				$winner = new OLPBlackbox_Enterprise_Winner(
					$winner->getTarget());
				return $winner;
			}
		}
		catch (OLPBlackbox_FailException $e)
		{
			/*
			 * one of the rules has failed the whole collection!
			 * this is usually CLK's DataX or UFC's Verify stuff failing.
			 */
			$this->valid_list = array();
		}

		return FALSE;
	}

	/**
	 * Initializes state information.
	 *
	 * @param Blackbox_IStateData $state_data
	 * @return void
	 */
	protected function initState(Blackbox_IStateData $state_data = NULL)
	{
		$this->state_data = new OLPBlackbox_Enterprise_TargetCollectionStateData(
			$this->getInitialStateData()
		);
		
		if (!is_null($state_data))
		{
			$this->state_data->addStateData($state_data);
		}
	}
	
	/**
	 * "Closes" (removes from the valid list) targets
	 * Returns whether the collection should still be considered
	 * valid (i.e., it still has open targets).
	 *
	 * @param array $names the names of targets to close
	 * @return bool
	 * @todo I find this revolting... come up with a better way
	 */
	protected function excludeTargets(array $names)
	{
		$new = array();
		$valid = FALSE;

		// our parent restricts our list to campaigns only
		foreach ($this->valid_list as $campaign)
		{
			$name = strtolower($campaign->getTarget()->getStateData()->name);

			if (!in_array($name, $names))
			{
				$new[] = $campaign;
				$valid = TRUE;
			}
		}

		$this->valid_list = $new;
		return $valid;
	}
}

?>
