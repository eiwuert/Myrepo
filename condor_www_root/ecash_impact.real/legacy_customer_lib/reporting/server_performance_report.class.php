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
			$this->search_query = new Customer_Performance_Report_Query($this->server);
	
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
			  'loan_type'       => $this->request->loan_type,
			  'react_type'		=> $this->request->react_type
			);
	
			$_SESSION['reports']['performance']['report_data'] = new stdClass();
			$_SESSION['reports']['performance']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['performance']['url_data'] = array('name' => 'Agent Actions', 'link' => '/?module=reporting&mode=performance');
	
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
	
			$data->search_results = $this->search_query->Fetch_Company_Performance_Data($start_date_YYYYMMDD,
											    $end_date_YYYYMMDD,
											    $this->request->loan_type,
											    $this->request->company_id,
											    $this->request->react_type);
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
		$_SESSION['reports']['performance']['report_data'] = $data;
	}
}

class Customer_Performance_Report_Query extends Base_Report_Query
{
        private static $TIMER_NAME = "Performance Report Query";
        private $system_id;

        public function __construct(Server $server)
        {
                parent::__construct($server);

                $this->system_id = $server->system_id;

        }

        public function Fetch_Company_Performance_Data($date_start, $date_end, $loan_type, $company_id, $react_type = 'both')
        {
                $this->timer->startTimer( self::$TIMER_NAME );

                $company_list = $this->Format_Company_IDs($company_id);
                $loan_type_list = $this->Get_Loan_Type_List($loan_type);

                // Start and end dates must be passed as strings with format YYYYMMDD
                $timestamp_start = $date_start . '000000';
                $timestamp_end   = $date_end   . '235959';

				$extra_sql = "";
				
				if ($react_type == "yes")
				{
					// Only reacts
					$extra_sql = "AND app.is_react = 'yes'";
				}
				else if ($react_type == "no")
				{
					// Only non-reacts
					$extra_sql = "AND app.is_react = 'no'";
				}

                // GF 12733: This isn't the ideal solution. Ideally we'd be putting an agent action each
                // time one of these columns needs increased. I cannot get a full agent list from a single
                // source, so I'm just going to do each source one query at a time, and merge it into a PHP
                // array. [benb]

                // This query gets all agents who have pulled from the verify, or underwriting queues
                $query = "
                        -- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
                        SELECT
                                UPPER(c.name_short)                                         AS company_name,
                                CONCAT(LOWER(a.name_first),
                                           ' ',
                                           LOWER(a.name_last))                              AS agent_name,
                                SUM(IF(act.name_short = 'verification' AND
                                        app.is_react = 'no', 1, 0))                         AS num_pull_verify_new,
                                SUM(IF(act.name_short = 'verification_react' AND
                                        app.is_react = 'yes', 1, 0))                        AS num_pull_verify_react,
                                SUM(IF(act.name_short = 'Underwriting' AND
                                        app.is_react = 'no', 1, 0))                         AS num_pull_underwriting_new,
                                SUM(IF(act.name_short = 'underwriting_react' AND
                                        app.is_react = 'yes', 1, 0))                        AS num_pull_underwriting_react,
                                SUM(IF(act.name_short='Additional Verification', 1, 0)) AS num_pull_addl
                        FROM
                                agent_action aact
                        JOIN
                                agent a ON (a.agent_id = aact.agent_id)
                        JOIN
                                company c ON (c.company_id = aact.company_id)
                        JOIN
                                action act ON (act.action_id = aact.action_id)
                        JOIN
                                application app ON (app.application_id = aact.application_id)
                        WHERE
                                aact.date_created BETWEEN '{$timestamp_start}' AND '{$timestamp_end}'
                        AND
                                aact.company_id IN {$company_list}
						$extra_sql
                        GROUP BY
                                aact.company_id, aact.agent_id
                ";

                $agents = array();

                $st = $this->db->query($query);

                while ($row = $st->fetch(PDO::FETCH_ASSOC))
                {
                        $cname = $row['company_name'];
                        $aname = $row['agent_name'];

						if ($row['num_pull_addl'] + $row['num_pull_verify_new'] + $row['num_pull_verify_react'] +
							$row['num_pull_underwriting_new'] + $row['num_pull_underwriting_react'])
						{
	                        $agents[$cname][$aname]['num_pull_addl']               = $row['num_pull_addl'];
    	                    $agents[$cname][$aname]['num_pull_verify_new']         = $row['num_pull_verify_new'];
        	                $agents[$cname][$aname]['num_pull_verify_react']       = $row['num_pull_verify_react'];
            	            $agents[$cname][$aname]['num_pull_underwriting_new']   = $row['num_pull_underwriting_new'];
                	        $agents[$cname][$aname]['num_pull_underwriting_react'] = $row['num_pull_underwriting_react'];
						}
                }

                // Now this query gets status changes
                $query = "
                        -- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
                        SELECT
                                UPPER(c.name_short)                                  AS company_name,
                                CONCAT(LOWER(a.name_first),
                                           ' ',
                                           LOWER(a.name_last))                       AS agent_name,
                                SUM(IF(ass.name_short='approved' AND
                                        app.is_react = 'no', 1, 0))                  AS num_funded_new,
                                (
									SELECT
										IF(COUNT(*)-1 < 0, 0, COUNT(*)-1)
									FROM
										status_history ish
									JOIN
										application_status iass ON (iass.application_status_id = ish.application_status_id)
									WHERE
										ish.application_id = app.application_id
									AND
										iass.name_short='approved'
								)                                                    AS num_funded_dupe,"./*WTF IS THIS */"
                                SUM(IF(ass.name_short='approved' AND
                                        app.is_react = 'yes', 1, 0))                 AS num_funded_react,
                                SUM(IF(ass.name='Approved'  AND
                                        ass.name_short='queued' AND
                                        app.is_react = 'no', 1, 0))                  AS num_approved_new,
                                SUM(IF(ass.name='Approved'  AND
                                        ass.name_short='queued' AND
                                        app.is_react = 'yes', 1, 0))                 AS num_approved_react,
                                SUM(IF(ass.name_short='withdrawn' AND
                                        app.is_react = 'no', 1, 0))                  AS num_withdrawn_new,
                                SUM(IF(ass.name_short='withdrawn' AND
                                        app.is_react = 'yes', 1, 0))                 AS num_withdrawn_react,
                                SUM(IF(ass.name_short='denied' AND
                                        app.is_react = 'no', 1, 0))                  AS num_denied_new,
                                SUM(IF(ass.name_short='denied' AND
                                        app.is_react = 'yes', 1, 0))                 AS num_denied_react,
                                SUM(IF(ass.name='Confirmed' AND
                                        ass.name_short='queued' AND
                                        app.is_react = 'no', 1, 0))                  AS num_reverified_new,
                                SUM(IF(ass.name='Confirmed' AND
                                        ass.name_short='queued' AND
                                        app.is_react = 'yes', 1, 0))                 AS num_reverified_react,
                                SUM(IF(ass.name='Additional', 1, 0))                 AS num_put_in_addl
                        FROM
                                status_history sh
                        JOIN
                                agent a ON (a.agent_id = sh.agent_id)
                        JOIN
                                company c ON (c.company_id = sh.company_id)
                        JOIN
                                application_status ass ON (ass.application_status_id = sh.application_status_id)
                        JOIN
                                application app ON (app.application_id = sh.application_id)
                        WHERE
                                sh.date_created BETWEEN '{$timestamp_start}' AND '{$timestamp_end}'
                        AND
                                sh.company_id IN {$company_list}
						$extra_sql
                        GROUP BY
                                sh.company_id, sh.agent_id
                ";

                $st = $this->db->query($query);

				while ($row = $st->fetch(PDO::FETCH_ASSOC))
				{
					$cname = $row['company_name'];
					$aname = $row['agent_name'];

					// This is a bit ugly, but just a simple check to exclude all agents with no stats
					if ($row['num_put_in_addl']     + $row['num_reverified_new'] + $row['num_reverified_react'] +
							$row['num_denied_new']      + $row['num_denied_react']   + $row['num_withdrawn_new']    +
							$row['num_withdrawn_react'] + $row['num_approved_new']   + $row['num_approved_react']   +
							$row['num_put_in_underwriting_new'] + $row['num_put_in_underwriting_react'] +
							$row['num_funded_new'] + $row['num_funded_react'] != 0)
					{
						$agents[$cname][$aname]['num_put_in_addl']        = $row['num_put_in_addl'];
						$agents[$cname][$aname]['num_reverified_new']     = $row['num_reverified_new'];
						$agents[$cname][$aname]['num_reverified_react']   = $row['num_reverified_react'];
						// The above is the same as the number of applications put into the Verifications queue
						// So I'm just going to duplicate it in PHP below. [benb]
						$agents[$cname][$aname]['num_put_in_verify_new']   = $row['num_reverified_new'];
						$agents[$cname][$aname]['num_put_in_verify_react'] = $row['num_reverified_react'];


						$agents[$cname][$aname]['num_denied_new']         = $row['num_denied_new'];
						$agents[$cname][$aname]['num_denied_react']       = $row['num_denied_react'];
						$agents[$cname][$aname]['num_withdrawn_new']      = $row['num_withdrawn_new'];
						$agents[$cname][$aname]['num_withdrawn_react']    = $row['num_withdrawn_react'];

						$agents[$cname][$aname]['num_approved_new']       = $row['num_approved_new'];
						$agents[$cname][$aname]['num_approved_react']     = $row['num_approved_react'];
						// The above is the same as the number of applications put into the Underwriting queue
						// so I'm going to just duplicate it in PHP below. [benb]
						$agents[$cname][$aname]['num_put_in_underwriting_new']   = $row['num_approved_new'];
						$agents[$cname][$aname]['num_put_in_underwriting_react'] = $row['num_approved_react'];

						$agents[$cname][$aname]['num_funded_new']         = $row['num_funded_new'];
						$agents[$cname][$aname]['num_funded_react']       = $row['num_funded_react'];
					}
				}

                $data = array();

                // Now make it ecash format friendly
                foreach  ($agents as $company_name => $company_data)
                {
                        foreach ($company_data as $agent_name => $agent_data)
                        {
                                $agent_data['agent_name']   = $agent_name;
								$agent_data['company_name'] = $company_name;
                                $data[$company_name][] = $agent_data;
                        }
                }

                $this->timer->stopTimer( self::$TIMER_NAME );

                return $data;
        }

        // Why is this here? I see no reference to it [benb]
        public function Fetch_OLP_Agent_Id()
        {
                $query = "
                                SELECT agent_id
                                FROM agent
                                WHERE login = 'olp'
                                AND active_status = 'active' ";

                return $this->db->querySingleValue($query);
        }
}

?>
