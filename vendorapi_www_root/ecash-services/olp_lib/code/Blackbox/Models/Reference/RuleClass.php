<?php
class Blackbox_Models_Reference_RuleClass extends Blackbox_Models_Reference_Model
{
	public static function getBy(array $where_args, array $override_dbs = NULL)
	{
		$query = "SELECT * FROM rule_class " . self::buildWhere($where_args) . " LIMIT 1";

		$base = new self();

		if (($row = $base->getDatabaseInstance(self::DB_INST_READ)->querySingleRow($query, $where_args)) !== FALSE)
		{
			$base->fromDbRow($row);
			return $base;
		}
		
		return NULL;
	}
	
	public function getColumns()
		{
			static $columns = array(
				'rule_class_id', 'class'
			);
			return $columns;
		}
	
	public function getPrimaryKey()
	{
		return array('rule_class_id');
	}
	
	public function getAutoIncrement()
	{
		return 'rule_class_id';
	}
	
	public function getTableName()
	{
		return 'rule_class';
	}
	
	public function getColumnID()
	{
		return 'rule_class_id';
	}

	public function getColumnName()
	{
		return 'class';
	}
}
