<?php
/**
 * ECash_Tokens_BusinessRuleTokenElement
 * This class represents a token that is pulled from a database that is
 * determined by a combination of company_id and loan_type_id and references
 * a business rule for it's value
 * 
 */
class ECash_Tokens_BusinessRuleTokenElement extends ECash_Tokens_TokenElement
{
	protected $component;
	protected $componentParm;
	protected $applicationId;
	
	/**
	 * constructs a ApplicationTokenElement
	 * 
	 * @param string name
	 * @param string component
	 * @param string componentParm
	 * @param int companyId
	 * @param int loanTypeId
	 * @param string applicationId
	 * @param int tokenId
	 */
	public function __construct($name = null, $component = null, $componentParm = null, $companyId = null, $loanTypeId = null, $applicationId = null, $tokenId = null, $date_created = null, $date_modified = null)
	{
		$this->name = $name;
		$this->component = $component;
		$this->componentParm = $componentParm;
		$this->tokenId = $tokenId;
		$this->companyId = $companyId;
		$this->loanTypeId = $loanTypeId;
		$this->applicationId = $applicationId;
		$this->date_created = $date_created;
		$this->date_modified = $date_modified;
	}
	/**
	 * This function saves the current Element to the database
	 * 
	 * @return bool whether element was saved
	 * */
	 public function save()
	 {
	 	if(empty($this->companyId) || empty($this->loanTypeId))
	 		return false;
	 		
	 	$model = ECash::getFactory()->getModel('Tokens');
		if(!empty($this->tokenId))
		{
			$model->loadby(array('token_id' => $this->tokenId));
		}
		else
		{
			$model->date_created = date("Y-m-d H:i:s", time());
		}

	 	$model->company_id = $this->companyId;
	 	$model->loan_type_id = $this->loanTypeId;
	 	$model->token_name = $this->name;
	 	$model->value_array = serialize(array('type' => 'business_rule', 'component' => $this->component, 'componentParm' => $this->componentParm));
	
		if($model->save())
	 	{
	 		$this->tokenId = $model->token_id;
			$this->date_created = $model->date_created;
			$this->date_modified = date("Y-m-d H:i:s", time());
	 		return true;
	 	}
	 	else
	 	{
	 		return false;
	 	}
	 	
	 }	
	 /**
	 * returns value
	 * 
	 * @return string value
	 */
	public function getValue()
	{
		return $this->getBusinessRuleReference();
	}
	
	/**
	 * sets the value
	 * 
	 * @param string columnName
	 */
	 public function setValue($component, $componentParm)
	 {
	 	$this->component = $component;
	 	$this->componentParm = $componentParm;
	 }
	/**
	 * returns applicationId
	 * 
	 * @return string ApplicationId
	 */
	public function getApplicationId()
	{
		return $this->applicationId;
	}
	/**
	 * returns Component
	 * 
	 * @return string component
	 */
	public function getComponent()
	{
		return $this->component;
	}
	/**
	 * returns ComponentParam
	 * 
	 * @return string componentparam
	 */
	public function getComponentParam()
	{
		return $this->componentParm;
	}
	/**
	 * sets the ApplicationId
	 * 
	 * @param string applicationId
	 */
	 public function setApplicationId($applicationId)
	 {
	 	$this->applicationId = $applicationId;
	 }
	 /**
	  * returns the value from the business rules of the most current rule set 
	  * for the given loan type id
	  * 
	  * @return string value
	  */
	 public function getBusinessRuleReference()
	 {
	 	if(empty($this->component) || empty($this->componentParm) || empty($this->companyId) || empty($this->loanTypeId))
	 		return null;

	 	if(!empty($this->applicationId))
	 	{
	 		$app = ECash::getApplicationById($this->applicationId);
	 	}	 	
	 	
	 	if(empty($this->applicationId) || !$app->exists())
	 	{	
	 		$rules_cache = new ECash_BusinessRulesCache(ECash::getFactory()->getDB());
	 		$rules = $rules_cache->Get_Latest_Rule_Set($this->loanTypeId);
	 	}
	 	else
	 	{
			$rules = $app->getBusinessRules();
	 	}
	 	
	 	if(empty($rules))
	 	{
	 		return null;
	 	}
	 	else
	 	{
	 		if(isset($rules[$this->component]) && count($rules[$this->component]) == 1)
	 		{
	 			return $rules[$this->component];
	 		}
	 		else
	 		{
	 			if(isset($rules[$this->component]) && isset($rules[$this->component][$this->componentParm]))
	 			{
	 				return $rules[$this->component][$this->componentParm];
	 			}
	 			else
	 			{
	 				return null;
	 			}
	 		}
	 	}
	 }	
	 /**
	  * get the Token Type
	  * 
	  * @return string Type
	  */
	  public function getType()
	  {
	  	return 'business_rule';
	  }	 
}

?>
