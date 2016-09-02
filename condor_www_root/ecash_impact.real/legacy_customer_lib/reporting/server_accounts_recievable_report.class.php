<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision: 16725 $
 */

require_once(SERVER_MODULE_DIR . "reporting/report_generic.class.php");
require_once(SERVER_CODE_DIR   . "accounts_recievable_report_query.class.php");

ini_set("memory_limit",-1);

class Customer_Report extends Report_Generic
{
	private $search_query;

	public function Generate_Report()
	{
		// Generate_Report() expects the following from the request form:
		//
		// criteria start_date YYYYMMDD
		// criteria end_date   YYYYMMDD
		// company_id
		//
		try
		{
			$this->search_query = new Customer_AR_Report_Query($this->server);
	
			$data = new stdClass();
	
			// Save the report criteria
			$data->search_criteria = array(
			  'specific_date_MM'   => $this->request->specific_date_month,
			  'specific_date_DD'   => $this->request->specific_date_day,
			  'specific_date_YYYY' => $this->request->specific_date_year,
			  'loan_type'          => $this->request->loan_type,
			  'company_id'         => $this->request->company_id
			);
	
			$_SESSION['reports']['accounts_recievable']['report_data'] = new stdClass();
			$_SESSION['reports']['accounts_recievable']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['accounts_recievable']['url_data'] = array('name' => 'AR Report', 'link' => '/?module=reporting&mode=accounts_recievable');
	
			if( ! checkdate($data->search_criteria['specific_date_MM'],
			                $data->search_criteria['specific_date_DD'],
			                $data->search_criteria['specific_date_YYYY']) )
			{
				$data->search_message = "Date invalid or not specified.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}
	
			$specific_date_YYYYMMDD = 10000 * $data->search_criteria['specific_date_YYYY'] +
			                          100   * $data->search_criteria['specific_date_MM'] +
			                                  $data->search_criteria['specific_date_DD'];

			$data->search_results = $this->search_query->Fetch_Payments_Due_Data($specific_date_YYYYMMDD,
										     							     $this->request->company_id);
		}
		catch (Exception $e)
		{
			echo $e;
			$data->search_message = "Unable to execute report. Reporting server may be unavailable.";
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}

		// we need to prevent client from displaying too large of a result set, otherwise
		// the PHP memory limit could be exceeded;
		/* Can't limit the report, this report will probably have > 10000 rows, must have it
		if(!empty($data->search_results) && count($data->search_results) > $this->max_display_rows)
		{
			$data->search_message = "Your report would have more than " . $this->max_display_rows . " lines to display. Please narrow the date range.";
			$this->server->transport->Set_Data($data);
			$this->server->transport->Add_Levels("message");
			return;
		}
		*/
		if( $data->search_results === 'invalid date' )
		{
			$data->search_message = "Invalid date.  Please select a date no earlier than " . date("m/d/Y", strtotime(Payments_Due_Report_Query::MAX_SAVE_DAYS . " days ago"));
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}

		// Sort if necessary
		$data = $this->Sort_Data($data);

		ECash::getTransport()->Add_Levels("report_results");
		ECash::getTransport()->Set_Data($data);
		$_SESSION['reports']['accounts_recievable']['report_data'] = $data;

	}
}

class Customer_AR_Report_Query extends Base_Report_Query
{
	const TIMER_NAME    = "AR Report Query - New";
	const ARCHIVE_TIMER = "AR Report Query - Archive";
	const CLI_TIMER     = "CLI - ";

	// # days worth of reports
	const MAX_SAVE_DAYS = "30";

