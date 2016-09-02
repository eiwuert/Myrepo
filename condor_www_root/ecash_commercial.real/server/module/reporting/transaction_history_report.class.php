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
			$this->search_query = new Transaction_History_Report_Query($this->server);

			$data = new stdClass();

			// Save the report criteria
			$data->search_criteria = array(
			  'specific_date_MM'   => $this->request->specific_date_month,
			  'specific_date_DD'   => $this->request->specific_date_day,
			  'specific_date_YYYY' => $this->request->specific_date_year,
			  'company_id'      => $this->request->company_id,
			  'loan_type'       => $this->request->loan_type
			);

			$_SESSION['reports']['transaction_history']['report_data'] = new stdClass();
			$_SESSION['reports']['transaction_history']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['transaction_history']['url_data'] = array('name' => 'Transaction History', 'link' => '/?module=reporting&mode=transaction_history');

			// Start date
			$start_date_YYYY = $this->request->specific_date_year;
			$start_date_MM	 = $this->request->specific_date_month;
			$start_date_DD	 = $this->request->specific_date_day;
			if(!checkdate($start_date_MM, $start_date_DD, $start_date_YYYY))
			{
				//return with no data
				$data->search_message = "Start Date invalid or not specified.";
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels("message");
				return;
			}

			$start_date_YYYYMMDD = 10000 * $start_date_YYYY	+ 100 * $start_date_MM + $start_date_DD;

			$data->search_results = $this->search_query->Fetch_Transaction_History_Data( $start_date_YYYYMMDD,
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
		$_SESSION['reports']['transaction_history']['report_data'] = $data;
	}
}

class Transaction_History_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Transaction History Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	/**
	 * Fetches data for the Transaction History Report
	 * @param   string $start_date YYYYmmdd
	 * @param   string $loan_type  standard || card
	 * @param   mixed  $company_id array of company_ids or 1 company_id
	 * @returns array
	 */
	public function Fetch_Transaction_History_Data($start_date, $loan_type, $company_id)
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

		$loan_type_list = $this->Get_Loan_Type_List($loan_type);

		$query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
            SELECT  th.application_id,
                    date_format(th.date_created, '%H:%i:%s') as date_modified,
                    th.transaction_register_id,
					tr.amount,
                    tt.name as transaction_type_name,
                    th.status_before,
                    th.status_after,
                    th.agent_id,
                    concat(ag.name_first, ' ', ag.name_last) as agent_name,
                    upper(c.name_short) as company_name,
                    th.company_id as company_id,
                    app.application_status_id
            FROM    transaction_history th,
                    transaction_type tt,
                    transaction_register tr,
                    agent ag,
                    application app,
                    company c
            WHERE   th.date_created BETWEEN {$start_date} AND {$end_date}
            AND		app.application_id = th.application_id
            AND     ag.agent_id = th.agent_id
            AND     tr.transaction_register_id = th.transaction_register_id
            AND     tt.transaction_type_id = tr.transaction_type_id
            AND		c.company_id = th.company_id
            AND     th.company_id IN ({$company_list})
            AND app.loan_type_id in (SELECT loan_type_id
                                     FROM loan_type
                                     WHERE name_short IN ({$loan_type_list}))
            ORDER BY th.application_id, th.transaction_register_id, th.transaction_history_id ";

		//$this->log->Write($query);

		$data = array();

		$st = $this->db->query($query);

    while ($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			$co = $row['company_name'];

			$this->Get_Module_Mode($row, $row['company_id']);

			$data[$co][] = $row;
		}

		$this->timer->stopTimer(self::$TIMER_NAME);

		return $data;
	}
}

?>
