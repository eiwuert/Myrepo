<?php
/**
 * This class is model (database/queries) for the admin access of the suppression lists rules.
 *
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */

class ECash_SuppressionRules
{
	protected $db;

	public function __construct(DB_Database_1 $db){
		$this->db = $db;
	}

	public function Get_Suppression_Lists()
	{
		$query = "
			SELECT
				sl.list_id 		AS list_id,
				sl.name 		AS name,
				sl.type 		AS type,
				sl.description 		AS description,
				sl.field_name 		AS field_name,
				sl.date_created 	AS date_created,
				sl.date_modified 	AS date_modified,
				sl.loan_action 		AS loan_action,
				slr.revision_id 	AS revision_id,
				slr.date_modified 	AS date_revised
			FROM suppression_lists as sl
				JOIN suppression_list_revisions as slr ON (slr.list_id = sl.list_id)
			WHERE slr.status = 'ACTIVE' AND sl.active = 1
			ORDER BY sl.name
		";
		$st = $this->db->query($query);
		$rtn = $st->fetchAll(PDO::FETCH_OBJ);
		return $rtn;
	}

	public function Get_Suppression_List_Values()
	{
		$query = "
			SELECT
				sl.list_id 		AS list_id,
				slr.revision_id 	AS revision_id,
				slv.value_id	 	AS value_id,
				slv.value	 	AS value
			FROM suppression_lists AS sl
				JOIN suppression_list_revisions AS slr ON (slr.list_id = sl.list_id AND slr.status = 'active')
				JOIN suppression_list_revision_values AS slrv ON (slrv.list_id = sl.list_id AND slrv.revision_id = slr.revision_id)
				JOIN suppression_list_values AS slv ON (slv.value_id = slrv.value_id)
			WHERE slr.status = 'ACTIVE' AND sl.active = 1
			ORDER BY sl.list_id, slv.value_id
		";
		$st = $this->db->query($query);
		$rtn = $st->fetchAll(PDO::FETCH_OBJ);
		return $rtn;
	}

	public function Get_Suppression_Values()
	{
		$query = "
			SELECT DISTINCT
				slv.value_id	 	AS value_id,
				slv.value	 	AS value
			FROM suppression_list_values AS slv
		";
		$st = $this->db->query($query);
		$rtn = $st->fetchAll(PDO::FETCH_OBJ);
		return $rtn;
	}

	public function Delete_Suppression_List_Value($list_id, $revision_id, $value_id)
	{
		$query = "
			DELETE FROM suppression_list_revision_values
			WHERE list_id = ? AND revision_id = ? AND value_id = ?
		";
		
		$args = array($list_id, $revision_id, $value_id);
		
		$this->db->queryPrepared($query, $args);
		return TRUE;
	}

	public function Add_Suppression_List_Value($list_id, $revision_id, $value)
	{
		$query = "
			SELECT value_id FROM suppression_list_values
			WHERE value = ?
		";

		$st = $this->db->queryPrepared($query, array($value));
		$rows = $st->fetchAll(PDO::FETCH_OBJ);
		if (($rows) && ($rows[0]->value_id >= 0)) $value_id = $rows[0]->value_id;
		else {
			$query = "
				INSERT INTO suppression_list_values
				(value, date_created)
				VALUES (?,NOW())
			";
			$this->db->queryPrepared($query, array($value));
	
			$value_id = $this->db->lastInsertId();
		}
		
		$query = "
			SELECT value_id FROM suppression_list_revision_values
			WHERE list_id = ? AND revision_id = ? AND value_id = ?
		";
		
		$args = array($list_id, $revision_id, $value_id);

		$st = $this->db->queryPrepared($query, $args);
		$rows = $st->fetchAll(PDO::FETCH_OBJ);
		if (($rows) && ($rows[0]->value_id >= 0)) return $value_id;
		else {
			$query = "
				INSERT INTO suppression_list_revision_values
				(list_id, revision_id, value_id)
				VALUES (?,?,?)
			";
			$this->db->queryPrepared($query, $args);
	
			$value_id = $this->db->lastInsertId();
		}
	}
}

?>
