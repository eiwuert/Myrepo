<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision: 17164 $
 */

require_once(SERVER_MODULE_DIR."/reporting/status_history_report.class.php");
class Customer_Report extends Report
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
			$this->search_query = new Customer_Status_History_Report_Query($this->server);
	
			$data = new stdClass();
	
			// Save the report criteria
			$data->search_criteria = array(
			  'start_date_MM'   => $this->request->start_date_month,
			  'start_date_DD'   => $this->request->start_date_day,
			  'start_date_YYYY' => $this->request->start_date_year,
			  'company_id'      => $this->request->company_id,
			  'status_type'     => $this->request->status_type
			);
	
			$_SESSION['reports']['status_history']['report_data'] = new stdClass();
			$_SESSION['reports']['status_history']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['status_history']['url_data'] = array('name' => 'Status History', 'link' => '/?module=reporting&mode=status_history');
	
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
	
			$start_date_YYYYMMDD = 10000 * $start_date_YYYY	+ 100 * $start_date_MM + $start_date_DD;

			$data->search_results = $this->search_query->Fetch_Status_History_Data( $start_date_YYYYMMDD,
				                                                                              $this->request->status_type,
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
		if(!empty($data->search_results) && count($data->search_results) > $this->max_display_rows)
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
		$_SESSION['reports']['status_history']['report_data'] = $data;
	}
}

