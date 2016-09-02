<?php
/**
 * ECash_Tokens_ApplicationTokenElement
 * This class represents a token that is pulled from a database that is
 * determined by a combination of company_id and loan_type_id and references
 * a column in the application object for it's value
 * 
 */
class ECash_Tokens_ApplicationTokenElement extends ECash_Tokens_TokenElement
{
	protected $columnName;
	protected $applicationId;
	
	/**
	 * constructs a ApplicationTokenElement
	 * 
	 * @param string name
	 * @param string columnName
	 * @param int companyId
	 * @param int loanTypeId
	 * @param string applicationId
	 * @param int tokenId
	 */
	public function __construct($name = null, $columnName = null, $companyId = null, $loanTypeId = null, $applicationId = null, $tokenId = null, $date_created = null, $date_modified = null)
	{
		$this->name = $name;
		$this->columnName = $columnName;
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
	 	$model->value_array = serialize(array('type' => 'application', 'columnName' => $this->columnName));

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
		return $this->getApplicationReference();
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
	 * returns columnName
	 * 
	 * @return string columnName
	 */
	public function getColumnName()
	{
		return $this->columnName;
	}
	/**
	 * sets the value
	 * 
	 * @param string columnName
	 */
	 public function setValue($columnName)
	 {
	 	$this->columnName = $columnName;
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
	  * returns the value from the application object of the reference column
	  * 
	  * @return string value
	  */
	 public function getApplicationReference()
	 {
	 	if(empty($this->applicationId) || empty($this->columnName))
	 		return null;
	 	$app = ECash::getApplicationById($this->applicationId);
	 	
	 	if($app->exists())
	 	{
	 		if(in_array($this->columnName, $app->getModel()->getColumns()))
	 		{
	 			return $app->{$this->columnName};
	 		}
	 		else
	 		{
	 			return null;
	 		}
	 	}
	 	else
	 	{
	 		return null;
	 	}
	 	
	 }
	 /**
	  * get the Token Type
	  * 
	  * @return string Type
	  */
	  public function getType()
	  {
	  	return 'application';
	  }	 
}

?>
