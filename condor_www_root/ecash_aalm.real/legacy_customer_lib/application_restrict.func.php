<?php

require_once(SERVER_CODE_DIR . 'server.class.php');

// Application restriction functions
function getSearchControlRestrictions()
{
	$control_wheres = array();
	$control_joins = array();
	$unions = array();
	static $ret_array;

	if ($ret_array != NULL) {
		return $ret_array;
	}

	$company_id = ECash::getCompany()->company_id;
	$agent_id	= ECash::getAgent()->getAgentId();

	if (method_exists(Server::Get_ACL(),'Get_Control_Info'))
	{
		$control_options = Server::Get_ACL()->Get_Control_Info($agent_id, $company_id);
	
		if(!empty($control_options) && eCash_Config::getInstance()->HAS_LOANTYPE_RESTRICTION)
		{
			foreach(Server::Get_ACL()->getAllowedCompanyIDs() as $company_id) 
			{
				if ($company_id != NULL)  {
					$control_options = Server::Get_ACL()->Get_Control_Info($agent_id, $company_id);
					$unions[] = "SELECT company_id, loan_type_id FROM loan_type WHERE loan_type.company_id = $company_id  AND loan_type.name_short IN ('". implode("','", $control_options) . "')";
				}
			}
			$control_joins['restrict'] = '(' . implode("\nUNION\n", $unions) . ") restrictions \nON (app.company_id = restrictions.company_id AND app.loan_type_id = restrictions.loan_type_id)\n";
			$control_wheres['restrict'] = "restrictions.loan_type_id IS NOT NULL\n";
		}
		else
		{
			if(Server::Get_ACL()->getAllowedCompanyIDs())
			{
				$control_wheres['company_id'] = "lt.company_id IN ('". implode("','", Server::Get_ACL()->getAllowedCompanyIDs()) . "')";
			}
		}

# This is commented out because the two queries that use this table have been set to have it automatically.  
# The main application loan type retrieval needed the data too.
# This module code is used only in search_query and application.func.php
//	$control_joins['loan_type'] = "loan_type lt ON (app.loan_type_id = lt.loan_type_id)";

		$ret_array = array('where'=>$control_wheres, 'join' => $control_joins);
	}
	else
		$ret_array = array();

	return $ret_array;
}

?>
