<?php

abstract class Analysis_Batch implements Analysis_IBatch
{
	const DB_NAME = 'ldb';
	const DB_USER = 'datax';
	const DB_PASS = 'Zah1tee0';
	const DB_PORT = 3306;

	protected static $cashline_status_lookup = array(
		'ACTIVE' => 'active',
		'INACTIVE' => 'paid',
		'HOLD' => 'quickcheck',
		'COLLECTION' => 'internal_collections',
		'DENIED' => 'denied',
		'BANKRUPTCY' => 'bankruptcy',
		'INCOMPLETE' => 'unknown',
		'' => 'unknown',
		'PENDING' => 'unknown',
		'SCANNED' => 'external_collections',
		'WITHDRAWN' => 'withdrawn'
	);

	protected $mode;
	protected $company;
	protected $company_id;
	protected $status_lookup;
	protected $time_start;
	protected $time_end;

	/**
	 * @var Analysis
	 */
	protected $analytics;

	/**
	 * @var DB_IConnection_1
	 */
	protected $ecash_db;

	/**
	 * @var DB_IConnection_1
	 */
	protected $legacy_db;

	protected $status_map;
	protected $status_reverse_map;
	protected $trans_map;
	protected $clearing_map;
	protected $event_map;
	protected $amount_map;
	protected $ach_map;
	protected $disbursement_map;

	protected $effective_date;
	protected $loan_count;

	public function __construct($company_name_short, DB_IConnection_1 $ecash_db, Analysis $a, DB_IConnection_1 $legacy_db = NULL)
	{
		$this->company = $company_name_short;
		$this->analytics = $a;

		// connect to the database
		$this->ecash_db = $ecash_db;
		$this->legacy_db = $legacy_db;

		// avoid tons of DB work for these values
		$this->setCompanyId();
		$this->fetchStatusMap();
		$this->fetchEventTypeMap();
		$this->fetchAmountTypeMap();
		$this->fetchTransactionTypeMap();

		// Set the customer name so customer specific models
		// can be loaded.
		$this->analytics->setCustomerName($this->customer_name);

	}

	public function execute($effective_date = NULL)
	{
		// convert to compatible date
		if ($effective_date === NULL)
		{
			$this->effective_date = date('Y-m-d H:i:s');
		}
		elseif (is_numeric($effective_date))
		{
			$this->effective_date = date('Y-m-d H:i:s', $effective_date);
		}

		$this->analytics->beginBatch($this->company, Analysis::SYSTEM_ECASH);

		// FOR NOW... this should be run as a group
		$this->analytics->truncateCompany();

		echo "Querying for applications ... ";

		$this->time_start = time();

		$query = "
			select
				ap.application_id,
				ap.archive_cashline_id cashline_id,
				ap.ssn,
				ap.name_last,
				ap.name_first,
				ap.name_middle,
				ap.phone_home,
				ap.phone_cell,
				ap.phone_work,
				ap.employer_name,
				ap.street address_street,
				ap.unit address_unit,
				ap.city address_city,
				ap.state address_state,
				ap.zip_code address_zipcode,
				ap.legal_id_number drivers_license,
				ap.ip_address ip_address,
				ap.email email_address,
				date_format(ap.date_created, '%Y-%m-%d') date_origination,
				ap.dob,
				ap.income_frequency pay_frequency,
				ap.income_monthly,
				ap.bank_aba,
				ap.bank_account
			from application ap
				left join application newer on (
					newer.customer_id = ap.customer_id
					and (newer.date_application_status_set > ap.date_application_status_set
						or (newer.date_application_status_set = ap.date_application_status_set
							and newer.application_id > ap.application_id))
					and newer.date_created < '{$this->effective_date}'
				)
			where ap.date_created < '{$this->effective_date}'
				and ap.company_id = {$this->company_id}
				and newer.application_id is null
		";
		$st_cust = $this->ecash_db->query($query);

		/// TODO: Add back SSN check between applications in query?
		/// TODO: Add back company_id to query?

		$count = 0;
		$this->loan_count = 0;
		echo "done!  Query took " . (time() - $this->time_start) . " seconds\n";
		while ($customer = $st_cust->fetch())
		{
			try
			{
				$this->analytics->beginCustomer($customer);

				$num_loans = $this->addLoansForCustomer($customer['ssn']);
				// We only want to add customers that have loans!
				if($num_loans > 0)
				{
					$this->analytics->endCustomer();
					$count++;
				}
				else
				{
					$this->analytics->abortCustomer();
				}
			}
			catch (Exception $e)
			{
				echo "WARNING: ", $e->getMessage(), "; skipping customer ({$customer['ssn']})...\n";

				$this->analytics->abortCustomer();
			}
		}

		$this->analytics->endBatch();

		$runtime = (time() - $this->time_start);
		$time = $this->secondsToTime($runtime);
		echo "Finished in {$time['hours']} hours, {$time['minutes']} minutes, {$time['seconds']} seconds\n";
		echo "Inserted {$count} customers, and {$this->loan_count} loans.\n";
	}

