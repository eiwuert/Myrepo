<?php

/**
 * @package Ecash.Models
 */
class ECash_Models_SreportList extends ECash_Models_IterativeModel
{
	protected $keyfile;

	public function getClassName()
	{
		return 'ECash_Models_Sreport';
	}

	public function getTableName()
	{
		return 'sreport';
	}

	// Fixes some weird bug, or at least stops the error message from appearing
	// I'd feel bad about this, but PHP isn't very helpful at debugging this.
	public function __sleep()
	{
	}

	public function loadByWithSorting(array $where_args, $sort_string = "")
	{
		$query = "SELECT * FROM sreport " . self::buildWhere($where_args) . " {$sort_string}";
		return ($this->statement = DB_Util_1::queryPrepared(
				$this->getDatabaseInstance(),
				$query,
				$where_args
		));
	}

	public function loadByWithDateRange(array $where_args, $date_field, $start_date, $end_date, $sort_string = "")
	{
		$query = "SELECT * FROM sreport " . self::buildWhere($where_args) . " AND {$date_field} BETWEEN '{$start_date}' AND '{$end_date}' {$sort_string}";
		return ($this->statement = DB_Util_1::queryPrepared(
				$this->getDatabaseInstance(),
				$query,
				$where_args
		));
	}

}

?>
