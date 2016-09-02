<?php

require_once("mysqli.1.php");

abstract class Trigger
{
	//this will be populated by child classes
	//and will contain columns to be audited
	protected $audit;

	//these substitutions will be made in the queries order here is
	//important as some sustitutions such as company_id happen in
	//other substitutions
	private static $SUBSTITUTIONS = array(
		'company_id' => "(SELECT company_id FROM company WHERE name_short='%value%')",
		'application_status_id' => "(select application_status_id from application_status where name_short = '%value%')",
		'loan_type_id' => "(SELECT loan_type_id FROM loan_type WHERE company_id = %%%company_id%%% and name_short = '%value%')",
		'bureau_id' => "(SELECT bureau_id FROM bureau WHERE name_short='%value%')",
		'flag_type_id' => "(SELECT flag_type_id FROM flag_type WHERE name_short='%value%')",
		'document_list_id' => "(SELECT document_list_id FROM document_list WHERE name='%value%' AND company_id = %%%company_id%%%)",
		'agent_id' => "(SELECT agent_id from agent where login='%value%')"
		);
	

	//the table we're updating/inserting
	private $table;
	//the data containing 'column_name' => 'value' pairs
	private $data;
	//the MySQLi_1 object
	private $mysqli;
	//a placeholder for column types & info
	//private $table_info;
	//traded for:
	//$_SESSION['table_trigger'][$table]
	//the agent_id to be used for auditing & history
	private $agent_id;
	//extra where clauses besides primary key
	private $where;
	// last query executed
	private $last_query;
	//Which status history entries are ok to duplicate
	private $no_duplicate_status = array('confirmed');
	//Status change to be inserted in status_history
	private $status_name_short;


	//agent_id is only optional if no auditing or history will be saved
	public function __construct(MySQLi_1 $mysqli, $table, $agent = NULL, $add_no_duplicate_status = NULL)
	{
		$this->mysqli = $mysqli;
		$this->table = $table;
		$this->last_query = FALSE;
		
		if(is_numeric($agent))
			$this->agent_id = $agent;
		elseif($agent == NULL)
			$this->agent_id = " is NULL ";
		else	
			$this->agent_id = $this->Substitute(self::$SUBSTITUTIONS['agent_id'], $agent);
		if(empty($_SESSION['table_trigger'][$table]))
		{
			$_SESSION['table_trigger'][$table] = $this->mysqli->Get_Table_Info($table);
		}
		$this->where = array();

		if( isset($add_no_duplicate_status) )
		{
			// Multiple additional allowances
			if( is_array($add_no_duplicate_status) )
			{
				$this->no_duplicate_status = array_merge($this->no_duplicate_status, $add_no_duplicate_status);
			}
			// Just one additional allowances
			elseif( is_string($add_no_duplicate_status) )
			{
				$this->no_duplicate_status[] = $add_no_duplicate_status;
			}
			// Allow any duplicates, or none at all
			elseif( is_bool($add_no_duplicate_status) )
			{
				$this->no_duplicate_status = $add_no_duplicate_status;
			}
		}
	}

	public function Escape_Value($column, $value)
	{
		//Some assumptions:
		//some column types may be left out: blob, year -- add them if you'd like
		//date/time types make an assumption that functions such as now() will contain
		//'(' and ')'... all others will be quoted
		if(strtoupper($value) == "NULL")
			return $value;

		$column_type = $_SESSION['table_trigger'][$this->table]->$column->Type;
		
		if((strpos($column_type, 'char') !== FALSE) ||
		   (strpos($column_type, 'text') !== FALSE)  ||
		   (strpos($column_type, 'enum') !== FALSE)  )
		{
			return "'" . $this->mysqli->Escape_String($value) . "'";
		}
		elseif(	(strpos($column_type, 'date') !== FALSE)  ||
			(strpos($column_type, 'int') !== FALSE)  ||
			(strpos($column_type, 'time') !== FALSE) )
		{
			if((strpos($value, '(') !== FALSE)  && (strpos($value, ')') !== FALSE) )
			{
				return $value;
			}
		}
		
		return "'" . $value . "'";			
	}

	public function Substitute($substitution_string, $value)
	{
		//don't do the substitution if the value is already an ID
		if(is_numeric($value))
		{
			return $value;
		}
   		$string = str_replace("%value%", $value, $substitution_string);
		$data = (object)$this->data;
		return preg_replace ("/%%%(.*?)%%%/e", "\$data->\\1", $string);
	}

