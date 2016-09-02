<?php

if (count($argv) < 3)
{
	echo <<<USAGE
Usage:   {$argv[0]} [mode] [customer]
Example: {$argv[0]} RC CLK

USAGE;
	exit;
}

require_once('libolution/AutoLoad.1.php');
AutoLoad_1::addSearchPath(dirname(__FILE__) . '/code/');

$mode     = $argv[1];
$customer = strtoupper($argv[2]);

$analysis_db = call_user_func(array($customer . '_Batch', 'getAnalysisDb'), $mode)->getConnection();



process($analysis_db);

exit;



// begin functions

function process($analysis_db)
{
	$company_map = getCompanies($analysis_db);
	
	$loans = countLoans($analysis_db);
	$cur_sig = NULL;
	$vars = NULL;
	
	foreach ($loans as $count)
	{
		
		$sig = date('Ym', $count['month']);
		
		if ($sig !== $cur_sig)
		{
			$vars = getProfitVariables($analysis_db, $sig);
			$cur_sig = $sig;
		}
		
		// get our property short
		$company = strtoupper($company_map[$count['company_id']]);
		
		if (isset($vars[$company]) && count($vars))
		{
			// apply the variables
			applyVariables($analysis_db, $count['company_id'], $count['month'], $vars[$company], $count);
		}
		else
		{
			echo "WARNING: Could not find variables for ", $company, " ", $sig, "; skipping\n";
		}
		
	}

	// update the loan performance data
	foreach ($company_map as $company => $prop_short)
	{
		echo "updating loan performance for $prop_short \n";
		updatePerformanceData($analysis_db, $company);
	}
}

	// this function uses data from the loan table and calculates cost and profit for the loan_performance table
	function updatePerformanceData(DB_IConnection_1 $db, $company)
	{
		$query = "UPDATE loan_performance lp , loan l
			SET lp.cost = ( lp.baddebt_principal_and_fees - lp.baddebt_paid_principal_and_fees
				+ IF(overhead_cost is null, 0, overhead_cost) 
				+ IF(acquisition_cost is null, 0, acquisition_cost) ),
				lp.profit = (l.fees_accrued - (lp.baddebt_principal_and_fees - 
					lp.baddebt_paid_principal_and_fees 
					+ IF(overhead_cost is null, 0, overhead_cost) 
					+ IF(acquisition_cost is null, 0, acquisition_cost)))
			WHERE lp.loan_id = l.loan_id 
			AND lp.overhead_cost IS NOT null 
			AND lp.acquisition_cost IS NOT null 
			AND lp.company_id = '" . $company . "'
			";
		
		$result = $db->query($query);
	}
	
	function getCompanies(DB_IConnection_1 $db)
	{
		
		$query = "
			SELECT
				company_id,
				name_short
			FROM
				company
		";
		$result = $db->query($query);
		
		$map = array();
		
		while ($rec = $result->fetch(DB_IStatement_1::FETCH_ASSOC))
		{
			$map[$rec['company_id']] = $rec['name_short'];
		}
		
		return $map;
		
	}
	
	function getProfitVariables(DB_IConnection_1 $db, $signature)
	{
		
		$query = "
			SELECT
				*
			FROM
				clk_profit_variables
			WHERE
				signature = '{$signature}'
		";
		$result = $db->query($query);
		
		$vars = array();
		
		while ($rec = $result->fetch(DB_IStatement_1::FETCH_ASSOC))
		{
			$vars[strtoupper($rec['property'])] = $rec;
		}
		
		return $vars;
		
	}
	
	function applyVariables(DB_IConnection_1 $db, $company_id, $month, $vars, $count)
	{
		
		// apply the variables over the proper loans to get per-loan costs
		$acquisition = (isset($count['new']) && ($count['new'] > 0)) ? ($vars['acquisition_cost'] / $count['new']) : 0;
		$overhead = (isset($count['total']) && ($count['total'] > 0)) ? ($vars['overhead_cost'] / $count['total']) : 0;
		
		$month_start = date('Y-m-1', $month);
		$month_end = date('Y-m-1', strtotime('+1 month', $month));
		
		$query = "
			UPDATE
				loan_performance,
				loan
			SET
				overhead_cost = {$overhead},
				acquisition_cost = IF(loan.loan_number = 1, {$acquisition}, 0)
			WHERE
				loan.loan_id = loan_performance.loan_id
				AND loan.company_id = {$company_id}
				AND loan.date_advance >= '{$month_start}'
				AND loan.date_advance < '{$month_end}'
		";
		$db->query($query);
		echo preg_replace('/\s+/', ' ', $query), "\n";
		
		return;
		
	}
	
	function countLoans(DB_IConnection_1 $db)
	{
		
		$query = "
			SELECT
				DATE_FORMAT(date_advance, '%Y-%m-1') AS month,
				company_id,
				COUNT(*) AS total,
				SUM(loan_number = 1) AS new,
				SUM(loan_number > 1) AS react
			FROM
				loan
			GROUP BY
				month,
				company_id
		";
		$result = $db->query($query);
		
		$loans = array();
		
		while ($rec = $result->fetch(DB_IStatement_1::FETCH_ASSOC))
		{
			$rec['month'] = strtotime($rec['month']);
			$loans[] = $rec;
		}
		
		return $loans;
		
	}
	
?>
