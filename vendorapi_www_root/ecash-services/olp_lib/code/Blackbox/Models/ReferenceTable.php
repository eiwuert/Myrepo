<?php
/**
 * Extended DB_Models_ReferenceTable_1 object to add toArray functionality.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Blackbox_Models_ReferenceTable extends DB_Models_ReferenceTable_1 implements Blackbox_Models_IReferenceTable
{
	/**
	 * Converts the reference table to an array with the ID as the index and the name as the value.
	 *
	 * @return array
	 */
	public function toArray($id_as_key = TRUE)
	{
		$array = array();
		
		foreach ($this->name_map as $name => $model)
		{
			if ($id_as_key)
			{
				$array[$model->{$this->empty->getColumnID()}] = $name;
			}
			else
			{
				$array[$name] = $model->{$this->empty->getColumnID()};
			}
		}
		
		return $array;
	}
}
