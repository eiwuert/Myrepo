<?php

class ECash_Data_Bureau extends ECash_Data_DataRetriever
{
	/**
	 * Grabs common information used in calls to DataX, most commonly Fund Updates.
	 *
	 * @param <int|array> $application_ids - A single application_id or an array of them
	 * @param <string|NULL> $status - A string to pass in the result of the query with the label 'status'
	 * @param <string|NULL> $rule_component - If supplied, used to look up a call type in the rules
	 * @param <string|NULL> $rule_component_parm - If supplied, used to look up a call type in the rules
	 * @return array of stdClass IDV Info indexed by application_id
	 */
	public function getIDVInformation($application_ids, $status = NULL, $rule_component = NULL, $rule_component_parm = NULL)
	{
		if(empty($application_ids)) return array();

		if(!is_array($application_ids)) $application_ids = array($application_ids);

		$query_call_type = "NULL AS call_type";

		if(!empty($rule_component) && !empty($rule_component_parm))
		{
			$query_call_type = "rscpv.parm_value AS call_type";

			$where_call_type = "
			AND rc.name_short = '{$rule_component}'
			AND rcp.parm_name = '{$rule_component_parm}'";
		}

		$application_info = array();

		$insert_list = implode(",",$application_ids);

		$db = ECash::getAppSvcDB();

		/**
		 * How this works: With a stored procedure, there is no good way to pass in
		 * a variable list of arguments such as a list of application_ids that will go
		 * inside an IN() clause, so we have to do some hackery.
		 *
		 * First we're creating a temporary table using a user-defined table type
		 * that the DBA's have created.  Next we insert all of our applications into that
		 * table, which will then be called within a subquery inside the stored procedure.
		 * Finally, we call the stored procedure and pass it the table name as an argument
		 * and it will do all the dirty work inside the stored procedure.
		 */
		 $query = 'CALL sp_idv_fetch_information_by_application_id ("'.$insert_list.'");';

		$result = $db->query($query);
		$rule_set_ids = array();
		while ($app = $result->fetch(DB_IStatement_1::FETCH_OBJ))
		{
			$application_info[$app->application_id] = $app;
			$rule_set_ids[] = $app->rule_set_id;
		}

		$placeholders = substr(str_repeat('?,', count($application_ids)), 0, -1);
		$rule_sets = substr(str_repeat('?,', count($rule_set_ids)), 0, -1);
		//get some info including authentication record
		$due_date_query = "
					SELECT MAX(date_effective) AS due_date,
					application_id
					FROM transaction_register
					WHERE 
					application_id in ({$placeholders})
					group by application_id";

		$rule_set_query = "
			SELECT
				rscpv.parm_value AS call_type,
				rscpv.rule_set_id
			FROM
				rule_set_component_parm_value AS rscpv 
				JOIN rule_component AS rc ON rc.rule_component_id = rscpv.rule_component_id
				JOIN rule_component_parm AS rcp ON rcp.rule_component_parm_id = rscpv.rule_component_parm_id				
			WHERE
				 rscpv.rule_set_id in ({$rule_sets})
				  {$where_call_type}

			";

		$data = array();

		$due_date_result = $this->db->queryPrepared($due_date_query, $application_ids);
		$rule_set_result = $this->db->queryPrepared($rule_set_query, $rule_set_ids);

		$rule_set_array = array();
		while ($app = $rule_set_result->fetch(DB_IStatement_1::FETCH_OBJ))
		{
			$rule_set_array[$app->rule_set_id] = $app->call_type;
		}

		$due_date_array = array();
		while ($app = $due_date_result->fetch(DB_IStatement_1::FETCH_OBJ))
		{
			$due_date_array[$app->application_id] = $app->due_date;
		}

		//some business logic :-/
		foreach($application_info as $id => $row)
		{
			if(empty($row->due_date))
			{
				$row->due_date = $row->date_first_payment;
			}
		
			if(empty($row->fund_date))
			{
				$row->fund_date = date('Y-m-d');
			}
	
			if(!empty($row->received_package))
			{
				$xml_doc = new SimpleXMLElement($row->received_package);
				$track_hash = (string)$xml_doc->TrackHash;
			}
			else
			{
				$track_hash = '';
			}

			$row->track_id   = $row->application_id;
			$row->track_hash = $track_hash;
			$row->fund_fee   = "0.00";
			$row->due_date = $due_date_array[$row->application_id];
			$row->call_type = $rule_set_array[$row->rule_set_id];
			$row->status = $status;

			$data[] = $row;
		}

		return $data;
	}
}
?>
