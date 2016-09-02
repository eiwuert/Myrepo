<?php
class Blackbox_Models_Reference_RuleModeType extends Blackbox_Models_Reference_Model
{
	public static function getBy(array $where_args, array $override_dbs = NULL)
	{
		$query = "SELECT * FROM rule_mode_type " . self::buildWhere($where_args) . " LIMIT 1";

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
				'rule_mode_type_id', 'name'
			);
			return $columns;
		}

	public function getPrimaryKey()
	{
		return array('rule_mode_type_id');
	}

	public function getAutoIncrement()
	{
		return 'rule_mode_type_id';
	}

	public function getTableName()
	{
		return 'rule_mode_type';
	}

	public function getColumnID()
	{
		return 'rule_mode_type_id';
	}

	public function getColumnName()
	{
		return 'name';
	}
}
