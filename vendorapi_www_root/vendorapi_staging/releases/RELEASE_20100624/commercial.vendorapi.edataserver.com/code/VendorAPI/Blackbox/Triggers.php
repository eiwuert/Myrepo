<?php

/**
 * A collection of triggers.
 *
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
class VendorAPI_Blackbox_Triggers implements IteratorAggregate, Countable
{
	/**
	 * Triggers?!?!
	 *
	 * @var array
	 */
	protected $triggers = array();

	/**
	 * Add a trigger to the list of triggers
	 *
	 * @param VendorAPI_Blackbox_Trigger $trigger
	 * @return void
	 */
	public function addTrigger(VendorAPI_Blackbox_Trigger $trigger)
	{
		$this->triggers[] = $trigger;
	}

	/**
	 * Whether any triggers were hit
	 * @return bool
	 */
	public function hasHitTriggers()
	{
		foreach ($this->triggers as $t)
		{
			if ($t->isHit()) return TRUE;
		}
		return FALSE;
	}

	/**
	 * The stat that should be hit based on the triggers hit
	 * @return string|FALSE
	 */
	public function getStatToHit()
	{
		foreach ($this->triggers as $t)
		{
			$stat = $t->getStat();
			if ($t->isHit()
				&& !empty($stat))
			{
				return $stat;
			}
		}
		return FALSE;
	}

	/**
	 * Return an array of the triggers
	 *
	 * @return array
	 */
	public function getTriggers()
	{
		return $this->triggers;
	}

	/**
	 * Return a new array iterator?
	 *
	 * @return string
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->triggers);
	}

	/**
	 * Returns the number of trigger actions
	 * that are in this collection.
	 *
	 * @return int
	 */
	public function count()
	{
		return count($this->triggers);
	}
}
