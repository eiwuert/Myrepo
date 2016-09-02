<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision: 17164 $
 */

require_once(SERVER_MODULE_DIR."/reporting/reminder_queue_report.class.php");

class Customer_Report extends Report
{
	public function Generate_Report()
	{

		try
		{		
			$search_query = new Customer_Reminder_Queue_Report_Query($this->server);

			$data = new stdClass();

			// Save the report criteria
			$data->search_criteria = array(
					'company_id'      => $this->request->company_id,
					'agent_id'        => $this->request->agent_id,
					);

			// Copy the search criteria into the session, but don't use the $data
			// object because it will be used to store aggregate data
			$_SESSION['reports']['reminder_queue']['report_data'] = new stdClass();
			$_SESSION['reports']['reminder_queue']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['reminder_queue']['url_data'] = array('name' => 'Reminder Queue', 'link' => '/?module=reporting&mode=reminder_queue');
			$data->search_results = $search_query->Fetch_Report_Results( $data->search_criteria['company_id'] , $data->search_criteria['agent_id'] );
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
			$data->search_message = "Your report would have more than " . $this->max_display_rows . " lines to display. Please choose more selective criteria.";
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}

		// Sort if necessary
		$data = $this->Sort_Data($data);

		ECash::getTransport()->Add_Levels("report_results");
		ECash::getTransport()->Set_Data($data);
		$_SESSION['reports']['reminder_queue']['report_data'] = $data;
	}
}

