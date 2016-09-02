<?php

require_once 'cfc_api/CFC/Target.php';
require_once 'cfc_api/bb.php';
require_once 'cfc_api/CFC/Application.php';
require_once 'cfc_api/CFC/IRule.php';
require_once 'cfc_api/CFC/Rules/DupeRule.php';
require_once 'cfc_api/CFC/Rules/RuleDecorator.php';
require_once 'cfc_api/CFC/Rules/CFCDUPE180.php';
require_once 'cfc_api/CFC/Rules/CFCDUPE90.php';
require_once 'cfc_api/CFC/Rules/CFCHOMEPH.php';
require_once 'cfc_api/CFC/Rules/DUPE.php';
require_once 'blackbox/Rules/ComparisonRule.php';
require_once 'blackbox/Rules/In.php';
require_once 'blackbox/Rules/NotIn.php';
require_once 'blackbox/Rules/LessThan.php';
require_once 'blackbox/Rules/Equals.php';
require_once 'blackbox/Rules/NotEquals.php';
require_once 'blackbox/Rules/Datax.php';
require_once 'blackbox/Rules/DataxIDV.php';
require_once SQL_LIB_DIR . 'loan_actions.func.php';
require_once SQL_LIB_DIR . 'app_stat_id_from_chain.func.php';


/**
 * this should later implement an interface if we actually have more
 * than one company that reprocesses [JustinF]
 */

class Reprocessor
{
	const FILTER_NUMERIC = 'numeric';

	const STATUS_DENIED = 'denied::applicant::*root';
	const STATUS_APPROVED = 'approved::servicing::customer::*root';
	const STATUS_PENDING = 'queued::verification::applicant::*root';
	
	private $server;
	private $request;
	
	public function __construct(Server $server, $request)
	{
		$this->server = $server;
		$this->request = $request;
	}

	public static function getTarget($ruleset_id = NULL, $start_at = NULL)
	{
		//alias the DB to 'CFC'
		try
		{
			//only set it if it hasn't been set yet
			DB_DatabaseConfigPool_1::get('CFC');
		}
		catch(Exception $e)
		{
			DB_DatabaseConfigPool_1::add('CFC', DB_DatabaseConfigPool_1::get(ECash_Models_WritableModel::ALIAS_MASTER));
		}
		
		return CFC_Target::getInstance($ruleset_id, $start_at);
	}
	
	public function reprocess($ruleset_id, $start_at)
	{
		//echo '<pre>', print_r($this->request, TRUE); die;
		
		//get our rule-runner
		$target = self::getTarget($ruleset_id, $start_at); //$ruleset_id = NULL, $start_at = NULL);
		//set the request stuff on a CFCApplication object for validation
		$app = new CFC_Application();
		$app->company = 'cfc';
		$app->loan_type = 'gold';
		
		$form_vars = array('application_id' => NULL,
						   //personal
						   'dob' => NULL,
						   'ssn' => self::FILTER_NUMERIC,
						   'customer_email' => NULL,
						   'name_last' => NULL,
						   'name_first' => NULL,
						   'name_middle' => NULL,
						   'name_suffix' => NULL,
						   'street' => NULL,
						   'unit' => NULL,
						   'city' => NULL,
						   'state' => NULL,
						   'zip' => self::FILTER_NUMERIC,
						   //general info
						   'bank_aba' => self::FILTER_NUMERIC,
						   'bank_account' => NULL,
						   'phone_home' => self::FILTER_NUMERIC,
						   'phone_work' => self::FILTER_NUMERIC,
						   'phone_cell' => self::FILTER_NUMERIC,
						   'has_checking' => NULL,
						   'opt_in' => NULL);
		//some regexes
		$numeric = '/[^0-9]/';
				
		foreach($form_vars as $name => $transformation)
		{
			if($transformation == self::FILTER_NUMERIC)
				$app->{$name} = preg_replace($numeric, '', $this->request->{$name});
			else
				$app->{$name} = $this->request->{$name};
		}

		//save edit changes to app

		//rerun business rules
		if (($valid = $target->valid($app)) === TRUE)
		{
			$app->application_status = $target->getApproved()
				? self::STATUS_APPROVED
				: self::STATUS_PENDING;
		}
		else
		{
			$app->application_status = self::STATUS_DENIED;
			//save loan action
			$app->addLoanAction($target->getReasonCode());			
		}

		$status_id = app_stat_id_from_chain(ECash_Config::getMasterDbConnection(), $app->application_status);
		Update_Status($this->server, $app->application_id,$app->application_status,null,$this->server->agent_id);
		foreach($app->getLoanActions() as $loan_action_set)
		{
			//echo '<!-- Loan Action: ', print_r($loan_action_set, TRUE), ' -->';
			$loan_action_id = Get_Loan_Action_ID($loan_action_set['loan_action']);
			Insert_Loan_Action($app->application_id, $loan_action_id, $status_id, $this->server->agent_id);
		
		}

		//echo '<pre>', print_r($app, TRUE); die;
	}
}

?>