	public function __construct(Server $server)
	{
		parent::__construct($server);
		$this->status_ids = null;
		
		$this->Add_Status_Id('arrangements_failed',           array('arrangements_failed', 'arrangements', 'collections', 'customer', '*root'));
		$this->Add_Status_Id('made_arrangements',          array('current',             'arrangements', 'collections', 'customer', '*root'));
		$this->Add_Status_Id('arrangements_hold',             array('hold',                'arrangements', 'collections', 'customer', '*root'));
		$this->Add_Status_Id('active',             array('active',  'servicing', 'customer', '*root'));
		$this->Add_Status_Id('past_due',             array('past_due',  'servicing', 'customer', '*root'));
		$this->Add_Status_Id('collections_new',             array('new',  'collections', 'customer', '*root'));
		$this->Add_Status_Id('collections_contact',             array('dequeued', 'contact',  'collections', 'customer', '*root'));
		$this->Add_Status_Id('collections_contact_queued',             array('queued', 'contact',  'collections', 'customer', '*root'));
		$this->Add_Status_Id('skip_trace',             array('skip_trace',  'collections', 'customer', '*root'));
		$this->Add_Status_Id('collections_(dequeued)',             array('indef_dequeue',  'collections', 'customer', '*root'));
		$this->Add_Status_Id('contact_follow up',             array('follow_up', 'contact',  'collections', 'customer', '*root'));
		$this->Add_Status_Id('bankruptcy_notification',             array('unverified',  'bankruptcy','collections', 'customer', '*root'));
		$this->Add_Status_Id('bankruptcy_verified',             array('verified',  'bankruptcy', 'collections', 'customer', '*root'));
		$this->Add_Status_Id('amortization',             array('amortization',  'bankruptcy', 'collections', 'customer', '*root'));
		$this->Add_Status_Id('servicing_hold',             array('hold',  'servicing', 'customer', '*root'));
		$this->Add_Status_Id('qc_arrangements',             array('arrangements',  'quickcheck', 'collections','customer', '*root'));
		$this->Add_Status_Id('qc_ready',             array('ready',  'quickcheck','collections', 'customer', '*root'));
		$this->Add_Status_Id('qc_sent',             array('sent',  'quickcheck','collections', 'customer', '*root'));
		$this->Add_Status_Id('qc_return',             array('return',  'quickcheck','collections', 'customer', '*root'));
		$this->Add_Status_Id('second_tier_(pending)',             array('pending',  'external_collections', '*root'));
		
		$this->collections_new = Search_Status_Map('new::collections::customer::*root', Fetch_Status_Map(FALSE) );
		$this->collections_contact = Search_Status_Map('dequeued::contact::collections::customer::*root', Fetch_Status_Map(FALSE) );
	}

	/**
	 * Gets the application_status_id's for the cashline branch to ensure the report does not include them
	 * @returns array ids
	 */
	private function Get_Cashline_Ids()
	{
		return implode( ",", array($this->cashline, $this->in_cashline, $this->pending_transfer) );
	}

	private function Get_Arrangement_Status_Ids()
	{
		return implode( ",", array($this->arrangements_failed, $this->made_arrangements, $this->arrangements_hold) );
	}
	
	private function Get_Status_Ids()
	{
		return implode( ",", $this->status_ids);
	}

	private function Get_Transaction_Type_Ids($company_list, $type)
	{
		switch( $type )
		{
			case 'principal':
				//				$name_short = " AND	name_short NOT LIKE '%\\_fee%'";
				$name_short = "";
				$principal  = " AND	affects_principal = 'yes'";
				break;
			
			case 'principal_non_disbursement':
				$name_short =  " AND	name_short not IN ('loan_disbursement','check_disbursement', 'moneygram_disbursement')";
				$principal  = " AND	affects_principal = 'yes'";
				break;
			case 'fee':
				$name_short = " AND	name_short IN ('payment_fee_ach_fail','assess_fee_ach_fail')";
				$principal  = "";
				//				$principal  = " AND	affects_principal = 'no'";
				break;
			case 'service_charge':
				$name_short = "AND name_short IN ('assess_service_chg', 'converted_sc_event', 'payment_service_chg', 'full_balance', 'personal_check_fees', 'h_fatal_cashline_return', 'h_nfatal_cashline_return', 'payout_fees')
							   OR name_short like '%Fees%'";
				$principal  = "";
				//				$principal  = " AND	affects_principal = 'no'";
				break;
			default:
				$name_short = '';
				$principal  = '';
				break;
		}

		$query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				event_type_id
			FROM
				transaction_type
				JOIN event_transaction USING (transaction_type_id)
			WHERE
				transaction_type.company_id    IN ({$company_list})
			
			{$name_short}
			{$principal}
		";
	//	echo '<pre>'.$query.'</pre>';
		$result = $this->db->Query($query);

		$ids = '';
		while( $row = $result->fetch(PDO::FETCH_OBJ) )
		{
			$ids .= $row->event_type_id . ",";
		}

		return (strlen($ids) > 0 ? substr($ids, 0, -1) : "");
	}

