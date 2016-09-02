<?php

// This sets up eCash defines and obstantiates the Config class
// as well as database connection.  You may need to change the path to eCash.
require_once('/virtualhosts/ecash_commercial/www/config.php');

require_once('/virtualhosts/libolution/DB/Models/IterativeModel.1.php');
require_once('ECash/Models/IterativeModel.php');
require_once('ECash/Models/LoanType.php');
require_once('ECash/Models/LoanTypeList.php');
require_once('CFE/API/CFE.php');
require_once('CFE/API/RuleSetDef.php');
require_once('CFE/API/RuleDef.php');
require_once('CFE/API/RuleSetList.php');
require_once('CFE/API/ActionDef.php');


$api = new CFE_API();

/**
 * Test fetchAllLoanTypes()
 */
$loan_types = $api->fetchAllLoanTypes();
foreach($loan_types as $loan_type)
{
	echo "======================================\n";
	$columns = $loan_type->getColumns();

	foreach($columns as $key)
	{
		echo "$key => {$loan_type->$key}", PHP_EOL;
	}
}
echo "======================================\n";


/**
 * Test fetchLoanType()
 */
$loan_type = $api->fetchLoanType('delaware_payday');
echo $loan_type->name, PHP_EOL;

/**
 * Test fetchAllRulesets()
 */
$rule_sets_list = $api->fetchAllRulesets('delaware_payday');
foreach($rule_sets_list as $rule_set)
{
	echo "======================================\n";
	$columns = $rule_set->getColumns();

	foreach($columns as $key)
	{
		echo "$key => {$rule_set->$key}", PHP_EOL;
	}
	
	/**
	 * Test getRules()
	 */
	$rules = $rule_set->getRules();
	foreach($rules as $rule)
	{
		echo "New Rule:", PHP_EOL;
		$columns = $rule->getColumns();
	
		foreach($columns as $key)
		{
			echo "$key => {$rule->$key}", PHP_EOL;
		}
	}
	
}
echo "======================================\n";

/**
 * Test fetchRuleset()
 */
$rule_set = $api->fetchRuleset(1);
$rules = $rule_set->getRules();
foreach($rules as $rule)
{
	echo "New Rule:", PHP_EOL;
	$columns = $rule->getColumns();

	foreach($columns as $key)
	{
		echo "$key => {$rule->$key}", PHP_EOL;
	}
}
echo "======================================\n";

/**
 * Test getAvailableActions()
 */
$actions = $api->getAvailableActions();
var_dump($actions);