<?php

	/**
	 * Encapsulates state data from asynchronous rule evaluation
	 *
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
class ECash_CFE_AsynchResult
{
	protected $ruleset_id;
	protected $loan_type_id;
	protected $loan_type_name;
	protected $attributes = array();

	public function __construct($ruleset_id = NULL, array $loan_type = NULL, array $attributes = NULL)
	{
		$this->loan_type_id = $loan_type['loan_type_id'];
		$this->loan_type_name = $loan_type['name_short'];
		$this->loan_type_name_long = $loan_type['name'];
		$this->ruleset_id = $ruleset_id;
		if ($attributes !== NULL) $this->attributes = $attributes;
	}

    public function getIsValid()
    {
    	return ($this->ruleset_id !== NULL && $this->loan_type_id !== NULL);
    }

    public function getRulesetId()
    {
    	return $this->ruleset_id;
    }
    
    public function getLoanTypeId()
    {
    	return $this->loan_type_id;
    }
    
    public function getLoanTypeName()
    {
    	return $this->loan_type_name;
    }
    
    public function getLoanTypeNameLong()
    {
    	return $this->loan_type_name_long;
    }

    public function getAttributes()
    {
    	return $this->attributes;
    }
	}

?>