<?php

require_once( SQL_LIB_DIR . 'fetch_status_map.func.php');

class External_Collections_Query
{
	protected $server;
	protected $status_map;

	/**
	 * @var DB_Database_1
	 */
	protected $db;

	public function __construct(Server $server)
	{
		$this->server = $server;
		$this->db = eCash_Config::getMasterDbConnection();
	}

	protected function Fetch_External_Collections_Adjustments(Array &$ids_to_delete)
	{
		$query = "
			SELECT
				ec.ext_corrections_id ext_corrections_id,
				ec.application_id application_id,
				CONCAT(a.name_first, ' ', a.name_last) customer_name,
				ecb.ext_collections_co ext_col_company_name,
				ec.adjustment_amount adjustment_amount,
				ec.date_created adjustment_date,
				ec.new_balance new_balance
			 FROM
			 	ext_corrections ec
			 	JOIN application a USING(application_id)
			 	JOIN ext_collections USING(application_id)
			 	JOIN ext_collections_batch AS ecb USING(ext_collections_batch_id)
			 FOR UPDATE
		";
		$st = $this->db->query($query);

		$adjustment_data = array();

		while ($row = $st->fetch(PDO::FETCH_OBJ))
		{
			$adjustment_data[] = $row;
			$ids_to_delete[] = $row->ext_corrections_id;
		}

		return $adjustment_data;  // row was not found
	}

	protected function Remove_External_Collections_Adjustments($ids_to_delete)
	{
		$id_batches = array_chunk($ids_to_delete, 50);

		foreach ($id_batches as $id_batch)
		{
			if (!count($id_batch)) break;
			$id_list = implode(', ', $id_batch);

			$query = "
				DELETE FROM ext_corrections
				WHERE ext_corrections_id IN ({$id_list})
			";
			$result = $this->db->exec($query);
		}
	}

	/* Gets a count of all applications marked as "pending->external_collections->*root" */
	public function Fetch_Adjustment_Count()
	{
		$ext_status = Search_Status_Map('pending::external_collections::*root', $this->getStatusMap());

		$query = "
			SELECT COUNT(*) count
			FROM ext_corrections ec
			 	JOIN application a USING(application_id)
			 	JOIN ext_collections USING(application_id)
			 	JOIN ext_collections_batch AS ecb USING(ext_collections_batch_id)
		";
		$count = $this->db->querySingleValue($query);
		return (int)$count;
	}

	public function Query_External_Collections_File( $ext_collections_batch_id, &$filename )
	{
		$query = "
			SELECT
				ecb.ec_filename,
				ecb.ec_file_outbound
			FROM ext_collections_batch AS ecb
			WHERE
				ecb.ext_collections_batch_id = ?
		";
		$row = $this->db->querySingleRow($query, array($ext_collections_batch_id), PDO::FETCH_OBJ);

		if ($row !== FALSE)
		{
			$filename = $row->ec_filename;
			return $row->ec_file_outbound;
		}

		return '';  // row was not found
	}

	public function Query_Available_Batch_Downloads($from_date, $to_date) //mantis:5598 - $to_date
	{
		if(!$limit = eCash_Config::getInstance()->MAX_SEARCH_DISPLAY_ROWS)
		{
			$limit = 500; // Set a limit of some sort if it's not configured.
		}

		//mantis:5598
		$from = $from_date->from_date_year . '-' . $from_date->from_date_month . '-' . $from_date->from_date_day;
		$to = $to_date->to_date_year . '-' . $to_date->to_date_month . '-' . $to_date->to_date_day;

		$query = "
			SELECT
				date_format(ecb.date_modified, '%m/%d/%Y') AS date_modified,
				date_format(ecb.date_created, '%m/%d/%Y') AS date_created,
				ecb.company_id,
				ecb.ext_collections_batch_id,
				ecb.ec_filename,
				ecb.batch_status,
				ecb.item_count AS record_count,
				if (ecb.is_adjustment, 'Adjustment', ecb.ext_collections_co) ext_collections_co
			FROM
				ext_collections_batch AS ecb
			WHERE
			 	ecb.company_id = ?
				AND date(ecb.date_created) BETWEEN ? AND ?
			 ORDER BY
				ecb.date_created DESC
			 LIMIT {$limit}
		";
		$st = $this->db->queryPrepared($query, array($this->server->company_id, $from, $to));
		return $st->fetchAll(PDO::FETCH_OBJ);
	}

