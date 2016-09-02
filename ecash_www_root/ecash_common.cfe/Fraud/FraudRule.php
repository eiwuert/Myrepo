<?php

require_once('libolution/Object.1.php');
require_once('FraudPrototype.php');
require_once('FraudProposition.php');

class FraudRule extends Object_1
{
	const RULE_TYPE_RISK = 'RISK';
	const RULE_TYPE_FRAUD = 'FRAUD';
	
	private $fraud_rule_id;
	private $date_modified;
	private $date_created;
	private $active;
	private $exp_date;
	private $rule_type;
	private $confirmed;
	private $name;
	private $comments;
	private $proposition;
	private $conditions = array();

	//these are used for display only
	private $modified_agent_name;
	private $created_agent_name;
	
	
	public function __construct($fraud_rule_id,
								$date_modified,
								$date_created,
								$active,
								$exp_date,
								$rule_type,
								$confirmed,
								$name,
								$comments)
	{
		$this->fraud_rule_id = $fraud_rule_id;
		$this->date_modified = $date_modified;
		$this->date_created = $date_created;
		$this->setIsActive($active);
		$this->exp_date = $exp_date;
		$this->rule_type = $rule_type;
		$this->confirmed = $confirmed;
		$this->name = $name;
		$this->comments = $comments;
	}

	public function getFraudRuleID()
	{
		return($this->fraud_rule_id);
	}

	public function setFraudRuleID($fraud_rule_id)
	{
		$this->fraud_rule_id = $fraud_rule_id;
	}

	public function getDateCreated()
	{
		return($this->date_created);
	}

	public function getDateModified()
	{
		return($this->date_modified);
	}

	public function getIsActive()
	{
		return($this->active);
	}

	public function setIsActive($active)
	{
		if($active) //should take care of 'on', '1', and TRUE
			$this->active = '1';
		else
			$this->active = '0';
	}
	
	public function getExpDate()
	{
		return($this->exp_date);
	}

	public function setExpDate($exp_date)
	{
		$this->exp_date = $exp_date;
	}

	public function getName()
	{
		return($this->name);
	}

	public function getComments()
	{
		return($this->comments);
	}
	
	public function addCondition(FraudPrototype $condition)
	{
		$this->conditions[] = $condition;
	}

	public function getConditions()
	{
		return($this->conditions);
	}
	
	public function getIsConfirmed()
	{
		return($this->confirmed);
	}

	public function setIsConfirmed($confirmed)
	{
		if($confirmed) //should take care of 'on', '1', and TRUE
			$this->confirmed = '1';
		else
			$this->confirmed = '0';
	}

	public function getRuleType()
 	{
		return($this->rule_type);
 	}

	public function getColumns()
	{
		$columns = array();
		foreach($this->conditions as $condition)
		{
			if(!$condition instanceof FraudCondition)
				return($condition->getFieldNames());
			$columns[] = $condition->getFieldName();
		}
		return($columns);
	}

	public function setProposition(FraudProposition $proposition)
	{
		$this->proposition = $proposition;
	}

	public function getProposition()
	{
		return($this->proposition);
	}

	public function getModifiedAgentName()
	{
		return($this->modified_agent_name);
	}

	public function setModifiedAgentName($modified_agent_name)
	{
		$this->modified_agent_name = $modified_agent_name;
	}
	
	public function getCreatedAgentName()
	{
		return($this->created_agent_name);
	}
	
	public function setCreatedAgentName($created_agent_name)
	{
		$this->created_agent_name = $created_agent_name;
	}		
}

?>