	protected function addLoansForCustomer($ssn)
	{
	//	echo "Inserting loans for customer identified by $ssn\n";
		$loans_for_customer = 0;
		$st_app = $this->queryApplicationsBySSN($ssn);
		while ($app = $st_app->fetch(PDO::FETCH_OBJ))
		{
			//echo "Attempting to process $app->application_id\n";
			if (($loan = $this->processApplication($app)) !== FALSE)
			{
				try
				{
					$this->analytics->addLoan($loan);
					$loans_for_customer++;
					$this->loan_count++;
					//echo "Added {$app->application_id} to customer\n";
				}
				catch (Analysis_LoanException $e)
				{
					if(! in_array($loan['status'], array('withdrawn','denied')))
					{
						echo "WARNING: ", $e->getMessage(), " Ignoring loan for application_id (", $app->application_id, ") - Status: {$loan['status']}\n";
					}

					// Useful for debugging purposes, but most warnings are for withdrawn and denied applications

					//$loan = $e->getLoan();

					//echo "status         : {$loan['status']} \n";
					//echo "date_advance   : {$loan['date_advance']} \n";
					//echo "fund_amount    : {$loan['fund_amount']} \n";
					//echo "principal_paid : {$loan['principal_paid']} \n";
					//echo "fees_accrued   : {$loan['fees_accrued']} \n";
					//echo "fees_paid      : {$loan['fees_paid']} \n";
					//echo "current_cycle  : {$loan['current_cycle']} \n";
					
				}
				catch (Exception $e)
				{
					echo "WARNING: ", $e->getMessage(), " Ignoring loan for application_id (", $app->application_id, ")\n";
				}
			}
			else
			{
				// echo "Failed processing application {$app->application_id}\n";
			}
		}
		// echo "Loans for customer: {$loans_for_customer}\n";

		return $loans_for_customer;
	}

