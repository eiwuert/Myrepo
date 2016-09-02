<?php

/**
 * @package Ecash.Models
 */
class ECash_Models_ExternalBatchReportList extends ECash_Models_IterativeModel
{
	public function getClassName()
	{
		return 'ECash_Models_ExternalBatchReport';
	}

	public function getTableName()
	{
		return 'external_batch_report';
	}

	// Fixes some weird bug, or at least stops the error message from appearing
	// I'd feel bad about this, but PHP isn't very helpful at debugging this.
	public function __sleep()
	{
	}
}

?>
