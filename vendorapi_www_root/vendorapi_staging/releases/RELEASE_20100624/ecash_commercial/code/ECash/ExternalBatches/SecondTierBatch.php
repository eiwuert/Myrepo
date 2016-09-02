<?php

// This is the default 2nd tier class
class ECash_ExternalBatches_SecondTierBatch extends ECash_ExternalBatches_ExternalBatch
{
	function __construct($db)
	{
		parent::__construct($db);

		$this->before_status = array('pending', 'external_collections', '*root');
		$this->after_status  = array('sent',    'external_collections', '*root');

		$this->filename = 'second_tier' . '_' . date('Ymd') . '_' . ECash::getCompany()->name_short;
		$this->filename_extension = 'xls';
		$this->format   = 'tsv';

		$this->headers        = TRUE;
		$this->quoted_headers = TRUE;

		// dequeue afterwards
		$this->dequeue  = TRUE;

		$this->sreport_type      = 'second_tier_batch';
		$this->sreport_data_type = 'second_tier_batch';

		$this->columns = array(
			"LastName"               => array(),
			"FirstName"              => array(),
			"Address"                => array(),
			"City"                   => array(),
			"State"                  => array(),
			"Zip"                    => array(),
			"CustomerPhone"          => array(),
			"SSN"                    => array(),
			"CellPhone"              => array(),
			"Employer"               => array(),
			"EmployerPhone"          => array(),
			"EmailAddress"           => array(),
			"ReferenceName1"         => array(),
			"ReferenceRelationship1" => array(),
			"ReferencePhone1"        => array(),
			"ReferenceName2"         => array(),
			"ReferenceRelationship2" => array(),
			"ReferencePhone2"        => array(),
			"CustomerNumber"         => array(),
			"BankName"               => array(),
			"AccountType"            => array(),
			"ABA"                    => array(),
			"AccountNumber"          => array(),
			"EmployerPhoneExt"       => array(),
			"DOB"                    => array(),
			"LastAdvAmount"          => array(),
			"LastAdvance"            => array(),
			"AccountBalance"         => array(),
			"LastQC"                 => array(),
			"PrincipalBalance"       => array(),
			"ChargesBalance"         => array(),
			"FeesBalance"            => array(),
			"LastTransDate"          => array(),
			"LastTransType"          => array(),
			"FailureReason"          => array(),
		);
	}