	/**
	 * Saves the data for 1 day of the report and deletes old entries
	 * @param array  $data array as returned from Fetch_Current_Data()
	 * @param string $date MySQL5 formatted date (YYYY-MM-DD)
	 * @access public
	 * @throws Exception
	 */
	public function Save_Report_Data($data, $date)
	{
		// Check the date passed
		if( strlen($date) == 10 )
		{
			$year  = substr($date, 0, 4);
			$month = substr($date, 5, 2);
			$day   = substr($date, 8, 2);
		}
		elseif( strlen($date) == 8 )
		{
			$year  = substr($date, 0, 4);
			$month = substr($date, 4, 2);
			$day   = substr($date, 6, 2);
		}
		else
		{
			$year  = null;
			$day   = null;
			$month = null;
		}

		if( ! checkdate($month, $day, $year) )
		{
			throw new Exception( "Payments Due Report [" . __METHOD__ . ":" . __LINE__ . "] invalid date parameter:  '{$date}'" );
		}

		// Quote the date for the query
		$date = "'{$date}'";

		// First make room
		// This is disabled for now.
		//$this->Delete_Old_Data();
		
		$company_ids = $this->Get_Company_Ids();

		foreach( $data as $co => $line )
		{
			for( $x = 0 ; $x < count($line) ; ++$x )
			{
				if($line[$x]['application_id'] != 'Totals:')
				{
					$save_query = "
						-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
						INSERT IGNORE INTO resolve_ar_report
							(date_created,company_name_short ,application_id ,name_last ,name_first,status,prev_status,
					fund_date ,company_id,fund_age ,collection_age ,status_age,payoff_amt ,principal_pending ,principal_fail,principal_total  ,
					fees_pending , fees_fail, fees_total,service_charge_pending,service_charge_fail ,service_charge_total,nsf_ratio)
						VALUES
						";
	
					
	
					// All this comes from the database, no escaping necessary
					$company_id = $company_ids[strtolower($co)];
					$co_name    =  $this->db->quote(strtolower($co))              ;
					$name_last  =  $this->db->quote($line[$x]['name_last'])         ;
					$name_first =  $this->db->quote($line[$x]['name_first'])        ;
					$status     =  $this->db->quote(strtolower($line[$x]['status']));
					$prev_status  = $this->db->quote($line[$x]['prev_status'])          ;
					$fund_date         =  $this->db->quote($line[$x]['fund_date'])           ;
	
					$fund_age  =  $this->db->quote($line[$x]['fund_age'])        ;
					$app_id     = $line[$x]['application_id'];
					$collection_age  = $line[$x]['collection_age'] ;
					$status_age    = $line[$x]['status_age'];
					$principal_pending  = number_format($line[$x]['principal_pending'],      2, ".", "");
					$fees_pending       = number_format($line[$x]['fees_pending'],           2, ".", "");
					$service_charge_pending    = number_format($line[$x]['service_charge_pending'], 2, ".", "");
					$principal_fail  = number_format($line[$x]['principal_fail'],      2, ".", "");
					$fees_fail       = number_format($line[$x]['fees_fail'],           2, ".", "");
					$service_charge_fail    = number_format($line[$x]['service_charge_fail'], 2, ".", "");
					$principal_total  = number_format($line[$x]['principal_total'],      2, ".", "");
					$fees_total       = number_format($line[$x]['fees_total'],           2, ".", "");
					$service_charge_total    = number_format($line[$x]['service_charge_total'], 2, ".", "");
					$nsf_ratio = number_format($line[$x]['nsf_ration'],     2, ".", "");
					$payoff_amt     = number_format($line[$x]['payoff_amt'],     2, ".", "");
					$date_created = $line[$x]['date_created'];
					
	
					$save_query .= "
						( '{$date_created}',    {$co_name}, {$app_id},  {$name_last},
						 {$name_first}, {$status}, {$prev_status}, {$fund_date},{$company_id},{$fund_age},  {$collection_age},        {$status_age}, {$payoff_amt},
						 {$principal_pending},    {$principal_fail}, {$principal_total}, {$fees_pending},   {$fees_fail}, {$fees_total}, {$service_charge_pending}, {$service_charge_fail},
						 {$service_charge_total}, {$nsf_ratio})
						";
					
			
					
			
					$db = ECash_Config::getMasterDbConnection();
					
					//$save_result = $this->mysqli->Query($save_query);
					$save_result = $db->Query($save_query);
				}
			}
		}
	}