	/* Gets a count of all applications marked as "pending->external_collections->*root" */
	public function Fetch_Pending_Count()
	{
		$ext_status = Search_Status_Map('pending::external_collections::*root', $this->getStatusMap());

		$query = "
			SELECT COUNT(*) AS count
			FROM
			(
				SELECT SUM(ea.amount) as amount
				FROM event_amount ea
					JOIN application a USING (application_id)
					JOIN transaction_register tr USING (transaction_register_id)
				WHERE tr.transaction_status = 'complete'
					AND a.application_status_id = ?
					AND	a.company_id = ?
				GROUP BY a.application_id
				HAVING amount > 0
			) amount
		";
		$count = $this->db->querySingleValue($query, array($ext_status, $this->server->company_id));
		return $count;
	}

	/* Grabs the applications that are marked for external_collections */
	public function Fetch_Ext_Coll_Records($application_id = NULL)
	{
		$ext_status = Search_Status_Map('pending::external_collections::*root', $this->getStatusMap());

		//Temporary change while we wait for some indexes over transaction_register
		// I wouldn't remove this if I were you, think of the queues!
		set_time_limit(120);

		// Modified as part of #21169
		// There's a bug that it's showing the date created of the last failed payment
		// rather than the date it failed. I have removed the portion of the query
		// which verifies that at least one failure exists as that's not a requisite
		// "Send to Second Tier" button as a good example of why that's not desired. [benb]
		$query = "
			SELECT
				ap.application_id    AS customernumber,
				ap.name_last         AS lastname,
				ap.name_first        AS firstname,
				ap.name_middle       AS middlename,
				ap.street            AS address1,
				ap.unit              AS address2,
				ap.city              AS city,
				ap.county            AS county,
				ap.state             AS state,
				ap.zip_code          AS zip,
				ap.phone_home        AS customerphone,
				ap.ssn               AS ssn,
				ap.phone_cell        AS cellphone,
				ap.employer_name     AS employer,
				ap.phone_work        AS employerphone,
				ap.email             AS emailaddress,
				ap.bank_name         AS bankname,
				ap.bank_account_type AS accounttype,
				ap.bank_aba          AS aba,
				ap.bank_account      AS accountnumber,
				ap.phone_work_ext    AS employerphoneext,
				IFNULL(DATE_FORMAT(ap.dob, '%m/%d/%Y'),'  /  /') AS dob,
				ap.fund_actual       AS lastadvamount,
				IFNULL(date_format(ap.date_fund_actual, '%m/%d/%Y'),'  /  /') AS lastadvance,
				ls.balance_complete  AS accountbalance,
				IFNULL(date_format(qc.lastqc, '%m/%d/%Y'),'  /  /') AS lastqc,
				lf.fail_date         AS last_fail_date,
				lf.name              AS last_fail_type,
				lf.reason            AS last_fail_reason,
				ls.principalbalance,
				ls.chargebalance,
				ls.feebalance
			FROM
				application AS ap
			LEFT JOIN 
			(
				SELECT
					SUM(ea.amount) balance_complete,
					a.application_id application_id,
					SUM(IF(eat.name_short='principal', ea.amount, 0)) principalbalance,
					SUM(IF(eat.name_short='service_charge', ea.amount, 0)) chargebalance,
					SUM(IF(eat.name_short='fee', ea.amount, 0)) feebalance
				FROM
					transaction_register AS tr
				JOIN 
					application AS a USING (application_id)
				JOIN 
					event_amount AS ea USING (event_schedule_id, transaction_register_id)
				JOIN 
					event_amount_type AS eat USING (event_amount_type_id)
				WHERE
				  	".((NULL === $application_id)
						? "a.application_status_id = {$ext_status}"
						: "a.application_id = {$application_id}")."
						AND tr.transaction_status = 'complete'
					GROUP BY application_id
			) AS ls ON (ap.application_id = ls.application_id)
		 	LEFT JOIN 
			(
				SELECT
					tr.application_id AS application_id,
					MAX(tr.date_created) AS lastqc
				FROM transaction_register AS tr
				JOIN 
					transaction_type AS tt USING (transaction_type_id)
				JOIN 
					application AS a USING (application_id)
				WHERE
					".((NULL === $application_id)
					? "a.application_status_id = {$ext_status}"
					: "a.application_id = {$application_id}")."
				AND 
					tt.name_short = 'quickcheck'
				GROUP 
					BY application_id
			) AS qc ON (qc.application_id = ls.application_id)
			LEFT JOIN
			(
				SELECT
					itr.application_id  AS application_id,
					DATE_FORMAT((
						SELECT MAX(date_created)
						FROM transaction_history AS th
						WHERE th.transaction_register_id = itr.transaction_register_id
						AND th.status_after = 'failed'
					), '%m/%d/%Y') AS fail_date,
					itr.date_modified   AS fail_date,
					IF(iach.ach_id IS NULL, iarcb.name, iarca.name) AS reason,
					itt.name            AS name
				FROM
					transaction_register itr
				JOIN
					transaction_type itt ON (itt.transaction_type_id = itr.transaction_type_id)
				LEFT JOIN
					ach iach ON (iach.ach_id = itr.ach_id)
				LEFT JOIN
					ecld iecld ON (iecld.ecld_id = itr.ecld_id)
				LEFT JOIN
					ach_return_code iarca ON (iarca.ach_return_code_id = iach.ach_return_code_id)
				LEFT JOIN
					ach_return_code iarcb ON (iarcb.name_short = iecld.return_reason_code)
				WHERE
					itr.transaction_status = 'failed'
				ORDER BY
					itr.date_modified 
				DESC
			) AS lf ON (lf.application_id = ap.application_id)
		 	WHERE
		 		".((NULL === $application_id)
					? "ap.application_status_id = {$ext_status}"
					: "ap.application_id = {$application_id}")."
		  	AND	
				ls.balance_complete > 0
		  	AND	
				ap.company_id = {$this->server->company_id}
			GROUP BY
				ap.application_id
		 	ORDER BY 
				customernumber
		";
		$st = $this->db->query($query);
		return $st->fetchAll(PDO::FETCH_ASSOC);
	}

	/* Gets the references for the application_id passed in*/
	public function Fetch_References_For_Ext_Coll($application_id)
	{
		$query = "
			SELECT
				pr.name_full AS referencename,
				pr.relationship AS referencerelationship,
				pr.phone_home AS referencephone
			FROM personal_reference AS pr
			WHERE pr.application_id = ?
			LIMIT 2
		";
		$st = $this->db->queryPrepared($query, array($application_id));
		return $st->fetchAll(PDO::FETCH_ASSOC);
	}

	/* Checks to see if a ext_coll batch file was generated for the date passed in or not*/
	public function Check_Sent_EC($strtotime)
	{
		return false;  // At the bottom this thing returns false.  No point in executing the sql if we always return false.

		$query = "
			SELECT *
			FROM process_log as pl
			WHERE pl.company_id = ".$this->server->company_id."
			  AND	pl.step = 'process_ec_records'
			  AND	pl.business_day BETWEEN ? AND ?
			  AND	pl.state = 'completed'
		";
		$args = array($this->server->company_id, date('Ymd',$strtotime), date('Ymd235959', $strtotime));
		$row = $this->db->querySingleRow($query, $args, PDO::FETCH_OBJ);

		// Just return false for now to not stop multiple processing in one day
		// Later, if that feature is needed, change one of these to true as necessary
		return FALSE;
	}

	/* Inserts the skeleton for the ext_coll_+batch record so that we can use the id when we update it*/
	public function Insert_Ext_Coll_Batch($company, $is_adjustment = false)
	{
		$adjustment = $is_adjustment ? 1 : 0;

		$query = "
			INSERT INTO ext_collections_batch
			(date_created, ext_collections_co, company_id, batch_status, is_adjustment)
			VALUES (CURRENT_TIMESTAMP(), ?, ?, 'created', ?)
		";
		$this->db->queryPrepared($query, array($company, $this->server->company_id, $adjustment));
		return $this->db->lastInsertId();
	}

	/* Inserts the for the ext_coll_record for each application outputted to the batch file */
	public function Insert_Ext_Coll_Record($application_id, $ec_batch_id, $balance)
	{
		$query = "
			INSERT INTO ext_collections
			(date_created, company_id, application_id, ext_collections_batch_id, current_balance)
			VALUES (CURRENT_TIMESTAMP(), ?, ?, ?, ?)
		";
		$this->db->queryPrepared($query, array($this->server->company_id, $application_id, $ec_batch_id, $balance));
		return $this->db->lastInsertId();
	}

	/* Updates the ext_collections_batch with the values passed*/
	public function Update_Ext_Coll_Batch($ec_batch_id, $filename, $file_contents, $item_count)
	{
		$query = "
			UPDATE
				ext_collections_batch
			SET
				batch_status     = 'sent',
				ec_file_outbound = ?,
				ec_filename      = ?,
				item_count       = ?
			WHERE
				ext_collections_batch_id = ?
		";
		$this->db->queryPrepared($query, array($file_contents, $filename, $item_count, $ec_batch_id));
	}

	public function Insert_Inc_Coll_Record($row_array, $batch_id)
	{
		list($t_agency,
			$t_company,
			$application_id,
			$ssn,
			$t_name,
			$edi_transaction_code,
			$correction_amount,
			$date_posted,
			$ext_collections_status,
			$reported_balance,
			$ext_collections_transaction_id) = $row_array;

		// this hard-coding map should probably be shoved somewhere else
		$company_map = array(
			"USFAC" => 3 ,
			"USFACB" => 3
		);
		$agency_map = array(
			"L100" => "pinion mgmt",
			"B100" => "pinion north"
		);
		$mapped_company_id = $company_map[$t_company];
		$ext_collections_co = $agency_map[$t_agency];

		$ssn = substr("0000000000" . $ssn, -9);

		if (is_numeric($edi_transaction_code))
		{
			$edi_transaction_code = substr("000" . $edi_transaction_code, -3);
		}

		$date_posted = date('Y-m-d', strtotime($date_posted));

		$query = "
			INSERT INTO incoming_collections_item (
				date_created,
				company_id,
				incoming_collections_batch_id,
				application_id,
				ssn,
				date_posted,
				edi_transaction_code,
				correction_amount,
				ext_collections_co,
				reported_balance,
				ext_collections_status,
				ext_collections_transaction_id,
				raw_record
			) VALUES (
				now(),
				{$mapped_company_id},
				{$batch_id},
				{$application_id},
				'{$ssn}',
				'{$date_posted}',
				'{$edi_transaction_code}',
				{$correction_amount},
				'{$ext_collections_co}',
				{$reported_balance},
				'{$ext_collections_status}',
				'{$ext_collections_transaction_id}',
				'" . serialize($row_array) . "'
			)
		";
		$this->db->exec($query);
	}

	public function Update_Inc_Coll_Batch_Status($batch_id, $status)
	{
		$query = "
			UPDATE incoming_collections_batch
			SET batch_status = ?
			WHERE incoming_collections_batch_id = ?
		";
		$this->db->queryPrepared($query, array($status, $batch_id));
	}

	public function Fetch_Ready_Inc_Coll_Batches($from_date, $to_date)
	{
		get_log("collections")->Write(__FILE__.":".__LINE__.":".__METHOD__.var_export($from_date, true).var_export($to_date, true), LOG_NOTICE);

		$from = $from_date->from_date_year . '-' . $from_date->from_date_month . '-' . $from_date->from_date_day;
		$to = $to_date->to_date_year . '-' . $to_date->to_date_month . '-' . $to_date->to_date_day;

		$query = "
			select
				b.*,
				success_count,
				success_aggregate,
				flagged_count,
				flagged_aggregate
			from
				incoming_collections_batch b
			LEFT JOIN (
				select
					i.incoming_collections_batch_id,
					count(*) as success_count,
					sum(i.correction_amount) as success_aggregate
				from incoming_collections_item i
				where i.status in ('completed','success')
				group by incoming_collections_batch_id
			) as s ON (s.incoming_collections_batch_id = b.incoming_collections_batch_id)
			LEFT JOIN (
				select
					i.incoming_collections_batch_id,
					count(*) as flagged_count,
					sum(i.correction_amount) as flagged_aggregate
				from incoming_collections_item i
				where i.status in ('flagged','failed')
				group by incoming_collections_batch_id
			) as f ON (f.incoming_collections_batch_id = b.incoming_collections_batch_id)
			WHERE date(b.date_created) BETWEEN ? AND ?
		";
		$st = $this->db->queryPrepared($query, array($from, $to));
		return $st->fetchAll(PDO::FETCH_OBJ);
	}

	public function Fetch_Inc_Coll_Batch($batch_id = NULL)
	{
		$query = "
			SELECT
				batch_status,
				file_name,
				file_contents
			FROM incoming_collections_batch
			WHERE incoming_collections_batch_id = ?
		";
		$row = $this->db->querySingleRow($query, array($batch_id), PDO::FETCH_ASSOC);
		return $row;
	}

	public function Munge_CLID_Inc_Coll_Record($id)
	{
		$bad_apps = $this->Check_Valid_Inc_Coll_Record($id);

		if (!count($bad_apps)) return;

		$query = "
			UPDATE
				incoming_collections_item i,
				application a
			SET
				i.application_id = a.application_id
			WHERE
				i.application_id = a.archive_cashline_id AND
				i.ssn = a.ssn AND
				i.company_id = a.company_id AND
				i.incoming_collections_batch_id = {$id} AND
				i.incoming_collections_item_id IN ( " . implode(", ",$bad_apps) . " )
		";
		$this->db->exec($query);
	}

	public function Check_Valid_Inc_Coll_Record($id, $item = FALSE)
	{
		$query = "
			SELECT i.incoming_collections_item_id
			FROM incoming_collections_item i
				LEFT JOIN application a ON (i.application_id = a.application_id)
			WHERE
				i.incoming_collections_" . (($item === TRUE) ? "item" : "batch" ) . "_id = ?
				AND (
					i.ssn != a.ssn OR
					i.company_id != a.company_id OR
					a.application_id IS NULL
				)
		";
		return $this->db->querySingleColumn($query, array($id));
	}

	public function Fetch_Inc_Coll_Records($batch_id, $exceptions = NULL)
	{
		if (is_array($exceptions) && count($exceptions))
		{
			$equery = " AND i.incoming_collections_item_id NOT IN (" . implode(",",$exceptions) . ") ";
		}

		$query = "
			SELECT
				i.*,
				if (t.action IS NOT NULL AND s.action IS NOT NULL AND s.action = t.action, t.action, 'other') as action
			FROM incoming_collections_item i
				LEFT JOIN incoming_collections_code_map t ON (i.edi_transaction_code = t.code_id AND
					i.ext_collections_co = t.ext_collections_co)
				LEFT JOIN incoming_collections_code_map s ON (i.ext_collections_status = s.code_id AND
					i.ext_collections_co = t.ext_collections_co)
			WHERE
				i.status = 'new' AND
				i.incoming_collections_batch_id = ?
				{$equery}
			GROUP BY i.incoming_collections_item_id
		";
		$st = $this->db->query($query, array($batch_id));
		return $st->fetchAll(PDO::FETCH_OBJ);
	}

	public function Inc_Coll_Item_Set_Message($item_id, $message)
	{
		if (is_array($item_id) && count($item_id))
		{
			$equery = " IN (" . implode(",",$item_id) . ") ";

		} else if (is_numeric($item_id))
		{
			$equery = " = {$item_id} ";
		} else
		{
			return;
		}

		$query = "
			UPDATE
				incoming_collections_item i
			SET
				i.result_msg = ?
			WHERE
				i.incoming_collections_item_id
				{$equery}
		";
		$this->db->queryPrepared($query, array($message));
	}

	protected function getStatusMap()
	{
		if (!$this->status_map)
		{
			$this->status_map = Fetch_Status_Map();
		}
		return $this->status_map;
	}
}

?>
