<?php
/**
 * Suppression list interface.
 *
 * Defines an interface for matching a value against a suppression list.
 *
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
interface TSS_ISuppressionList_1
{
	/**
	 * Matches $value against the suppression list and returns TRUE if there is a match.
	 *
	 * @param string $value
	 * @return bool
	 */
	public function match($value);
}
