<?php
/**
 * @package Ecash.Models
 */
class ECash_Models_ExternalBatchType extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
{
	public function getColumns()
	{
		static $columns = array(
				'date_modified', 'date_created', 'external_batch_type_id',
				'name', 'name_short'
		);
		return $columns;
	}

	public function getPrimaryKey()
	{
		return array('external_batch_type_id');
	}

	public function getAutoIncrement()
	{
		return 'external_batch_type_id';
	}

	public function getTableName()
	{
		return 'external_batch_type';
	}

	public function getTypeId($name_short)
	{
		$type = new self($this->getDatabaseInstance());
		$type->loadBy(array('name_short' => $name_short));
		if(empty($type->external_batch_type_id))
		{
			$type->name_short = $name_short;
			$type->name = ucwords(str_replace('_',' ',$name_short));
			$type->insert();
		}
		return $type->external_batch_type_id;
	}


}
?>
