<?php

/**
 * Class to encapsulate an eCash loan type, rule set, and
 * the business rules therein.  The main purpose of this class is
 * just to give easy access to the ids and names of loan types and
 * rule sets.
 * 
 * @author Chris Barmonde <christopher.barmonde@sellingsource.com>
 *
 */
class OLPECash_LoanType
{
	/**
	 * Payday loan.
	 *
	 */
	const TYPE_PAYDAY = 'payday';
	
	/**
	 * Title loan.
	 *
	 */
	const TYPE_TITLE = 'title';
	
	/**
	 * Card loan.
	 *
	 */
	const TYPE_CARD = 'card';
	
	/**
	 * LDB Connection
	 *
	 * @var DB_Database_1
	 */
	private $ldb;
	
	/**
	 * Property short for the current eCash company.
	 *
	 * @var string
	 */
	protected $property_short;
	
	/**
	 * ECash_BusinessRules class
	 *
	 * @var ECash_BusinessRules
	 */
	protected $business_rules;
	
	/**
	 * The current loan type.  It should match with
	 * one of the constants in this class.
	 *
	 * @var string
	 */
	protected $loan_type;
	
	/**
	 * The eCash short name for the loan type
	 *
	 * @var string
	 */
	protected $loan_type_short;
	
	/**
	 * The eCash full name for the loan type
	 *
	 * @var string
	 */
	protected $loan_type_name;
	
	/**
	 * The ID inside of eCash for the loan type
	 *
	 * @var int
	 */
	protected $loan_type_id;
	
	/**
	 * Rule Set ID from eCash for the given loan type and property
	 *
	 * @var int
	 */
	protected $rule_set_id;
	
	/**
	 * Has all the rules for the current rule set.
	 * 
	 * @var array
	 */
	protected $rule_set;
	
	/**
	 * Constructor
	 *
	 * @param string $property_short Prop short for the company
	 * @param DB_Database_1 $ldb Database connection LDB
	 * @param string $loan_type One of the TYPE constants from this class
	 */
	public function __construct($property_short, DB_Database_1 $ldb, $loan_type = self::TYPE_PAYDAY)
	{
		$this->property_short = $property_short;
		$this->loan_type = $loan_type;
		
		$this->ldb = $ldb;
		$this->business_rules = new ECash_BusinessRules($this->ldb);
		
		$this->loan_type_short = self::getLoanTypeShort($property_short, $loan_type);
		$this->getRuleSet();
	}
	
	/**
	 * Returns the eCash loan_type for a given application based on property short
	 * and the type of loan we have (payday, title, card, etc).
	 *
	 * @param string $property_short Company we're looking for
	 * @param string $loan_type Type of loan we have.  Use the constants defined in this class
	 * 		in order to populate this (TYPE_PAYDAY, TYPE_TITLE, TYPE_CARD)
	 * @return string
	 */
	public static function getLoanTypeShort($property_short, $loan_type = self::TYPE_PAYDAY)
	{
		$loan_type_short = 'standard';

		if (EnterpriseData::isCompanyProperty(EnterpriseData::COMPANY_GENERIC, $property_short)
			|| EnterpriseData::isCompanyProperty(EnterpriseData::COMPANY_LCS, $property_short))
		{
			$loan_type_short = 'payday_loan';
		}

		return $loan_type_short;
	}
	
	/**
	 * Returns the current rule set for the specified loan type and company
	 *
	 * @param bool $reload Whether or not to force a reload of the rule set.
	 * @return array
	 */
	public function getRuleSet($reload = FALSE)
	{
		if (empty($this->rule_set) || $reload)
		{
			$query = "SELECT
						lt.loan_type_id,
						lt.name,
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
	
			$stmt = $this->ldb->queryPrepared($query, array($this->property_short, $this->loan_type_short));
			if ($row = $stmt->fetch(PDO::FETCH_OBJ))
			{
				$this->loan_type_id = $row->loan_type_id;
				$this->loan_type_name = $row->name;
				$this->rule_set_id = $row->rule_set_id;
				
				$this->rule_set = $this->business_rules->Get_Rule_Set_Tree($this->rule_set_id);
			}
		}

		return $this->rule_set;
	}
	
	/**
	 * Magic function to return class variables
	 *
	 * @param string $name Name of the variable
	 * @return mixed
	 */
	public function __get($name)
	{
		$value = NULL;
		
		if (isset($this->$name))
		{
			$value = $this->$name;
		}
		
		return $value;
	}
}

?>