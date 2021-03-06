<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

require_once(SERVER_MODULE_DIR . "reporting/report_generic.class.php");
require_once(SERVER_CODE_DIR   . "payments_due_report_query.class.php");

ini_set("memory_limit",-1);

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
			$this->search_query = new Payments_Due_Report_Query($this->server);
	
			$data = new stdClass();
	
			// Save the report criteria
			$data->search_criteria = array(
			  'start_date_MM'   => $this->request->start_date_month,
			  'start_date_DD'   => $this->request->start_date_day,
			  'start_date_YYYY' => $this->request->start_date_year,
			  'end_date_MM'     => $this->request->end_date_month,
			  'end_date_DD'		=> $this->request->end_date_day,
			  'end_date_YYYY'	=> $this->request->end_date_year,
			  'loan_type'		=> $this->request->loan_type,
			  'company_id'		=> $this->request->company_id
			);
	
			$_SESSION['reports']['payments_due']['report_data'] = new stdClass();
			$_SESSION['reports']['payments_due']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['payments_due']['url_data'] = array('name' => 'ACH Payments Due', 'link' => '/?module=reporting&mode=payments_due');
	
			if( ! checkdate($data->search_criteria['start_date_MM'],
			                $data->search_criteria['start_date_DD'],
			                $data->search_criteria['start_date_YYYY']) )
			{
				$data->search_message = "Date invalid or not specified.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}

			if( ! checkdate($data->search_criteria['end_date_MM'],
							$data->search_criteria['end_date_DD'],
							$data->search_criteria['end_date_YYYY']) )
			{
				$data->search_message = "Date invalid or not specified.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}
	
			$start_date_YYYYMMDD = 10000 * $data->search_criteria['start_date_YYYY'] +
			                       100   * $data->search_criteria['start_date_MM'] +
			                               $data->search_criteria['start_date_DD'];

			$end_date_YYYYMMDD	 = 10000 * $data->search_criteria['end_date_YYYY'] +
								   100   * $data->search_criteria['end_date_MM']   +
										   $data->search_criteria['end_date_DD'];


			$data->search_results = $this->search_query->Fetch_Payments_Due_Data($start_date_YYYYMMDD,
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
		/* Can't limit the report, this report will probably have > 10000 rows, must have it
		if(!empty($data->search_results) && count($data->search_results) > $this->max_display_rows)
		{
			$data->search_message = "Your report would have more than " . $this->max_display_rows . " lines to display. Please narrow the date range.";
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
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
		$_SESSION['reports']['payments_due']['report_data'] = $data;

	}
}

?>
