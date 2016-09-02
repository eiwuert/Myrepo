<?php
/**
 * ECash_Tokens_TokenManager
 * This manages the tokens that are pulled from the database
 * based on company and loan types
 * 
 * 	//instance token manager
	$manager = ECash::getFactory()->getTokenManager();

	//creation of a universal static token
	$new = $manager->getNewToken('static');
	$new->setName('test');
	$new->setValue('foshizzle1');
	$new->save();
	//creation of a company static token
	$new2 = $manager->getNewToken('static');
	$new2->setName('test');
	$new2->setValue('foshizzle2');
	$new2->setCompanyId(1);
	$new2->save();
	//creation of a loan type static token
	$new3 = $manager->getNewToken('static');
	$new3->setName('test');
	$new3->setValue('foshizzle3');
	$new3->setCompanyId(1);
	$new3->setLoanTypeId(22);
	$new3->save();
	//creation of a loan type application token
	$new4 = $manager->getNewToken('application');
	$new4->setName('loan first name');
	$new4->setValue('name_first');
	$new4->setCompanyId(1);
	$new4->setLoanTypeId(22);
	$new4->save();
	//creation of a loan type business rule token
	$new5 = $manager->getNewToken('business_rule');
	$new5->setName('IDV');
	$new5->setValue('IDV_CALL', 'IDV_CALL');
	$new5->setCompanyId(1);
	$new5->setLoanTypeId(22);
	$new5->save();

	//get tokens for a company
	$tokens = $manager->getTokensByCompanyId(1);	
	echo "get by company:\n";
	foreach($tokens as $name => $token)
	{
		echo "\nToken: " . $name;
		echo "\nValue: " . $token->getValue() . "\n";
	}
	//get tokens for a loan type
	echo "\nget by loan type:\n";
	$tokens = $manager->getTokensByLoanTypeId(1,22);	
	foreach($tokens as $name => $token)
	{
		echo "\nToken: " . $name;
		echo "\nValue: " . $token->getValue() . "\n";
	}	
	//get tokens by application and/or company loan type
	echo "\nget by Application:\n";
	$tokens = $manager->getTokensByApplicationId(119701,'nsc','standard');	
	foreach($tokens as $name => $token)
	{
		echo "\nToken: " . $name;
		echo "\nValue: " . $token->getValue() . "\n";
	}	
	
	//deleting tokens	
	$new->delete();
	$new2->delete();
	$new3->delete();
	$new4->delete();
	$new5->delete();	
 * 
 * 
 */
 
class ECash_Tokens_TokenManager
{
	/**
	 * return a empty Token Elment of a certain type
	 * 
	 * @param string type
	 * 
	 * @return ECash_Tokens_TokenElement
	 */
	public function getNewToken($type = 'static')
	{
		switch($type)
		{
			case 'static':
				return new ECash_Tokens_TokenElement();
			break;
			
			case 'application':
				return new ECash_Tokens_ApplicationTokenElement();
			break;
			
			case 'business_rule':
				return new ECash_Tokens_BusinessRuleTokenElement();
			break;
			
			default:
				throw new exception('token type does not exist');
			break;
		}
		
	}
	/**
	 * converts a TokensList model to an array of ECash_Tokens_TokenElement
	 * 
	 * @param ECash_Models_TokensList $model
	 * 
	 * @return array ECash_Tokens_TokenElement
	 */
	protected function toTokens(ECash_Models_TokensList $model, $applicationId = null)
	{
		
		$tokenList = array();
		foreach($model as $row)
		{
			$value_array = unserialize($row->value_array);
			switch($value_array['type'])
			{
				case 'static':
					if($this->shouldCreate($row, $tokenList))
					{
						$tokenList[$row->token_name] = new ECash_Tokens_TokenElement($row->token_name, $value_array['value'], $row->company_id, $row->loan_type_id, $row->token_id, $row->date_created, $row->date_modified);
					}
				break;
				case 'application':
					if($this->shouldCreate($row, $tokenList))
					{					
						$tokenList[$row->token_name] = new ECash_Tokens_ApplicationTokenElement($row->token_name, $value_array['columnName'], $row->company_id, $row->loan_type_id, $applicationId, $row->token_id, $row->date_created, $row->date_modified);
					}
				break;
				case 'business_rule':
					if($this->shouldCreate($row, $tokenList))
					{
						$tokenList[$row->token_name] = new ECash_Tokens_BusinessRuleTokenElement($row->token_name, $value_array['component'], $value_array['componentParm'], $row->company_id, $row->loan_type_id, $applicationId, $row->token_id, $row->date_created, $row->date_modified);
					}
				break;
				default:
				//this row is broke
				break;
			}
			
		}
		return $tokenList;
	}
	/**
	 * Determines precedence for the tokens
	 * There are three levels of tokens to be defined
	 * Universal, Company and Loan Type
	 *	
	 * Universal is defined as company_id and loan_type_id being NULL
	 * Company is defined as company_id being an int and loan_type_id being NULL
	 * Loan Type is defined as company_id being an int and loan_type_id being an int
	 *	
	 * If a name is shared between the levels, Loan Type takes precedence over Company 
	 * and Company takes precedence over Universal
	 *
	 *@param stdclass $row
	 *@param array tokenList
	 *
	 *@return bool  
	 */
	protected function shouldCreate($row, $tokenList)
	{
			if(isset($tokenList[$row->token_name]))
			{
				if(empty($row->company_id) && empty($row->loan_type_id))
				{
					//current row is universal so anything that is already set takes precedence
					return false;
				}
				elseif(!empty($row->company_id) && empty($row->loan_type_id))
				{
					//current row is Company level will only overwrite a universal
					$current_company_id = $tokenList[$row->token_name]->getCompanyId();
					$current_loan_type_id = $tokenList[$row->token_name]->getLoanTypeId();
					if(empty($current_company_id) && empty($current_loan_type_id))
					{
						return true;
					}
					else
					{
						return false;
					}
				}	
				else
				{
					//current row is loan type level so it should overwrite anything currently set
					return true;
				}
			}
			else
			{
				return true;
			}	
	}
	/**
	 * retrieves all the tokens for a company
	 * 
	 * @param int companyId
	 * 
	 * @return array ECash_Tokens_TokenElement
	 */
	 public function getTokensByCompanyId($companyId = 0, $getLowerTier = true)
	 {
	 	$model = ECash::getFactory()->getModel('TokensList');
	 	$model->loadByCompany($companyId, $getLowerTier);
	 	return $this->toTokens($model);
	 }
	 
