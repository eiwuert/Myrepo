<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

require_once(SERVER_MODULE_DIR."/reporting/report_generic.class.php");

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
			$this->search_query = new Customer_Verification_Performance_Report_Query($this->server);
	
			$data = new stdClass();
	
			// Save the report criteria
			$data->search_criteria = array(
			  'start_date_MM'   => $this->request->start_date_month,
			  'start_date_DD'   => $this->request->start_date_day,
			  'start_date_YYYY' => $this->request->start_date_year,
			  'end_date_MM'     => $this->request->end_date_month,
			  'end_date_DD'     => $this->request->end_date_day,
			  'end_date_YYYY'   => $this->request->end_date_year,
			  'company_id'      => $this->request->company_id,
			  'loan_type'       => $this->request->loan_type
			);
	
			$_SESSION['reports']['verification_performance']['report_data'] = new stdClass();
			$_SESSION['reports']['verification_performance']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['verification_performance']['url_data'] = array('name' => 'Verification Performance', 'link' => '/?module=reporting&mode=verification_performance');
	
			// Start date
			$start_date_YYYY = $this->request->start_date_year;
			$start_date_MM	 = $this->request->start_date_month;
			$start_date_DD	 = $this->request->start_date_day;
			if(!checkdate($start_date_MM, $start_date_DD, $start_date_YYYY))
			{
				//return with no data
				$data->search_message = "Start Date invalid or not specified.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}
	
			// End date
			$end_date_YYYY	 = $this->request->end_date_year;
			$end_date_MM	 = $this->request->end_date_month;
			$end_date_DD	 = $this->request->end_date_day;
			if(!checkdate($end_date_MM, $end_date_DD, $end_date_YYYY))
			{
				//return with no data
				$data->search_message = "End Date invalid or not specified.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}
	
			$start_date_YYYYMMDD = 10000 * $start_date_YYYY	+ 100 * $start_date_MM + $start_date_DD;
			$end_date_YYYYMMDD	 = 10000 * $end_date_YYYY	+ 100 * $end_date_MM   + $end_date_DD;
	
			if($end_date_YYYYMMDD < $start_date_YYYYMMDD)
			{
				//return with no data
				$data->search_message = "End Date must not precede Start Date.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}
	
			$data->search_results = $this->search_query->Fetch_Verification_Performance_Data($start_date_YYYYMMDD,
													 $end_date_YYYYMMDD,
													 $this->request->loan_type,
													 $this->request->company_id);
		}
		catch (Exception $e)
		{
			$data->search_message = "Unable to execute report. Reporting server may be unavailable.";
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}
		// we need to prevent client from displaying too large of a result set, otherwise
		// the PHP memory limit could be exceeded;
		if( $data->search_results === false )
		{
			$data->search_message = "Your report would have more than " . $this->max_display_rows . " lines to display. Please narrow the date range.";
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}

		// Sort if necessary
		$data = $this->Sort_Data($data);

		ECash::getTransport()->Add_Levels("report_results");
		ECash::getTransport()->Set_Data($data);
		$_SESSION['reports']['verification_performance']['report_data'] = $data;

	}
}

