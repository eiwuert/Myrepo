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
			$this->search_query = new Customer_ACH_Batch_Report_Query($this->server);
	
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
	
			$_SESSION['reports']['achbatch']['report_data'] = new stdClass();
			$_SESSION['reports']['achbatch']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['achbatch']['url_data'] = array('name' => 'ACH Batch', 'link' => '/?module=reporting&mode=achbatch');
	
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
											    $this->request->loan_type,
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
		$_SESSION['reports']['achbatch']['report_data'] = $data;
	}
}

class Customer_ACH_Batch_Report_Query extends Base_Report_Query
{
        private static $TIMER_NAME = "ACh Batch Report Query";
        private $system_id;

        public function __construct(Server $server)
        {
                parent::__construct($server);

                $this->system_id = $server->system_id;

        }

        public function Fetch_ACH_Data($date_start, $date_end, $loan_type, $company_id)
        {
                $this->timer->startTimer( self::$TIMER_NAME );

                $company_list = $this->Format_Company_IDs($company_id);
                $loan_type_list = $this->Get_Loan_Type_List($loan_type);

				if ($loan_type == 'all')
					$loan_type_sql = "";
				else
					$loan_type_sql = "AND lt.name_short = '{$loan_type}'\n";


                // Start and end dates must be passed as strings with format YYYYMMDD
                $timestamp_start = $date_start . '000000';
                $timestamp_end   = $date_end   . '235959';

		
	      $query = "
	      		SELECT  a.transaction_date          AS report_date,
		        a.num_debit_attempted             AS debit_num_attempted,
		        a.debit_amount                    AS debit_total_attempted,
		        a.num_credit_attempted             AS credit_num_attempted,
		        a.credit_amount                    AS credit_total_attempted,
		        IFNULL(b.num_returned, 0)              AS num_returns_actual_day,
		        IFNULL(b.amount_returned, 0)           AS total_returns_actual_day,
		        IFNULL(b.num_unauthorized, 0)             AS num_unauthorized,
		        IFNULL(b.total_unauthorized, 0)           AS total_unauthorized,
		        (a.num_debit_attempted + a.num_credit_attempted)   AS net_attempted,
		        (a.debit_amount - a.credit_amount - IFNULL(b.amount_returned, 0))   AS net_after_returns,
		        (a.debit_amount - a.credit_amount)         AS net_total

		FROM
				( -- Debits made during date range
		    SELECT
		        DATE_FORMAT(ach.date_created, '%m/%d/%Y')           AS transaction_date,
		        SUM(IF(ach_type = 'credit', 1, 0))          AS num_credit_attempted,
		        SUM(IF(ach_type = 'debit', 1, 0))          AS num_debit_attempted,
		        SUM(IF(ach_type = 'credit', ach.amount, 0)) AS credit_amount,
		        SUM(IF(ach_type = 'debit', ach.amount, 0)) AS debit_amount
		    FROM ach
			JOIN application app ON (app.application_id = ach.application_id)
			JOIN loan_type lt ON (lt.loan_type_id = app.loan_type_id)
		    JOIN
		    (
		        SELECT tr.ach_id,
		               es.context
		        FROM transaction_register AS tr
		        JOIN event_schedule AS es on es.event_schedule_id = tr.event_schedule_id
		        GROUP BY tr.ach_id
		    ) AS tr1 ON tr1.ach_id = ach.ach_id
		    WHERE 1 = 1
			{$loan_type_sql}
		    AND ach.date_created BETWEEN '$timestamp_start' AND '$timestamp_end'
		    AND ach.company_id in $company_list
		    GROUP BY transaction_date
		) AS a
		LEFT JOIN
		( -- Returns received during date range
		    SELECT
		        DATE_FORMAT(ar.date_created, '%m/%d/%Y')             AS return_date,
		        SUM(IF(tr1.context != 'arrangement', 1, 1))          AS num_returned,
		        SUM(ach.amount) AS amount_returned,
				SUM(IF(ac.name_short = 'R10', 1, 0)) as num_unauthorized,
				SUM(IF(ac.name_short = 'R10', ach.amount, 0)) as total_unauthorized
			    FROM ach_report AS ar
		    JOIN ach ON ach.ach_report_id = ar.ach_report_id
		    JOIN
		    (
		        SELECT tr.ach_id,
		               es.context
		        FROM transaction_register AS tr
		        JOIN event_schedule AS es on es.event_schedule_id = tr.event_schedule_id
		        GROUP BY tr.ach_id
		    ) AS tr1 ON tr1.ach_id = ach.ach_id
		    JOIN ach_return_code ac using (ach_return_code_id)
		    WHERE ar.date_created BETWEEN '$timestamp_start' AND '$timestamp_end'
		    AND ach.date_created  BETWEEN '$timestamp_start' AND '$timestamp_end'
		    AND ach.ach_type = 'debit'
		    AND ach.ach_status = 'returned'
		    AND ach.company_id in $company_list
		    GROUP BY return_date
		) AS b ON a.transaction_date = b.return_date

                       
                ";

                $st = $this->db->query($query);

                $data = array();

		while($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			$data['company'][] = $row;
		}

                $this->timer->stopTimer( self::$TIMER_NAME );

                return $data;
        }

}

?>
