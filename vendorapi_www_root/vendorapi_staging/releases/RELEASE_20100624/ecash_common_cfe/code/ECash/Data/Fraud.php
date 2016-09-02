<?php

  /**
   * Most of this class is taken from the original server/code/application_fraud_query.class.php
   */
class ECash_Data_Fraud extends ECash_Data_DataRetriever
{
	/**
	 * gets prototypes for rule comparison
	 *
	 * @return array prototype rows
	 */
	public function getPrototypes($rule_type, $confirmed, ECash_Models_FraudRule $changed_rule = NULL)
	{
		$rule_type_sql = $this->getRuleTypeWhere($rule_type);
		$confirmed_sql = $this->getConfirmedWhere($confirmed);
		$ignore_rule_sql = $this->getIgnoreRuleWhere($changed_rule);

		$prototypes = array();
		
		$query = "
				select
				c.field_name,
				c.field_comparison,
				c.prototype_id
				from fraud_condition c
				inner join fraud_rule r on (c.fraud_rule_id = r.fraud_rule_id)
				where r.active = 1
				{$confirmed_sql}
				{$rule_type_sql}
				{$ignore_rule_sql}
				group by c.field_name, c.field_comparison, c.prototype_id
				order by prototype_id
			";
		$result = $this->db->query($query);
				
		while ($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$prototypes[] = $row;
		}

		return $prototypes;
		//print_r($prototypes);
	}

	private function getRuleTypeWhere($rule_type)
	{
		return ($rule_type !== NULL ? "and r.rule_type = '{$rule_type}'" : '');
	}

	private function getConfirmedWhere($confirmed)
	{
		//sorry for the nested ternary, Tom Reinertson would be proud [JustinF]
		return ($confirmed !== NULL ? ('and r.confirmed = ' . ($confirmed ? '1' : '0')) : '');
	}

	private function getIgnoreRuleWhere(ECash_Models_FraudRule $changed_rule = NULL)
	{
		if($changed_rule !== NULL &&
		   $changed_rule->fraud_rule_id && //so we don't try to add this line if FraudRuleID is not set (new rule)
			(!$changed_rule->active || //exclude a rule -- if we want to see the effects of turning it off
			 ($confirmed !== NULL && !$change_rule->confirmed))) //or exclude a rule being unconfirmed
			return "and r.fraud_rule_id <> {$changed_rule->fraud_rule_id}";

		//else
		return '';
	}
	
