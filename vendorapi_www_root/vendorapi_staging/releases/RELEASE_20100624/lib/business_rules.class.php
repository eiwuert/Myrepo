<?php

class Business_Rules
{

	public function __construct($mysqli = NULL)
	{
		if($mysqli == NULL)
		{
			$this->mysqli = new MySQLi_1(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		}
		else
		{
			$this->mysqli = $mysqli;
		}
	}	
	
	public function Create_New_Rule_Set($loan_type_id, $rule_set_id)
	{
		$query = "select
					active_status,
					name
				from
				 	rule_set
				where
					loan_type_id = ".$loan_type_id."
					and rule_set_id = ".$rule_set_id;

		$name = '';
		$active_status = '';
		$result = $this->mysqli->Query($query);
		$row = $result->Fetch_Object_Row();
		if (count($row))
		{
			if (substr($row->name, -3, 1) == ":")
			{
				//echo 'here';
				$name = substr($row->name, 0, strlen($row->name) -19) . " " . date("m-d-Y H:i:s");
			}
			else
			{
				$name = $row->name . " " . date("m-d-Y H:i:s");
			}

			$active_status = $row->active_status;
		}

		$query = "insert into rule_set (date_modified, date_created, active_status, name, loan_type_id, date_effective) values "
					. " (now(), now(), '" . $active_status . "', '" . $name . "', " . $loan_type_id . ", now())";

		$result = $this->mysqli->Query($query);

		return $this->mysqli->Insert_Id();
	}

	public function All_Fields_Are_Enabled($loan_type_id, $rule_set_id, $rule_component_id, $rule_component_parm_id)
	{
		$result = FALSE;

		// check loan type
		$query = "select active_status from loan_type where loan_type_id = " . $loan_type_id;
		$query_result = $this->mysqli->Query($query);
		if($row = $query_result->Fetch_Object_Row())
		{
			if ($row->active_status == 'active')
			{
				$result = TRUE;
			}
		}
	
		if ($result)
		{
			// rule set
			$query = "select active_status from rule_set where loan_type_id = " . $loan_type_id . ' AND rule_set_id = '. $rule_set_id;
			$query_result = $this->mysqli->Query($query);
			if($row = $query_result->Fetch_Object_Row())
			{
				if ($row->active_status == 'active')
				{
					$result = TRUE;
				}
				else
				{
					$result = FALSE;
				}
			}
			else
			{
				$result = FALSE;
			}
		}
	
		if ($result)
		{
			// rule component 
			$query = "select active_status from rule_component where rule_component_id = " . $rule_component_id;
			$query_result = $this->mysqli->Query($query);
			if($row = $query_result->Fetch_Object_Row())
			{
				if ($row->active_status == 'active')
				{
					$result = TRUE;
				}
				else
				{
					$result = FALSE;
				}
			}
			else
			{
				$result = FALSE;
			}
		}

		if ($result)
		{
			// rule set component 
			$query = "select active_status from rule_set_component where rule_component_id = " 
							. $rule_component_id . ' AND rule_set_id = ' . $rule_set_id;
			$query_result = $this->mysqli->Query($query);
			if($row = $query_result->Fetch_Object_Row())
			{
				if ($row->active_status == 'active')
				{
					$result = TRUE;
				}
				else
				{
					$result = FALSE;
				}
			}
			else
			{
				$result = FALSE;
			}
		}

		if ($result)
		{
			// rule component  parm
			$query = "select active_status from rule_component_parm where rule_component_parm_id = "
						  . $rule_component_parm_id . ' AND rule_component_id = ' . $rule_component_id;
			$query_result = $this->mysqli->Query($query);
			if($row = $query_result->Fetch_Object_Row())
			{
				if ($row->active_status == 'active')
				{
					$result = TRUE;
				}
				else
				{
					$result = FALSE;
				}
			}
			else
			{
				$result = FALSE;
			}
		}

		return $result;
	}

	public function Is_User_Configurable($rule_component_id, $rule_component_parm_id)
	{
		$result = FALSE;

		// rule component  parm
		$query = "select user_configurable from rule_component_parm where rule_component_parm_id = "
					  . $rule_component_parm_id . ' AND rule_component_id = ' . $rule_component_id;
		$query_result = $this->mysqli->Query($query);
		if($row = $query_result->Fetch_Object_Row())
		{
			if (trim(strtolower($row->user_configurable)) == 'yes')
			{
				$result = TRUE;
			}
			else
			{
				$result = FALSE;
			}
		}
		else
		{
			$result = FALSE;
		}

		return $result;
	}	

	public function Is_Component_Grandfathered($rule_component_id)
	{
		// rule component  parm
		$query = "SELECT grandfathering_enabled FROM rule_component WHERE rule_component_id = " . $rule_component_id;

		$query_result = $this->mysqli->Query($query);
		if($row = $query_result->Fetch_Object_Row())
		{
			if (trim(strtolower($row->grandfathering_enabled)) == 'yes')
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	public function Create_New_Rule_Set_Comp($old_rule_set_id, $new_rule_set_id)
	{
		$query = "select
					active_status,
					rule_component_id,
					sequence_no 
				from
				 	rule_set_component
				where
				 	rule_set_id = " . $old_rule_set_id;

		$result = $this->mysqli->Query($query);
		while($row = $result->Fetch_Object_Row())
		{
			$insert_query = "insert into rule_set_component
										(date_modified, date_created, active_status, rule_set_id, rule_component_id, sequence_no)
								  values
								  		(now(), now(), '" . $row->active_status . "', "
									. $new_rule_set_id . ", "
									. $row->rule_component_id . ", "
									. $row->sequence_no . ");";

			$this->mysqli->Query($insert_query);
			$this->Create_New_Rule_Component_Parms($old_rule_set_id, $new_rule_set_id, $row->rule_component_id);
		}

		return TRUE;
	}

	public function Create_New_Rule_Set_Component_Values($old_rule_set_id, $new_rule_set_id, $rule_component_parm_id)
	{
		$query = "select
						agent_id,
						rule_component_id,
						parm_value
					 from
					 	rule_set_component_parm_value
					 where
					 	rule_set_id = " . $old_rule_set_id . "
					 	and  rule_component_parm_id = " . $rule_component_parm_id;

		$result = $this->mysqli->Query($query);

		while($row = $result->Fetch_Object_Row())
		{
			$insert_query = "insert into rule_set_component_parm_value
										(date_modified,
										 date_created,
										 agent_id,
										 rule_set_id,
										 rule_component_id,
										 rule_component_parm_id,
										 parm_value
										 )
								  values
								  		(now(),
										 now(), "
										. $row->agent_id . ", "
										. $new_rule_set_id . ", "
										. $row->rule_component_id . ", "
										. $rule_component_parm_id . ", "
										. "'" . $row->parm_value . "');";

			$this->mysqli->Query($insert_query);
		}

		return TRUE;
	}

	public function Create_New_Rule_Component_Parms($old_rule_set_id, $new_rule_set_id, $rule_component_id)
	{
		$query = "select
					rule_component_parm_id
				from
					rule_component_parm
				where 
					rule_component_id = " . $rule_component_id;

		$result = $this->mysqli->Query($query);
		while($row = $result->Fetch_Object_Row())
		{
			$this->Create_New_Rule_Set_Component_Values($old_rule_set_id, $new_rule_set_id, $row->rule_component_parm_id);
		}

		return TRUE;
	}

	public function Update_Rule_Set_Component_Value($rule_set_id, $rule_component_id, $rule_component_parm_id,
											$rule_component_parm_value, $agent_id)
	{
		$query = "update
						rule_set_component_parm_value
					 	set agent_id = " . $agent_id . ", parm_value = '" . $rule_component_parm_value . "' " 
						. "where rule_set_id = " . $rule_set_id . " "
						. "and rule_component_id = " . $rule_component_id . " "
						. "and rule_component_parm_id = " . $rule_component_parm_id;
						
		$this->mysqli->Query($query);

		return TRUE;
	}

	public function Get_Loan_Types($company_id)
	{
		$query = "select
					lt.active_status,
					lt.company_id,
					lt.loan_type_id,
					lt.name,
					lt.name_short
				  from
				  	loan_type lt 
				  where 
				  	company_id = ".$company_id."
				  order by
				  	lt.name";


		$result = $this->mysqli->Query($query);
		$loan_types = array();
		while($row = $result->Fetch_Object_Row())
		{
			$loan_types[] = $row;
		}

		return $loan_types;		
	}

	/**
	 * This returns the rule_set_id based off of the loan_type_id passed in.
	 *
	 * @param: $loan_type_id - This is the loan type id.
	 *
	 * @return: $result - This is the rule_set_id if an active rule_set is found.
	 *                    If an active rule set is not found, FALSE is returned.
	 */
	public function Get_Current_Rule_Set_Id($loan_type_id)
	{
		$result = FALSE;
		
		$query = "
					SELECT
						rule_set_id
					FROM
						rule_set
					WHERE
						  loan_type_id	 = $loan_type_id
			          AND active_status  ='active'
			          AND date_effective <= now()
			        ORDER BY date_effective DESC
			        LIMIT 1
		";
		
		$query_obj = $this->mysqli->Query($query);
		while ($row_obj = $query_obj->Fetch_Object_Row())
		{
			$result = $row_obj->rule_set_id;
		}

		return $result;
	}

	public function Get_Rule_Sets()
	{
		$query = "select
					rs.active_status,
					rs.rule_set_id,
					rs.name,
					rs.loan_type_id,
					rs.date_effective
				  from
				  	rule_set rs 
				  order by
				  	rs.rule_set_id desc
				  ";

		$result = $this->mysqli->Query($query);
		$rule_set = array();
		while($row = $result->Fetch_Object_Row())
		{
			$rule_set[] = $row;
		}

		return $rule_set;		
	}

	public function Get_Rule_Components()
	{
		$query = "select
					rc.active_status,
					rc.rule_component_id,
					rc.name,
					rc.grandfathering_enabled,
					rc.name_short
				  from
				  	rule_component rc
				  order by 
				  	rc.name
				  ";

		$result = $this->mysqli->Query($query);
		$rule_components = array();
		while($row = $result->Fetch_Object_Row())
		{
			$rule_components[] = $row;
		}

		return $rule_components;		
	}

	public function Get_Rule_Set_Components()
	{
		$query = "SELECT
					rsc.active_status,
					rsc.rule_set_id,
					rsc.rule_component_id
				  FROM
				  	rule_set_component rsc
				  JOIN rule_component AS rc USING (rule_component_id)
				  ORDER BY
				  	rsc.rule_set_id, rc.name ";

		$result = $this->mysqli->Query($query);
		$rule_set_components = array();
		$last_rule_set_id = 0;
		$seq_no = 1;
		
		while($row = $result->Fetch_Object_Row())
		{
			if($row->rule_set_id != $last_rule_set_id)
			{
				$last_rule_set_id = $row->rule_set_id;
				$seq_no = 1;
			}
			
			$row->sequence_no = $seq_no;
			$rule_set_components[] = $row;
			$seq_no++;
		}
				
		return $rule_set_components;		
	}

	public function Get_Rule_Component_Params()
	{
		$query = "select
					rcp.active_status,
					rcp.rule_component_parm_id,
					rcp.rule_component_id,
					rcp.display_name,
					rcp.parm_name,
					rcp.parm_subscript,
					rcp.sequence_no,
					rcp.description,
					rcp.parm_type,
					rcp.user_configurable,
					rcp.input_type,
					rcp.value_label,
					rcp.value_min,
					rcp.value_max,
					rcp.value_increment,
					rcp.length_min,
					rcp.length_max,
					rcp.enum_values,
					rcp.preg_pattern
				  from
				  	rule_component_parm rcp
				  order by
				  	rcp.sequence_no ";

		$result = $this->mysqli->Query($query);
		$rule_component_param = array();
		while($row = $result->Fetch_Object_Row())
		{
			$rule_component_param[] = $row;
		}
		
		return $rule_component_param;		
	}

	public function Get_Rule_Set_Component_Values()
	{
		$query = "select
					rscpv.rule_set_id,
					rscpv.rule_component_id,
					rscpv.rule_component_parm_id,
					rscpv.parm_value
				  from
				  	rule_set_component_parm_value rscpv";

		$result = $this->mysqli->Query($query);
		$rule_set_component_parm_value = array();
		while($row = $result->Fetch_Object_Row())
		{
			$rule_set_component_parm_value[] = $row;
		}

		return $rule_set_component_parm_value;		
	}

	public function Get_Rule_Set_Component_Parm_Values($company_short, $component_name_short)
	{
		$query = "select rcp.parm_name, rscpv.parm_value
					from rule_set_component_parm_value rscpv
					inner join rule_component_parm rcp on (rcp.rule_component_parm_id = rscpv.rule_component_parm_id)
					inner join rule_set_component rsc on (rsc.rule_component_id = rcp.rule_component_id)
					inner join rule_component rc on (rc.rule_component_id = rsc.rule_component_id)
					inner join rule_set rs on (rs.rule_set_id = rsc.rule_set_id)
					inner join loan_type lt on (lt.loan_type_id = rs.loan_type_id)
					inner join company c on (c.company_id = lt.company_id)
					where rc.name_short = '{$component_name_short}'
					and c.name_short = '{$company_short}'";

		$result = $this->mysqli->Query($query);
		$rule_set_component_parm_value = array();
		while($row = $result->Fetch_Object_Row())
		{
			$rule_set_component_parm_value[$row->parm_name] = $row->parm_value;
		}

		return $rule_set_component_parm_value;		
	}

	public function Get_Rule_Set_Id_For_Application($application_id)
	{
		$query = <<<EOQ
			SELECT rule_set_id
			FROM application
			WHERE application_id = {$application_id}
EOQ;
		
		$query_obj = $this->mysqli->Query($query);
		if($row_obj = $query_obj->Fetch_Object_Row())
		{
			$result = $row_obj->rule_set_id;
		}
		
		return $result; 
	}
	
	public function Get_Rule_Set_Tree($rule_set_id)
	{
		$query = "SELECT
					rc.name_short as rule_name,
					rcp.parm_name,
					rcp.presentation_type,
					rcpv.parm_value
				FROM
					rule_set_component rsc,
					rule_component rc,
					rule_component_parm rcp,
					rule_set_component_parm_value rcpv
				WHERE
					rsc.rule_component_id = rc.rule_component_id
					AND rsc.rule_set_id = {$rule_set_id}
					AND rc.active_status = 'active'
					AND rc.rule_component_id = rcpv.rule_component_id
					AND rcp.rule_component_parm_id = rcpv.rule_component_parm_id
					AND rcpv.rule_set_id = rsc.rule_set_id";
		
		$data_structure = array();
		
		$result = $this->mysqli->Query($query);
		

		while($row = $result->Fetch_Object_Row())
		{
			if (!isset($data_structure[$row->rule_name]))
			{
				$data_structure[$row->rule_name] = array();
			}
			$data_structure[$row->rule_name][$row->parm_name] = $row->parm_value;
		}

		// prune items with only one value
		foreach ($data_structure as $rule=>$value)
		{
			if (count($value) === 1)
			{
				$data_structure[$rule] = reset($value);
			}
		}

		return $data_structure;
	}
	
	public function Get_Latest_Rule_Set($loan_type_id)
	{
		$rsid = $this->Get_Current_Rule_Set_Id($loan_type_id);
		return $this->Get_Rule_Set_tree($rsid);
	}
	
	public function Get_Loan_Type_For_Company($company_short, $type='standard')
	{
		$query = "SELECT lt.loan_type_id
                          FROM loan_type lt, company c
                          WHERE lt.company_id = c.company_id
                          AND c.name_short = '{$company_short}'
                          AND lt.name_short = '{$type}'
                          AND lt.active_status = 'active'";

		$result = $this->mysqli->Query($query);
		$row = $result->Fetch_Object_Row();
		return ($row->loan_type_id);
	}

	/**
	 * @desc
	 *		This calculates the service charge on the loan amount passed in, based on the 
	 * 	business rules.
	 *
	 *	@parm
	 * 	$application_id - The app_id.
	 * 	$fund_amount - The amount of the loan.
	 *
	 *	@return
	 *		$result - The amount of interest on the loan.
	 */
	public function Calc_Original_Service_Charge_On_Loan($application_id, $fund_amount)
	{
		$rule_set_tree = $this->Get_Rule_Set_Tree($this->Get_Rule_Set_Id_For_Application($application_id));
		$result = $fund_amount * ($rule_set_tree['svc_charge_percentage'] / 100);
		return $result;
	}

}

?>
