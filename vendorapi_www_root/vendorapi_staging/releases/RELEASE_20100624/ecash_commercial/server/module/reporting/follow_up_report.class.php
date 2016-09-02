<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

require_once("report_generic.class.php");

class Report extends Report_Generic
{
	private $search_query;

	public function __construct(Server $server, $request, $module_name, $report_name)
	{
		parent::__construct($server, $request, $module_name, $report_name);

	}

	// Put the data to be displayed in the Transport Class
	public function Generate_Report()
	{
		
		try 
		{		
			$this->search_query = new Follow_Up_Report_Query($this->server);		
			$data = new stdClass();
	
			$data->search_criteria = array(
			  'start_date_MM'   => $this->request->start_date_month,
			  'start_date_DD'   => $this->request->start_date_day,
			  'start_date_YYYY' => $this->request->start_date_year,
			  'end_date_MM'     => $this->request->end_date_month,
			  'end_date_DD'     => $this->request->end_date_day,
			  'end_date_YYYY'   => $this->request->end_date_year,
			  'company_id' 		=> $this->request->company_id,
			  'loan_type'  		=> $this->request->loan_type,
			  'follow_up_queue' => $this->request->follow_up_queue
			);
	
			$_SESSION['reports']['follow_up']['report_data'] = new stdClass();
			$_SESSION['reports']['follow_up']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['follow_up']['url_data'] = array('name' => 'Follow Up', 'link' => '/?module=reporting&mode=follow_up');
	
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
	

			$data->search_results = $this->search_query->Fetch_Agent_Comment_Data($start_date_YYYYMMDD,
										      $end_date_YYYYMMDD,
										      $this->request->company_id,
										      $this->request->loan_type,
										      $this->request->follow_up_queue);
		}
		catch (Exception $e)
		{
			$data->search_message = "Unable to execute report. Reporting server may be unavailable.";
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}

		ECash::getTransport()->Add_Levels("report_results");

		// Sort if necessary
		$data = $this->Sort_Data($data);

		ECash::getTransport()->Set_Data($data);
		$_SESSION['reports']['follow_up']['report_data'] = $data;
	}
}

?>