class Customer_Verification_Performance_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME = "Verification Performance Report Query";

	private $system_id;

	public function __construct(Server $server)
	{
		parent::__construct($server);

		$this->system_id = $server->system_id;

                $this->Add_Status_Id('withdrawn', array('withdrawn', 'applicant', '*root'));
                $this->Add_Status_Id('denied',    array('denied',    'applicant', '*root'));
	}

	public function Fetch_Verification_Performance_Data($date_start, $date_end, $loan_type, $company_id)
	{
		$this->timer->startTimer( self::$TIMER_NAME );

		//echo "\n<br><pre>" . print_r($_SESSION,true) . "</pre><br>\n";
		if (is_array($_SESSION['auth_company']['id']) && count($_SESSION['auth_company']['id']) > 0)
		{
			$auth_company_ids = $_SESSION['auth_company']['id'];
		}
		else
		{
			$auth_company_ids = array(-1);
		}

		$performance_data = array();

		$max_report_retrieval_rows = MAX_REPORT_DISPLAY_ROWS + 1;

		if( $company_id > 0 )
			$company_list = "'{$company_id}'";
		else
			$company_list = "'" . implode("','", $auth_company_ids) . "'";

		$loan_type_list = $this->Get_Loan_Type_List($loan_type);

		// Start and end dates must be passed as strings with format YYYYMMDD
		$timestamp_start = $date_start . '000000';
		$timestamp_end	 = $date_end   . '235959';

		$query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				upper(co.name_short)        AS company_name,
				concat(lower(a.name_last),
				       ', ',
				       lower(a.name_first))		 AS agent_name,
				sum(num_verified)           AS num_approved,
				sum(num_in_underwriting)    AS num_in_underwriting,
				sum(num_funded)             AS num_funded,
				sum(num_withdrawn)          AS num_withdrawn,
				sum(num_denied)             AS num_denied,
				sum(num_sendback)           AS num_reverified
			 FROM
				loan_type lt,
				(SELECT	shv.company_id,
					shv.agent_id,
					shv.application_id,
					1 as num_verified,
					(SELECT
						count(distinct 'X')
					  FROM
						status_history          AS shiu
					  WHERE
						shiu.company_id     =  shv.company_id
					   AND	shiu.application_id =  shv.application_id
					   AND	shiu.application_status_id = {$this->in_underwriting}
					   AND	shiu.date_created   < IFNULL((SELECT min(shiuref.date_created)
						                               FROM  status_history          AS shiuref
						                               WHERE shiuref.application_id = shv.application_id
						                                AND  shiuref.company_id     = shv.company_id
						                                AND  shiuref.application_status_id = {$this->reverified}
						                                AND  shiuref.date_created   > shv.date_created
						                              ), '2099-12-31 23:59:59'
						                             )
					   AND	shiu.date_created   > shv.date_created
					) as num_in_underwriting,
					(SELECT count(distinct 'X')
		                           FROM  status_history          AS shf
		                           WHERE shf.company_id      =  shv.company_id
		                            AND  shf.application_id  =  shv.application_id
		                            AND  shf.application_status_id = {$this->active}
		                            AND  shf.date_created    < IFNULL((SELECT min(shfref.date_created)
		                                                                FROM  status_history          AS shfref
		                                                                WHERE shfref.application_id =  shv.application_id
		                                                                 AND  shfref.company_id     =  shv.company_id
		                                                                 AND  shfref.application_status_id = {$this->reverified}
		                                                                 AND  shfref.date_created   > shv.date_created
		                                                              ), '2099-12-31 23:59:59'
		                                                             )
		                            AND  shf.date_created    > shv.date_created
								 ) as num_funded,
								 (SELECT count(distinct 'X')
		                           FROM  status_history          AS shw
		                           WHERE shw.company_id      =  shv.company_id
		                            AND  shw.application_id  =  shv.application_id
		                            AND  shw.application_status_id = {$this->withdrawn}
		                            AND  shw.date_created    < IFNULL((SELECT min(shwref.date_created)
		                                                                FROM  status_history          AS shwref
		                                                                WHERE shwref.application_id =  shv.application_id
		                                                                 AND  shwref.company_id     =  shv.company_id
		                                                                 AND  shwref.application_status_id = {$this->reverified}
		                                                                 AND  shwref.date_created   > shv.date_created
		                                                              ), '2099-12-31 23:59:59'
		                                                             )
		                            AND  shw.date_created    > shv.date_created
		                         ) as num_withdrawn,
		                         (SELECT count(distinct 'X')
		                           FROM  status_history          AS shde
		                           WHERE shde.company_id     = shv.company_id
		                            AND  shde.application_id = shv.application_id
		                            AND  shde.application_status_id	= {$this->denied}
		                            AND  shde.date_created   < IFNULL((SELECT min(shderef.date_created)
		                                                                FROM  status_history          AS shderef
		                                                                WHERE shderef.application_id =  shv.application_id
		                                                                 AND  shderef.company_id     =  shv.company_id
		                                                                 AND  shderef.application_status_id = {$this->reverified}
		                                                                 AND  shderef.date_created   > shv.date_created
		                                                              ), '2099-12-31 23:59:59'
		                                                             )
		                            AND  shde.date_created   > shv.date_created
		                         ) as num_denied,
		                         (SELECT count(distinct 'X')
		                           FROM  status_history          AS shs
		                           WHERE shs.company_id      =  shv.company_id
		                            AND  shs.application_id  =  shv.application_id
		                            AND  shs.application_status_id = {$this->reverified}
		                            AND  shs.date_created    <= IFNULL((SELECT min(shsref.date_created)
		                                                                 FROM  status_history          AS shsref
		                                                                 WHERE shsref.application_id =  shv.application_id
		                                                                  AND  shsref.company_id     =  shv.company_id
		                                                                  AND  shsref.application_status_id = {$this->reverified}
		                                                                  AND  shsref.date_created   > shv.date_created
		                                                               ), '1900-01-01 00:00:00'
		                                                              )
		                            AND  shs.date_created > shv.date_created
		                         ) as num_sendback
		                   FROM  status_history AS shv
		                   WHERE shv.date_created BETWEEN '{$timestamp_start}'
		                                              AND '{$timestamp_end}'
		                    AND  shv.agent_id       >  0
		                    AND  shv.application_status_id = {$this->approved}
		                    AND  shv.company_id IN ({$company_list})
		                 ) temp
		           LEFT OUTER JOIN agent   a  ON temp.agent_id   = a.agent_id
		           LEFT OUTER JOIN company co ON temp.company_id = co.company_id
		           WHERE lt.company_id =  co.company_id
		            AND  lt.name_short IN ({$loan_type_list})
					AND  a.system_id   =  {$this->system_id}
		           GROUP BY co.name_short, lower(a.name_last), lower(a.name_first), temp.agent_id
		           ORDER BY co.name_short, lower(a.name_last), lower(a.name_first), temp.agent_id
		           LIMIT {$this->max_display_rows}";

		//echo "\n<br><pre>" . print_r($query,true) . "</pre><br>\n";

		$db = ECash_Config::getMasterDbConnection();

		//$result = $this->db->Query($query);
		$result = $db->Query($query);

		if( $result->rowCount() == $this->max_display_rows )
			return false;

		while( $row = $result->fetch(PDO::FETCH_ASSOC) )
		{
			// Need data as array( Company => array( 'colname' => 'data' ) )
			//   Do all data formatting here
			$company_name = $row['company_name'];

			$row['agent_name'] = ucwords($row['agent_name']);
			$performance_data[$company_name][] = $row;
		}

		$this->timer->stopTimer( self::$TIMER_NAME );

		return $performance_data;
	}
}

?>
