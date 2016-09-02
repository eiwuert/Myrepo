<?php

class ECash_BusinessRules
{
	protected $db;

	public function __construct(DB_Database_1 $db)
	{
		$this->db = $db;
	}

	public function Create_New_Rule_Set($loan_type_id, $rule_set_id)
	{
		$query = "
			SELECT
				active_status,
				name
			FROM rule_set
			WHERE loan_type_id = ?
				and rule_set_id = ?
		";
		$row = $this->db->querySingleRow($query, array($loan_type_id, $rule_set_id));

		// AM: shouldn't continue without a result from this query,
		// or active_status and name will be blank
		if ($row === FALSE)
		{
			throw new Exception('Missing rule set, '.$rule_set_id);
		}
	
		$name = $row['name'];
		$active_status = $row['active_status'];
		
		// suffix the name with a date (and remove the old one, if it exists)
		if (substr($name, -3, 1) == ":") $name = substr($name, 0, -19);
		$name .= date('m-d-Y H:i:s');

		$query = "
			INSERT INTO rule_set
			(date_modified, date_created, active_status, name, loan_type_id, date_effective)
			VALUES (NOW(), NOW(), ?, ?, ?, NOW())
		";
		$this->db->queryPrepared($query, array($active_status, $name, $loan_type_id));

		return $this->db->lastInsertId();
	}

	public function All_Fields_Are_Enabled($loan_type_id, $rule_set_id, $rule_component_id, $rule_component_parm_id)
	{
		// check that everything is active
		return ($this->isLoanTypeActive($loan_type_id)
			&& $this->isRuleSetActive($loan_type_id, $rule_set_id)
			&& $this->isRuleComponentActive($rule_component_id)
			&& $this->isRuleSetComponentActive($rule_set_id, $rule_component_id)
			&& $this->isRuleComponentParmActive($rule_component_id, $rule_component_parm_id));
	}

	public function Is_User_Configurable($rule_component_id, $rule_component_parm_id)
	{
		// rule component  parm
		$query = "
			select user_configurable
			from rule_component_parm
			where rule_component_parm_id = ?
				AND rule_component_id = ?
		";
		$config = $this->db->querySingleValue($query, array($rule_component_parm_id, $rule_component_id));
		return ($config == 'yes');
	}

	public function Is_Component_Grandfathered($rule_component_id)
	{
		// rule component  parm
		$query = "
			SELECT grandfathering_enabled
			FROM rule_component
			WHERE rule_component_id = ?
		";
		$enabled = $this->db->querySingleValue($query, array($rule_component_id));
		return ($enabled == 'yes');
	}

