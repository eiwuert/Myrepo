<?php

require_once("mysqli.1.php");
require_once("security.6.php");
require_once("ecash_admin_resources.php");

/**
* Access Control List class
* using MySQLi and new database design
*/
class ACL_2
{
	/**
	* Holds the mysqli object for performing queries
	*
	* @access protected
	* @var    object
	*/
	protected $mysqli;

	/**
	* Holds the system ID. This variable should be assigned as soon as the 
	* system_id becomes avaliable. This should be assigned with the
	* Set_System_Id(..) function.
	*
	* @access protected
	* @var	 object
	*/
	protected $system_id;
	protected $sorted_user_acl;
	protected $unsorted_user_acl;
	
	/**
	* constructor, saves the mysqli object or creates a new one if one is not supplied.
	* It is STRONGLY RECOMMENDED that you call the Set_System_Id right after the constructor
	* if you have access to the system_id.
	*
	* @param MySQLi_1 $mysqli	optional MySQLi_1 object
	*
	* @access public
	* @return void
	*/
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

		$this->sorted_user_acl = array();
		$this->unsorted_user_acl = array();
	}

	/****************************************************************************/
	/****************************************************************************/
	//
	//  START REQUIRED INTERNAL FUNCTIONS
	//
	/****************************************************************************/
	/****************************************************************************/



	/**
	 *
	 */
	public function Set_System_Id($system_id)
	{
		$this->system_id = $system_id;
	}

	/**
	 *
	 */
	public function Get_System($system_name_short)
	{
		$query = "select
					system_id,
					name,
					name_short
				  from system
				  where active_status = 'Active'
				  and name_short = '{$system_name_short}'";
		$result = $this->mysqli->Query($query);

		return $result->Fetch_Object_Row();
	}





	/****************************************************************************/
	/****************************************************************************/
	//
	//  STOP REQUIRED INTERNAL FUNCTIONS
	//
	/****************************************************************************/
	/****************************************************************************/




	/****************************************************************************/
	/****************************************************************************/
	//
	//  ADMIN FUNCTIONS START
	//
	/****************************************************************************/
	/****************************************************************************/

	/**
	* Returns an array of all compaines
	*
	* @access public
	* @throws MySQL_Exception
	* @return array		array of company row objects
	*/
	public function Get_Companies($ignore_inactive = FALSE)
	{
		
		$query = "SELECT company_id, name, name_short, property_id from company";
		
		if ($ignore_inactive)
		{
			$query .= " where company.active_status = 'active' ORDER By name ASC";
		}
		
		$result = $this->mysqli->Query($query);
		
		$companies = array();
		$archive = array();
		
		while ($row = $result->Fetch_Object_Row())
		{
			
			if (strtolower(substr($row->name, 0, 7)) == 'archive')
			{
				$archive[] = $row;
			}
			else
			{
				$companies[] = $row;
			}
			
		}
		
		$companies = array_merge($companies, $archive);
		return $companies;
		
	}

	/**
	* Returns an array of all agents
	*
	* @access public
	* @throws MySQL_Exception
	* @return array		array of agent row objects
	*/
	public function Get_Agents()
	{
		$system_sql = ($this->system_id == NULL) ? "" : "where system_id = " . $this->system_id . " ";
		$query = "select
					a.active_status,
					a.agent_id,
					a.name_last,
					a.name_first,
					a.login,
					a.system_id
				  from
				  	agent a
				  {$system_sql}
				  order by login
				  ";

		$result = $this->mysqli->Query($query);
		$agents = array();
		while($row = $result->Fetch_Object_Row())
		{
			$agents[] = $row;
		}

		return $agents;		
	}


	/**
	 * 
	 */
	public function Get_Agents_For_Company($company_id, $control_option = null, $active_only = false)
	{
		$control_where = '';
		if (isset($control_option))
		{
			$control_where = "co.name_short = '{$control_option}' AND ";
		}
		
		$active_where = '';
		if ($active_only)
		{
			$active_where = "a.active_status = 'active' AND ";
		}
		
		$query = "
			SELECT 
				a.active_status,
				a.agent_id,
				a.name_last,
				a.name_first,
				a.login,
				a.system_id
			FROM 
				agent a
				JOIN agent_access_group AS aag USING (agent_id)
				JOIN access_group_control_option AS agco USING (access_group_id)
				JOIN control_option AS co USING (control_option_id)
			WHERE 
				$active_where
				$control_where
				company_id = {$company_id}
			ORDER BY 
				name_first ASC, name_last ASC
		";

		$result = $this->mysqli->Query($query);
		$agents = array();
		while($row = $result->Fetch_Object_Row())
		{
			$agents[] = $row;
		}

		return $agents;		
	}
	



	/**
	* Adds a new agent to the database
	*
	* @param string $login		login/username
	* @param string $name_first	first name
	* @param string $name_last	last name
	* @param string $password	clear text password
	* @param boolean $active	optionally specify if the user is active (default TRUE)
	* 
	* @access public
	* @throws MySQL_Exception
	* @return integer			new agent_id for the agent just added
	*/
	public function Add_Agent($login, $name_first, $name_last, $password, $active = TRUE)
	{
		$password = Security_6::Mangle_Password($password);
		$active_status = $active ? 'Active' : 'Inactive';

		$query = "insert into agent
				  (date_created, login, name_first, name_last, active_status, system_id, crypt_password)
				  values
				  (now(), '{$login}', '{$name_first}', '{$name_last}', '{$active_status}', " . $this->system_id . ", '{$password}')";

		$result = $this->mysqli->Query($query);
		return $this->mysqli->Insert_Id();
	}

	/**
	* Updates an existing agent
	*
	* @param string $agent_id	agent_id of the agent to update
	* @param string $login		login/username update
	* @param string $name_first	first name update
	* @param string $name_last	last name update
	* @param boolean $active	specify if the user is active
	* @param string $password	optional clear text password if it is to be changed
	* 
	* @access public
	* @throws MySQL_Exception
	* @return MySQLi_Result_1	the result object of the query
	*/
	public function Update_Agent($agent_id, $login, $name_first, $name_last, $password = NULL, $active = TRUE)
	{
		$system_sql = ($this->system_id == NULL) ? "" : ", system_id = " . $this->system_id . " ";

		$password_sql = '';
		if($password)
		{
			$password = Security_6::Mangle_Password($password);
			$password_sql = ", crypt_password = '{$password}'";
		}

		$active_status = $active ? 'Active' : 'Inactive';
		
		$query = "update agent set
					login = '{$login}'
					, name_first = '{$name_first}'
					, name_last = '{$name_last}'
					, active_status = '{$active_status}'
					{$password_sql}
					{$system_sql}
				  where agent_id = '{$agent_id}'";

		return $this->mysqli->Query($query);
	}

	/**
	* Returns an array of all groups plus an indicator
	* if there are any agents associated
	*
	* @access public
	* @throws MySQL_Exception
	* @return array		array of group row objects 
	*/
	public function Get_Groups()
	{
		$system_sql = ($this->system_id == NULL) ? "" : "WHERE ag.system_id = " . $this->system_id . " ";

		$query = "select
					ag.active_status,
					ag.company_id,
					ag.access_group_id as group_id,
					ag.name,
					if(count(aag.agent_id) = 0, 0, 1) is_used
				  from
				  	access_group ag left join agent_access_group aag
				  on (aag.access_group_id = ag.access_group_id
				  )
				  {$system_sql}
				  group by
					ag.active_status,
					ag.company_id,
					ag.name, ag.access_group_id;
				  ";

		//echo "<pre>Query:\n" . str_replace("\t","  ",$query) . "</pre>\n";
		//exit;
		$result = $this->mysqli->Query($query);
		$groups = array();
		while($row = $result->Fetch_Object_Row())
		{
			$groups[] = $row;
		}

		return $groups;							
	}

	/**
	 *
	 */
	public function Add_Group($name, $company_id, $section_ids, $read_onlys)
	{
		$query = "insert into access_group
				  (date_modified, date_created, active_status, company_id, system_id, name)
				  values
				  (now(), now(), 'Active', {$company_id}, " . $this->system_id . ", '{$name}')";

		$result = $this->mysqli->Query($query);

		$group_id = $this->mysqli->Insert_Id();

		$this->Update_Sections($group_id, $company_id, $section_ids, $read_onlys);

		return $group_id;
	}

	/**
	 *
	 */
	protected function Update_Sections($group_id, $company_id, $section_ids, $read_onlys)
	{
		$this->Delete_ACL($group_id);
		
		$query = "insert into acl
				  (date_modified, date_created, active_status, company_id, access_group_id, section_id, read_only)
				  values
				  ";

		$values = array();
		foreach($section_ids as $section_id)
		{
			$values[] = "(now(), now(), 'Active', {$company_id}, {$group_id}, {$section_id}, ".($read_onlys[$section_id] ? 1 : 0).")";
		}
		$query .= implode(",\n", $values);

		$this->mysqli->Query($query);
	}

	/**
	 *
	 */
	protected function Delete_ACL($group_id)
	{
		$query = "delete from acl where access_group_id = {$group_id}";

		$this->mysqli->Query($query);		
	}

	/**
	 *
	 */
	public function Update_Group($group_id, $name, $company_id, $section_ids, $read_onlys)
	{
		$system_sql = ($this->system_id == NULL) ? "" : ", system_id = " . $this->system_id . " ";
		$query = "update access_group set
					name = '{$name}',
					company_id = {$company_id}
					{$system_sql}
				  where access_group_id = {$group_id}";

		$this->mysqli->Query($query);
		
		$this->Update_Sections($group_id, $company_id, $section_ids, $read_onlys);
	}


	/**
	 *
	 */
	public function Remove_Group($group_id)
	{
		$this->Delete_ACL($group_id);

		// delete the access group
		$query = "delete from access_group where access_group_id = {$group_id}";
		$this->mysqli->Query($query);

		$this->Remove_Access_Group_Control_Options($group_id);

		return TRUE;
	}



	/**
	 *
	 */
	public function Remove_Access_Group_Control_Options($access_group_id)
	{
		$query = "delete from access_group_control_option where access_group_id = {$access_group_id}";
		$this->mysqli->Query($query);

		return TRUE;
	}



	/**
	* Returns an array of all sections that an agent
	* is allowed to see/use based on relationships and
	* active status
	*
	* @param integer $agent_id	agent_id to check for
	* 
	* @access public
	* @throws MySQL_Exception
	* @return array		array of section row objects 
	*/
	public function Get_Allowed_Sections($agent_id)
	{
		$system_sql = ($this->system_id == NULL) ? "" : "and s.system_id = " . $this->system_id . " ";

		$query = "select
						acl.company_id,
						s.active_status,
						s.name,
						s.description,
						s.system_id,
						s.section_id,
						s.section_parent_id,
						s.sequence_no,
						s.level,
						acl.read_only
					from
						section s,
						agent a,
						acl,
						agent_access_group aag
					where
						a.agent_id = {$agent_id}
					and a.active_status = 'Active'
					and a.agent_id = aag.agent_id
					and aag.active_status = 'Active'
					and aag.access_group_id = acl.access_group_id
					and acl.active_status = 'Active'
					and acl.section_id = s.section_id
					and s.active_status = 'Active'
					{$system_sql}
						";

		$result = $this->mysqli->Query($query);
		$sections = array();
		while($row = $result->Fetch_Object_Row())
		{
			$sections[$row->company_id][$row->name] = $row;
		}

		return $sections;				
	}



	/*
	*/
	public function Get_Company_Agent_Allowed_Sections($agent_id, $company_id)
	{
		$system_sql = ($this->system_id == NULL) ? "" : "and s.system_id = " . $this->system_id . " ";

		$query = "select
						acl.company_id,
						s.active_status,
						s.name,
						s.description,
						s.system_id,
						s.section_id,
						s.section_parent_id,
						s.sequence_no,
						s.level,
						acl.read_only
					from
					  	section s,
						agent a,
						acl,
						agent_access_group aag
					where
						a.agent_id = {$agent_id}
					and a.active_status = 'Active'
					and a.agent_id = aag.agent_id
					and aag.active_status = 'Active'
					and aag.access_group_id = acl.access_group_id
					and acl.active_status = 'Active'
					and acl.section_id = s.section_id
					and acl.company_id = " . $company_id . "
					and s.active_status = 'Active'
					{$system_sql}
						";

		$result = $this->mysqli->Query($query);
		$sections = array();
		while($row = $result->Fetch_Object_Row())
		{
			$sections[$row->section_id] = $row;
		}

		return $sections;				
	}



	/*
	 *
	 */
	public function Get_All_Control_Options()
	{
		$control_option = array();

		$query = "select
						control_option_id,
						name_short,
						name,
						description,
						type
					from
						control_option;";

		$result = $this->mysqli->Query($query);
		while($row = $result->Fetch_Object_Row())
		{
			$control_option[$row->control_option_id] = $row;
		}

		return $control_option;
	}



	/**
	 *
	 */
	public function Get_All_Access_Group_Control_Options()
	{
		
		$query = "
			select
				access_group_id,
				control_option_id
			from
				access_group_control_option
		";
		$result = $this->mysqli->Query($query);
		
		$all = array();
		
		while($row = $result->Fetch_Object_Row())
		{
			
			if (!isset($all[$row->access_group_id]))
			{
				$all[$row->access_group_id] = array(
					'access_group_id' => (int)$row->access_group_id,
					'sections' => array(),
				);
			}
			
			$all[$row->access_group_id]['sections'][] = $row->control_option_id;
			
		}
		
		$all = array_values($all);
		
		return $all;
		
	}



	/**
	 *
	 */
	public function Add_Access_Group_Control_Options($access_group_id, $used_options)
	{
		foreach ($used_options as $key => $value)
		{
			$query = "insert into
							access_group_control_option (date_modified, date_created, access_group_id, control_option_id)
						values
							(now(), now(), " . $access_group_id . ", " . $value . ");";

			$this->mysqli->Query($query);
		}

		return TRUE;
	}



	/**
	*/
	public function Get_Control_Info($agent_id, $company_id)
	{
		
		$query = "
			SELECT
				name_short
			FROM
				control_option AS co JOIN
				access_group_control_option AS agco ON (agco.control_option_id = co.control_option_id) JOIN
				agent_access_group AS aag ON (aag.access_group_id = agco.access_group_id)
			WHERE
				aag.agent_id = {$agent_id} AND
				aag.company_id = {$company_id}
		";
		$result = $this->mysqli->Query($query);
		
		$names = array();
		
		while ($row = $result->Fetch_Array_Row(MYSQLI_NUM))
		{
			$names[] = $row[0];
		}
		
		return $names;
		
	}




	/**
	* Returns an array of all sections ordered by
	* level then sequence
	*
	* @access public
	* @throws MySQL_Exception
	* @return array		array of section row objects 
	*/
	public function Get_Sections()
	{
		
		// JRS: Added read_only_option field from DB schema
		$system_sql = ($this->system_id == NULL) ? "" : "	and s.system_id = " . $this->system_id . " ";
		
		$query = "
			select
				s.name,
				s.description,
				s.active_status,
				s.system_id,
				s.section_id,
				s.section_parent_id,
				s.sequence_no,
				s.level,
				s.read_only_option
			from
					section s
			where
				s.active_status = 'active'
				{$system_sql}
			order by
				s.section_parent_id,
				s.sequence_no
		";
		$result = $this->mysqli->Query($query);
		
		$sections = array();
		
		while($row = $result->Fetch_Object_Row())
		{
			$sections[] = $row;
		}

		return $sections;
		
	}
	
	/**
	* Adds an agent to a group
	*
	* @param integer $agent_id	agent_id to associate
	* @param integer $group_id	group_id to associate
	*
	* @access public
	* @throws MySQL_Exception
	* @return boolean		TRUE if the add was successful
	*/
	public function Add_Agent_To_Group($agent_id, $group_id)
	{
		$query = "insert into agent_access_group
				  	(date_created, active_status,
					company_id,
					agent_id, access_group_id)
				  values
				  	(now(), 'Active',
					(select company_id from access_group where access_group_id = {$group_id}),
					{$agent_id}, {$group_id})";

		$result = $this->mysqli->Query($query);
		return TRUE;
	}

	/**
	* Remove an agent to a group
	*
	* @param integer $agent_id
	* @param integer $group_id
	*
	* @access public
	* @throws MySQL_Exception
	* @return boolean		TRUE if the add was successful
	*/
	public function Delete_Agent_From_Group($agent_id, $group_id)
	{
		$query = "delete from agent_access_group
				  where agent_id = {$agent_id}
				  and access_group_id = {$group_id}";

		$result = $this->mysqli->Query($query);
		return TRUE;
	}

	/**
	 *
	 */
	public function Get_Companies_Groups($logged_in_agent_id, $section_name = 'privs')
	{
		$system_sql = ($this->system_id == NULL) ? "" : "and ag.system_id = " . $this->system_id . " ";
		$system_sql2 = ($this->system_id == NULL) ? "" : "and s.system_id = " . $this->system_id . " ";

		$query = "
				  select distinct
				  	acl.company_id,
					acl.access_group_id as group_id,
					ag.name group_name
				  from
					acl,
					access_group ag
				  where
				  	acl.access_group_id = ag.access_group_id
				  {$system_sql}
				  and acl.company_id in (				 
							SELECT
								acl2.company_id
					   		from
							  	agent_access_group aag,
								acl acl2,
								section s
							where
								aag.agent_id = {$logged_in_agent_id}
							and aag.access_group_id = acl2.access_group_id
							and acl2.section_id = s.section_id
							{$system_sql2}
							and s.name = '{$section_name}'
							)
				  	";
		
		$result = $this->mysqli->Query($query);

		$rows = array();
		while( $row = $result->Fetch_Object_Row() )
		{
			$rows[] = $row;
		}
		return $rows;		
	}

	/**
	 *
	 * @params
	 *		$logged_in_agent_id  This is the loged in agent id.
	 *		$level  This is the level and its childern to retrieve.
	 */
	public function Get_Groups_Sections($logged_in_agent_id, $level = 0)
	{
		
		$system_sql = ($this->system_id == NULL) ? "" : "and section.system_id = " . $this->system_id . " ";
		
		$query = "
			SELECT
				DISTINCT acl.company_id
			from
				agent_access_group as aag,
				acl,
				section
			where
				aag.agent_id = {$logged_in_agent_id}
				and aag.access_group_id = acl.access_group_id
				and acl.section_id = section.section_id
				{$system_sql}
		";
		$result = $this->mysqli->Query($query);
		
		$companies = array();
		
		while ($row = $result->Fetch_Array_Row())
		{
			$companies[] = $row['company_id'];
		}
		
		$query = "
			select distinct
				acl.access_group_id as group_id,
				acl.read_only as read_only,
				section.section_id
			from
				acl,
				section
			where
				acl.section_id = section.section_id
				{$system_sql}
				and acl.company_id in (".implode(', ', $companies).")
				and section.level >= " . $level;
		
		$result = $this->mysqli->Query($query);
		
		$rows = array();
		while( $row = $result->Fetch_Object_Row() )
		{
			$rows[] = $row;
		}
		
		return $rows;
		
	}

	/**
	 *
	 */
	public function Get_Agents_Groups()
	{
		$query = "
			select
				aag.agent_id,
				aag.access_group_id as group_id
			from
				agent_access_group aag
		";
		
		$result = $this->mysqli->Query($query);

		$rows = array();
		while( $row = $result->Fetch_Object_Row() )
		{
			$rows[] = $row;
		}
		return $rows;		
	}



	/****************************************************************************/
	/****************************************************************************/
	//
	//  ADMIN FUNCTIONS STOP
	//
	/****************************************************************************/
	/****************************************************************************/



	/**
	 *
	 */
   public function Fetch_User_ACL($agent_id, $company_id)
   {
      
      $user_acl = $this->Get_Allowed_Sections($agent_id);
			
      foreach ($user_acl as $key=>$value)
      {
				
				$company_array = $user_acl[$key];
				
				// kill this..?
				if (isset($company_array['ecash']))
				{
					unset($company_array['ecash']);
				}
				
				foreach ($company_array as $values)
				{
					if (isset($values->level)) $values->level -= 2;
				}
				
      }
			
			$this->unsorted_user_acl = $user_acl;
			$this->Sort_User_Acl($company_id);

      return TRUE;
   }



	/**
	*
	*/
	protected function Sort_User_Acl($company_id)
	{
		
		if (isset($this->unsorted_user_acl[$company_id]))
		{
			$admin_resources = new Admin_Resources($this->unsorted_user_acl[$company_id], 0, 0);
			$this->sorted_user_acl = $admin_resources->getTree();
		}
		else
		{
			$this->sorted_user_acl = array();
		}
		
		return;
		
	}




	/**
	*
	*/
  public function Acl_Access_Ok($section, $company_id)
	{
		if ($company_id === NULL) $company_id = $this->company_id;
		return isset($this->unsorted_user_acl[$company_id][$section]);
	}

	/**
	*
	*/
   public function Get_Acl_Access($parent = NULL)
	{
		$result = array();
		if ($parent == NULL)
		{
			foreach($this->sorted_user_acl as $sorted_parent => $sorted_child)
			{
				$result[] = $sorted_parent;
			}
		}
		else
		{		  
			foreach($this->sorted_user_acl as $sorted_parent => $sorted_child)
			{
				if ($sorted_child['name'] == $parent)
				{
				  
					if (isset($sorted_child['children']))
				  {
						foreach($sorted_child['children'] as $sorted_child_key => $sorted_child_value)
						{
							$result[] = $sorted_child_key;
						}
				  }
				  else
				  {
				    $result = array();
				  }
					
				}
			}
		}

		return $result;
	}
	
	/**
	*
	*/
   public function Get_Acl_Names($array_of_names)
	{
		
		$result = array();
		$found = FALSE;
		
		foreach ($array_of_names as $name)
		{
			foreach($this->sorted_user_acl as $key => $value)
			{
				if ($key == $name)
				{
					$result[$key] = $value['name'];
					$found = TRUE;
				}
			}
		}

		if (!$found)
		{
			
			foreach($this->sorted_user_acl as $key => $value)
			{
				
				foreach ($value as $child_name => $child_value)
				{
					if (is_array($child_value) && count($child_value) > 0 )
					{
						foreach($child_value as $xxx => $yyy)
						{
							if (in_array($xxx, $array_of_names)) //($xxx == $test)
							{
								$result[$xxx] = $yyy['name'];
							}
						}
					}
				}
				
			}
			
		}

		return $result;
	}
	
}

?>
