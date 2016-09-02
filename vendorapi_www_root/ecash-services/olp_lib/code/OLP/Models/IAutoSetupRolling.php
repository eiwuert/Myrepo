<?php

/**
 * Interface to define that we want this model to autoload table names.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
interface OLP_Models_IAutoSetupRolling
{
	/**
	 * Automatically sets up the table names.
	 *
	 * @return void
	 */
	public function autoSetTableNames();
}

?>