	public function Fetch_Payments_Due_Data($specific_date,  $company_id, $mode = null, $save = false)
	{
		$year  = substr($specific_date, 0, 4);
		$month = substr($specific_date, 4, 2);
		$day   = substr($specific_date, 6, 2);
		$timestamp = mktime(0, 0, 0, $month, $day, $year);

		// CLI or web?
		if( ! empty($mode) && strtolower($mode) == 'cli' )
			$timer = self::CLI_TIMER;
		else
			$timer = "";

		// Recent, should be in saved table
		$data = array();
		if( $timestamp < mktime(0, 0, 0)  && !$save)
		{
			$timer .= self::ARCHIVE_TIMER;
			$data = $this->Fetch_Past_Data($specific_date,  $company_id, $timer);
		
		
			if (count($data) > 0)
				return $data;
		}
		
		$timer .= self::TIMER_NAME;
		$data = $this->Fetch_Current_Data($specific_date,  $company_id, $timer);
		
		if ($save) {
			$this->Save_Report_Data($data, $specific_date);
		}
		return $data;
	}
	
	private function Delete_Old_Data()
	{
		// DELETE old data
		$delete_query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			DELETE FROM resolve_payments_due_report
			 WHERE report_date <= DATE_SUB(CURRENT_DATE(), INTERVAL " . self::MAX_SAVE_DAYS . " DAY)
			";
	}

