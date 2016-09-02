<?php

/**
 * Adds toArray functionality to DB_Models_IReferenceTable_1.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
interface Blackbox_Models_IReferenceTable
{
	/**
	 * Converts the reference table to an array with the ID as the index and the name as the value.
	 *
	 * @return array
	 */
	public function toArray($id_as_key = TRUE);
}
