<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision: 17164 $
 */
require_once(SERVER_MODULE_DIR."/reporting/inactive_paid_status_report.class.php");
require_once( SQL_LIB_DIR . "fetch_status_map.func.php");

class Customer_Report extends Report
{
	private $search_query;

	public function Generate_Report()
	{

		try
		{		
			$this->search_query = new Customer_Reactivation_Marketing_Report_Query($this->server);

			// Generate_Report() expects the following from the request form:
			//
			// criteria start_date YYYYMMDD
			// criteria end_date   YYYYMMDD
			// company_id
			//

			$data = new stdClass();

			// Save the report criteria
			$data->search_criteria = array(
					'start_date_MM'   => $this->request->start_date_month,
					'start_date_DD'   => $this->request->start_date_day,
					'start_date_YYYY' => $this->request->start_date_year,
					'end_date_MM'     => $this->request->end_date_month,
					'end_date_DD'     => $this->request->end_date_day,
					'end_date_YYYY'   => $this->request->end_date_year,
					'company_id'      => $this->request->company_id
					);

			$_SESSION['reports']['reactivation_marketing']['report_data'] = new stdClass();
			$_SESSION['reports']['reactivation_marketing']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['reactivation_marketing']['url_data'] = array('name' => 'Reactivation Marketing', 'link' => '/?module=reporting&mode=reactivation_marketing');

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
				$this->server->transport->Set_Data($data);
				$this->server->transport->Add_Levels("message");
				return;
			}

			$data->search_results = $this->search_query->Fetch_Reactivation_Marketing_Data( $start_date_YYYYMMDD,
					$end_date_YYYYMMDD,
					$this->request->company_id
					);
		}
		catch (Exception $e)
		{
			echo '<pre>' . print_r($e,true);
			//$data->search_message = "Unable to execute report. Reporting server may be unavailable.";
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}

		// we need to prevent client from displaying too large of a result set, otherwise
		// the PHP memory limit could be exceeded;
		$num_results = 0;
		foreach ($data->search_results as $company => $results)
		{
			$num_results += count($results);

			if ($num_results >= $this->max_display_rows)
			{
				$data->search_message = "Your report would have more than " . $this->max_display_rows . " lines to display. Please narrow the date range.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}			
		}

		// Sort if necessary
		$data = $this->Sort_Data($data);

		ECash::getTransport()->Add_Levels("report_results");
		ECash::getTransport()->Set_Data($data);
		$_SESSION['reports']['reactivation_marketing']['report_data'] = $data;
	}
}

class Customer_Reactivation_Marketing_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Reactivation Marketing Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	/**
	 * Fetches data for the Inactive Paid Status Report
	 * @param   string $start_date YYYYmmdd
	 * @param   string $end_date   YYYYmmdd
	 * @param   int  $company_id company_id
	 * @returns array
	 */
	public function Fetch_Reactivation_Marketing_Data($start_date, $end_date, $company_id)
	{
		$this->timer->startTimer(self::$TIMER_NAME);

		$FILE = __FILE__;
		$METHOD = __METHOD__;
		$LINE = __LINE__;

		$disallowed_statuses_array = array(
				"pending::external_collections::*root",
				"recovered::external_collections::*root",
				"sent::external_collections::*root",
				"indef_dequeue::collections::customer::*root",
				"new::collections::customer::*root",
				"active::servicing::customer::*root",
				"approved::servicing::customer::*root",
				"past_due::servicing::customer::*root",
				"arrangements_failed::arrangements::collections::customer::*root",
				"current::arrangements::collections::customer::*root",
				"hold::arrangements::collections::customer::*root",
				"unverified::bankruptcy::collections::customer::*root",
				"verified::bankruptcy::collections::customer::*root",
				"dequeued::contact::collections::customer::*root",
				"follow_up::contact::collections::customer::*root",
				"queued::contact::collections::customer::*root",
				"ready::quickcheck::collections::customer::*root",
				"sent::quickcheck::collections::customer::*root",
				);

		$status_map = Fetch_Status_Map();
		$inactive_paid_status = Search_Status_Map('paid::customer::*root', $status_map);

		foreach ($disallowed_statuses_array as $status) {
			$disallowed_status_ids_array[] = Search_Status_Map($status, $status_map);
		}

		$disallowed_status_ids = implode (',', $disallowed_status_ids_array);

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


		$query = <<<END_SQL
			-- eCash 3.0, File: $FILE , Method: $METHOD, Line: $LINE
			SELECT
			UPPER(co.name_short) as name_short, 
			a.application_status_id, 
			a.company_id,
			co.name_short AS company_name,
			a.application_id, 
			a.name_last, 
			a.name_first, 
			a.ssn,
			a.phone_home AS home_phone,
			a.phone_cell AS cell_phone,
			a.phone_work AS work_phone,
			a.email AS email_address,
			a.street AS address,
			a.city  AS city,
			a.state AS state,
			a.zip_code AS zip_code,
			a.date_application_status_set, 
			a.fund_actual,
			IF(exists(
				SELECT application_id
				FROM
					application a2
				WHERE
					 a2.customer_id = a.customer_id 
				and  application_id <> a.application_id 
				and a2.date_created > a.date_created
				), 'R', '') AS reactivated,
			IF(dl.ssn is null,'','Y') as do_not_loan,
			IF(dnm.table_row_id is null, '', 'Y') as do_not_market
				FROM
				application a
				LEFT JOIN application a2 USING (ssn)
				LEFT JOIN company co ON a.company_id = co.company_id
				LEFT JOIN (select * from do_not_loan_flag where active_status = 'active') dl on a.ssn = dl.ssn
				LEFT JOIN (select * from application_field join application_field_attribute using (application_field_attribute_id) where field_name = 'do_not_market' and table_name = 'application') dnm on dnm.table_row_id = a.application_id
				WHERE
				a.application_status_id = $inactive_paid_status
				AND a.company_id IN ({$company_list})
				AND a.date_application_status_set BETWEEN {$start_date}000000 AND {$end_date}235959
				GROUP BY a.ssn
				ORDER BY a.date_application_status_set
				LIMIT {$this->max_display_rows}
END_SQL;

	//	$db = ECash_Config::getMasterDbConnection();
		$data = array();
		$fetch_result = $this->db->query($query);
		$sub_query = "select * from application_field join application_field_attribute using (application_field_attribute_id) where table_row_id = ? and table_name = 'application'";
		$sub_query_result = $this->db->prepare($sub_query); 		
		while ($row = $fetch_result->fetch(PDO::FETCH_ASSOC))
		{
			$co = strtoupper($row['name_short']);

			$this->Get_Module_Mode($row, $row['company_id']);
			//Not displaying numbers that are flagged as do not contact, doing it here because it is faster than having three sub queries in the main query [#16638]
			$sub_query_result->execute(array($row['application_id'])) ;
			while ($sub_row = $sub_query_result->fetch(PDO::FETCH_ASSOC))
			{
				
				if($sub_row['field_name'] == 'do_not_contact')
				{
					switch($sub_row['column_name'])
					{
						case 'phone_home':
						$row['home_phone'] = '';
						break;
						case 'phone_work':
						$row['work_phone'] = '';
						break;
						case 'phone_cell':
						$row['cell_phone'] = '';
						break;
					}
				}
			}
			$data[$co][] = $row;
		}
		$this->timer->stopTimer(self::$TIMER_NAME);

		return $data;
	}
}

?>