	private function Fetch_Past_Data($specific_date, $company_id, $timer)
	{
		$this->timer->startTimer( $timer );

		$data = array();

		if(isset($_SESSION) && is_array($_SESSION['auth_company']['id']) && count($_SESSION['auth_company']['id']) > 0)
		{
			$auth_company_ids = $_SESSION['auth_company']['id'];
		}
		else
		{
			$auth_company_ids = array(-1);
		}

		if( $company_id > 0 )
			$company_list = "'{$company_id}'";
		else
			$company_list = "'" . implode("','", $auth_company_ids) . "'";

	//	$loan_type_list = $this->Get_Loan_Type_List($loan_type);

		$past_query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT DISTINCT
				ar.date_created   AS date_created,
				ar.company_name_short   AS company_name,
				ar.application_id       AS application_id,
				ar.name_last            AS name_last,
				ar.name_first           AS name_first,
				ar.status               AS status,
				ar.prev_status          AS prev_status,
				
				ar.fund_date            AS fund_date,
				ar.company_id			AS company_id,
				ar.fund_age AS fund_age,
				
				ar.collection_age           	AS collection_age,
				
				ar.status_age       AS status_age,
				ar.payoff_amt AS payoff_amt,
				ar.service_charge_total            AS service_charge_total,
				ar.principal_total            AS principal_total,
				ar.fees_total                 AS fees_total,
				ar.principal_pending            AS principal_pending,
				ar.principal_fail            AS principal_fail,
				ar.fees_pending                 AS fees_pending,
				ar.service_charge_pending      AS service_charge_pending,
				ar.service_charge_fail      AS service_charge_fail,
				ar.fees_fail      AS fees_fail,
				ar.nsf_ratio           AS nsf_ratio
			FROM
				resolve_ar_report ar
			WHERE
				ar.date_created = '{$specific_date}'
			
			 AND	ar.company_id           IN ({$company_list})
			
			ORDER BY company_name_short
			";
		//echo '<pre>' .$past_query .'</pre>';
		$past_result = $this->db->Query($past_query);

		$data = array();
		$grands = array();
		while( $row = $past_result->fetch(PDO::FETCH_ASSOC) )
		{
			$co = strtoupper($row['company_name']);
			$date_created = $row['date_created'];
		//	unset($row['company_name']);
			
		//	$this->Get_Module_Mode($row);
			$grands['payoff_amt']  += $row['payoff_amt'];
			$grands['principal_total'] += $row['principal_total'];
			$grands['principal_pending'] += $row['principal_pending'];
			$grands['fees_pending']      += $row['fees_pending'];
			$grands['fees_total']        += $row['fees_total'];
			$grands['service_charge_pending'] += $row['service_charge_pending'];
			$grands['service_charge_total'] += $row['service_charge_total'];
			$grands['principal_fail']      += $row['principal_fail'];
			$grands['fees_fail']           += $row['fees_fail'];
			$grands['service_charge_fail'] += $row['service_charge_fail'];
			
			$row['name_first'] = ucfirst($row['name_first']);
			$row['name_last'] = ucfirst($row['name_last']);
			$row['status'] = ucfirst($row['status']);			
			
			if($row['payoff_amt'] != 0)
			{
				$row['nsf_ratio'] = 100 * (( $row['principal_fail'] +  $row['service_charge_fail'])/($row['payoff_amt']));
			}
			else
			{
				$row['nsf_ratio'] = 0;
			}
		

			$data[$co][] = $row;
		}
		$this->timer->Timer_Stop( $timer );

		return $data;
	}

