<?php

/**
 * An interface to allow using models with DB_Models_ReferenceTable_1
 *
 * @author Mike Lively <mike.lively@sellingsource.com>
 * @see DB_Models_ReferenceTable_1
 */
interface DB_Models_IReferenceModel_1
{

	/**
	 * Returns the column that contains the table ID of each item
	 * @return string
	 */
	public function getColumnID();

	/**
	 * Returns the column that contains the name of each item
	 * @return string
	 */
	public function getColumnName();
}

?>