class Customer_Reminder_Queue_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME = "TITLE Report Query";

	public function Fetch_Report_Results( $company_id, $agent_id )
	{
		// Determine how long it take to do this
		$this->timer->startTimer(self::$TIMER_NAME);

		$company_list = $this->Format_Company_IDs($company_id);


		// Use this in the LIMIT statement of your query
		$max_report_retrieval_rows = $this->max_display_rows + 1;

		// Now initialize the data array we will be returning
		$data = array();

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

		// Build query
		$query_parts = array();

		// GF #12527: The changed parts of this query were due to some ugly unions and order of fields [benb]
		if($agents_selected)
		{
			// Now build a query
			$query_parts[] = "
				SELECT
				c.name AS company_name ,
				c.company_id AS company_id ,
				a.application_status_id AS application_status_id ,
				a.application_id AS app_id ,
				a.name_first AS first ,
				a.name_last AS last ,
				lt.name_short AS loan_type,
				a.date_next_contact AS date ,
				'No' AS arranged ,
				CONCAT(
						ag.name_first ,
						' ' ,
						ag.name_last
					  ) AS 'agent'
					FROM
					company                 AS c   ,
				application             AS a   ,
				agent_affiliation       AS aa  ,
				application_status_flat AS asf ,
				agent                   AS ag  ,
				loan_type                 AS lt
					WHERE 1 = 1
					AND c.company_id = a.company_id
					AND c.company_id = aa.company_id
					AND a.application_id = aa.application_id
					AND asf.application_status_id = a.application_status_id
					AND ag.agent_id = aa.agent_id
					AND c.company_id IN {$company_list}
					AND aa.agent_id IN ({$agent_id_list})
					AND aa.affiliation_type = 'owner'
					AND aa.affiliation_area = 'collections'
					AND lt.loan_type_id = a.loan_type_id
					AND aa.date_expiration > NOW()
					AND	( 0 = 1
							OR  asf.level0 = 'queued'
							OR  asf.level0 = 'dequeued'
							OR  ( 1 = 1
								AND asf.level0 = 'follow_up'
								AND a.date_next_contact < NOW()
								)
						)
					AND	asf.level1 = 'contact'
					AND	asf.level2 = 'collections'
					AND	asf.level3 = 'customer'
					AND	asf.level4 = '*root'
					";
			$query_parts[] = "
				SELECT
				c.name AS company_name ,
				c.company_id AS company_id ,
				a.application_status_id AS application_status_id ,
				a.application_id AS app_id ,
				a.name_first AS first ,
				a.name_last AS last ,
				lt.name_short AS loan_type,
				es.date_event AS date ,
				'Yes' AS arranged ,
				CONCAT(
						ag.name_first ,
						' ' ,
						ag.name_last
					  ) AS 'agent'
					FROM
					company                 AS c   ,
				application             AS a   ,
				agent_affiliation       AS aa  ,
				event_schedule          AS es  ,
				application_status_flat AS asf ,
				agent                   AS ag  ,
				loan_type                 AS lt
					WHERE 1 = 1
					AND c.company_id = a.company_id
					AND c.company_id = aa.company_id
					AND c.company_id = es.company_id
					AND a.application_id = aa.application_id
					AND a.application_id = es.application_id
					AND asf.application_status_id = a.application_status_id
					AND ag.agent_id = aa.agent_id
					AND c.company_id IN {$company_list}
					AND lt.loan_type_id = a.loan_type_id
					AND aa.agent_id IN ({$agent_id_list})
					AND aa.affiliation_type = 'owner'
					AND aa.affiliation_area = 'collections'
					AND aa.date_expiration > NOW()
					AND es.date_event BETWEEN
					DATE_FORMAT(CURRENT_DATE(),'%Y-%m-%d 00:00:00') AND
					DATE_FORMAT(DATE_ADD(CURRENT_DATE(), INTERVAL 2 DAY),'%Y-%m-%d 23:59:59')
					AND es.event_status = 'scheduled'
					AND	asf.level0 = 'current'
					AND	asf.level1 = 'arrangements'
					AND	asf.level2 = 'collections'
					AND	asf.level3 = 'customer'
					AND	asf.level4 = '*root'
					";
		}
		if($unassigned_selected)
		{
			// Now build a query
			$query_parts[] = "
				SELECT
				c.name_short AS company_name ,
				c.company_id AS company_id ,
				a.application_status_id AS application_status_id ,
				a.application_id AS app_id ,
				a.name_first AS first ,
				a.name_last AS last ,
				lt.name_short AS loan_type,
				'None' AS date ,
				'No' AS arranged ,
				'Unassigned' AS 'agent'
					FROM
					company                 AS c   ,
				application             AS a   ,
				application_status_flat AS asf ,
				loan_type                 AS lt
					WHERE 1 = 1
					AND c.company_id = a.company_id
					AND asf.application_status_id = a.application_status_id
					AND lt.loan_type_id = a.loan_type_id
					AND c.company_id IN {$company_list}
					AND	( 0 = 1
							OR  asf.level0 = 'queued'
							OR  ( 1 = 1
								AND asf.level0 = 'dequeued'
								AND a.date_application_status_set < DATE_SUB(NOW(), INTERVAL 30 MINUTE)
								)
							OR  ( 1 = 1
								AND asf.level0 = 'follow_up'
								AND a.date_next_contact < NOW()
								)
						)
					AND	asf.level1 = 'contact'
					AND	asf.level2 = 'collections'
					AND	asf.level3 = 'customer'
					AND	asf.level4 = '*root'
					AND NOT EXISTS (
							SELECT
							aa.application_id
							FROM
							agent_affiliation AS aa
							WHERE 1 = 1
							AND aa.affiliation_type = 'owner'
							AND aa.affiliation_area = 'collections'
							AND aa.date_expiration > NOW()
							AND aa.application_id = a.application_id
							)
					";
		}
		$query = "-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			(".join(") UNION DISTINCT (", $query_parts).")
			ORDER BY
			date ASC, app_id ASC
			LIMIT
			{$max_report_retrieval_rows}
		";
		$query = preg_replace('/(^\s+--.*$)|(^\s+)/m','',$query);

		// Run query
		$result = $this->db->Query($query);

		// Cap result size
		if( $result->rowCount() == $max_report_retrieval_rows )
		{
			return(FALSE);
		}

		// Process results
		while($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			// Grab the company name out of the row
			$company_name = $row['company_name'];
			unset($row['company_name']);

			// Clean up NULLs
			if(NULL === $row['date'])
			{
				$row['date'] = 'None';
			}

			// If you want to be able to link the column, you need this
			$this->Get_Module_Mode($row);

			//Take care of name casing
			$row['first'] = ucfirst($row['first']);
			$row['last'] = ucfirst($row['last']);
			// Pass the data out by company
			$data[$company_name][] = $row;
		}

		// Determine how long it take to do this
		$this->timer->stopTimer(self::$TIMER_NAME);

		// Return the data they want
		return($data);
	}
}

?>
