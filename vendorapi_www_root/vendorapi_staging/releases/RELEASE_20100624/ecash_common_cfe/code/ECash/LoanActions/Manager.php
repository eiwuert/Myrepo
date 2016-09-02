<?php

  /**
   * @todo this should use the factory
   */

class ECash_LoanActions_Manager extends Object_1
{

    public function getLoanActions()
    {
	return new ECash_LoanActions_LoanActions();
    }
    
    public function getLoanActionSection()
    {
	return new ECash_LoanActions_LoanActionSection();
    }

    public function getLoanActionSectionRelation()
    {
	return new ECash_LoanActions_LoanActionSectionRelation();
    }

    public function getLoanActionProcess()
    {
	return new ECash_LoanActions_LoanActionProcess();
    } 
    
    public function getLoanActionHistory()
    {
	return new ECash_LoanActions_LoanActionHistory();
    }     
}

?>