	// We've got a list of application IDs we're working on 
	// get the extra data related to this to fill into the data
	// member
	public function process()
	{
		$this->updateProgress("Processing applications for batch",15);
		if ($this->company_id == NULL)
			throw new Exception('Second Tier batch requires a company ID');

		$application_list = implode(',', $this->application_ids);

		$query = "
			SELECT
				ap.application_id,
				ap.name_last                                                     AS LastName,
				ap.name_first                                                    AS FirstName,
				CONCAT(ap.street, ' ', IFNULL(ap.unit,''))                                  AS Address,
				ap.city                                                          AS City,
				ap.state                                                         AS State,
				ap.zip_code                                                      AS Zip,
				ap.phone_home                                                    AS CustomerPhone,
				ap.ssn                                                           AS SSN,
				ap.encryption_key_id                                             AS encryption_key_id,
				ap.phone_cell                                                    AS CellPhone,
				ap.employer_name                                                 AS Employer,
				ap.phone_work                                                    AS EmployerPhone,
				ap.email                                                         AS EmailAddress,
				(
					SELECT
						name_full
					FROM
						personal_reference pr
					WHERE
						pr.application_id = ap.application_id
					ORDER BY personal_reference_id DESC
					LIMIT 1
				)                                                                 AS ReferenceName1,
				(
					SELECT
						relationship
					FROM
						personal_reference pr
					WHERE
						pr.application_id = ap.application_id
					ORDER BY personal_reference_id DESC
					LIMIT 1
				)                                                                  AS ReferenceRelationship1,
				(
					SELECT
						phone_home
					FROM
						personal_reference pr
					WHERE
						pr.application_id = ap.application_id
					ORDER BY personal_reference_id DESC
					LIMIT 1
				)                                                                  AS ReferencePhone1,
				(
					SELECT
						name_full
					FROM
						personal_reference pr
					WHERE
						pr.application_id = ap.application_id
					ORDER BY personal_reference_id DESC
					LIMIT 1
					OFFSET 1
				)                                                                 AS ReferenceName2,
				(
					SELECT
						relationship
					FROM
						personal_reference pr
					WHERE
						pr.application_id = ap.application_id
					ORDER BY personal_reference_id DESC
					LIMIT 1
					OFFSET 1
				)                                                                  AS ReferenceRelationship2,
				(
					SELECT
						phone_home
					FROM
						personal_reference pr
					WHERE
						pr.application_id = ap.application_id
					ORDER BY personal_reference_id DESC
					LIMIT 1
					OFFSET 1
				)                                                                  AS ReferencePhone2,

				ap.application_id                                                AS CustomerNumber,
				ap.bank_name                                                     AS BankName,
				ap.bank_account_type                                             AS AccountType,
				ap.bank_aba                                                      AS ABA,
				ap.bank_account                                                  AS AccountNumber,
				ap.phone_work_ext                                                AS EmployerPhoneExt,
				ap.dob                                                           AS dob,
				ap.fund_actual                                                   AS LastAdvAmount,
				IFNULL(date_format(ap.date_fund_actual, '%m/%d/%Y'),'  /  /')    AS LastAdvance,
				ls.balance_complete                                              AS AccountBalance,
				IFNULL(date_format(qc.lastqc, '%m/%d/%Y'),'  /  /')              AS LastQC,
				ls.principalbalance                                              AS PrincipalBalance,
				ls.chargebalance 	                                             AS ChargesBalance,
				ls.feebalance 	                                                 AS FeesBalance,
				lf.fail_date_formatted                                           AS LastTransDate,
				lf.name                                                          AS LastTransType,
				lf.reason                                                        AS FailureReason,
				ls.principalbalance                                              AS PrincipalBalance,
				ls.chargebalance                                                 AS ChargesBalance,
				ls.feebalance                                                    AS FeesBalance,
				ls.balance_complete                                              AS AccountBalance
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
				    tr.transaction_status = 'complete'
				GROUP BY 
					application_id
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
					), '%m/%d/%Y') AS fail_date_formatted,
					itr.date_modified   AS modify_date,
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
				ap.company_id = {$this->company_id}
			AND
				ap.application_id IN ({$application_list})
			GROUP BY
				ap.application_id
		 	ORDER BY 
				CustomerNumber
		";

		$st = $this->db->query($query);

		$crypt = new ECash_Models_Encryptor(ECash::getMasterDb());

		while (($row = $st->fetch(PDO::FETCH_ASSOC)))
		{
			$row['SSN'] = $crypt->decrypt($row['SSN'], $row['encryption_key_id']);
			$row['AccountNumber'] = $crypt->decrypt($row['AccountNumber'], $row['encryption_key_id']);
			$dob = $crypt->decrypt($row['dob'], $row['encryption_key_id']);
			unset($row['dob']);
			$row['DOB'] = ($dob == NULL) ? ' / / ' : $dob;
			
			$this->data[$row['application_id']] = $row;
		}

		return TRUE;
	}

	public function run($num_apps = null)
	{
		if($this->external_batch_report_id == null)
		{
			throw new Exception('External Batch Report ID was not set! External Batch Report ID needs to be set using setExternalBatchReportId($id)!');
		}
		parent::run($num_apps);
	}
	protected function postprocess()
	{
		$this->updateProgress("Running post processing",10);
		//Save it to the database, first and foremost!
		$this->saveToDb();
		$company_id       = $this->company_id;

		// Create the ext collections batch
		$ext_batch = ECash::getFactory()->getModel('ExtCollectionsBatch');
		$ext_batch->date_created     = date('Y-m-d H:i:s');
		$ext_batch->date_modified    = date('Y-m-d H:i:s');
		$ext_batch->company_id       = ECash::getCompany()->company_id;
		$ext_batch->sreport_id       = $this->sreport_id;

		// No longer using this
		$ext_batch->ec_file_outbound   = NULL;

		$ext_batch->item_count         = $this->getAppCount();
		$ext_batch->ec_filename        = $this->getFilename();
		$ext_batch->is_adjustment      = 0;

		$ext_batch->external_batch_report_id = $this->external_batch_report_id;

		$ext_batch->save();

		$app_data = $this->getAppData();

		// Save individual details
		foreach ($app_data as $app)
		{
			$ext_col = ECash::getFactory()->getModel('ExtCollections');
			$ext_col->date_created  = date('Y-m-d H:i:s');
			$ext_col->date_modified = date('Y-m-d H:i:s');

			$ext_col->company_id               = ECash::getCompany()->company_id;
			$ext_col->application_id           = $app['application_id'];
			$ext_col->ext_collections_batch_id = $ext_batch->ext_collections_batch_id;
			$ext_col->current_balance          = $app['AccountBalance'];
			$ext_col->save();
		}

		$this->updateProgress("Updating applications status",5);
		
		foreach ($this->application_ids as $application_id)
		{
			if ($this->after_status != NULL)
			{
				$this->updateProgress("Updating {$application_id}'s status",.001);
				Update_Status(NULL, $application_id, $this->after_status, null, null, true);
			}

			if ($this->dequeue === TRUE)
			{
				$qm = ECash::getFactory()->getQueueManager();
				$qm->removeFromAllQueues(new ECash_Queues_BasicQueueItem($application_id));
			}
			
		}
		$this->updateProgress("Updated application status and dequeued applications",5);
		return TRUE;

	}

}

?>
