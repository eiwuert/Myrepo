<?php

require_once("schedule_event.class.php");
require_once(COMMON_LIB_DIR . "pay_date_calc.3.php");
require_once(COMMON_LIB_DIR . "applog.1.php");
require_once(SQL_LIB_DIR . "scheduling.func.php");
require_once(SQL_LIB_DIR . "app_mod_checks.func.php");
require_once(SQL_LIB_DIR . "debt_company.func.php");

// Our DFA classes -- Customer Specific
require_once(CUSTOMER_LIB. "ach_returns_dfa.php");
require_once(CUSTOMER_LIB. "complete_schedule_dfa.php");

class Loan_Scheduler
{
	public $log;
	public $last_schedule = null;
	public $schedule_status;

/**
 * Create_Edited_Schedule - Moved to sql/lib/scheduling.func.php
 */


	
}

?>
