<?php

require 'libolution/AutoLoad.1.php';

class Status extends DB_Models_ReferenceModel_1
{
	public function getTablename()
	{
		return 'status';
	}

	public function getColumns()
	{
		return array('status_id', 'name_short', 'description');
	}

	public function getPrimaryKey()
	{
		return array('status_id');
	}

	public function getAutoIncrement()
	{
		return 'status_id';
	}

	public function getColumnID()
	{
		return 'status_id';
	}

	public function getColumnName()
	{
		return 'name_short';
	}
}

// 1. Initialize our database
$config = new DB_SQLiteConfig_1(':memory:');
$db = $config->getConnection();
initializeDB($db);

// 2. Create a reference table using our reference model
$status = new DB_Models_ReferenceTable_1(new Status($db), TRUE);

// 3. Now we can access individual statuses by name
var_dump(
	$status->pending->status_id,
	$status->toID('pending'),
	$status->completed->status_id
);

function initializeDB(DB_IConnection_1 $db)
{
	$db->exec('
		create table status (
			status_id integer primary key autoincrement,
			name_short text,
			description text
		)
	');

	$s1 = new Status($db);
	$s1->name_short = 'pending';
	$s1->description = "Application has not been sold.";
	$s1->save();

	$s2 = new Status($db);
	$s2->name_short = 'completed';
	$s2->description = "Application was sold.";
	$s2->save();
}

?>