	/**
	 * Returns an associative array of calls to perform on a query builder to create the
	 * ApplicationsBySSN query. Array is keyed for extensions to manipulate easily.
	 *
	 * @return array
	 */
	protected function getQueryPlanForApplicationsBySSN()
	{
		// I'm not using query binding inside the plan because if an extension wants to remove part
		// it has to magically know what values are bound to it.

		return array(
			'addSelect' => array(
				'application_id' => 'ap.application_id',
				'archive_cashline_id' => 'ap.archive_cashline_id',
				'ssn' => 'ap.ssn',
				'application_status_id' => "(
						SELECT application_status_id
						FROM status_history AS sh
						WHERE  sh.application_id = ap.application_id
							AND sh.date_created < " . $this->ecash_db->quote($this->effective_date) . "
						ORDER BY date_created desc, status_history_id desc
						LIMIT 1
					) AS application_status_id",
				'date_fund_actual' => 'ap.date_fund_actual',
				'date_first_payment' => 'ap.date_first_payment',
				'fund_actual' => 'ap.fund_actual',
				'promo_id' => "(
						SELECT promo_id
						FROM campaign_info AS ci
						WHERE ci.application_id = ap.application_id
						ORDER BY (promo_id IN (10000, 33662, 33831)), campaign_info_id ASC
						LIMIT 1
					) AS promo_id",
				'converted_princ_bal' => "(
						SELECT amount
						FROM transaction_register AS register
						WHERE register.application_id = ap.application_id
							AND register.transaction_type_id = " . $this->ecash_db->quote($this->trans_map['converted_principal_bal']) . "
							AND register.amount > 0
							LIMIT 1
					) AS converted_princ_bal",
				'converted_fees_bal' => "(
						SELECT amount
						FROM transaction_register AS register
						WHERE
							register.application_id = ap.application_id
							AND register.transaction_type_id = " . $this->ecash_db->quote($this->trans_map['converted_service_chg_bal']) . "
							AND register.amount > 0
							limit 1
					) AS converted_fees_bal",
				'date_first_pending' => "(
						SELECT sh.date_created
						FROM status_history AS sh
						WHERE sh.application_id = ap.application_id
							AND sh.application_status_id = " . $this->ecash_db->quote($this->status_reverse_map['pending::prospect::*root']) . "
							AND sh.date_created < " . $this->ecash_db->quote($this->effective_date) . "
						ORDER BY date_created ASC, status_history_id ASC
						LIMIT 1
					) as date_first_pending",
				'campaign_short' => "(
						SELECT campaign_name
						FROM campaign_info AS ci
						WHERE ci.application_id = ap.application_id
						ORDER BY (promo_id IN (10000, 33662, 33831)), campaign_info_id ASC
						LIMIT 1
					) AS campaign_short",
				'promo_id_first' => "(
						SELECT promo_id
						FROM campaign_info AS ci
						WHERE ci.application_id = ap.application_id
						ORDER BY (promo_id IN (10000, 33662, 33831)), date_created ASC, campaign_info_id ASC
						LIMIT 1
					) AS promo_id_first",
				'promo_id_final' => "(
						SELECT promo_id
						FROM campaign_info AS ci
						WHERE ci.application_id = ap.application_id
						ORDER BY (promo_id IN (10000, 33662, 33831)), date_created DESC, campaign_info_id DESC
						LIMIT 1
					) AS promo_id_final",
				'price_point' => 'ap.price_point',
			),
			'setFrom' => 'application AS ap',
			'addWhere' => array(
				'company_id' => 'ap.company_id = ' . $this->ecash_db->quote($this->company_id),
				'date_created' => 'ap.date_created < ' . $this->ecash_db->quote($this->effective_date),
				'fund_actual' => 'ap.date_fund_actual IS NOT NULL ',
				'ssn' => 'ap.ssn = ?',
			),
			'setOrderBy' => 'ap.application_id DESC',
		);
	}

	protected function queryApplicationsBySSN($ssn)
	{
		if (empty($this->prepared['application_by_ssn']))
		{
			$qb = new DB_QueryBuilder($this->ecash_db);
			foreach ($this->getQueryPlanForApplicationsBySSN() as $function => $calls)
			{
				foreach ((array)$calls as $parameters)
				{
					call_user_func_array(array($qb, $function), (array)$parameters);
				}
			}

			$this->prepared['application_by_ssn'] = $this->ecash_db->prepare($qb->getQuery());
		}

		$this->prepared['application_by_ssn']->execute(array($ssn));
		return $this->prepared['application_by_ssn'];
	}

	protected function processApplication($app)
	{
		$status = $this->status_map[$app->application_status_id];

		// convert to an analytics status
		$status = $this->convertStatus(
			$status->level0,
			$status->level1,
			$status->level2,
			$status->level3,
			$status->level4,
			$status->level5
		);

		if (! in_array($status, array('ignore', 'denied', 'withdrawn')))
		{
			$bal = $this->fetchBalanceInfo($app->application_id);
			$mt = $this->fetchCycleInfo($app->application_id);
			$ach = $this->fetchACHInfo($app->application_id);

			if(!empty($mt->first_return_date)) // Except for funding
			{
				$col = $this->fetchCollectedAmounts($app->application_id, $mt->first_return_date);
				$col->amount_fees = (!empty($col->amount_fees)) ? $col->amount_fees : 0;
				$col->amount_principal = (!empty($col->amount_principal)) ? $col->amount_principal : 0;
			}
			else
			{
				$col = new stdClass();
				$col->amount_fees      = 0;
				$col->amount_principal = 0;
			}
			
			// add standard information
			$loan = $this->loanFromApplication($app);

			// add additional information
			$loan['status'] = $status;
			$loan['principal_paid'] = ($bal ? (float)$bal->principal_paid : 0);
			$loan['fees_accrued'] = ($bal ? (float)$bal->fees_accrued : 0);
			$loan['fees_paid'] = ($bal ? (float)$bal->fees_paid : 0);
			
			/**
			 * The following values are the amount that has been collected by
			 * the agents since the account's first return date. [#21781]
			 */
			$loan['collection_fees']      = abs($col->amount_fees);
			$loan['collection_principal'] = abs($col->amount_principal);
			
			/// TODO: Was 0 to NULL but old script escaping turned back to 0;
			$loan['first_return_pay_cycle'] = ($mt->first_return_cycle == 0 ? 0 : $mt->first_return_cycle);
			$loan['current_cycle'] = $mt->current_cycle;

			if ($status == 'paid')
			{
				$loan['date_loan_paid'] = $mt->date_last_completed_item;
			}

			if ($ach !== NULL)
			{
				$loan['first_return_date'] = $ach->first_return_date;
				$loan['first_return_code'] = $ach->first_return_code;
				$loan['first_return_msg'] = $ach->first_return_msg;
				$loan['last_return_date'] = $ach->last_return_date;
				$loan['last_return_code'] = $ach->last_return_code;
				$loan['last_return_msg'] = $ach->last_return_msg;
			}

			return $loan;
		}
		return FALSE;
	}

	/**
	 * Returns a base loan information array based off the application result object.
	 *
	 * @param object $app
	 * @return array
	 */
	protected function loanFromApplication($app)
	{
		$loan = array();
		$loan['application_id'] = $app->application_id;
		$loan['date_advance'] = $app->date_fund_actual;
		$loan['date_first_payment'] = $app->date_first_payment;
		$loan['date_application_sold'] = $app->date_first_pending;
		$loan['fund_amount'] = $app->fund_actual;
		$loan['campaign_short'] = $app->campaign_short;
		$loan['promo_id'] = $app->promo_id;
		$loan['promo_id_first'] = $app->promo_id_first;
		$loan['promo_id_final'] = $app->promo_id_final;
		$loan['lead_price'] = $app->price_point;
		
		return $loan;
	}

	protected function getDisbursementTypes()
	{
		return array(
			'loan_disbursement',
		);
	}

	protected function fetchBalanceInfo($application_id)
	{
		$query = "
			SELECT
				SUM(
		    		IF(
		    			ea.event_amount_type_id = {$this->amount_map['principal']}
			      			AND tr.transaction_type_id NOT IN ({$this->trans_map['cancel_principal']}, " . implode(',', $this->disbursement_map) . "),
		      			-ea.amount,
		      			0
					)
				) AS principal_paid,
		    	SUM(
		    		IF(
		    			ea.event_amount_type_id IN ({$this->amount_map['service_charge']}, {$this->amount_map['fee']})
							AND ea.amount > 0,
						ea.amount,
						0
					)
				) AS fees_accrued,
		    	SUM(
		    		IF(
		    			ea.event_amount_type_id IN ({$this->amount_map['service_charge']}, {$this->amount_map['fee']})
			    			AND ea.amount < 0,
						-ea.amount,
		      			0
					)
				) AS fees_paid
			FROM event_amount AS ea
				JOIN transaction_register AS tr ON (tr.transaction_register_id = ea.transaction_register_id)
				LEFT JOIN transaction_history AS th ON (
					th.transaction_register_id = ea.transaction_register_id
					AND th.date_created > '{$this->effective_date}'
				)
				LEFT JOIN transaction_history AS th_first ON (
					th_first.transaction_register_id = ea.transaction_register_id
					AND th_first.date_created > '{$this->effective_date}'
					AND th_first.transaction_history_id < th.transaction_history_id
				)
			WHERE ea.application_id = {$application_id}
				AND tr.date_created < '{$this->effective_date}'
				AND tr.transaction_type_id not in ({$this->trans_map['converted_principal_bal']}, {$this->trans_map['converted_service_chg_bal']})
				AND th_first.transaction_history_id IS NULL
				AND (
					# If the transaction hasn't changed since the batch started then use its information
					(
						th.transaction_history_id IS NULL
						AND tr.transaction_status = 'complete'
					)
					# Otherwise use the next transaction history entry to determine the last status.
					OR (
						th.transaction_history_id IS NOT NULL
						AND th.status_before = 'complete'
					)
				)
		";

		return $this->ecash_db->query($query)->fetch(PDO::FETCH_OBJ);
	}

	protected function fetchCycleInfo($application_id)
	{
		$query = "
      select
	      (
		      select
			      count(*)
		      from event_schedule es
		      where
			      application_id = {$application_id}
			      and event_type_id = '{$this->event_map['payment_service_chg']}'
			      and event_status = 'registered'
			      and date_created < '{$this->effective_date}'
			      and origin_id is null
	      ) current_cycle,
	      (
		      select
			      count(*)
		      from event_schedule es
		      where
			      application_id = {$application_id}
			      and event_type_id = '{$this->event_map['payment_service_chg']}'
			      and event_status = 'registered'
			      and origin_id is null
			      and date_event <
						(
				      select min(tr.date_effective)
				      from transaction_register tr
						join transaction_history h on (
							h.transaction_register_id = tr.transaction_register_id

							# TODO: FIX ME?
							AND h.application_id = tr.application_id
						)
				      where tr.application_id = {$application_id}
				      			and tr.transaction_type_id in (".implode(', ', $this->clearing_map['ach']).")
								and h.date_created < '{$this->effective_date}'
								and h.status_after = 'failed'
								and tr.transaction_status = 'failed'
								and tr.amount < 0
						)
	      ) first_return_cycle,
	      (
		      select
			      cast(th.date_created as date)
		      from transaction_history th
			      join transaction_register tr on (th.application_id = tr.application_id
			        and th.transaction_register_id = tr.transaction_register_id)
		      where
			      tr.application_id = {$application_id}
			      and tr.transaction_status = 'failed'
			      and th.status_after = 'failed'
			      and th.date_created < '{$this->effective_date}'
				  and tr.amount < 0
		      order by th.date_created ASC
		      limit 1
	      ) AS first_return_date,
	      (
		      select
			      cast(th.date_created as date)
		      from transaction_register tr
			      join transaction_history th on (th.application_id = tr.application_id
			        and th.transaction_register_id = tr.transaction_register_id)
		      where
			      tr.application_id = {$application_id}
			      and th.status_after = 'complete'
			      and th.date_created < '{$this->effective_date}'
		      order by tr.date_created desc, th.date_created desc, tr.transaction_register_id desc
		      limit 1
	      ) date_last_completed_item
		";
		$mt = $this->ecash_db->query($query)
			->fetch(PDO::FETCH_OBJ);

		/// NOTE: Modified date_last_completed_item to ensure history entry is same application
		/// NOTE: Removed <= from first_return_cycle (old version has just <)

		return $mt;
	}

	/**
	 * Grabs balances for the total amount of debits made after a 
	 * customer's first fail date.  Will exclude certain transactions 
	 * which are defined in getExcludedCollectionTypes.
	 *
	 * @param int $application_id
	 * @param string $first_fail_date
	 * @return unknown
	 */
	protected function fetchCollectedAmounts($application_id, $first_fail_date)
	{
		$query = "
		SELECT
		        SUM(IF(eat.name_short = 'principal', ea.amount, 0)) AS amount_principal,
		        SUM(IF(eat.name_short IN('service_charge','fee'), ea.amount, 0)) AS amount_fees
		FROM transaction_register AS tr
		JOIN event_amount AS ea ON ea.event_schedule_id = tr.event_schedule_id AND ea.transaction_register_id = tr.transaction_register_id
		JOIN event_amount_type AS eat ON eat.event_amount_type_id = ea.event_amount_type_id
		JOIN transaction_type AS tt ON tt.transaction_type_id = tr.transaction_type_id
		WHERE tr.application_id = {$application_id}
		AND tr.transaction_status = 'complete'
		AND tt.name_short NOT IN ('".implode("','", $this->getExcludedCollectionTypes()) ."')
		AND tr.amount < 0
		AND tr.date_effective > '{$first_fail_date}'
		";
		$mt = $this->ecash_db->query($query)
			->fetch(PDO::FETCH_OBJ);

		return $mt;
	}

	/**
	 * Returns transaction types that are excluded from the query
	 * in fetchCollectedAmounts().
	 *
	 * @return array
	 */
	protected function getExcludedCollectionTypes()
	{
		return array(	'payment_service_chg', 
						'repayment_principal',
						'debt_writeoff_princ',
						'debt_writeoff_fees',
						'adjustment_internal_princ',
						'adjustment_internal_fees',
						'payment_fee_ach_fail',
						'writeoff_fee_ach_fail',
						'paydown',
						'payout_fees',
						'payout_principal',
					);
	}
	
	protected function fetchACHInfo($app_id)
	{
		$query = "
      select
          IFNULL(ar1.date_request, cast(th1.date_created as date)) as first_return_date,
	      arc1.name_short first_return_code,
	      arc1.name first_return_msg,
          IFNULL(ar2.date_request, cast(th2.date_created as date)) as last_return_date,
	      arc2.name_short last_return_code,
	      arc2.name last_return_msg
      from ach a1
        join transaction_register tr1 on tr1.ach_id = a1.ach_id
        join transaction_history as th1 on th1.transaction_register_id = tr1.transaction_register_id
	    left join ach_report ar1 on (ar1.ach_report_id = a1.ach_report_id)
	    left join ach_return_code arc1 on (arc1.ach_return_code_id = a1.ach_return_code_id)
        join ach a2 on (a2.application_id = a1.application_id)
        join transaction_register tr2 on tr2.ach_id = a2.ach_id
        join transaction_history as th2 on th2.transaction_register_id = tr2.transaction_register_id
	    left join ach_report ar2 on (ar2.ach_report_id = a2.ach_report_id)
	    left join ach_return_code arc2 on (arc2.ach_return_code_id = a2.ach_return_code_id)
      where
	      a1.application_id = {$app_id}
          and tr1.transaction_status = 'failed'
          and th1.status_after = 'failed'
          and th1.date_created < '{$this->effective_date}'
          and tr2.transaction_status = 'failed'
          and th2.status_after = 'failed'
          and th2.date_created < '{$this->effective_date}'
		  and a1.ach_type = 'debit'
		  and a2.ach_type = 'debit'
      order by first_return_date asc, a1.ach_id asc,
               last_return_date desc, a2.ach_id desc
      limit 1
    ";
    	/// NOTE: Modified order by for when only two returns happened on the same day.
		$ach = $this->ecash_db->query($query)
			->fetch(PDO::FETCH_OBJ);
		return is_object($ach) ? $ach : NULL;
	}

	protected function convertCashlineStatus($cashline_status)
	{
		if (isset(self::$cashline_status_lookup[$cashline_status]))
		return self::$cashline_status_lookup[$cashline_status];
		return 'unknown';
	}

	protected function convertStatus($level0, $level1, $level2, $level3, $level4, $level5)
	{
		switch (true)
		{
			case ( // denied
				($level0 == 'denied' && $level1 == 'applicant' &&  $level2 == '*root')
			):
				return 'denied';

			/**
			  * Note: The 'Chargeoff' status is used by eCash Commercial customers where the customer is 
			  * charged off, but the customer has not set up a relationship with an external collections 
			  * agency yet.  This was the best place to lump the applications. [BR]
			  */
			case ( //external collections
				($level0 == 'sent' && $level1 == 'external_collections' && $level2 == '*root')
				|| ($level0 == 'recovered' && $level1 == 'external_collections' && $level2 == '*root')
				|| ($level0 == 'pending' && $level1 == 'external_collections' && $level2 == '*root')
 				|| ($level0 == 'chargeoff' && $level1 == 'collections' && $level2 == 'customer' && $level3 == '*root')
				|| ($level0 == 'write_off' && $level1 == 'customer' && $level2 == '*root')
				|| ($level0 == 'verified' && $level1 == 'deceased' && $level2 == 'collections' && $level3 == 'customer' && $level4 == '*root')
			):
				return 'external_collections';

			case ( //withdrawn
				($level0 == 'withdrawn' && $level1 == 'applicant' && $level2 == '*root')
			):
				return 'withdrawn';

			case ( // paid
				($level0 == 'paid' && $level1 == 'customer' && $level2 == '*root') ||
				($level0 == 'settled' && $level1 == 'customer' && $level2 == '*root') ||
				($level0 == 'allowed' && $level1 == 'external_collections' && $level2 == '*root') ||
				($level0 == 'internal_recovered' && $level1 == 'external_collections' && $level2 == '*root')
			):
				return 'paid';

			case ( //active
				($level0 == 'active' && $level1 == 'servicing' && $level2 == 'customer' && $level3 == '*root')
				|| ($level0 == 'past_due' && $level1 == 'servicing' && $level2 == 'customer' && $level3 == '*root')
				//($level0 == 'approved' && $level1 == 'servicing' && $level2 == 'customer' && $level3 == '*root') ||
				//($level0 == 'hold' && $level1 == 'servicing' && $level2 == 'customer' && $level3 == '*root') ||
			):
				return 'active';

			case ( //internal_collections
				($level0 == 'new' && $level1 == 'collections' && $level2 == 'customer' && $level3 == '*root') ||
				($level0 == 'indef_dequeue' && $level1 == 'collections' && $level2 == 'customer' && $level3 == '*root') ||
				($level0 == 'queued' && $level1 == 'contact' && $level2 == 'collections' && $level3 == 'customer' && $level4 == '*root') ||
				($level0 == 'dequeued' && $level1 == 'contact' && $level2 == 'collections' && $level3 == 'customer' && $level4 == '*root') ||
				($level0 == 'follow_up' && $level1 == 'contact' && $level2 == 'collections' && $level3 == 'customer' && $level4 == '*root') ||
				($level0 == 'arrangements_failed' && $level1 == 'arrangements' && $level2 == 'collections' && $level3 == 'customer' && $level4 == '*root') ||
				($level0 == 'current' && $level1 == 'arrangements' && $level2 == 'collections' && $level3 == 'customer' && $level4 == '*root') ||
				($level0 == 'hold' && $level1 == 'arrangements' && $level2 == 'collections' && $level3 == 'customer' && $level4 == '*root') ||
				($level0 == 'collections_rework' && $level1 == 'collections' && $level2 == 'customer' && $level3 == '*root') ||
				($level0 == 'unverified' && $level1 == 'deceased' && $level2 == 'collections' && $level3 == 'customer' && $level4 == '*root')
			):
				return 'internal_collections';

			case ( // quickcheck
				($level0 == 'ready' && $level1 == 'quickcheck' && $level2 == 'collections' && $level3 == 'customer' && $level4 == '*root') ||
				($level0 == 'sent' && $level1 == 'quickcheck' && $level2 == 'collections' && $level3 == 'customer' && $level4 == '*root') ||
				($level0 == 'arrangements' && $level1 == 'quickcheck' && $level2 == 'collections' && $level3 == 'customer' && $level4 == '*root') ||
				($level0 == 'return' && $level1 == 'quickcheck' && $level2 == 'collections' && $level3 == 'customer' && $level4 == '*root')
			):
				return 'quickcheck';

			case ( //bankruptcy
				($level0 == 'unverified' && $level1 == 'bankruptcy' && $level2 == 'collections' && $level3 == 'customer' && $level4 == '*root') ||
				($level0 == 'verified' && $level1 == 'bankruptcy' && $level2 == 'collections' && $level3 == 'customer' && $level4 == '*root')
			):
				return 'bankruptcy';

			case ( //amortization
				($level0 == 'amortization' && $level1 == 'bankruptcy' && $level2 == 'collections' && $level3 == 'customer' && $level4 == '*root')
			):
				return 'amortization';

			default:
				return 'ignore';
		}
	}

	protected function setCompanyId()
	{
		$res = $this->ecash_db->query("
			select
				company_id
			from company
			where name_short = '{$this->company}'
			and active_status = 'active'
		");

		if ($res->rowCount() < 1)
		{
			throw new Exception("fatal error while fetching ecash company id");
		}

		$this->company_id = $res->fetch(PDO::FETCH_OBJ)->company_id;
	}

	protected function fetchStatusMap()
	{
		$st = $this->ecash_db->query("
			SELECT *
			FROM application_status_flat
		");

		$this->status_map = array();
		$this->status_reverse_map = array();

		while ($status = $st->fetch(PDO::FETCH_OBJ))
		{
			$this->status_map[$status->application_status_id] = $status;

			$chain = $status->level0;
			if($status->level1 != null) { $chain .= "::" . $status->level1; }
			if($status->level2 != null) { $chain .= "::" . $status->level2; }
			if($status->level3 != null) { $chain .= "::" . $status->level3; }
			if($status->level4 != null) { $chain .= "::" . $status->level4; }
			if($status->level5 != null) { $chain .= "::" . $status->level5; }

			$this->status_reverse_map[$chain] = $status->application_status_id;
		}
	}

	protected function fetchEventTypeMap()
	{
		$st = $this->ecash_db->query("
			select event_type_id, name_short
			from event_type
			where company_id = {$this->company_id}
				and active_status='active'
		");

		$this->event_map = array();

		while ($type = $st->fetch(PDO::FETCH_OBJ))
		{
			$this->event_map[$type->name_short] = $type->event_type_id;
		}
	}

	protected function fetchAmountTypeMap()
	{
		$st = $this->ecash_db->query("
			select event_amount_type_id, name_short
			from event_amount_type
		");

		$this->amount_map = array();

		while ($type = $st->fetch(PDO::FETCH_OBJ))
		{
			$this->amount_map[$type->name_short] = $type->event_amount_type_id;
		}
	}

	protected function fetchTransactionTypeMap()
	{
		$st = $this->ecash_db->query("
			select transaction_type_id, name_short, clearing_type
			from transaction_type
			where company_id = {$this->company_id}
				and active_status = 'active'
		");

		$this->trans_map = array();
		$this->clearing_map = array();
		$this->disbursement_map = array();

		$disbursement_types = $this->getDisbursementTypes();

		while ($type = $st->fetch(PDO::FETCH_OBJ))
		{
			$this->trans_map[$type->name_short] = $type->transaction_type_id;

			// index by clearing type
			!isset($this->clearing_map[$type->clearing_type])
				? $this->clearing_map[$type->clearing_type] = array($type->transaction_type_id)
				: $this->clearing_map[$type->clearing_type][] = $type->transaction_type_id;

			if ($type->clearing_type == 'ach')
			{
				$this->ach_map[] = $type->transaction_type_id;
			}

			if (in_array($type->name_short, $disbursement_types))
			{
				$this->disbursement_map[] = $type->transaction_type_id;
			}
		}
	}

	/**
	 * Very simple function to convert 
	 * seconds to a readable hours/minutes/seconds.
	 * Returns an array of values
	 */
	private function secondsToTime($input)
	{
		$result = array('hours' => 0, 'minutes' => 0, 'seconds' => 0);

		$result['hours'] = floor($input / 3600);
		$hours_remain = $input % 3600;
		$result['minutes'] = floor($hours_remain / 60);
		$result['seconds'] = $hours_remain % 60;

		return $result;
	}

}

?>
