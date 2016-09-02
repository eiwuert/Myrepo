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
			$this->search_query = new Agent_Internal_Recovery_Report_Query($this->server);

			$data = new stdClass();

			// Save the report criteria
			$data->search_criteria = array(
			  'start_date_MM'   => $this->request->start_date_month,
			  'start_date_DD'   => $this->request->start_date_day,
			  'start_date_YYYY' => $this->request->start_date_year,
			  'end_date_MM'     => $this->request->end_date_month,
			  'end_date_DD'     => $this->request->end_date_day,
			  'end_date_YYYY'   => $this->request->end_date_year,
			  'agent_id'		=> $this->request->agent_id,
			  'company_id'      => $this->request->company_id,
			  'loan_type'       => $this->request->loan_type
			);

			$_SESSION['reports']['agent_internal_recovery']['report_data'] = new stdClass();
			$_SESSION['reports']['agent_internal_recovery']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['agent_internal_recovery']['url_data'] = array('name' => 'Agent Internal Recovery', 'link' => '/?module=reporting&mode=reporting&mode=agent_internal_recovery');

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

			$data->search_results = $this->search_query->Fetch_Agent_Internal_Recovery_Data(
					$start_date_YYYYMMDD,
												 $end_date_YYYYMMDD,
												 $this->request->agent_id,
					$this->request->company_id,
					$this->request->loan_type);
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
        $num_results = 0;
        foreach ($data->search_results as $company => $results)
        {
            $num_results += count($results);

            if ($num_results > $this->max_display_rows)
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
		$_SESSION['reports']['agent_internal_recovery']['report_data'] = $data;
	}
}

class Agent_Internal_Recovery_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Payment Type Success Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	public function Fetch_Agent_Internal_Recovery_Data($date_start, $date_end, $agent_id, $company_id, $loan_type)
	{
		// If they want an affiliated agent
		$agents_selected = FALSE;
		$unassigned_selected = FALSE;
		if(!is_array($agent_id) || 0 == count($agent_id))
		{
			$agent_id = array(0);
		}
		foreach($agent_id as $id)
		{
			if(0 == $id)
			{
				$unassigned_selected = TRUE;
			}
			else
			{
				$agents_selected = TRUE;
			}
		}

		// Build a SQL list
		$agent_id_list = join(",",$agent_id);

		$max_report_retrieval_rows = $this->max_display_rows + 1;

		$this->timer->startTimer(self::$TIMER_NAME);

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

        if ($loan_type == 'all')
            $loan_type_sql = "";
        else
            $loan_type_sql = "AND lt.name_short = '{$loan_type}'\n";


		// Start and end dates must be passed as strings with format YYYYMMDD
		$timestamp_start = $date_start . '000000';
		$timestamp_end	 = $date_end   . '235959';
		$data = array();

		$query = "
			SELECT	
			UPPER(co.name_short) AS company_name,
			co.company_id AS company_id,
			CONCAT(
			    ag.name_first ,
			    ' ' ,
			    ag.name_last
			    ) AS 'agent',
			SUM(IF(tr.transaction_status IN ('complete','failed'), 1,0)) AS Total_Arrangments,
			SUM(IF(tr.transaction_status IN ('complete','failed'), tr.amount,0)) AS Total_Amount,
			SUM(IF(tr.transaction_status = 'failed', 1,0)) AS Failed_Arrangments,
			SUM(IF(tr.transaction_status = 'failed', tr.amount,0)) AS Failed_Amount,
			SUM(IF(tr.transaction_status = 'complete', 1,0)) AS Completed_Arrangments,
			SUM(IF(tr.transaction_status = 'complete', tr.amount,0)) AS Completed_Amount
			FROM 
				agent_affiliation_event_schedule as afes
			LEFT JOIN
				agent_affiliation as af using (agent_affiliation_id)
			JOIN 
				transaction_register as tr using (event_schedule_id)
			JOIN 
				company as co on (af.company_id = co.company_id)
			JOIN
				agent as ag using (agent_id)
			JOIN
				application app ON (app.application_id = tr.application_id)
			JOIN
				loan_type lt ON (lt.loan_type_id = app.loan_type_id)
			WHERE 
				tr.date_effective BETWEEN '{$timestamp_start}' AND '{$timestamp_end}'
			AND 
				co.company_id IN ({$company_list})
			AND 
				ag.agent_id IN ({$agent_id_list})
			{$loan_type_sql}
			GROUP BY 
				company_name,agent
		";
	
		$st = $this->db->query($query);

		while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			// Seriously We can count this row if it has no Arrangemtns at all
			if($row['Total_Arrangments'] > 0)
			{
				$company_name = $row['company_name'];
			//	unset($row['company_name']);
				$row['Paid_Perc'] = ($row['Completed_Arrangments'] / $row['Total_Arrangments']) * 100;
				$row['Paid_Amount_Perc'] = ($row['Completed_Amount'] / $row['Total_Amount']) * 100;
				$row['Failed_Perc'] = ($row['Failed_Arrangments'] / $row['Total_Arrangments']) * 100;
				$row['Failed_Amount_Perc'] = ($row['Failed_Amount'] / $row['Total_Amount']) * 100;
				$data[$company_name][] = $row;
			}
		}

		$this->timer->stopTimer(self::$TIMER_NAME);
		return $data;
	}
}

?>