	//create an insert statement and possibly insert a application status history
	public function Insert($data)
	{
		$this->data = $data;

		// We need the unaltered appliction_status_id for the status_history entry
		if( isset($this->data['application_status_id']) )
		{
			$this->status_name_short = $data['application_status_id'];
		}
		else
		{
			$this->status_name_short = "null";
		}

		$query = "insert into {$this->table}\n SET \n ";

		//do substitutions first b/c of neccessary order
		foreach(self::$SUBSTITUTIONS as $column => $value)
		{
			if(!empty($this->data[$column]))
			{
				$this->data[$column] = $this->Substitute(self::$SUBSTITUTIONS[$column], $this->data[$column]);
			}
		}

		foreach($this->data as $column => $value)
		{
			
			//only escape columns that haven't already been substituted
			if(empty(self::$SUBSTITUTIONS[$column]))
			{
				$this->data[$column] = $this->Escape_Value($column, $value);
			}
			$set_cols .= ($set_cols) ? ",".$column."=".$this->Escape_Value($column, $value) : $column."=".$this->Escape_Value($column, $value);
		}
		$query .= $set_cols;
		
		$commit = !$this->mysqli->In_Query();
		
		if($commit)
			$this->mysqli->Start_Transaction();

		try
		{
			$this->last_query = $query;
			$this->mysqli->Query($query);
			//run this after the main query (b/c subselects will rely on data being there)
			$this->Add_Status_History();
		}
		catch(Exception $e)
		{
			if($commit)
				$this->mysqli->Rollback();
			throw $e;
		}

		if($commit)
			$this->mysqli->Commit();
	}

	//create an update statement and possibly insert a application status history *and/or* an audit record
	public function Update($data)
	{
		$this->data = $data;

		// We need the unaltered appliction_status_id for the status_history entry
		if( isset($this->data['application_status_id']) )
		{
			$this->status_name_short = $data['application_status_id'];
		}
		else
		{
			$this->status_name_short = "null";
		}

		$query = "update {$this->table} set\n";

		$primary_key_column = $this->Get_Primary();

		//do substitutions first b/c of neccessary order
		foreach(self::$SUBSTITUTIONS as $column => $value)
		{
			if(!empty($this->data[$column]))
			{
				$this->data[$column] = $this->Substitute(self::$SUBSTITUTIONS[$column], $this->data[$column]);
			}
		}
		
		$values = array();
		foreach($this->data as $column => $value)
		{
			if(empty(self::$SUBSTITUTIONS[$column]))
			{
				$this->data[$column] = $this->Escape_Value($column, $value);
			}

			if($column != $primary_key_column)
				$values[] = $column . " = " . $this->data[$column];
		}

		$query .= join(',', $values);

		$query .= " where {$primary_key_column} = {$this->data[$primary_key_column]}" . $this->Get_Where();

		//$audit_values = $this->Get_Audit_Values();
		//$old_info = $this->Get_Last_Application_Status_Info();

		$row_count = NULL;
		
		$commit = !$this->mysqli->In_Query();

		if($commit)
			$this->mysqli->Start_Transaction();
		
		try
		{
			//echo "{$query}<br>";
			$this->last_query = $query;
			$this->mysqli->Query($query);
			$row_count = $this->mysqli->Affected_Row_Count();
			if($row_count)
			{
				//run these after the main query only if the update has effected rows
				//$this->Add_Status_History($old_info);
				//$this->Add_Audit($audit_values);
			}
		}
		catch(Exception $e)
		{
			if($commit)
				$this->mysqli->Rollback();
			throw $e;
		}

	   	if($commit)
			$this->mysqli->Commit();

		return $row_count;
	}

	public function Add_Where($column, $value, $operator = '=', $condition = 'AND')
	{
		$this->where[] = " {$condition} {$column} {$operator} " . $this->Escape_Value($column, $value);
	}

	//for any custom clauses that may require subselects,
	//order of precedence (with parens), etc
	public function Add_Where_String($string)
	{
		$this->where[] = " {$string}";
	}
	
	public function Get_Last_Query()
	{
		return $this->last_query;	
	}

	private function Get_Where()
	{
		$where_sql = "";
		foreach($this->where as $where_clause)
		{
			$where_sql .= $where_clause;
		}
		return $where_sql;
	}
	
