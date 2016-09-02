<?php
if (!defined('MYSQL_DB'))
{
	if (isset($_SESSION) && isset($_SESSION['config']) && isset($_SESSION['config']->mode) && (strtolower($_SESSION['config']->mode) == 'rc'))
	{
		define('MYSQL_DB', 'rc_olp_bb_visitor');
	}
	else
	{
		define('MYSQL_DB', 'olp_bb_visitor');
	}
}

$class_names = Array(
	'Client' => 'client', 
	'Target' => 'target', 
	'Campaign' => 'campaign', 
	'Rules' => 'rules', 
	'Tier' => 'tier', 
	);
foreach ($class_names as $current_class_name => $current_database_name)
	require_once("{$current_class_name}.class.php");

function join_where_clause($constraint)
{
	if (!$constraint)
		return '';

	$where = "WHERE \n";
	if (is_array($constraint))
	{
		foreach ($constraint as $current_constraint)
		{
			$where .= "\t{$current_constraint}\n";
		}
	}
	else
		$where .= "\t{$constraint}\n";

	return $where;
}

function parse_entered_date($entered_date)
{
	// Unambiguous SQL Date
	if ((strlen($entered_date) == 10) && ($entered_date[4] == '-') && ($entered_date[7] == '-'))
		return $entered_date;

	$has_slashes = strpos($entered_date, '/') !== FALSE;
	if (!$has_slashes)
		return FALSE;

	$us_date_parts = explode('/', $entered_date);
	for ($count = 0; $count < sizeof($us_date_parts); $count++)
	{
		$us_date_parts[$count] = sprintf("%02.0f", $us_date_parts[$count]);
	}
	switch (sizeof($us_date_parts))
	{
		case 3:
			// US Date
			return $us_date_parts[2] . '-' . $us_date_parts[0] . '-' . $us_date_parts[1];
			break;

		case 2:

			// US Date Missing Year
			return date('Y') . '-' . $us_date_parts[0] . '-' . $us_date_parts[1];
	}

	// Reject everything else
	return '0000-00-00';
}
