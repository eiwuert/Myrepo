<?php

require_once( SERVER_CODE_DIR . "base_report_query.class.php" );
require_once( LIB_DIR . "business_rules.class.php" );


class Reactivation_Marketing_Report_Query extends Base_Report_Query
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
	public function Fetch_Reactivation_Marketing_Data($start_date, $end_date, $company_id, $loan_type, $status)
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
		$withdrawn_status = Search_Status_Map('withdrawn::applicant::*root', $status_map);
		$denied_status = Search_Status_Map('denied::applicant::*root', $status_map);
		
		switch($status)
		{
			case 'inactive':
				$status_list = "$inactive_paid_status";
			break;
			case 'denied':
				$status_list = "$denied_status";
			break;
			case 'withdrawn':
				$status_list = " $withdrawn_status";
			break;
			case 'all':
			default:
				$status_list = "$inactive_paid_status, $withdrawn_status, $denied_status";
			break;
		}
		
		if( $loan_type == 'all' )
			$loan_type_list = $this->Get_Loan_Type_List($loan_type);
		else
			$loan_type_list = "'{$loan_type}'";
			
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
			a.ip_address,
			a.company_id,
			co.name_short AS company_name,
			a.application_id, 
			a.name_last, 
			a.name_first, 
			a.ssn,
			a.phone_home AS home_phone,
			a.phone_cell AS cell_phone,
			a.phone_work AS work_phone,
			a.phone_work_ext AS work_ext,
			a.email AS email_address,
			a.street AS address,
			a.city  AS city,
			a.state AS state,
			a.zip_code AS zip_code,
			a.date_application_status_set, 
			a.fund_actual,
			a.date_fund_actual,
			IF(a.application_status_id = $inactive_paid_status, a.date_application_status_set, null) as date_payoff,
			UPPER(stat.name) as status,
			ash.date_created as esign_date,
			site.name as source_site
				FROM
				application a
				JOIN application_status stat using (application_status_id)
				JOIN loan_type lt using (loan_type_id)
				LEFT JOIN application a2 USING (ssn)
				LEFT JOIN company co ON a.company_id = co.company_id
				LEFT JOIN (select * from do_not_loan_flag where active_status = 'active') dl on a.ssn = dl.ssn
				LEFT JOIN (select * from application_field join application_field_attribute using (application_field_attribute_id) where field_name = 'do_not_market' and table_name = 'application') dnm on dnm.table_row_id = a.application_id
				LEFT JOIN (select status_history.date_created, application_id from status_history join application_status using (application_status_id) where name = 'Pending') ash on a.application_id = ash.application_id 
				Left Join campaign_info ci on (a.application_id = ci.application_id)
				LEFT JOIN site on (ci.site_id = site.site_id)
				WHERE
				a.application_status_id in ($status_list)
				AND a.company_id IN ({$company_list})
				AND a.date_application_status_set BETWEEN {$start_date}000000 AND {$end_date}235959
				AND dl.ssn is null
				and dnm.table_row_id is null
				AND lt.name_short IN ({$loan_type_list})
				and not exists (SELECT application_id
								FROM
									application a2
								WHERE
									 a2.customer_id = a.customer_id 
								and  application_id <> a.application_id 
								and a2.date_created > a.date_created
				)
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
						$row['work_ext'] = '';
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