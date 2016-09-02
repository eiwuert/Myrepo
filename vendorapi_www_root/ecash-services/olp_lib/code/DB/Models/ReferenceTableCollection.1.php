<?php

/**
 * A collection of reference tables. Allows you to chainload multiple reference
 * tables in a row that all supply the same ID <-> Name relationship.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class DB_Models_ReferenceTableCollection_1 implements DB_Models_IReferenceTable_1
{
	/**
	 * @var DB_Models_ReferenceTable_1
	 */
	protected $reference_tables = array();
	
	/**
	 * Appends the reference table to the collection.
	 *
	 * @param DB_Models_IReferenceTable_1 $reference_table
	 * @return void
	 */
	public function addReferenceTable(DB_Models_IReferenceTable_1 $reference_table)
	{
		$this->reference_tables[] = $reference_table;
	}
	
	/**
	 * Returns the name for a given ID. Returns FALSE if not found.
	 *
	 * @param int $id
	 * @return string
	 */
	public function toName($id)
	{
		$name = FALSE;
		
		foreach ($this->reference_tables AS $reference_table)
		{
			if ($name = $reference_table->toName($id))
			{
				break;
			}
		}
		
		return $name;
	}
	
	/**
	 * Returns the id for a given name. Returns FALSE if not found.
	 *
	 * @param string $name
	 * @return int
	 */
	public function toId($name)
	{
		$id = FALSE;
		
		foreach ($this->reference_tables AS $reference_table)
		{
			if ($id = $reference_table->toId($name))
			{
				break;
			}
		}
		
		return $id;
	}
	
	/**
	 * Returns all ids for a given name.
	 *
	 * @param string $name
	 * @return array
	 */
	public function toIdAll($name)
	{
		$ret = array();
		
		foreach ($this->reference_tables AS $reference_table)
		{
			if ($id = $reference_table->toId($name))
			{
				$ret[] = $id;
			}
		}
		
		return $ret;
	}
}

?>
