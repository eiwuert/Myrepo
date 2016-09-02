<?php

require_once( SERVER_CODE_DIR . "base_report_query.class.php" );
require_once( LIB_DIR . "business_rules.class.php" );

class Aging_Summary_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Aging Summary Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
		bcscale(2);
	}

	/**
	 * Fetches data for the Aging Report
	 * @param   string $start_date YYYYmmdd
	 * @param   string $end_date   YYYYmmdd
	 * @param   string $loan_type  standard || card
	 * @param   mixed  $company_id array of company_ids or 1 company_id
	 * @returns array
	 */
	public function Fetch_Aging_Summary_Data($company_id, $loan_type, $run_date)
	{
		
		$this->timer->startTimer(self::$TIMER_NAME);
		
		$loan_type_list = $this->Get_Loan_Type_List($loan_type);
		$loan_type_id_list = $this->Get_Loan_Type_List($loan_type, TRUE);
		
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
			     
		
		$query = "
			-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
SELECT 
DISTINCT tr.application_id,
CONCAT(app.name_last , ', ', app.name_first) as name_full,
app.ssn as ssn,
app.date_fund_actual as fund_date,
DATEDIFF('{$run_date}', 
CASE
	WHEN
	(
	SELECT LEAST(IFNULL((SELECT transaction_register.date_effective from transaction_register
	JOIN ach USING (ach_id)
	JOIN ach_return_code as arc USING (ach_return_code_id)
	where transaction_register.application_id = tr.application_id
	AND arc.is_fatal = 'yes'
	AND ach.ach_type != 'credit'
	ORDER BY transaction_register.date_effective ASC LIMIT 1), '2222:22:22')
	,
	IFNULL((SELECT transaction_register.date_effective from transaction_register
	WHERE transaction_register_id = (
		select origin_id from transaction_register
		JOIN event_schedule as es USING (event_schedule_id)
		where transaction_register.application_id = tr.application_id
		AND transaction_status = 'failed'
		AND context = 'reattempt'
		ORDER BY transaction_register.date_effective ASC LIMIT 1)), '2222:22:22')
	)
	) != '2222:22:22'
	THEN
	(
	SELECT LEAST(IFNULL((SELECT transaction_register.date_effective from transaction_register
	JOIN ach USING (ach_id)
	JOIN ach_return_code as arc USING (ach_return_code_id)
	where transaction_register.application_id = tr.application_id
	AND arc.is_fatal = 'yes'
	AND ach.ach_type != 'credit'
	ORDER BY transaction_register.date_effective ASC LIMIT 1), '2222:22:22')
	,
	IFNULL((SELECT transaction_register.date_effective from transaction_register
	WHERE transaction_register_id = (
		select origin_id from transaction_register
		JOIN event_schedule as es USING (event_schedule_id)
		where transaction_register.application_id = tr.application_id
		AND transaction_status = 'failed'
		AND context = 'reattempt'
		ORDER BY transaction_register.date_effective ASC LIMIT 1)), '2222:22:22')
	)
	)
ELSE
(
IF(
# if the last failure comes after the last complete transaction, return the failure's date effective
(select tr_1.date_effective from transaction_register as tr_1
JOIN transaction_type as tt USING (transaction_type_id)
JOIN event_schedule USING (event_schedule_id)
where tr_1.application_id = tr.application_id
AND (((tt.clearing_type = 'external') AND (tt.affects_principal = 'no'))               
OR ((tt.clearing_type = 'ach') AND (tt.affects_principal = 'no')) 
OR (tt.affects_principal = 'yes'))
AND tr_1.transaction_status = 'complete'
AND ( event_schedule.context != 'arrangement' AND event_schedule.context != 'partial' )
ORDER BY tr_1.date_effective DESC LIMIT 1)
<
(select transaction_register.date_effective from transaction_register
where transaction_register.application_id = tr.application_id
AND transaction_status = 'failed'
ORDER BY transaction_register.date_effective DESC LIMIT 1)
,
# Do Checks to determine failure type and return effective date accordingly
(SELECT IF(
    (SELECT COUNT(transaction_register.date_effective)
    FROM transaction_register where transaction_register.application_id = tr.application_id
      AND transaction_status = 'failed') > 1, # This should be pulled from business rule max_svc_chg_failures
    (SELECT transaction_register.date_effective
    FROM transaction_register where transaction_register.application_id = tr.application_id
      AND transaction_status = 'failed'
    ORDER BY transaction_register.date_effective ASC LIMIT 1),
    (SELECT transaction_register.date_effective
    FROM transaction_register where transaction_register.application_id = tr.application_id
  	AND transaction_status = 'failed'
    ORDER BY transaction_register.date_effective DESC LIMIT 1))
)
,
# return next scheduled transaction due date
(select event_schedule.date_effective from event_schedule 
where event_schedule.application_id = tr.application_id
AND event_status = 'scheduled'
AND (amount_principal + amount_non_principal) < 0
ORDER BY event_schedule.date_effective ASC LIMIT 1)
))END 
) as num_days_delinquent,
0 as balance,
0 as interest,
0 as fee,
0 as total,
asf.application_status_id,
asf.level0_name as status,
app.loan_type_id,
co.name as company_name,
lt.name as loan_type_name
FROM application as app
JOIN transaction_register as tr USING (application_id)
JOIN application_status_flat as asf USING (application_status_id)
JOIN loan_type as lt USING (loan_type_id)
JOIN company as co ON (app.company_id = co.company_id)
WHERE app.application_status_id NOT IN (109, 18, 124, 19)
AND tr.date_created <= '{$run_date}'
AND app.company_id IN ({$company_list})
AND app.date_fund_actual IS NOT NULL
AND	lt.name_short IN ({$loan_type_list})
GROUP BY tr.application_id
";			
		
		//echo '<pre>'.$query.'</pre>';
		//$this->log->Write($query);
		
		$data = array();
		require_once(SQL_LIB_DIR . "scheduling.func.php");
		require_once(ECASH_COMMON_DIR . "ecash_api/interest_calculator.class.php");

		$biz_rules = new ECash_Business_Rules(ECash_Config::getMasterDbConnection());
	    $holidays = Fetch_Holiday_List();
		$pdc = new Pay_Date_Calc_3($holidays);
		
		//Preload necessary rulesets
		$lt_ids = explode(',', str_replace("'", "", $loan_type_id_list));

		foreach($lt_ids as $loan_type_id)
		{
			$rule_set_id = $biz_rules->Get_Current_Rule_Set_Id($loan_type_id);
			$lt_rules[intval($loan_type_id)] = $biz_rules->Get_Rule_Set_Tree($rule_set_id);
		}
		
		$fetch_result = $this->db->Query($query);
		
		$groups = array(
				'Current',
				"1-15",
				'16-30',
				'31-45',
				'46-60',
				'61-90',
				'91+');
		$totals = array();
		while( $row = $fetch_result->fetch(PDO::FETCH_ASSOC) )
		{
			$rules = $lt_rules[$row['loan_type_id']];
			$schedule = Fetch_Schedule($row['application_id']);
			$balance_info = Fetch_Balance_Information($row['application_id'], $run_date);
			$row['balance'] = ($balance_info->principal_pending > 0) ? $balance_info->principal_pending : $balance_info->principal_balance;
			$row['interest'] = $balance_info->service_charge_balance;
			$interest = bcadd($row['interest'], Interest_Calculator::scheduleCalculateInterest($rules, $schedule, $run_date));
			$renewal_class =  ECash::getFactory()->getRenewalClassByApplicationID($row['application_id']);
			// I definitely don't think this is needed here
			// Don't accrue past default date
			if ($rules['loan_type_model'] == 'CSO')
			{
				if ($renewal_class->hasDefaulted($row['application_id']))
					$interest = 0.00;
			}

			$row['interest'] = $interest;
			$row['fee'] = $balance_info->fee_balance;
			$row['total'] = bcadd($row['balance'] , bcadd($row['interest'] , $row['fee']));
			
			if($row['total'] > 0)
			{
			
				$grouping = ceil($row['num_days_delinquent']/15);
				
				if ($grouping < 0)
				{ 
					$group_num = 0;
				}
				else if ($grouping > 4 && $grouping < 6)
				{ 
					$group_num = 5;
				}
				else if ($grouping > 6)
				{ 
					$group_num = 6;
				}else{
					$group_num = $grouping;
				}
				//$grouping = $groups[$group_num];
	
				//$this->Get_Module_Mode($row, $row['company_id']);
				$company_loantype = $row['company_name'] . " - " . $row['loan_type_name'];
				
				// Initialize the $data[$company_loantype] array so it displays properly
				if(count($data[$company_loantype]) == 0) 
				{ 
					$data[$company_loantype] = array();
					$data[$company_loantype] = array_pad($data[$company_loantype], 7, array());
					foreach($data[$company_loantype] as $num => $group_info)
					{
						$data[$company_loantype][$num]['num_days'] = $groups[$num];
						$data[$company_loantype][$num]['num_loans'] = 0;
						$totals[$company_loantype]['loans'] = 0;
						$totals[$company_loantype]['principal'] = 0;
					}
				}
					
					$data[$company_loantype][$group_num]['num_loans']++;
					$totals[$company_loantype]['loans']++;
					$data[$company_loantype][$group_num]['balance'] = bcadd($data[$company_loantype][$group_num]['balance'], $row['balance']);
					$totals[$company_loantype]['principal'] = bcadd($totals[$company_loantype]['principal'], $row['balance']);
					$data[$company_loantype][$group_num]['interest'] = bcadd($data[$company_loantype][$group_num]['interest'], $row['interest']);
					$data[$company_loantype][$group_num]['fee'] = bcadd($data[$company_loantype][$group_num]['fee'], $row['fee']);
					$data[$company_loantype][$group_num]['total'] = bcadd($data[$company_loantype][$group_num]['total'], $row['total']);
			}

		}
		
		//Calculate bucket percentages
		foreach($data as $company_loantype => $group)
		{
			foreach($group as $bucket_num => $bucket)
			{
				$data[$company_loantype][$bucket_num]['pct_loans'] = ($bucket['num_loans'] / $totals[$company_loantype]['loans']) * 100;
				$data[$company_loantype][$bucket_num]['pct_balance'] = ($bucket['balance'] / $totals[$company_loantype]['principal']) * 100;
			}
		}

		
		foreach($data as $company_loantype_group)
		{
			ksort($company_loantype_group);			
		}
		$this->timer->stopTimer(self::$TIMER_NAME);
		ksort($data);
		
		return $data;
	}
}

?>
