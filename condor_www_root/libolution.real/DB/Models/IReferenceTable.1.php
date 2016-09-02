<?php

/**
 * Encapsulates a reference table.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
interface DB_Models_IReferenceTable_1
{
	/**
	 * Returns the name for a given ID
	 * @param int $id
	 * @return string
	 */
	public function toName($id);
	
	/**
	 * Returns the id for a given name
	 * @param string $name
	 * @return int
	 */
	public function toId($name);
}

?>
