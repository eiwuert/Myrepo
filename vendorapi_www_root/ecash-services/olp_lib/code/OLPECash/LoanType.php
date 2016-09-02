<?php

/**
 * Class to encapsulate an eCash loan type, rule set, and
 * the business rules therein.  This class alows persistence
 * through the loan_type for an application
 * 
 * @author Adam Englander <adam.englander@sellingsource.com>
 *
 */
class OLPECash_LoanType
{
	/**
	 * Company level rules
	 */
	const TYPE_COMPANY = 'company_level';
	
	/**
	 * Has all the rules for the current rule set.
	 * 
	 * @var array
	 */
	protected $rule_set;
	
	/**
	 * Rule set id
	 *
	 * @var int
	 */
	protected $rule_set_id;
	
	/**
	 * Application ID
	 *
	 * @var int
	 */
	protected $application_id;
	
	/**
	 * Loan type ID
	 *
	 * @var int
	 */
	protected $loan_type_id; 

	/**
	 * ApplicationValue model for rule_type_short
	 *
	 * @var DB_Models_Decorator_ReferencedWritableModel_1
	 */
	protected $loan_type_short_model; 
	
	/**
	 * eCash database connection
	 *
	 * @var DB_Database_1
	 */
	protected $ldb;
	
	/**
	 * Array of templates from the businesss rules
	 *
	 * @var array
	 */
	protected $templates;
	
	/**
	 * eCash Company name short
	 *
	 * @var string
	 */
	protected $company_name_short;
	
	/**
	 * Constructor
	 *
	 * @param DB_Database_1 $ldb Database connection for storing/retrieving loan type information
	 * @param string $company_name_short eCash Company name short
	 * @param DB_Models_Decorator_ReferencedWritableModel_1 $application_value
	 * @return void
	 */
	public function __construct(
		DB_Database_1 $ldb,
		$company_name_short,
		DB_Models_Decorator_ReferencedWritableModel_1 $application_value
	)
	{
		$this->loan_type_short_model = $application_value;
		$this->company_name_short = $company_name_short;
		$this->ldb = $ldb;
	}
	
	/**
	 * Returns the eCash loan_type name short for a given application.
	 *
	 * @return string
	 */
	public function getLoanTypeShort()
	{
		return $this->loan_type_short_model->value;
	}
	
	/**
	 * Sets the eCash loan_type name short for a given application.
	 *
	 * @param string $loan_type_short
	 * @return bool
	 */
	public function setLoanTypeShort($loan_type_short)
	{
		$this->loan_type_short_model->value = $loan_type_short;
		return TRUE;
	}
	
	/**
	 * Returns the eCash loan_type ID for a given application.
	 *
	 * @return int
	 */
	public function getLoanTypeID()
	{
		if (empty($this->loan_type_id) && $this->getLoanTypeShort() !== NULL)
		{
			$this->loadByLoanTypeShort($this->getLoanTypeShort());
		}
		return $this->loan_type_id;
	}
	
	/**
	 * Sets the eCash loan_type ID for a given application.
	 *
	 * @param int $loan_type_id
	 * @return bool
	 */
	public function setLoanTypeID($loan_type_id)
	{
		$this->loan_type_id = $loan_type_id;
		return TRUE;
	}
	
	/**
	 * Sets the eCash rule set ID for a given application.
	 *
	 * @param int $rule_set_id
	 * @return bool
	 */
	public function setRuleSetID($rule_set_id)
	{
		$this->rule_set_id = $rule_set_id;
		return TRUE;
	}
	
	/**
	 * Ruleset ID
	 *
	 * @return int
	 */
	public function getRuleSetID()
	{
		if (empty($this->rule_set_id) && $this->getLoanTypeShort() !== NULL)
		{
			$this->loadByLoanTypeShort($this->getLoanTypeShort());
		}
		return $this->rule_set_id;
	}
	
	/**
	 * Sets the application ID for a given application.
	 *
	 * @param int $application_id
	 * @return bool
	 */
	public function setApplicationID($application_id)
	{
		$this->application_id = $application_id;
		return TRUE;
	}
	
	/**
	 * Get the application ID
	 *
	 * @return int
	 */
	public function getApplicationID()
	{
		return $this->application_id;
	}
	
	/**
	 * Returns the current rule set for the specified loan type and company
	 *
	 * @param bool $reload Whether or not to force a reload of the rule set.
	 * @return array
	 */
	public function getRuleSet($reload = FALSE)
	{
		if ($reload || empty($this->rule_set))
		{
			$this->loadRuleSet();
		}

		return $this->rule_set;
	}
	
	/**
	 * Get the eCash business rules class object
	 *  his function is meant to abstract retrieving the object for unit tests 
	 * 
	 * @return ECash_BusinessRules
	 */
	protected function getEcashBusinessRules()
	{
		return new ECash_BusinessRules($this->ldb);
	}
	