	public function Fetch_Current_Data($specific_date, $company_id, $timer)
	{
		$this->timer->startTimer( $timer );

		if(isset($_SESSION) && is_array($_SESSION['auth_company']['id']) && count($_SESSION['auth_company']['id']) > 0)
		{
			$auth_company_ids = $_SESSION['auth_company']['id'];
		}
		else
		{
			$auth_company_ids = array(-1);
		}

		$data = array();

	

		if( $company_id > 0 )
			$company_list = "'{$company_id}'";
		else
			$company_list = "'" . implode("','", $auth_company_ids) . "'";

			// get ID lists for query
		$status_ids              = $this->Get_Status_Ids();
		$arrangement_status_ids  = $this->Get_Arrangement_Status_Ids();
		$principal_type_ids      = $this->Get_Transaction_Type_Ids($company_list, 'principal');
		$principal_non_disbursement_type_ids      = $this->Get_Transaction_Type_Ids($company_list, 'principal_non_disbursement');
		$fee_type_ids            = $this->Get_Transaction_Type_Ids($company_list, 'fee');
		$service_charge_type_ids = $this->Get_Transaction_Type_Ids($company_list, 'service_charge');
		$all_type_ids = "{$principal_type_ids},{$fee_type_ids},{$service_charge_type_ids}";
		$alt_date = date('Y-m-d', strtotime($specific_date));
		

		$collections_ids = "({$this->collections_new}, {$this->collections_contact}, {$this->collections_contact_queued})";

		// For each Application Id
		$query = "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				
				UPPER(c.name_short)                                   AS company_name_short,
				a.application_id                                      AS application_id,
				a.name_last                                           AS name_last,
				a.name_first                                          AS name_first,
				ass.name                                              AS status,
				DATEDIFF(now(),a.date_fund_actual)                    AS fund_age,
				DATEDIFF(now(),(
					select 
						sv.date_created 
					from 
						status_history sv 
					where 
						sv.application_id = a.application_id 
					order by 
						sv.date_created desc 
					limit 0,1 
					)
				)	                                                  AS status_age,	
				if(
            		ass.application_status_id IN {$collections_ids},
            		DATEDIFF(now(),(
						select 
							sv.date_created 
						from 
							status_history sv 
						where 
							sv.application_id = a.application_id 
						and 
							sv.application_status_id in {$collections_ids} 
						order by 
							sv.date_created asc 
						limit 0,1 
						)
            		 ),		
				
					DATEDIFF(now(),if(
						ISNULL(
							(
								select 
									sv.date_created 
								from 
									status_history sv 
								where 
									sv.application_id = a.application_id 
								and 
									sv.application_status_id in {$collections_ids} 
								order by 
									sv.date_created asc 
								limit 0,1 
								)
							),
							now(),
							(
								select 
									sv.date_created 
								from 
									status_history sv 
								where 
									sv.application_id = a.application_id 
								and 
									sv.application_status_id in {$collections_ids} 
								order by 
									sv.date_created asc 
								limit 0,1 
							)
						)
					)			
				)                                                       as collection_age,
												
				a.date_fund_actual as fund_date,
				a.company_id company_id,
				(
					select 
						name 
					from 
						status_history sv 
					join 
						application_status as2 on sv.application_status_id=as2.application_status_id 
					where 
						sv.application_id = a.application_id 
					and 
						as2.name != ass.name 
					order by 
						sv.date_created desc 
					limit 0,1 
				)                                                       as prev_status,
				CURDATE()                                               as date_created,
				(
					SELECT
						SUM(
							IF(
								tr.transaction_register_id IS NULL, 
								es.amount_principal + es.amount_non_principal, 
								tr.amount
							)
						)
					FROM
						event_schedule es
					LEFT JOIN 
						transaction_register tr USING (event_schedule_id)
					WHERE
						es.application_id = a.application_id 
					AND
						tr.transaction_status IN ('complete', 'pending')
							
				)                                                       as payoff_amt,
				a.fund_actual											AS principal_total,
				(
					SUM(
						IF(
								eat.name_short = 'service_charge'
							AND
								tr.transaction_status NOT IN ('failed')
							AND
								ea.amount > 0,
							ea.amount,
							0
						)
					)
				)                                                       AS service_charge_total,
				(
					SUM(
						IF(
								eat.name_short = 'fee'
							AND
								tr.transaction_status NOT IN ('failed')
							AND
								ea.amount > 0,
							ea.amount,
							0
						)
					)
				)                                                       AS fees_total,

				(
					SUM(	
						IF(
								eat.name_short = 'principal' 
							and 
								tr.transaction_status IN ('complete', 'pending'),
							ea.amount,
							0
						)
					) 
				)                                      					AS principal_pending,
				(
					SUM(
						IF(
								eat.name_short = 'principal'
							AND
								tr.transaction_status IN ('failed')
							AND
								ea.amount < 0,
							ea.amount,
							0
						)
					)
				)                                                       AS principal_fail,
				(
					SUM(	
						IF(
								eat.name_short = 'fee' 
							and 
								tr.transaction_status IN ('complete', 'pending'),
							ea.amount,
							0
						)
					) 
				)                                      					AS fees_pending,
				(
					SUM(
						IF(
								eat.name_short = 'fee'
							AND
								tr.transaction_status IN ('failed')
							AND
								ea.amount < 0,
							ea.amount,
							0
						)
					)
				)                                                       AS fees_fail,
				(
					SUM(	
						IF(
								eat.name_short = 'service_charge' 
							and 
								tr.transaction_status IN ('complete', 'pending'),
							ea.amount,
							0
						)
					) 
				)                                      					AS service_charge_pending,
				(
					SUM(
						IF(
								eat.name_short = 'service_charge'
							AND
								tr.transaction_status IN ('failed')
							AND
								ea.amount < 0,
							ea.amount,
							0
						)
					)
				)                                                       AS service_charge_fail
			FROM 
				event_schedule es
				JOIN company c USING (company_id)
				JOIN event_amount ea USING (event_schedule_id)
				JOIN event_amount_type eat USING (event_amount_type_id)
				LEFT JOIN transaction_register tr USING(event_schedule_id)
				JOIN application a ON es.application_id = a.application_id
				JOIN application_status ass USING (application_status_id)
				JOIN loan_type lt USING (loan_type_id)
			WHERE 
				(
					tr.transaction_register_id = ea.transaction_register_id OR
					tr.transaction_register_id IS NULL
				) AND
				
				
				es.company_id IN ({$company_list}) AND
				es.event_status <> 'suspended' AND
				
				
				a.application_status_id IN ({$status_ids})
			GROUP BY
				company_name_short,
				application_id,
				name_last,
				name_first,
				status,
				company_id
			HAVING payoff_amt > 0
			ORDER BY
				company_name_short,
				status
			
		";
	
	
		// This report should ALWAYS hit the master.
		//echo '<pre>' .$query .'</pre>';
		//exit();
		$db = ECash_Config::getMasterDbConnection();
		$result = $db->Query($query);
		$grands = array();
		$data = array();
		while( $row = $result->fetch(PDO::FETCH_ASSOC) )
		{
			$co = $row['company_name_short'];
			$id = $row['application_id'];
			$date_created = $row['date_created'];
		//	$this->Get_Module_Mode($row);
			
			$row['principal_fail']      = -$row['principal_fail'];
			$row['service_charge_fail'] = -$row['service_charge_fail'];
			$row['fees_fail']           = -$row['fees_fail'];


			// We're doing a truncation method where the failed amount is at maximum the pending amount so the NSF
			// ratio will be 100% max, this is stupid, but I'm only following orders. [benb]
			$principal_fail = ($row['principal_fail'] > $row['principal_pending']) ? $row['principal_pending'] : $row['principal_fail'];
			$service_charge_fail = ($row['service_charge_fail'] > $row['service_charge_pending']) ? $row['service_charge_pending'] : $row['service_charge_fail'];
			$fees_fail = ($row['fees_fail'] > $row['fees_pending']) ? $row['fees_pending'] : $row['fees_failed']; 

			if ($row['payoff_amt'] == 0)
				$row['nsf_ratio'] = 0;
			else
			{
				$row['nsf_ratio'] = 100 * (($principal_fail + $service_charge_fail + $fees_fail) / $row['payoff_amt']);
			}
			
			$grands['payoff_amt']  += $row['payoff_amt'];
			$grands['principal_total'] += $row['principal_total'];
			$grands['principal_pending'] += $row['principal_pending'];
			$grands['fees_pending']      += $row['fees_pending'];
			$grands['fees_total']        += $row['fees_total'];
			$grands['service_charge_pending'] += $row['service_charge_pending'];
			$grands['service_charge_total'] += $row['service_charge_total'];
			$grands['principal_fail']      += $row['principal_fail'];
			$grands['fees_fail']           += $row['fees_fail'];
			$grands['service_charge_fail'] += $row['service_charge_fail'];
				
			$data[$co][] = array(
				'application_id' => $row['application_id'],
				'company_name' => $row['company_name_short'],
				'name_last'    => ucfirst($row['name_last']),
				'name_first'   => ucfirst($row['name_first']),
				'status'       => $row['status'],
				'prev_status'  => $row['prev_status'],
				'fund_age'     => $row['fund_age'],
				'collection_age' => $row['collection_age'],
				'status_age'  => $row['status_age'],
				'date_created'   => $row['date_created'],
				'payoff_amt'      => $row['payoff_amt'],
				'principal_total' => $row['principal_total'],
				'principal_pending' => $row['principal_pending'],
				'fees_pending'      => $row['fees_pending'],
				'fees_total'        => $row['fees_total'],
				'service_charge_pending' => $row['service_charge_pending'],
				'service_charge_total' => $row['service_charge_total'],
				'principal_fail'      => $row['principal_fail'],
				'fees_fail'           =>$row['fees_fail'],
				'service_charge_fail' => $row['service_charge_fail'],
				'fund_date'         => $row['fund_date'],
				'nsf_ratio' => $row['nsf_ratio'],
				'module'         => isset($row['module']) ? $row['module'] : null,
				'mode'           => isset($row['mode']) ? $row['mode'] : null
			);
		}
		$this->timer->stopTimer( $timer );
		return $data;
	}
}

?>
