<html>
<head>
<title>eCash Business Rule Values</title>
<style type="text/css">
	body
	{
		font-family: arial;
		font-size: 10pt;
		color: black;
	}

	table
	{
		font-size:inherit;
		empty-cells: show;
	    border: 0px;
		background: transparent;
		color: #333;
		padding: 0px;
		border-spacing: 0;
	}
	
	th
	{
		padding: 2px 10px 2px 0px;
		border-top: 1px solid #aaa;
		border-bottom: 1px solid #aaa;
		text-align: left;
	}
	
	td
	{
		padding: 2px 10px 2px 0px;	
		text-align: left;
	}
</style>
</head>
<body>

<?php

	/**
	 * eCash Business Rule Dump
	 * 
	 * This is just a little hack to make it easier to view a dump of the more recent
	 * rule sets available on an eCash Instance for all available loan types in all
	 * available companies.  It's far from clean or perfect, but it serves its purpose well.
	 * 
	 * @author Brian Ronald <brian.ronald@sellingsource.com>
	 */
	require_once('config.php');

	if ($_REQUEST['selection'] === 'loan_type' && (isset($_REQUEST['loan_type_id']) && ctype_digit((string) $_REQUEST['loan_type_id'])))
	{
		getLoanTypeValues($_REQUEST['loan_type_id']);
	}
	elseif ($_REQUEST['selection'] === 'application_id' && (isset($_REQUEST['application_id']) && ctype_digit((string) $_REQUEST['application_id'])))
	{
		getApplicationValues($_REQUEST['application_id']);
	}
	else
	{
		showMenuOptions();
	}
	
	function showMenuOptions($type = null, $loan_type = null, $application_id = null)
	{
		$loan_types = array();

		if($type === 'application_id')
		{
			$lt_selected  = '';
			$app_selected = 'checked';
		}
		else
		{
			$lt_selected  = 'checked';
			$app_selected = '';
		}

		echo "<form>";
		echo "<b>Select either a loan type or enter an application_id to retrieve the rule set.</b><br>\n";
		echo "<input type=\"radio\" name=\"selection\" value=\"loan_type\" $lt_selected>\n";
		echo "&nbsp; Loan Type: &nbsp;\n <select name=\"loan_type_id\">\n";

		$loan_types = getLoanTypes();
		
		foreach($loan_types as $lt)
		{
			$name = "({$lt->company_short}) - {$lt->loan_type}";
			
			$selected = ($lt->loan_type_id === $loan_type) ? 'selected' : '';
			
			echo "<option $selected value=\"{$lt->loan_type_id}\">{$name}</option>\n";
		}
		echo "</select><br>\n";
		echo "<input type=\"radio\" name=\"selection\" value=\"application_id\" $app_selected>\n";
		echo "&nbsp; Application ID: <input name=\"application_id\" size=\"12\" value=\"$application_id\"><br>\n";
		echo "<input type=\"submit\" value=\"Submit\">\n";
		echo "</form>\n";
	}
	
	function getLoanTypes()
	{
		$sql = "
		SELECT c.company_id, c.name_short as company_short, lt.name, lt.name_short as loan_type, lt.loan_type_id
		FROM loan_type AS lt
		JOIN company AS c ON (c.company_id = lt.company_id)
		ORDER BY company_id, loan_type_id";

		$db = eCash_Config::getMasterDbConnection();
		$result = $db->query($sql);
		while($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$loan_types[] = $row;
		}
		
		return $loan_types;		
	}
	
	function getLoanTypeValues($loan_type_id)
	{
		showMenuOptions('loan_type', $loan_type_id);
		$ecash_rules = getECashBusinessRuleValues($loan_type_id);
		$cfe_rules   = getCFERuleValues($loan_type_id);
		
		renderECashBusinessRuleValues($ecash_rules);
		renderCFERuleValues($cfe_rules);
	}

	function getApplicationValues($application_id)
	{
		showMenuOptions('application_id', '', $application_id);
		$app_data = getApplicationInfo($application_id);
		
		if(!empty($app_data))
		{
			$ecash_rules = getECashBusinessRuleValues(null, $app_data->rule_set_id);
			$cfe_rules   = getCFERuleValues(null, $app_data->cfe_rule_set_id);
		
			/** Document Data **/
			define('eCash_Document_DIR', LIB_DIR . "Document/");
			require_once eCash_Document_DIR . "ApplicationData.class.php";
			require_once eCash_Document_DIR . "DeliveryAPI/Condor.class.php";
			require_once( SERVER_CODE_DIR . "server_factory.class.php" );

			$server = Server_Factory::get_server_class('skeletal', NULL);
			$server->Load_Company_Config($app_data->company_id);

			$data     = eCash_Document_ApplicationData::Get_Data($server, $application_id);
			$doc_data = eCash_Document_DeliveryAPI_Condor::Map_Data($server, $data);
			/** End of Document Data **/

			renderECashBusinessRuleValues($ecash_rules);
			renderCFERuleValues($cfe_rules);
			renderDocumentDataValues($doc_data);
		}
	}
	
	function getECashBusinessRuleValues($loan_type_id = null, $rule_set_id = null)
	{
		if(! empty($rule_set_id))
		{
			$where = "rscpv.rule_set_id = $rule_set_id";
		}
		else
		{
			$where = "rscpv.rule_set_id = ( SELECT MAX(rule_set_id) FROM rule_set WHERE loan_type_id = $loan_type_id )";
		}
		
		$rule_values = array();
		$sql = "
		SELECT  lt.name as loan_type,
				rscpv.rule_set_id,
		        rc.rule_component_id,
				rc.name_short,
				rcp.rule_component_parm_id,
		        rcp.parm_name,
		        rscpv.parm_value
		FROM    rule_set_component_parm_value AS rscpv
		JOIN 	rule_set AS rs ON rs.rule_set_id = rscpv.rule_set_id
		JOIN	loan_type AS lt ON lt.loan_type_id = rs.loan_type_id
		JOIN    rule_component AS rc ON (rscpv.rule_component_id = rc.rule_component_id)
		JOIN    rule_component_parm AS rcp ON (rscpv.rule_component_parm_id = rcp.rule_component_parm_id)
		WHERE   $where
		ORDER BY rc.rule_component_id, rcp.rule_component_parm_id ";

		$db = eCash_Config::getMasterDbConnection();
		$result = $db->query($sql);
		while($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$rule_values[] = $row;
		}

		return $rule_values;
	}	
	
	function getCFERuleValues($loan_type_id = null, $rule_set_id = null)
	{
		if(! empty($rule_set_id))
		{
			$where = "cr.cfe_rule_set_id = $rule_set_id";
		}
		else
		{
			$where = "cr.cfe_rule_set_id = ( SELECT MAX(cfe_rule_set_id) FROM cfe_rule_set WHERE loan_type_id = $loan_type_id )";
		}
		
		$rule_values = array();
		$sql = "
		SELECT  cr.cfe_rule_set_id,
				cr.cfe_rule_id,
		        cr.name as rule_name,
		        ce.short_name as event,
		        crc.operand1,
		        crc.operator,
		        crc.operand2,
		        ca.name as action
		FROM cfe_rule as cr
		LEFT JOIN cfe_event AS ce ON ce.cfe_event_id = cr.cfe_event_id
		LEFT JOIN cfe_rule_condition AS crc ON crc.cfe_rule_id = cr.cfe_rule_id
		LEFT JOIN cfe_rule_action AS cra ON cra.cfe_rule_id = cr.cfe_rule_id
		LEFT JOIN cfe_action AS ca ON ca.cfe_action_id = cra.cfe_action_id
		WHERE $where
		ORDER BY ce.name, cr.salience, crc.sequence_no ";

		$db = eCash_Config::getMasterDbConnection();
		$result = $db->query($sql);
		while($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$rule_values[] = $row;
		}

		return $rule_values;
	}

	function getApplicationInfo($application_id)
	{
		$sql = "
		SELECT  a.application_id,
				a.company_id,
				c.name_short as company,
				a.loan_type_id,
				lt.name AS loan_type,
		        a.rule_set_id, 
		        a.cfe_rule_set_id
		FROM    application AS a
		JOIN    company AS c ON c.company_id = a.company_id
		JOIN	loan_type AS lt ON lt.loan_type_id = a.loan_type_id
		WHERE   application_id = {$application_id} ";

		$db = eCash_Config::getMasterDbConnection();
		$result = $db->query($sql);
		if($row = $result->fetch(PDO::FETCH_OBJ))
		{
			return $row;
		}

		return NULL;
	
	}
	
	function renderECashBusinessRuleValues($data)
	{
		$loan_type_name = $data[0]->loan_type;
		
		echo "<b>eCash Business Rules for '$loan_type_name' Loan Type</b><br>\n";
		echo '<table>' . "\n";
		echo "<tr>\n";

		echo "<th>Rule Set ID</th>\n";
		echo "<th>Rule Component ID</th>\n";
		echo "<th>Rule Component Name</th>\n";
		echo "<th>Rule Component Parm ID</th>\n";
		echo "<th>Rule Component Parm Name</th>\n";
		echo "<th>Rule Component Value</th>\n";
		echo "</tr>\n";
		
		foreach($data as $v)
		{
			echo "<tr>\n";
			echo "<td>{$v->rule_set_id}</td>\n";
			echo "<td>{$v->rule_component_id}</td>\n";
			echo "<td>{$v->name_short}</td>\n";
			echo "<td>{$v->rule_component_parm_id}</td>\n";
			echo "<td>{$v->parm_name}</td>\n";
			echo "<td>{$v->parm_value}</td>\n";
			echo "</tr>\n";
		}
		echo "</table>\n";
	}	

	function renderCFERuleValues($data)
	{
		echo "<br><hr size='1'><br>\n";
		echo "<b>CFE Business Rules\n";
		echo '<table>' . "\n";
		echo "<tr>\n";
		
		echo "<th>Rule Set ID</th>\n";
		echo "<th>Rule ID</th>\n";
		echo "<th>Rule Name</th>\n";
		echo "<th>Rule Event</th>\n";
		echo "<th>Operand 1</th>\n";
		echo "<th>Operator</th>\n";
		echo "<th>Operand 2</th>\n";
		echo "<th>Action</th>\n";
		echo "</tr>\n";
		
		foreach($data as $v)
		{
			echo "<tr>\n";
			echo "<td>{$v->cfe_rule_set_id}</td>\n";
			echo "<td>{$v->cfe_rule_id}</td>\n";
			echo "<td>{$v->rule_name}</td>\n";
			echo "<td>{$v->event}</td>\n";
			echo "<td>{$v->operand1}</td>\n";
			echo "<td>{$v->operator}</td>\n";
			echo "<td>{$v->operand2}</td>\n";
			echo "<td>{$v->action}</td>\n";
			echo "</tr>\n";
		}
		echo "</table>\n";
	}

	function renderDocumentDataValues($data)
	{
		echo "<br><hr size='1'><br>\n";
		echo "<b>eCash Token Values\n";
		echo '<table>' . "\n";
		echo "<tr>\n";
		
		echo "<th>Token Name</th>\n";
		echo "<th>Value</th>\n";
		echo "</tr>\n";
		
		ksort($data);
		
		foreach($data as $token_name => $value)
		{
			if(preg_match('/Child/', $token_name)) continue;
			
			$value = htmlentities($value);
			
			echo "<tr>\n";
			echo "<td>{$token_name}</td>\n";
			echo "<td>{$value}</td>\n";
			echo "</tr>\n";
		}
		echo "</table>\n";

	}
	
?>