	public function Create_New_Rule_Set_Comp($old_rule_set_id, $new_rule_set_id)
	{
		$query = "
			select
				active_status,
				rule_component_id,
				sequence_no
			from rule_set_component
			where rule_set_id = ?
		";
		$st = $this->db->queryPrepared($query, array($old_rule_set_id));

		// prepare this for multiple uses
		$insert = $this->db->prepare("
			INSERT INTO rule_set_component
			(date_modified, date_created, active_status, rule_set_id, rule_component_id, sequence_no)
			values (NOW(), NOW(), ?, ?, ?, ?)
		");

		while ($row = $st->fetch(PDO::FETCH_OBJ))
		{
			$insert->execute(array(
				$row->active_status,
				$new_rule_set_id,
				$row->rule_component_id,
				$row->sequence_no,
			));
			$this->Create_New_Rule_Component_Parms($old_rule_set_id, $new_rule_set_id, $row->rule_component_id);
		}

		return TRUE;
	}

	public function Create_New_Rule_Set_Component_Values($old_rule_set_id, $new_rule_set_id, $rule_component_parm_id)
	{
		$query = "
			select
				agent_id,
				rule_component_id,
				parm_value
			from rule_set_component_parm_value
			where rule_set_id = ?
				and rule_component_parm_id =  ?
		";
		$st = $this->db->queryPrepared($query, array($old_rule_set_id, $rule_component_parm_id));

		// prepare this for multiple executions
		$insert = $this->db->prepare("
			insert into rule_set_component_parm_value
			(date_modified, date_created, agent_id, rule_set_id, rule_component_id, rule_component_parm_id, parm_value)
			values (now(), now(), ?, ?, ?, ?, ?)
		");

		while ($row = $st->fetch(PDO::FETCH_OBJ))
		{
			$insert->execute(array(
				$row->agent_id,
				$new_rule_set_id,
				$row->rule_component_id,
				$rule_component_parm_id,
				$row->parm_value,
			));
		}

		return TRUE;
	}

	public function Create_New_Rule_Component_Parms($old_rule_set_id, $new_rule_set_id, $rule_component_id)
	{
		$query = "
			select rule_component_parm_id
			from rule_component_parm
			where rule_component_id = ?
		";
		$st = $this->db->queryPrepared($query, array($rule_component_id));

		while($row = $st->fetch(PDO::FETCH_OBJ))
		{
			$this->Create_New_Rule_Set_Component_Values(
				$old_rule_set_id,
				$new_rule_set_id,
				$row->rule_component_parm_id
			);
		}

		return TRUE;
	}

	public function Update_Rule_Set_Component_Value($rule_set_id, $rule_component_id,
		$rule_component_parm_id, $rule_component_parm_value, $agent_id)
	{
		$query = "
			update rule_set_component_parm_value
			set
				agent_id = ?,
				parm_value = ?
			where rule_set_id = ?
				and rule_component_id = ?
				and rule_component_parm_id = ?
		";

		$args = array(
			$agent_id,
			$rule_component_parm_value,
			$rule_set_id,
			$rule_component_id,
			$rule_component_parm_id
		);

		$this->db->queryPrepared($query, $args);
		return TRUE;
	}

	public function Get_Loan_Types($company_id)
	{
		$query = "
			select
				lt.active_status,
				lt.company_id,
				lt.loan_type_id,
				lt.name,
				lt.name_short
			from loan_type lt
			where	company_id = ?
			order by lt.name
		";
		$st = $this->db->queryPrepared($query, array($company_id));
		return $st->fetchAll(PDO::FETCH_OBJ);
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
		$query = "
			SELECT rule_set_id
			FROM rule_set
			WHERE loan_type_id = ?
				AND active_status  ='active'
				AND date_effective <= now()
			ORDER BY date_effective DESC
			LIMIT 1
		";
		$id = $this->db->querySingleValue($query, array($loan_type_id));
		return $id;
	}

	public function Get_Rule_Sets()
	{
		$query = "
			select
				rs.active_status,
				rs.rule_set_id,
				rs.name,
				rs.loan_type_id,
				rs.date_effective
			from rule_set rs
			order by rs.rule_set_id desc
		";
		$st = $this->db->query($query);
		return $st->fetchAll(PDO::FETCH_OBJ);
	}

	public function Get_Rule_Components()
	{
		$query = "
			select
				rc.active_status,
				rc.rule_component_id,
				rc.name,
				rc.grandfathering_enabled,
				rc.name_short
		  from rule_component rc
		  order by rc.name
		";
		$st = $this->db->query($query);
		return $st->fetchAll(PDO::FETCH_OBJ);
	}

	public function Get_Rule_Set_Components()
	{
		$query = "
			SELECT
				rsc.active_status,
				rsc.rule_set_id,
				rsc.rule_component_id
			FROM rule_set_component rsc
				JOIN rule_component AS rc USING (rule_component_id)
			ORDER BY rsc.rule_set_id, rc.name
		";
		$st = $this->db->query($query);

		$rule_set_components = array();
		$last_rule_set_id = 0;
		$seq_no = 1;

		while ($row = $st->fetch(PDO::FETCH_OBJ))
		{
			if($row->rule_set_id != $last_rule_set_id)
			{
				$last_rule_set_id = $row->rule_set_id;
				$seq_no = 1;
			}

			$row->sequence_no = $seq_no++;
			$rule_set_components[] = $row;
		}

		return $rule_set_components;
	}

	public function Get_Rule_Component_Params()
	{
		$query = "
			select
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
		  from rule_component_parm rcp
		  order by rcp.sequence_no
		";
		$st = $this->db->query($query);
		return $st->fetchAll(PDO::FETCH_OBJ);
	}

	public function Get_Rule_Set_Component_Values()
	{
		$query = "
			select
				rule_set_id,
				rule_component_id,
				rule_component_parm_id,
				parm_value
			from rule_set_component_parm_value
		";
		$st = $this->db->query($query);
		return $st->fetchAll(PDO::FETCH_OBJ);
;
	}

	public function Get_Rule_Set_Ids_By_Parm_Value($parm_name, $parm_value)
	{
		$query = "
			SELECT 
				rs.rule_set_id
			FROM
				rule_set_component_parm_value rscpv
			JOIN
				rule_component_parm rcp ON rscpv.rule_component_parm_id = rcp.rule_component_parm_id
			JOIN
				rule_set rs ON rscpv.rule_set_id = rs.rule_set_id
			JOIN 
				rule_component rc ON rcp.rule_component_id = rc.rule_component_id
			WHERE 
				rcp.parm_name = ?
			AND
				rscpv.parm_value = ?
		";
		$st = $this->db->queryPrepared($query, array($parm_name, $parm_value));

		$values = array();

		while ($row = $st->fetch(PDO::FETCH_OBJ))
		{
			$values[] = $row->rule_set_id;
		}

		return $values;	
		
	}
	
	public function Get_Rule_Set_Component_Parm_Values($company_short, $component_name_short)
	{
		$query = "
			select
				rcp.parm_name,
				rscpv.parm_value
			from rule_set_component_parm_value rscpv
				inner join rule_component_parm rcp on (rcp.rule_component_parm_id = rscpv.rule_component_parm_id)
				inner join rule_set_component rsc on (rsc.rule_component_id = rcp.rule_component_id)
				inner join rule_component rc on (rc.rule_component_id = rsc.rule_component_id)
				inner join rule_set rs on (rs.rule_set_id = rsc.rule_set_id)
				inner join loan_type lt on (lt.loan_type_id = rs.loan_type_id)
				inner join company c on (c.company_id = lt.company_id)
			where rc.name_short = '{$component_name_short}'
				and c.name_short = '{$company_short}'
		";
		$st = $this->db->queryPrepared($query, array($component_name_short, $company_short));

		$values = array();

		while ($row = $st->fetch(PDO::FETCH_OBJ))
		{
			$values[$row->parm_name] = $row->parm_value;
		}

		return $values;
	}

	public function Get_Rule_Set_Id_For_Application($application_id)
	{
		$query = "
			SELECT rule_set_id
			FROM application
			WHERE application_id = ?
		";
		$row = $this->db->querySingleRow($query, array($application_id), PDO::FETCH_OBJ);
		return $row->rule_set_id;
	}

	public function Get_Rule_Set_Tree($rule_set_id)
	{
		$query = "
			SELECT
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
				AND rsc.rule_set_id = ?
				AND rc.active_status = 'active'
				AND rc.rule_component_id = rcpv.rule_component_id
				AND rcp.rule_component_parm_id = rcpv.rule_component_parm_id
				AND rcpv.rule_set_id = rsc.rule_set_id
		";
		$st = $this->db->queryPrepared($query, array($rule_set_id));

		$data_structure = array();

		while ($row = $st->fetch(PDO::FETCH_OBJ))
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
		$query = "
			SELECT lt.loan_type_id
			FROM loan_type lt
				JOIN company c ON (c.company_id = lt.company_id)
			WHERE
				c.name_short = ?
				AND lt.name_short = ?
				AND lt.active_status = 'active'
		";
		$row = $this->db->querySingleRow($query, array($company_short, $type), PDO::FETCH_OBJ);
		return $row->loan_type_id;
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
		$application = ECash::getApplicationById($application_id);
		$rate_calc = $application->getRateCalculator();
		$result = $fund_amount * ($rate_calc->getPercent() / 100);
		return $result;
	}

	protected function isLoanTypeActive($type_id)
	{
		// check loan type
		$query = "
			select active_status
			from loan_type
			where loan_type_id = ?
		";
		$active = $this->db->querySingleValue($query, array($type_id));
		return ($active == 'active');
	}

	protected function isRuleSetActive($type_id, $ruleset_id)
	{
		// rule set
		$query = "
			select active_status
			from rule_set
			where loan_type_id = ?
				AND rule_set_id = ?
		";
		$active = $this->db->querySingleValue($query, array($type_id, $ruleset_id));
		return ($active == 'active');
	}

	protected function isRuleComponentActive($component_id)
	{
		// rule component
		$query = "
			select active_status
			from rule_component
			where rule_component_id = ?
		";
		$active = $this->db->querySingleValue($query, array($component_id));
		return ($active == 'active');
	}

	protected function isRuleSetComponentActive($ruleset_id, $component_id)
	{
		// rule set component
		$query = "
			select active_status
			from rule_set_component
			where rule_component_id = ?
				AND rule_set_id = ?
		";
		$active = $this->db->querySingleValue($query, array($component_id, $ruleset_id));
		return ($active == 'active');
	}

	protected function isRuleComponentParmActive($component_id, $parm_id)
	{
		// rule component parm
		$query = "
			select active_status
			from rule_component_parm
			where rule_component_parm_id = ?
				AND rule_component_id = ?
		";
		$active = $this->db->querySingleValue($query, array( $parm_id, $component_id));
		
		return ($active == 'active');
	}
}

?>