	public function getViolations(ECash_Application $app, array $prototypes, $rule_type, $confirmed, ECash_Models_FraudRule $changed_rule = NULL)
	{
		$rule_type_sql = $this->getRuleTypeWhere($rule_type);
		$confirmed_sql = $this->getConfirmedWhere($confirmed);
		$ignore_rule_sql = $this->getIgnoreRuleWhere($changed_rule);

		$queries = array();
		foreach($prototypes as $prototype_id => $prototype)
		{
			
			// query against each prototype that can be applied to the passed data
			$join_list = array();
				
			// selecting the rule, in the end..				
			$query = "
				SELECT
				r.*,
				c0.prototype_id
				FROM fraud_rule r
				";
				
			// exclude unpublished rules...
			$where_list = array("\nwhere r.active=1
				{$rule_type_sql}
				{$confirmed_sql}
				{$ignore_rule_sql}");
				
			// condition counter for generating alias names
			$cnt = 0;
			
			// add a set of conditions to the where clause for each possible rule condition

			$fields = $prototype->getFields();
			foreach($fields as $field_name => $comparison)
			{
				$join_list[] = "inner join fraud_condition c{$cnt} ON (c{$cnt}.fraud_rule_id = r.fraud_rule_id)";
				$where_list[] = "and c{$cnt}.prototype_id='$prototype_id'";				
				$where_list[] = "and " . $prototype->formatSearch($field_name, $this->db->quote($app->Model->$field_name), "c{$cnt}.field_value");
				$cnt++;
			}
				
			// put the pieces together
			$query = $query . join("\n", $join_list) . join("\n", $where_list);

			// add this query to our list of queries to run
			$queries[] = $query;
		}

		// our return object.. contains rule objects representing 'hits'			
		$violations = array(ECash_Fraud_Rule::RULE_TYPE_FRAUD => array(), ECash_Fraud_Rule::RULE_TYPE_RISK => array());
			
		// run all our queries, and place any results we get into our result object
		// justin likes unions
		$big_query = join("\nUNION\n", $queries);

		//if(strpos($app->Model->name_first,'tss') !== FALSE)
		//	echo "<!-- {$big_query} -->";
				
		//incase we found no rules (likely of a certain type)
		if(!empty($big_query))
		{
			$rs=$this->db->query($big_query);
				
			while ( $row = $rs->fetch(PDO::FETCH_OBJ) )
			{
				$rule = new ECash_Fraud_Rule($this->db);
				$rule->Model->fraud_rule_id = $row->fraud_rule_id;				
				$rule->Model->date_modified = $row->date_modified;
				$rule->Model->date_created = $row->date_created;
				$rule->Model->active = $row->active;
				$rule->Model->exp_date = $row->exp_date;
				$rule->Model->rule_type = $row->rule_type;
				$rule->Model->confirmed = $row->confirmed;
				$rule->Model->name = $row->name;
				$rule->Model->comments = $row->comments;
				$rule->Model->setDataSynched();

				$rule->addCondition($prototypes[$prototype_id]);
				$violations[$rule->getRuleType()][] = $rule;
			}
		}

		// violate the user (sicko)
		if($this->rule_type != NULL)
			return($violations[$this->rule_type]);
		
		return($violations);
	}
	
	/**
	 * Returns the correct (or next new) prototype ID for the current set of conditions
	 *
	 * @param array $conditions array of ECash_Fraud_Condition objects
	 */
	public function getPrototypeId(array $conditions)
	{
		$count = count($conditions);
		
		$select = "
				select c0.prototype_id
				from fraud_condition c0";

		$where = "
				where c0.field_name = '{$conditions[0]->FieldName}' 
				and c0.field_comparison = '{$conditions[0]->FieldComparison}'
				group by prototype_id";
		
		for($i = 1; $i < count($conditions); $i++)
		{
			$select .= "
					join
					(
						select prototype_id
						from fraud_condition
						where field_name = '{$conditions[$i]->FieldName}'
						and field_comparison = '{$conditions[$i]->FieldComparison}'
						group by prototype_id
					) c{$i} on (c{$i}.prototype_id = c0.prototype_id)";
		}
		
		$select .= "
				join
				(
					select prototype_id
					from
					(
						select prototype_id, field_name, field_comparison 
						from fraud_condition
						group by prototype_id, field_name, field_comparison
					) cnt_grp
					group by prototype_id
					having count(*) = {$count}
				) cnt on (cnt.prototype_id = c0.prototype_id)";

		$select .= $where;

		//echo $select;

		$result = $this->db->query($select);
		$row = $result->fetch(PDO::FETCH_OBJ);

		if(empty($row))
		{
			$query = "select ifnull(max(prototype_id)+1, 1) as prototype_id from fraud_condition";
			$result = $this->db->query($query);
			$row = $result->fetch(PDO::FETCH_OBJ);
		}

		return $row->prototype_id;
	}


	/**
	 * @param int $rule_id
	 * @return ECash_Fraud_Rule fraud rule object with associated conditions
	 */
	public function getFraudRule($rule_id)
	{
		$rules = array();
		
		$select = "
			select
				r.fraud_rule_id,
				r.date_modified,
				r.date_created,
				r.modified_agent_id,
				r.created_agent_id,
				r.active,
				from_unixtime(r.exp_date) exp_date,
				r.rule_type,
				r.confirmed,
				r.name,
				r.comments,
				date_format(fp.date_created, '%Y/%m/%d') prop_date_created,
				fp.fraud_proposition_id,
				fp.agent_id prop_agent_id,
				fp.question,
				fp.description,
				fp.quantify,
				fp.file_name,
				fp.file_size,
				fp.file_type,
				cond.fraud_condition_id,
				cond.field_name,
				cond.field_comparison,
				cond.field_value,
				cond.prototype_id
			from
				fraud_condition cond
			inner join fraud_rule r on (r.fraud_rule_id = cond.fraud_rule_id)
			left join fraud_proposition fp on (fp.fraud_rule_id = r.fraud_rule_id)
			where r.fraud_rule_id = {$rule_id}
		order by r.name, r.fraud_rule_id, cond.fraud_condition_id
			";

		/*
				concat(fram.name_first, ' ', fram.name_last) modified_agent_name,
				concat(frac.name_first, ' ', frac.name_last) created_agent_name,
				concat(fpa.name_first, ' ', fpa.name_last) prop_agent_name,
			inner join agent frac on (frac.agent_id = r.created_agent_id)
			inner join agent fram on (fram.agent_id = r.modified_agent_id)
			left join agent fpa on (fp.agent_id = fpa.agent_id)
		*/		 
		$result = $this->db->query($select);

		$rule = NULL;
		
		while($row = $result->fetch(PDO::FETCH_OBJ))
		{
			if($rule == NULL)
			{
				$rule = new ECash_Fraud_Rule($this->db);
				$rule->Model->fraud_rule_id = $row->fraud_rule_id;				
				$rule->Model->date_modified = $row->date_modified;
				$rule->Model->date_created = $row->date_created;
				$rule->Model->modified_agent_id = $row->modified_agent_id;
				$rule->Model->created_agent_id = $row->created_agent_id;
				$rule->Model->active = $row->active;
				$rule->Model->exp_date = $row->exp_date;
				$rule->Model->rule_type = $row->rule_type;
				$rule->Model->confirmed = $row->confirmed;
				$rule->Model->name = $row->name;
				$rule->Model->comments = $row->comments;
				$rule->Model->setDataSynched();

				if(!empty($row->fraud_proposition_id))
				{
					$prop = ECash::getFactory()->getModel('FraudProposition');
					$prop->fraud_proposition_id = $row->fraud_proposition_id;
					$prop->date_created = $row->prop_date_created;
					$prop->agent_id = $row->prop_agent_id;
					$prop->question = $row->question;
					$prop->description = $row->description;
					$prop->quantify = $row->quantify;
					$prop->file_name = $row->file_name;
					$prop->file_size = $row->file_size;
					$prop->file_type = $row->file_type;
					$prop->setDataSynched();
					$rule->setPropositionModel($prop);
				}
			}

			$condition = new ECash_Fraud_Condition($row->field_name, $row->field_comparison, $row->field_value);
			$condition->setFraudConditionId($row->fraud_condition_id);
			$rule->addCondition($condition);

		}

		//echo "<!-- ", print_r($rule, TRUE), " -->";
		return $rule;		
	}

	/**
	 * Gets a list of names formatted for the Fraud Rule admin panel
	 *
	 * @param const/string $type rule type from ECash_Fraud_Rule
	 * @param string $active filter for active (active/inactive)
	 * @param int $confirmed filter for confirmed (0/1)
	 * @return array rule id's with rule names
	 */
	public function getRuleNames($type, $active = NULL, $confirmed = NULL)
	{
		$rule_names = array();
	
		$active_sql = '';
		$confirmed_sql = '';
		
		if($active !== NULL)
		{
			$active_sql = "AND fr.active = {$active}";
		}

		if($confirmed !== NULL)
		{
			$confirmed_sql = "AND fr.confirmed = {$confirmed}";
		}
		
		$select = "
			select
				fr.fraud_rule_id,
				(case
				    when fr.active = 0 and fr.confirmed = 0 then concat('* ', fr.name, if(fp.fraud_proposition_id IS NOT NULL, concat(' (#',fp.fraud_proposition_id,')'), ''))
					when fr.active = 0 and fr.confirmed = 1 then concat('*(c) ', fr.name, if(fp.fraud_proposition_id IS NOT NULL, concat(' (#',fp.fraud_proposition_id,')'), ''))
					when fr.active = 1 and fr.confirmed = 1 then concat('(c) ', fr.name, if(fp.fraud_proposition_id IS NOT NULL, concat(' (#',fp.fraud_proposition_id,')'), ''))					
					else concat(fr.name, if(fp.fraud_proposition_id IS NOT NULL, concat(' (#',fp.fraud_proposition_id,')'), ''))
				end) as name
			from
				fraud_rule fr
			left join fraud_proposition fp on (fp.fraud_rule_id = fr.fraud_rule_id)
			where fr.rule_type = '{$type}'
			{$active_sql}
			{$confirmed_sql}
		order by fr.name
			";

		$result = $this->db->query($select);
		
		while($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$rule_names[$row->fraud_rule_id] = $row->name;
		}

		return $rule_names;
	}

	/**
	 * For the fraud rules expiring report
	 */
	public function getExpiringRules($date_start, $date_end)
	{
		
		$select = "
			select
				fr.fraud_rule_id,
				if(fr.rule_type = 'FRAUD', 'Fraud', 'High Risk') rule_type,
				fr.name,
				date_format(from_unixtime(fr.exp_date), '%Y/%m/%d') exp_date
			from
				fraud_rule fr
			where fr.active = 1
			and fr.exp_date between unix_timestamp('{$date_start}') and unix_timestamp('{$date_end}')

			UNION
				
			select
				fr.fraud_rule_id,
				if(fr.rule_type = 'FRAUD', 'Fraud', 'High Risk') rule_type,
				fr.name,
				date_format(from_unixtime(fr.exp_date), '%Y/%m/%d') exp_date
			from
				fraud_rule fr
			where fr.active = 1
			and fr.exp_date < now()
		order by exp_date desc, rule_type, name asc
			limit ". $this->max_display_rows;

		//echo "<!-- {$select} -->";
		
		$result = $this->db->query($select);

		//this stuff is for reporting so put it in an 'all' company
		$rules = array('All' => array());
		while($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$rules['All'][] = $row;
		}

		return $rules;		
	}
	
	/**
	 * Deletes a fraud_application record for an application, used by
	 * the UI and fraud cronjob via the fraud manager.
	 *
	 * @param string const ECash_Fraud_Rule::RULE_TYPE_*
	 */
	public function clearAllByType($application_id, $type)
	{
		$query = 'delete fa from fraud_application fa
					inner join fraud_rule fr on (fr.fraud_rule_id = fa.fraud_rule_id)
					where application_id = ?
					and fr.rule_type = ?';

		return DB_Util_1::execPrepared($this->db, $query, array($application_id, $type));
	}

	/**
	 * @param int application_id
	 * @return array
	 */
	public function getFraudRulesAndFields($application_id)
	{
		//Query taken from the old fetch_loan_all query
		$query = "
		SELECT			
			fr.fraud_rules,
			-- ff.fields,
			ff.fields AS fraud_fields,
			rr.risk_rules,
			rf.risk_fields
		FROM application ap		
			LEFT JOIN (
				SELECT GROUP_CONCAT(fr.name SEPARATOR ';') as fraud_rules, fa.application_id
				FROM fraud_rule fr
				JOIN fraud_application fa on (fa.fraud_rule_id = fr.fraud_rule_id)
				WHERE fr.rule_type = 'FRAUD'
				AND application_id   = {$application_id}
				GROUP BY fa.application_id
			) fr ON (fr.application_id = ap.application_id)
			LEFT JOIN (
				SELECT GROUP_CONCAT(af.column_name SEPARATOR ',') AS fields,
					af.table_row_id as application_id
				FROM application_field af
				JOIN application_field_attribute afa on (afa.application_field_attribute_id = af.application_field_attribute_id)
				WHERE af.table_name = 'application'
				AND afa.field_name = 'fraud'
				AND af.table_row_id   = {$application_id}
				GROUP BY af.table_row_id
			) ff ON (ff.application_id = ap.application_id)
			LEFT JOIN (
				SELECT GROUP_CONCAT(fr.name SEPARATOR ';') AS risk_rules,
					fa.application_id
				FROM fraud_rule fr
				JOIN fraud_application fa on (fa.fraud_rule_id = fr.fraud_rule_id)
				WHERE fr.rule_type = 'RISK'
				AND application_id   = {$application_id}
				GROUP BY fa.application_id
			) rr ON (rr.application_id = ap.application_id)
			LEFT JOIN (
				SELECT GROUP_CONCAT(af.column_name SEPARATOR ',') as risk_fields,
					af.table_row_id as application_id
				FROM application_field af
				INNER JOIN application_field_attribute afa on (afa.application_field_attribute_id = af.application_field_attribute_id)
				WHERE af.table_name = 'application'
				AND afa.field_name = 'high_risk'
				AND af.table_row_id  = {$application_id}
				GROUP BY af.table_row_id
			) rf ON (rf.application_id = ap.application_id)
			WHERE ap.application_id = {$application_id}
		";
		
		return DB_Util_1::querySingleRow($this->db, $query);
	}
}

?>