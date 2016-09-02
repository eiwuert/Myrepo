<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

require_once(SERVER_MODULE_DIR."/reporting/report_generic.class.php");

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
			$this->search_query = new Customer_Status_Overview_Report_Query($this->server);
	
			$data = new stdClass();
	
			// Save the report criteria
			$data->search_criteria = array(
			  'company_id'      => $this->request->company_id,
			  'status_type'    	=> $this->request->status_type,
			  'balance_type'	=> $this->request->balance_type
			);
	
			if(isset($this->request->date))
			{
				// Dates before the end of the requested date
				$date = $this->request->date;
			}
			else
			{
				// Dates before the end of today
				$date = date('Ymd') . "235959";
			}
	
			$_SESSION['reports']['status_overview']['report_data'] = new stdClass();
			$_SESSION['reports']['status_overview']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['status_overview']['url_data'] = array('name' => 'Status Overview', 'link' => '/?module=reporting&mode=status_overview');
	
	
			$data->search_results = $this->search_query->Fetch_Status_Overview_Data( $this->request->status_type, $this->request->balance_type, $date, $this->request->company_id);
		}
		catch (Exception $e)
		{
			$data->search_message = "Unable to execute report. Reporting server may be unavailable.";
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}

		// This doesn't work. I'd fix it, but I'm not sure we're supposed to limit this [benb]
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
		$_SESSION['reports']['status_overview']['report_data'] = $data;
	}
}


class Customer_Status_Overview_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Status Overview Report Query";

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
	public function Fetch_Status_Overview_Data($status_type, $balance_type, $date, $company_id)
	{
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
			
			
		switch($balance_type)
		{
			case "positive":
				$type = ">";
				break;
			case "negative":				
			    $type = "<";
				break;
			case "zero":
				$type = "=";
				break;
		}       
		
		$status_type = explode(',',$status_type);

		// The status type actually is a comma separated list of status ids!
		$status = "'" . implode("','", $status_type) . "'";

		$query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT	
				ap.application_id,
				ap.name_first,
				ap.name_last,
				CONCAT(SUBSTR(ap.ssn, 1,3), '-', SUBSTR(ap.ssn, 4,2), '-', SUBSTR(ap.ssn, -4)) AS ssn,
				ap.phone_home,
				ap.phone_work,
				ap.phone_cell,
				ap.street,
				ap.city,
				ap.county,
				ap.state,
				ass.name as status,
    			 
				 Case when  (
                                        SELECT
                                               
                                                SUM(ea.amount) AS balance
                                        FROM
                                                event_amount AS ea
                                                JOIN event_amount_type eat USING (event_amount_type_id)
						JOIN transaction_register tr USING (transaction_register_id)
                                        WHERE
                                                (ea.company_id IN ({$company_list})) AND
                                                ea.application_id = ap.application_id and
                                                (eat.name_short in ('principal','fee','service_charge')) AND
                                                (tr.transaction_status in ('complete','pending'))
					
				
				) is null
				then 0
				else
				(
 									 SELECT
                                               
                                                SUM(ea.amount) AS balance
                                        FROM
                                                event_amount AS ea
                                                JOIN event_amount_type eat USING (event_amount_type_id)
						JOIN transaction_register tr USING (transaction_register_id)
                                        WHERE
                                                (ea.company_id IN ({$company_list})) AND
                                                ea.application_id = ap.application_id and
                                                (eat.name_short in ('principal','fee','service_charge')) AND
                                                (tr.transaction_status in ('complete','pending'))
					
				
				)
				
				
				end AS balance,
				ap.application_status_id AS application_status_id,
        		ap.company_id,
        		c.name_short as company_name
			FROM
				 application ap
			JOIN 
				application_status ass ON (ass.application_status_id = ap.application_status_id)
			JOIN 
				company c ON (c.company_id = ap.company_id)
			LEFT OUTER JOIN
				application_column ac on (ap.application_id = ac.application_id)
			WHERE
				ap.application_status_id IN ({$status})
			
			";

			// GF #16134: Hide test applications if LIVE [benb]
			if (EXECUTION_MODE == 'LIVE')
			{
				$query .= "AND (ap.name_last NOT LIKE '%tsstest%' AND ap.name_first NOT LIKE '%tsstest%')\n";
			}
		
	

			$query .= "
            AND 
				ap.company_id IN ({$company_list})
            HAVING
				(balance {$type} 0)
           	ORDER BY
				company_id,
				status
			";			

		$data = array();
	//	echo '<pre>' . $query;
		$fetch_result = $this->db->query($query);
		$sub_query = "select * from application_field join application_field_attribute using (application_field_attribute_id) where table_row_id = ? and table_name = 'application'";
		$sub_query_result = $this->db->prepare($sub_query); 		
		while ($row = $fetch_result->fetch(PDO::FETCH_ASSOC))
		{
			$co = strtoupper($row['company_name']);

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
						$row['phone_home'] = '';
						break;
						case 'phone_work':
						$row['phone_work'] = '';
						break;
						case 'phone_cell':
						$row['phone_cell'] = '';
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