class Customer_Status_History_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Status History Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	/**
	 * Fetches data for the Manual Payment Report
	 * @param   string $start_date YYYYmmdd
	 * @param   string $end_date   YYYYmmdd
	 * @param   string $loan_type  standard || card
	 * @param   mixed  $company_id array of company_ids or 1 company_id
	 * @returns array
	 */
	public function Fetch_Status_History_Data($start_date, $status_type, $company_id)
	{
		$this->timer->startTimer(self::$TIMER_NAME);

		// Yes, we're only searching within a single day
		$end_date   = "{$start_date}235959";
		$start_date = "{$start_date}000000";

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

		$status_map = $this->Get_Status_Names();
		$status =  $status_type;
		$default_agent = ECash_Config::getInstance()->DEFAULT_AGENT_ID;
//		switch ($status_type)
//		{
//			case 'confirmed':
//				$status = app_stat_id_from_chain($this->mysqli, array('queued','verification','applicant','*root') );
//				$status .= "," . app_stat_id_from_chain($this->mysqli, array('dequeued','verification','applicant','*root') );
//				break;
//			case 'confirmed_followup':
//				$status = app_stat_id_from_chain($this->mysqli, array('follow_up','verification','applicant','*root') );
//				break;
//			case 'approved':
//				$status = app_stat_id_from_chain($this->mysqli, array('queued','underwriting','applicant','*root') );
//				$status .= "," . app_stat_id_from_chain($this->mysqli, array('dequeued','underwriting','applicant','*root') );
//				break;
//			case 'approved_followup':
//				$status = app_stat_id_from_chain($this->mysqli, array('follow_up','underwriting','applicant','*root') );
//				break;
//			case 'pre_fund':
//				$status = app_stat_id_from_chain($this->mysqli, array('approved','servicing','customer','*root') );
//				break;
//			case 'active':
//				$status = app_stat_id_from_chain($this->mysqli, array('active','servicing','customer','*root') );
//				break;
//			case 'funding_failed':
//				$status = app_stat_id_from_chain($this->mysqli, array('funding_failed','servicing','customer','*root') );
//				break;
//			case 'servicing_hold':
//				$status = app_stat_id_from_chain($this->mysqli, array('hold','servicing','customer','*root') );
//				break;
//			case 'past_due':
//				$status = app_stat_id_from_chain($this->mysqli, array('past_due','servicing','customer','*root') );
//				break;
//			case 'cashline':
//				$status = app_stat_id_from_chain($this->mysqli, array('cashline','customer','*root') );
//				break;
//			case 'second_tier_pending':
//				$status = app_stat_id_from_chain($this->mysqli, array('pending','external_collections','*root') );
//				break;
//			case 'second_tier_sent':
//				$status = app_stat_id_from_chain($this->mysqli, array('sent','external_collections','*root') );
//				break;
//			case 'made_arrangements':
//				$status = app_stat_id_from_chain($this->mysqli, array('current','arrangements','collections','customer','*root') );
//				break;
//			case 'arrangements_failed':
//				$status = app_stat_id_from_chain($this->mysqli, array('arrangements_failed','arrangements','collections','customer','*root') );
//				break;
//			case 'arrangements_hold':
//				$status = app_stat_id_from_chain($this->mysqli, array('hold','arrangements','collections','customer','*root') );
//				break;
//			case 'bankruptcy_notified':
//				$status = app_stat_id_from_chain($this->mysqli, array('unverified','bankruptcy','collections','customer','*root') );
//				break;
//			case 'bankruptcy_verified':
//				$status = app_stat_id_from_chain($this->mysqli, array('verified','bankruptcy','collections','customer','*root') );
//				break;
//			case 'collections_contact':
//				$status = app_stat_id_from_chain($this->mysqli, array('queued','contact','collections','customer','*root') );
//				$status .= "," . app_stat_id_from_chain($this->mysqli, array('dequeued','contact','collections','customer','*root') );
//				break;
//			case 'qc_ready':
//				$status = app_stat_id_from_chain($this->mysqli, array('ready','quickcheck','collections','customer','*root') );
//				break;
//			case 'qc_sent':
//				$status = app_stat_id_from_chain($this->mysqli, array('sent','quickcheck','collections','customer','*root') );
//				break;
//			case 'qc_returned':
//				$status = app_stat_id_from_chain($this->mysqli, array('return','quickcheck','collections','customer','*root') );
//				break;
//			case 'collections_new':
//				$status = app_stat_id_from_chain($this->mysqli, array('new','collections','customer','*root') );
//				break;
//			case 'collections_dequeued':
//				$status = $this->Get_Status_Id( array('indef_dequeue','collections','customer','*root') );
//				break;
//			case 'collections_followup':
//				$status = app_stat_id_from_chain($this->mysqli, array('follow_up','contact','collections','customer','*root') );
//				break;
//			default:
//				die ("Error: Invalid status selected!");
//				break;
//		}

		$query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
            SELECT  sh.application_id,
                    date_format(sh.date_created, '%H:%i:%s') as date_created,
                    date_format(sh.date_created, '%Y-%m-%d %H:%i:%s')  as stamp,
                    sh.application_status_id AS application_status_id,
                    sh.status_history_id,
                    CONCAT(ag.name_last, ', ', ag.name_first) as agent_name,
                    sh.agent_id,
                    c.name_short as company_name,
                    sh.company_id
            FROM    status_history sh 
			JOIN
					company c ON (c.company_id = sh.company_id)
			LEFT JOIN
					agent ag ON (ag.agent_id = IF(sh.agent_id,sh.agent_id,{$default_agent}))
            WHERE   
				sh.date_created  BETWEEN {$start_date} AND {$end_date}
            AND		
				sh.application_status_id IN ({$status})
            AND     
				sh.company_id IN ({$company_list})
            ORDER BY 
				status_history_id ASC";

		//$this->log->Write($query);

		$data = array();
		$fetch_result = $this->db->Query($query);

		while($row = $fetch_result->fetch(PDO::FETCH_ASSOC))
		{
			$co = $row['company_name'];
			unset($row['company_name']);

			$this->Get_Module_Mode($row, $row['company_id']);
			$row['previous_status_id'] = $this->Get_Previous_Status($row['application_id'], $row['stamp'], $row['status_history_id']);

			// Status is a string, comma delimited.
			if((in_array($row['application_status_id'], explode(',', $status))) || (in_array($row['previous_status_id'], explode(',', $status))))
			{
				if(! empty($row['previous_status_id'])) {
					$row['previous_status'] = $status_map[$row['previous_status_id']];
				} else {
					$row['previous_status'] = "";
				}
				$row['new_status'] = $status_map[$row['application_status_id']];

				$data[$co][] = $row;
			}
		}

		$this->timer->stopTimer(self::$TIMER_NAME);

		return $data;
	}

	function Get_Previous_Status($application_id, $timestamp, $prev_status_history_id)
	{
		$query = "
		-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
		SELECT DISTINCT status_history_id, application_id, application_status_id
		FROM status_history
		WHERE application_id = $application_id
		AND	date_created <= '$timestamp'
		AND status_history_id < $prev_status_history_id
		ORDER BY status_history_id DESC LIMIT 1";

		$result = $this->db->Query($query);

		if($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$previous_status = $row->application_status_id;
			return $previous_status;
		}

		return false;
	}
}

?>