	//add a new application status history record if the status has changed
	private function Add_Status_History($old_info = NULL)
	{
		return TRUE;
		
		// Don't do a history insert if it is a duplicate and the type isnt allowed
		if( in_array($this->status_name_short, $this->no_duplicate_status) &&
		    $this->Status_History_Entry_Exists($this->data['application_id']) )
		{
			return;
		}

		$old_status = '';
		$old_agent = '';
		if($old_info)
		{
			$old_status = $old_info->name_short;
			$old_agent = $old_info->agent;
		}

		$agent_different = TRUE;

		if(is_numeric($this->agent_id))
		{
			$agent_different = $this->agent_id != $old_agent;
		}
		elseif($this->agent_id != " is NULL ")
		{
			$agent_different = $this->Substitute(self::$SUBSTITUTIONS['agent_id'], $old_agent)
				!= $this->Substitute(self::$SUBSTITUTIONS['agent_id'], $this->agent_id);
		}
		
		//prevent the status from being entered twice in a row
		if(!empty($this->data['application_status_id']) &&
		   ($this->data['application_status_id'] != $this->Substitute(self::$SUBSTITUTIONS['application_status_id'], $old_status) ||
			$agent_different) )
		{
			$query = "insert into status_history
						(date_created,
						company_id,
						application_id,
						agent_id,
						application_status_id)
					  values
					  	(now(),
						(select company_id from application where application_id = {$this->data['application_id']}),
						{$this->data['application_id']},
						{$this->agent_id},
						{$this->data['application_status_id']})";

			$this->mysqli->Query($query);
		}
	}

	private function Status_History_Entry_Exists($application_id)
	{
		$query = "
				  SELECT application_status_id
 				   FROM  status_history
 				   WHERE company_id=(SELECT company_id FROM application WHERE application_id={$application_id})
					AND  application_id={$application_id}
					AND  application_status_id=(SELECT application_status_id FROM application_status WHERE name_short='{$this->status_name_short}')";

		$result = $this->mysqli->Query($query);

		if( $result->Row_Count() > 0 )
			return true;
		else
			return false;
	}

	private function Get_Last_Application_Status_Info()
	{
		if($this->table == 'application' && array_search('application_status_id', array_keys($this->data)) !== FALSE)
		{
			$agent_select = "agent.login as agent";
			$agent_table = ",agent";
			$agent_where = "and status_history.agent_id = agent.agent_id";
			if(is_numeric($this->agent_id))
			{
				$agent_select = "status_history.agent_id as agent";
				$agent_table = "";
				$agent_where = "";
			}
			
			$query = "select
						application_status.name_short,
						{$agent_select}
					  from
					  	status_history,
					  	application_status
						{$agent_table}
					  where status_history.application_id = {$this->data['application_id']}
					  and status_history.application_status_id = application_status.application_status_id
					  {$agent_where}
					  order by status_history_id desc
					  limit 1";

			//echo "{$query}\n";
			
			$result = $this->mysqli->Query($query);

			$row = $result->Fetch_Object_Row();
			if($row) return $row;
		}
		return NULL;
	}

	
	//add an audit record if a column specified to be audited has
	//changed this function relies on being called before the update
	//b/c subselects record the old values
	private function Add_Audit($values)
	{
		return TRUE;
		
		$query = "insert into application_audit
				  	(date_created,
				  	company_id,
					application_id,
					table_name,
					column_name,
				  	value_before,
					value_after,
					update_process,
					agent_id
					)
					values
					";
		
		if(count($values))
		{
			$query .= join(",\n", $values);
			//run the query
			//echo "{$query}\n";
			return $this->mysqli->Query($query);
		}
	}

	private function Get_Audit_Values()
	{
		$values = array();
		foreach($this->data as $column_name => $new_value)
		{
			//only insert a record for columns we're auditing
			if(array_search($column_name, $this->audit) !== FALSE)
			{
				$column_type = $_SESSION['table_trigger'][$this->table]->$column_name->Type;
				//format it the way the DB sees it (esp. if it's a date/time)
				if((strpos('date', $column_type) !== FALSE) ||
				   (strpos('time', $column_type) !== FALSE))
				{
					$select_new_value = "timestamp(" . $new_value . ")";
					$select_old_value = "timestamp(" . $column_name . ") as {$column_name}";
				}
				else
				{
					$select_new_value = $new_value;
					$select_old_value = $column_name;
				}
				
				//get old value
				$old_row = $this->Get_Column($select_old_value);

				$new_row = $this->Get_Value($select_new_value);

				//if old/new are different, insert a new row
				//echo "old: {$old_row->$column_name}, new: {$new_row->value}\n";
				if(!$old_row || $old_row->$column_name != $new_row->value)
				{
					$values[] = "(now(),
								(select company_id from application where application_id = {$this->data['application_id']}),
								{$this->data['application_id']},
								'{$this->table}',
								'{$column_name}',
								'{$old_row->$column_name}',
								{$new_value},
								'php::trigger::{$this->table}',
								{$this->agent_id})";
				}
			}
		}
		return $values;
	}

	private function Get_Column($column)
	{
		$primary_key_column = $this->Get_Primary();		
		//get the old value from the table
		$select_query = "select
							{$column}
					  	 from
						  	{$this->table}
						 where
						 {$primary_key_column} = {$this->data[$primary_key_column]}";

		//echo "{$select_query}\n";
				
		$result = $this->mysqli->Query($select_query);
		return $result->Fetch_Object_Row();		
	}

	private function Get_Value($value)
	{
		//get the new value, formatted as necessary
		$select_query = "select
								{$value}
								as value";

		$result = $this->mysqli->Query($select_query);
		return $result->Fetch_Object_Row();
	}
	
	//returns the column marked with the 'PRI' type key in mysql
	private function Get_Primary()
	{
		foreach($_SESSION['table_trigger'][$this->table] as $column_name => $column_info)
		{
			if($column_info->Key == "PRI")
				return $column_name;
		}
		return NULL;
	}

}

