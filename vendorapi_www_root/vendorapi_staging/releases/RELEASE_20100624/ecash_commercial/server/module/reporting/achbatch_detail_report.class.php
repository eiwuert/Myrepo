<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

require_once(SERVER_MODULE_DIR."/reporting/report_generic.class.php");
require_once( SERVER_CODE_DIR . "base_report_query.class.php" );

class Report extends Report_Generic
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
			$this->search_query = new ACH_Batch_Detail_Query($this->server);
	
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
			);
	
			$_SESSION['reports']['achbatch_detail']['report_data'] = new stdClass();
			$_SESSION['reports']['achbatch_detail']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['achbatch_detail']['url_data'] = array('name' => 'ACH Batch', 'link' => '/?module=reporting&mode=achbatch_detail');
	
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
	
			$data->search_results = $this->search_query->Fetch_ACH_Data($start_date_YYYYMMDD,
											    $end_date_YYYYMMDD,
											    $this->request->company_id);
		}
		catch (Exception $e)
		{
			$data->search_message = $e->getMessage();
//			$data->search_message = "Unable to execute report. Reporting server may be unavailable.";
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}

		// we need to prevent client from displaying too large of a result set, otherwise
		// the PHP memory limit could be exceeded;
		if( $data->search_results === false )
		{
			$data->search_message = $this->max_display_rows_error;
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}

		// Sort if necessary
		$data = $this->Sort_Data($data);

		ECash::getTransport()->Add_Levels("report_results");
		ECash::getTransport()->Set_Data($data);
		$_SESSION['reports']['achbatch_detail']['report_data'] = $data;
	}
}

class ACH_Batch_Detail_Query extends Base_Report_Query
{
	private static $TIMER_NAME = "ACH Batch Detail Query";
	private $system_id;

	public function __construct(Server $server)
	{
		parent::__construct($server);

		$this->system_id = $server->system_id;

	}

	public function Fetch_ACH_Data($date_start, $date_end, $company_id)
	{
		$this->timer->startTimer( self::$TIMER_NAME );

		$company_list = $this->Format_Company_IDs($company_id);

		// Start and end dates must be passed as strings with format YYYYMMDD
		$timestamp_start = $date_start . '000000';
		$timestamp_end   = $date_end   . '235959';

		
		$query = "
				SELECT
					ach.ach_batch_id,
					upper(co.name_short) AS company_name,
			        DATE_FORMAT(ach.ach_date, '%m/%d/%Y') AS ach_date,
					ach.company_id,
					ach.application_id,
					app.name_first,
					app.name_last,
					app.bank_aba,
					right(app.bank_account, 4) as bank_last4,
					ach.ach_type,
					ach.ach_status,
					ach.amount,
					rc.name_short as return_code,
					DATE_FORMAT(r.date_created, '%m/%d/%Y')           AS return_date,
					app.application_status_id
				FROM ach
				JOIN application app ON (app.application_id = ach.application_id)
				JOIN company co on (co.company_id = app.company_id)
			    JOIN
		    	(
		        	SELECT tr.ach_id,
		               es.context
			        FROM transaction_register AS tr
			        JOIN event_schedule AS es on es.event_schedule_id = tr.event_schedule_id
			        GROUP BY tr.ach_id
		    	) AS tr1 ON tr1.ach_id = ach.ach_id
				LEFT JOIN ach_return_code rc on (rc.ach_return_code_id = ach.ach_return_code_id)
				LEFT JOIN ach_report r on (r.ach_report_id = ach.ach_report_id)
			    WHERE
					ach.date_created BETWEEN '$timestamp_start' AND '$timestamp_end'
				AND ach.company_id in $company_list	
                ";

		$st = $this->db->query($query);

		$data = array();

		while($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			$company_name = $row['company_name'];
			unset($row['company_name']);

			$this->Get_Module_Mode($row);

			$data[$company_name][] = $row;
		}

		$this->timer->stopTimer( self::$TIMER_NAME );

		return $data;
	}

}

?>