	/**
	 * Load the rule set based on the current model state
	 *
	 * @return void
	 */
	protected function loadRuleSet()
	{
		$rule_set_id = $this->getRuleSetID();
		$loan_type_id = $this->getLoanTypeID();
		$business_rules = $this->getEcashBusinessRules();
		$application_id = $this->getApplicationID();
		// If there is no ruleset ID set but there is an application_id, try to get the ruleset for the application first
		if (empty($rule_set_id) && !empty($application_id))
		{
			$rule_set_id = $business_rules->Get_Rule_Set_Id_For_Application($this->getApplicationID());
		}
		
		// If we still have no rule set ID but have a loan type id, try and get the latest rule set ID
		if (empty($rule_set_id) && !empty($loan_type_id))
		{
			// If the application is not is LDB yet, get the latest rule set ID as it likely has not changed
			$rule_set_id = $business_rules->Get_Current_Rule_Set_Id($loan_type_id);
		}
		
		// If we have a rule set, get the tree
		if (!empty($rule_set_id))
		{
			// Since we have a reul set ID, set it just in case it's not set
			$this->setRuleSetID($rule_set_id);
			$this->rule_set = $business_rules->Get_Rule_Set_Tree($rule_set_id);
		}
		// Otherwise return an empty array
		else
		{
			$this->rule_set = array();
		}
	}
	
	/**
	 * Save the rule set information.  Returns a boolean success indicator
	 *
	 * @return bool
	 */
	public function save()
	{
		$this->loan_type_short_model->application_id = $this->getApplicationID();
		$this->loan_type_short_model->name = 'loan_type_short';
		return $this->loan_type_short_model->save();
	}
	
	/**
	 * Load the model data by application ID.  Returns a boolean success indicator
	 *
	 * @param int $application_id Application ID
	 * @return bool
	 */
	public function loadByApplicationID($application_id)
	{
		$this->setApplicationID($application_id);

		$success = $this->loan_type_short_model->loadBy(
			array(
				'application_id' => $application_id,
				'name' => 'loan_type_short'));

		/*
		 * legacy is to account for applications created before
		 * the refactor of OLPECash_LoanType.
		 * 
		 * @todo Remove this functionality 4 weeks after
		 * the initial implementation of the refactor
		 */
		if (!$success)
		{
			$success = $this->legacy();
		}
		return $success;
	}
	
	/**
	 * Load the model by Loan Type Short.  Returns a boolean success indicator
	 *
	 * @param string $loan_type_short
	 * @return bool
	 */
	public function loadByLoanTypeShort($loan_type_short)
	{
		$this->setLoanTypeShort($loan_type_short);
		$query = "SELECT
					lt.loan_type_id,
					lt.name_short,
					rs.rule_set_id
				FROM loan_type lt
				INNER JOIN rule_set rs USING (loan_type_id)
				WHERE lt.company_id = (
					SELECT company_id
					FROM company
					WHERE name_short = ?
					AND active_status = 'active'
				)
				AND lt.name_short = ?
				AND lt.active_status = 'active'
				ORDER BY rs.date_effective DESC LIMIT 1";

		$stmt = $this->ldb->queryPrepared($query, array($this->company_name_short, $loan_type_short));

		if ($row = $stmt->fetch(PDO::FETCH_OBJ))
		{
			$this->setLoanTypeID($row->loan_type_id);
			// update the loan type short with the value from the database
			$this->setLoanTypeShort($row->name_short);
			$this->setRuleSetID($row->rule_set_id);
		}
		return TRUE;
	}
	
	/**
	 * Returns the OLP Templates for the current loan_type
	 *
	 * @return array
	 */
	public function getTemplates()
	{
		$ruleset = $this->getRuleSet();
		$templates = (isset($ruleset['olp_templates'])) ? $ruleset['olp_templates'] : array();
		return $templates;
	}
	
	/**
	 * Get the OLP Template value for a given tmplate name 
	 *
	 * @param string $template_name
	 * @return string
	 */
	public function getTemplateValue($template_name)
	{
		$templates = $this->getTemplates();
		$template = (isset($templates[$template_name])) ? $templates[$template_name] : NULL;
		return $template;
	}
	
	/**
	 * Provide legacy backward compatibility to account for applications created before
	 * the refactor of OLPECash_LoanType.
	 * 
	 * @return void
	 * @todo Remove this functionality 4 weeks after
	 * the initial implementation of the refactor
	 *
	 */
	private function legacy()
	{
		$success = TRUE;
		if ($this->getLoanTypeShort() == NULL)
		{
			$query = "SELECT
						a.loan_type_id,
						lt.name_short,
						a.rule_set_id
					FROM application a
					INNER JOIN loan_type lt USING (loan_type_id)
					WHERE a.application_id = ?";
	
			$stmt = $this->ldb->queryPrepared($query, array($this->getApplicationID()));
	
			if ($row = $stmt->fetch(PDO::FETCH_OBJ))
			{
				$this->setLoanTypeID($row->loan_type_id);
				// update the loan type short with the value from the database
				$this->setLoanTypeShort($row->name_short);
				$this->setRuleSetID($row->rule_set_id);
				$success = $this->save();
			}
		}
		return $success;
	}
}

?>