/**
 * The following are the classes that should be instantiated.  They
 * should also populate the audit array for any columns that need to
 * be audited.  $agent is NOT OPTIONAL if any columns are to be
 * audited *and/or* application_status_id can possibly be altered.
 */

class Application_Trigger extends Trigger
{
	public function __construct(MySQLi_1 $mysqli, $agent, $add_no_duplicate_status = NULL)
	{
		parent::__construct($mysqli, 'application', $agent, $add_no_duplicate_status);
		//if the array below is not empty, $agent is not optional!
		$this->audit = array('bank_name',
							 'bank_aba',
							 'bank_account',
							 'date_fund_actual',
							 'fund_actual',
							 'date_first_payment',
							 'income_monthly',
							 'income_direct_deposit',
							 'income_frequency',
							 'paydate_model',
							 'day_of_week',
							 'last_paydate',
							 'day_of_month_1',
							 'day_of_month_2',
							 'week_1',
							 'week_2',
							 'ssn',
							 'dob',
							 'email',
							 'name_last',
							 'name_first',
							 'street',
							 'unit',
							 'city',
							 'state',
							 'zip_code',
							 'phone_home');
	}
}

class Bureau_Inquiry_Trigger extends Trigger
{
	public function __construct(MySQLi_1 $mysqli, $agent = NULL, $add_no_duplicate_status = NULL)
	{
		parent::__construct($mysqli, 'bureau_inquiry', $agent, $add_no_duplicate_status);
		//if the array below is not empty, $agent_id is not optional!
		$this->audit = array();
	}
}

class Campaign_Info_Trigger extends Trigger
{
	public function __construct(MySQLi_1 $mysqli, $agent = NULL, $add_no_duplicate_status = NULL)
	{
		parent::__construct($mysqli, 'campaign_info', $agent, $add_no_duplicate_status);
		//if the array below is not empty, $agent is not optional!
		$this->audit = array();
	}
}

class Application_Flag_Trigger extends Trigger
{
	public function __construct(MySQLi_1 $mysqli, $agent = NULL, $add_no_duplicate_status = NULL)
	{
		parent::__construct($mysqli, 'application_flag', $agent, $add_no_duplicate_status);
		//if the array below is not empty, $agent is not optional!
		$this->audit = array();
	}	
}

class Site_Trigger extends Trigger
{
	public function __construct(MySQLi_1 $mysqli, $agent = NULL, $add_no_duplicate_status = NULL)
	{
		parent::__construct($mysqli, 'site', $agent, $add_no_duplicate_status);
		//if the array below is not empty, $agent is not optional!
		$this->audit = array();
	}	
}

class Personal_Reference_Trigger extends Trigger
{
	public function __construct(MySQLi_1 $mysqli, $agent = NULL, $add_no_duplicate_status = NULL)
	{
		parent::__construct($mysqli, 'personal_reference', $agent, $add_no_duplicate_status);
		//if the array below is not empty, $agent is not optional!
		$this->audit = array();
	}	
}

class Document_Trigger extends Trigger
{
	public function __construct(MySQLi_1 $mysqli, $agent = NULL, $add_no_duplicate_status = NULL)
	{
		parent::__construct($mysqli, 'document', $agent, $add_no_duplicate_status);
		//if the array below is not empty, $agent is not optional!
		$this->audit = array();
	}	
}

class Demographics_Trigger extends Trigger
{
	public function __construct(MySQLi_1 $mysqli, $agent = NULL, $add_no_duplicate_status = NULL)
	{
		parent::__construct($mysqli, 'demographics', $agent, $add_no_duplicate_status);
		//if the array below is not empty, $agent is not optional!
		$this->audit = array();
	}		
}

class Login_Trigger extends Trigger
{
	public function __construct(MySQLi_1 $mysqli, $agent = NULL, $add_no_duplicate_status = NULL)
	{
		parent::__construct($mysqli, 'login', $agent, $add_no_duplicate_status);
		//if the array below is not empty, $agent is not optional!
		$this->audit = array();
	}
}

?>