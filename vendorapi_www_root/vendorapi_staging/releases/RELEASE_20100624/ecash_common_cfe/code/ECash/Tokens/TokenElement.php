<?php
/**
 * ECash_Tokens_TokenElement
 * This class represents a token that is pulled from a database that is
 * determined by a combination of company_id and loan_type_id
 * 
 */
class ECash_Tokens_TokenElement
{
	protected $name;
	protected $value;
	protected $tokenId;
	protected $companyId;
	protected $loanTypeId;
	protected $date_created;
	protected $date_modified;
	
	/**
	 * constructs a TokenElement
	 * 
	 * @param string name
	 * @param string value
	 * @param int companyId
	 * @param int loanTypeId
	 * @param int tokenId
	 */
	public function __construct($name = null, $value = null, $companyId = null, $loanTypeId = null, $tokenId = null, $date_created = null, $date_modified = null)
	{
		$this->name = $name;
		$this->value = $value;
		$this->tokenId = $tokenId;
		$this->companyId = $companyId;
		$this->loanTypeId = $loanTypeId;
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
	 	$model = ECash::getFactory()->getModel('Tokens');
		if(!empty($this->tokenId))
		{
			$model->loadby(array('token_id' => $this->tokenId));
		}
		else
		{
			$model->date_created = date("Y-m-d H:i:s", time());
		}

	 	$model->company_id = empty($this->companyId) ? 0 : $this->companyId;
	 	$model->loan_type_id = empty($this->loanTypeId) ? 0 : $this->loanTypeId;
	 	$model->token_name = $this->name;
	 	$model->value_array = serialize(array('type' => 'static', 'value' => $this->value));
		
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
	 * This function deletes the current Element from the database
	 * 
	 * @return bool whether element was deleted
	 */
	public function delete()
	{
		if(is_null($this->tokenId))
			return false;
		
		$model = ECash::getFactory()->getModel('tokens');
		$model->token_id = $this->tokenId;
		return $model->delete();
	}
	/**
	 * returns value
	 * 
	 * @return string value
	 */
	public function getValue()
	{
		return $this->value;
	}
	/**
	 * returns name
	 * 
	 * @return string name
	 */
	public function getName()
	{
		return $this->name;
	}
	/**
	 * returns database ID
	 * 
	 * @return int Id
	 */
	public function getId()
	{
		return $this->tokenId;
	}
	/**
	 * returns loan type id
	 * 
	 * @return int loanTypeId
	 */
	public function getLoanTypeId()
	{
		return $this->loanTypeId;
	}
	/**
	 * returns company id
	 * 
	 * @return int companyId
	 */
	public function getCompanyId()
	{
		return $this->companyId;
	}
	/**
	 * sets the value
	 * 
	 * @param string value
	 */
	 public function setValue($value)
	 {
	 	$this->value = $value;
	 }
	/**
	 * sets the name
	 * 
	 * @param string name
	 */
	 public function setName($name)
	 {
	 	$this->name = $name;
	 }
	/**
	 * sets the tokenId
	 * 
	 * @param int Id
	 */
	 public function setId($Id)
	 {
	 	$this->tokenId = $Id;
	 }	
	/**
	 * sets the companyId
	 * 
	 * @param int companyId
	 */
	 public function setCompanyId($companyId)
	 {
	 	$this->companyId = $companyId;
	 }
	/**
	 * sets the loanTypeId
	 * 
	 * @param int loanTypeId
	 */
	 public function setLoanTypeId($loanTypeId)
	 {
	 	$this->loanTypeId = $loanTypeId;
	 }
	 /**
	  * get the Token Type
	  * 
	  * @return string Type
	  */
	  public function getType()
	  {
	  	return 'static';
	  }
	 /**
	  * get the Token date created
	  * 
	  * @return timestamp
	  */
	  public function getDateCreated()
	  {
	  	return $this->date_created;
	  }
	 /**
	  * get the Token Type
	  * 
	  * @return timestamp
	  */
	  public function getDateModified()
	  {
	  	return $this->date_modified;
	  }
}





?>