	/**
	 * retrieves all the tokens for a Loan Type Id
	 * 
	 * @param int companyId
	 * @param int loanTypeId
	 * 
	 * @return array ECash_Tokens_TokenElement
	 */
	 public function getTokensByLoanTypeId($companyId = 0, $loanTypeId = 0, $applicationId = null, $getLowerTier = true)
	 {
	 	$model = ECash::getFactory()->getModel('TokensList');
	 	$model->loadByLoanType($companyId, $loanTypeId, $getLowerTier);
	 	return $this->toTokens($model, $applicationId);	 	
	 }
	 
	/**
	 * retrieves a token by name for a company or loanTypeId
	 * 
	 * @param string name
	 * @param int company_id
	 * @param int loanTypeId
	 * 
	 * @return ECash_Tokens_TokenElement
	 */
	 public function getToken($name, $company_id = 0, $loanTypeId = 0, $applicationId = null)
	 {
	 	$tokens = $this->getTokensByLoanTypeId($company_id, $loanTypeId, $applicationId);
	 	return $tokens[$name];
	 }
	/**
	 * retrieves a token by database Id
	 * 
	 * @param int id
	 * 
	 * @return ECash_Tokens_TokenElement
	 */
	 public function getTokenById($id, $applicationId = null)
	 {
	 	$model = ECash::getFactory()->getModel('TokensList');
	 	$model->loadBy(array('token_id' => $id));
	 	return array_pop($this->toTokens($model, $applicationId));	
	 }	
	/**
	 * retrieves all tokens by application or a company or loanType
	 *  
	 * @param string applicationId
	 * @param string company
	 * @param string loanType
	 * 
	 * @return array ECash_Tokens_TokenElement
	 */
	 public function getTokensbyApplicationId($applicationId, $company = null, $loanType = null)
	 {
	 	$app = ECash::getApplicationById($applicationId);
	 	if($app->exists())
	 	{
	 		$companyId = $app->company_id;
	 		$loanTypeId = $app->loan_type_id;
	 	}
	 	else
	 	{
		 	$company_model = ECash::getFactory()->getModel('Company');
		 	$loan_type_model = ECash::getFactory()->getModel('LoanType');
		 	if(!$company_model->loadBy(array('name_short' => $company)))
		 	{
		 		return array();
		 	}
		 	if(!$loan_type_model->loadBy(array('name_short' => $loanType)))
		 	{
		 		return array();
		 	}
	 		$companyId = $company_model->company_id;
	 		$loanTypeId = $loan_type_model->loan_type_id;
	 	}
	 	return $this->getTokensByLoanTypeId($companyId, $loanTypeId, $applicationId);
	 }	 
	
}



?>
