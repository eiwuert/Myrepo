<?php
/**
 * Extended DB_Models_ReferenceTableCollection_1 object to add toArray functionality.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Blackbox_Models_ReferenceTableCollection extends DB_Models_ReferenceTableCollection_1 implements Blackbox_Models_IReferenceTable
{
	/**
	 * Enforces that each model is of type Blackbox_Models_ReferenceTable.
	 *
	 * @param Blackbox_Models_ReferenceTable $reference_table
	 * @return void
	 */
	public function addReferenceTable(Blackbox_Models_ReferenceTable $reference_table)
	{
		parent::addReferenceTable($reference_table);
	}
	
	/**
	 * Converts the reference table to an array with the ID as the index and the name as the value.
	 *
	 * @return array
	 */
	public function toArray($id_as_key = TRUE)
	{
		$array = array();
		
		foreach ($this->reference_tables AS $reference_table)
		{
			$array = array_merge($reference_table->toArray($id_as_key), $array);
		}
		
		return $array;
	}
}
