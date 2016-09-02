<?php

class ECash_Data_Status extends ECash_Data_DataRetriever
{
	public function hasCollections($application_id)
	{
		//I thought I could replace the subselect with items from a cached status list,
		//but I don't want to decipher what all statuses that stupid subselect includes
		$query = "SELECT count(*)
				FROM status_history
				WHERE application_id = ?
				AND application_status_id in (
				    (SELECT application_status_id
				     FROM application_status_flat
				     WHERE (level1='external_collections' and level0 != 'recovered')
				     OR (level2='collections') OR (level1='collections'))";

		$st = $this->db->prepare($query);
		$st->execute(array($application_id));

		return (($st->fetchColumn() > 0) ? TRUE : FALSE);
	}

	/**
	 * Replaces ecash_api.2.php _Get_Status_Date()
	 */
	public function getEarliestStatusDate($application_id, $status_ids)
	{
		$status_ids = implode(",", $status_ids);
		$query = "
			SELECT
				sh.date_created
			FROM    status_history AS sh
			WHERE   sh.application_id = ?
			AND     sh.application_status_id IN ($status_ids)
			ORDER BY date_created ASC
			LIMIT 1";

		return DB_Util_1::querySingleValue($this->db, $query, array($application_id));
	}
}

?>