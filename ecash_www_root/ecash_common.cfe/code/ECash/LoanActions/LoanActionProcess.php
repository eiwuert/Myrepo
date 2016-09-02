<?php

	class ECash_LoanActions_LoanActionProcess 
	{
	protected $model;
	
	protected $return_array;
	
	
	private function getLoanActionProcesses($where_arg = array())
	{
		$loan_actions = array();
		$asf 	= ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
	    $la 	= ECash::getFactory()->getModel('LoanActionsList');        	
		$las 	= ECash::getFactory()->getModel('LoanActionSectionList');
		$lap 	= ECash::getFactory()->getModel('LoanActionSectionRelation');
	    $model 	= ECash::getFactory()->getModel('LoanActionProcessList');
	    
	    $la->loadBy(array());  
	    foreach($la as $item)
	    {
		$loan_actions[$item->column_data['loan_action_id']] = $item->column_data;
	    }                 

	    $las->loadBy(array());  
	    foreach($las as $item)
	    {
		$loan_action_section[$item->column_data['loan_action_section_id']] = $item->column_data;
	    }           
		   
	    $model->loadBy($where_arg);            
	    foreach($model as $item)
	    {
			$item_data	= $item->column_data;
			$key = $item_data["loan_action_section_relation_id"];
			$lap->loadBy(array("loan_action_section_relation_id" => $key));

			$return_array[$key]["loan_action_section_relation_id"] 	= $key;
			$return_array[$key]["loan_action_id"] 					= $lap->loan_action_id;
			$return_array[$key]["loan_action_name"] 				= $loan_actions[$lap->loan_action_id]["description"];
			$return_array[$key]["loan_action_section_id"] 			= $lap->loan_action_section_id;
			$return_array[$key]["loan_action_section_name"] 		= $loan_action_section[$lap->loan_action_section_id]["description"];            		
			$return_array[$key]["application_status_id"] 			= $item_data['application_status_id'];
			$return_array[$key]["application_status_name"] 			= $asf[$item_data['application_status_id']]->level0_name;
			$return_array[$key]["current_application_status_id"] 	= ($item_data['current_application_status_id'] > 1) ? $item_data['current_application_status_id'] : null;
			$return_array[$key]["current_application_status_name"] 	= ($item_data['current_application_status_id'] > 1) ? $asf[$item_data['current_application_status_id']]->level0_name : null;            		

	    }      
 
	    return $return_array;        	
	}
	
	public function getAllLoanActionProcesses()
	{
		return $this->getLoanActionProcesses();
	}
	}

?>
