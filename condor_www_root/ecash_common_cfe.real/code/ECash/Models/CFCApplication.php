<?php

require_once 'Application.php';
require_once 'RuleSet.php';

/**
 * @package Ecash.Models
 */

class ECash_Models_CFCApplication extends ECash_Models_Application
{
	const RULE_NAME_PREFIX = 'CFC Default Rule Set -';
	
	public function __construct()
	{
		parent::__construct();
		//set these to overcome non-nullable fields unused by CFC
		$this->is_react = 'no';
		$this->bank_name = '';
		$this->is_watched = 'no';
		$this->schedule_model_id = 0;
		$this->setAgent('ocp');
		$this->setModifyingAgent('ocp');
	}

	public function getAutoIncrement()
	{
		return 'application_id';
	}
	
	public function setLoanType($type_name_short)
	{
		parent::setLoanType($type_name_short);
		$this->setRuleSet(self::RULE_NAME_PREFIX . ucfirst($type_name_short));
	}

}

?>