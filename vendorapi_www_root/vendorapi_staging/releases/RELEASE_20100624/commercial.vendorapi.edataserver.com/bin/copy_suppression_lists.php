<?php

if ($_SERVER['argc'] != 3)
{
	die('Usage: ' . basename(__FILE__) . ' <property_short> <database>' . "\n");
}

echo "\nSource (Reporting) Username: ";
$user["source"]["name"] = get_password();
echo "\nSource (Reporting) Password: ";
$user["source"]["pass"] = get_password();

// $source_db = new PDO('mysql:host=reporting.olp.ept.tss;dbname=olp_blackbox', $user["source"]["name"], $user["source"]["pass"]);
$source_db = new PDO('mysql:host=reporting.dbproxy.tss;port=3314;dbname=olp_blackbox', $user["source"]["name"], $user["source"]["pass"]);
$source_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


echo "\nTarget (eCash) Username: ";
$user["target"]["name"] = get_password();
echo "\nTarget (eCash) Password: ";
$user["target"]["pass"] = get_password();

$target_db = new PDO($_SERVER['argv'][2], $user["target"]["name"], $user["target"]["pass"]);
$target_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$v_lists = new VerificationLists($source_db);

$list_ids = $v_lists->getLists($_SERVER['argv'][1]);

echo "\n\n", 'Copying ', count($list_ids), ' to the target database.', "\n";

foreach ($list_ids as $list_id => $type)
{
	$v_lists->copyListIdToDatabase($list_id, $type, $target_db);
}

class VerificationLists
{
	protected $pdo;
	protected $company_short;
	protected $company_id;
	
	public function __construct(PDO $pdo)
	{
		$this->pdo = $pdo;
	}

	public function getLists($company_short)
	{
		$this->company_short = $company_short;

		$sup_rules = $this->getAllSuppressionRules($company_short);

		$list_ids = array();
		foreach ($sup_rules as $rule)
		{
			$rule_value = unserialize($rule['rule_value']);

			foreach ($rule_value as $list_id => $list_info)
			{
				if (is_array($list_info))
				{
					$list_info = $list_info[0];
				}
				$list_ids[$list_id] = $list_info;
			} 
		}

		return $list_ids;
	}

	public function copyListIdToDatabase($list_id, $type, PDO $db, $prefix = 'suppression_')
	{
		$this->company_id = $this->getCompanyID($db);
		$this->insertRowsToDatabase($this->getListRows($list_id, $type), $db, $prefix . 'lists');
		$this->insertRowsToDatabase($this->getListRevisionRows($list_id), $db, $prefix . 'list_revisions');
		$this->insertRowsToDatabase($this->getListRevisionValueRows($list_id), $db, $prefix . 'list_revision_values');
		$this->insertRowsToDatabase($this->getListValueRows($list_id), $db, $prefix . 'list_values');
	}

	protected function getCompanyID($db)
	{
		$query = "SELECT company_id FROM company WHERE name_short = ?";
		$stm = $db->prepare($query);
		$stm->execute(array($this->company_short));
		$row = $stm->fetchAll(PDO::FETCH_ASSOC);
		return $row[0]["company_id"];	
	}
	
	protected function getListRows($list_id, $type)
	{
		$query = "SELECT list_id, name, field_name, date_created, date_modified, description, loan_action, active, {$this->company_id} as company_id, '{$type}' as type FROM lists WHERE list_id = ?";
		return $this->fetchAllRowsFromQueryForListId($query, $list_id);
	}

	protected function getListRevisionRows($list_id)
	{
		$query = "
			SELECT DISTINCT list_revisions.* FROM lists
			JOIN list_revisions USING (list_id)
			WHERE list_id = ?;
		";
		return $this->fetchAllRowsFromQueryForListId($query, $list_id);
	}

	protected function getListRevisionValueRows($list_id)
	{
		$query = "
			SELECT DISTINCT list_revision_values.* FROM lists
			JOIN list_revisions USING (list_id)
			JOIN list_revision_values USING (list_id, revision_id)
			WHERE list_id = ?;
		";
		return $this->fetchAllRowsFromQueryForListId($query, $list_id);
	}

	protected function getListValueRows($list_id)
	{
		$query = "
			SELECT DISTINCT list_values.* FROM lists
			JOIN list_revisions USING (list_id)
			JOIN list_revision_values USING (list_id, revision_id)
			JOIN list_values USING  (value_id)
			WHERE list_id = ?;
		";
		return $this->fetchAllRowsFromQueryForListId($query, $list_id);
	}

	protected function fetchAllRowsFromQueryForListId($query, $list_id)
	{
		$stm = $this->pdo->prepare($query);
		$stm->execute(array($list_id));
		return $stm->fetchAll(PDO::FETCH_ASSOC);
	}

	protected function insertRowsToDatabase(Array $data, PDO $db, $table)
	{
		if (count($data)) {
			$column_names = implode(',', array_keys($data[0]));
			$place_holders = substr(str_repeat('?,', count($data[0])), 0, -1);
			$query = "INSERT IGNORE INTO {$table} ({$column_names}) VALUES ({$place_holders})";
			$stm = $db->prepare($query);

			// This is here to catch the existing lists.
			if ($table == 'suppression_lists')
			{
				$query = "update {$table} set type = ? where list_id = ?";
				$stm_suppression_lists = $db->prepare($query);
			}

			foreach ($data as $datum)
			{
				$stm->execute(array_values($datum));
				if ($table == 'suppression_lists')
				{
					echo "{$datum['name']}\n";
					$stm_suppression_lists->execute(array($datum['type'], $datum['list_id']));
				}
			}
		}
	}

	protected function getAllSuppressionRules($company_short)
	{
		$query = "
			SELECT t.property_short, otherr.*
			FROM
				target t
				JOIN rule r ON (t.rule_id = r.rule_id)
				JOIN rule_revision rr1 ON (rr1.rule_id = r.rule_id AND rr1.active = 1)
				JOIN rule_relation r_rel ON (r_rel.rule_id = r.rule_id AND r_rel.rule_revision_id = rr1.rule_revision_id)
				JOIN rule otherr ON (otherr.rule_id = r_rel.child_id)
				JOIN rule_definition rd ON (rd.rule_definition_id = otherr.rule_definition_id)
			WHERE
				rd.name_short = 'suppression_lists'
				AND t.property_short = ?
		";

		$result = $this->pdo->prepare($query);
		$result->execute(array($company_short));

		return $result->fetchAll(PDO::FETCH_ASSOC);
	}
}
function get_password()
{
	$ostty = `stty -g`;
	system(
		'stty -echo -icanon min 1 time 0 2>/dev/null || ' .
		'stty -echo cbreak'
	);  

	$password = trim(fgets(STDIN));

	system("stty $ostty");

	return $password;
}


?>
