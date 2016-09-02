<?php

require_once(DIR_LIB . "mysqli.1.php");
require_once(LIB_DIR . "security.php");
require_once("admin_resources.php");

/**
* Access Control List class
* using MySQLi and new database design
*/
class ACL_3
{
	/**
	* Holds the mysqli object for performing queries
	*
	* @access private
	* @var    object
	*/
	private $mysqli;

	/**
	* Holds the system ID. This variable should be assigned as soon as the 
	* system_id becomes avaliable. This should be assigned with the
	* Set_System_Id(..) function.
	*
	* @access private
	* @var	 object
	*/
	private $system_id;



	private $sorted_user_acl;
	private $unsorted_user_acl;
	
	private $acl_disable_mask;
	
	private $server;


	
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
	public function __construct(Server $server, $mysqli = NULL)
	{
		if($mysqli == NULL)
		{
			$this->mysqli = new MySQLi_1(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		}
		else
		{
			$this->mysqli = $mysqli;
		}


		// set user acl
		//$this->user_acl
		$this->sorted_user_acl = array();
		$this->unsorted_user_acl = array();
		$this->acl_disable_mask = array();
		
		
		$this->server = $server;
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
	* Gets the views for a section
	*
	* @param string $company_id	the Company ID you are getting views for
	* @param string $section_id	the ID of the Section you are getting views for
	* 
	* @access public
	* @throws MySQL_Exception
	* @return array		array of section view flags
	*/
	public function Get_Section_Views($company_id, $module)
	{
		$query = "SELECT name FROM section_views
						LEFT JOIN company_section_view ON section_views.section_view_id = company_section_view.section_view_id
						WHERE company_id = ".$company_id." 
						AND company_section_view.section_id = (select section_id from section where name = '".$module."')";

		$result = $this->mysqli->Query($query);

		//$section_views = array();
		
		while( $row = (array) $result->Fetch_Object_Row() )
		{
			$section_views[$row['name']] = TRUE;
		}

		return $section_views;
	}
	
	/**
	* Returns an array of all compaines
	*
	* @access public
	* @throws MySQL_Exception
	* @return array		array of company row objects
	*/
	public function Get_Companies_All($ignore_inactive = FALSE)
	{
		$query = "SELECT company_id, name, name_short, property_id from company";

		if ($ignore_inactive)
		{
			$query .= " and company.active_status = 'active'";
		}
		
		$result = $this->mysqli->Query($query);

		$companies = array();
		while( $row = $result->Fetch_Object_Row() )
		{
			$companies[] = $row;
		}

		return $companies;
	}
	
	/**
	* Returns an array of all compaines
	*
	* @access public
	* @throws MySQL_Exception
	* @return array		array of company row objects
	*/
	public function Get_Companies($ignore_inactive = FALSE)
	{
		$query = "SELECT company_id, name, name_short, property_id from company where company_id = {$this->server->company_id}";

		if ($ignore_inactive)
		{
			$query .= " and company.active_status = 'active'";
		}
		
		$result = $this->mysqli->Query($query);

		$companies = array();
		while( $row = $result->Fetch_Object_Row() )
		{
			$companies[] = $row;
		}

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
		$system_sql = "where company_id = ".$this->server->company_id . ( ($this->system_id == NULL) ? "" : " and system_id = " . $this->system_id);
		$query = "select
					a.active_status,
					a.agent_id,
					a.name_last,
					a.name_first,
					a.login,
					a.system_id,
					a.company_id
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
				  (date_created, login, name_first, name_last, active_status, system_id, crypt_password, company_id)
				  values
				  (now(), '{$login}', '{$name_first}', '{$name_last}', '{$active_status}', " . $this->system_id . ", '{$password}',".$this->server->company_id.")";

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
		$system_sql = ($this->system_id == NULL) ? "" : "and ag.system_id = " . $this->system_id . " ";

		$query = "select
					ag.active_status,
					ag.company_id,
					ag.access_group_id as group_id,
					ag.name,
					if(count(aag.agent_id) = 0, 0, 1) is_used
				  from
				  	access_group ag left join agent_access_group aag
				  on (aag.access_group_id = ag.access_group_id {$system_sql})
				  where ag.company_id = {$this->server->company_id}				  
				  group by
					ag.active_status,
					ag.company_id,
					ag.access_group_id,
					ag.name
				  ";

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
	public function Add_Group($name, $company_id, $section_ids)
	{
		$query = "insert into access_group
				  (date_modified, date_created, active_status, company_id, system_id, name)
				  values
				  (now(), now(), 'Active', {$company_id}, " . $this->system_id . ", '{$name}')";

		$result = $this->mysqli->Query($query);

		$group_id = $this->mysqli->Insert_Id();

		$this->Update_Sections($group_id, $company_id, $section_ids);

		return $group_id;
	}

	/**
	 *
	 */
	private function Update_Sections($group_id, $company_id, $section_ids)
	{
		$this->Delete_ACL($group_id);
		
		$query = "insert into acl
				  (date_modified, date_created, active_status, company_id, access_group_id, section_id)
				  values
				  ";

		$values = array();
		foreach($section_ids as $section_id)
		{
			$values[] = "(now(), now(), 'Active', {$company_id}, {$group_id}, {$section_id})";
		}
		$query .= implode(",\n", $values);

		$this->mysqli->Query($query);
	}

	/**
	 *
	 */
	private function Delete_ACL($group_id)
	{
		$query = "delete from acl where access_group_id = {$group_id}";

		$this->mysqli->Query($query);		
	}

	/**
	 *
	 */
	public function Update_Group($group_id, $name, $company_id, $section_ids)
	{
		$system_sql = ($this->system_id == NULL) ? "" : ", system_id = " . $this->system_id . " ";
		$query = "update access_group set
					name = '{$name}',
					company_id = {$company_id}
					{$system_sql}
				  where access_group_id = {$group_id}";

		$this->mysqli->Query($query);
		
		$this->Update_Sections($group_id, $company_id, $section_ids);
	}

	/**
	 *
	 */
	public function Remove_Group($group_id)
	{
		$this->Delete_ACL($group_id);

		$query = "
			DELETE FROM access_group
			WHERE access_group_id = $group_id";

		$this->mysqli->Query($query);		
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
						s.default_section_id,
						s.sequence_no,
						s.level
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
					order by s.sequence_no
						";

		$result = $this->mysqli->Query($query);
		$sections = array();
		while($row = $result->Fetch_Object_Row())
		{
			$sections[$row->company_id][$row->name] = $row;
		}
		


		return $sections;				
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
		$system_sql = ($this->system_id == NULL) ? "" : "where s.system_id = " . $this->system_id . " ";
		$query = "select
						s.name,
						s.description,
						s.active_status,
						s.system_id,
						s.section_id,
						s.section_parent_id,
						s.default_section_id,
						s.sequence_no,
						s.level
					from
					  	section s
					{$system_sql}
					order by s.section_parent_id, s.sequence_no";

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
		$system_sql = ($this->system_id == NULL) ? "" : "and s.system_id = " . $this->system_id . " ";
		$system_sql2 = ($this->system_id == NULL) ? "" : "and s2.system_id = " . $this->system_id . " ";

		$query = "
				  select distinct
					acl.access_group_id as group_id,
					s.section_id
				  from
					acl,
					section s
				  where
				  	acl.section_id = s.section_id
				  {$system_sql}
				  and acl.company_id in (				 
							SELECT
								acl.company_id
					   		from
							  	agent_access_group aag,
								acl,
								section s2
							where
								aag.agent_id = {$logged_in_agent_id}
							and aag.access_group_id = acl.access_group_id
							and acl.section_id = s2.section_id
							{$system_sql2}
							)
					and s.level >= " . $level;

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
		$query = "select
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
      $section_array = array();
      $company_array = array();
      $user_acl = array();
      $user_acl = $this->Get_Allowed_Sections($agent_id);
      foreach ($user_acl as $key => $value)
      {
         $company_array = $user_acl[$key];
         foreach ($company_array as $company_key => $company_value)
         {
			$section_array = $company_array[$company_key];
			foreach($section_array as $section_key => $section_value)
			{
				if ($section_key == 'level')
				{
					$user_acl[$key][$company_key]->$section_key =  $user_acl[$key][$company_key]->level - 2;
				}
			}
         }
      }

		$this->unsorted_user_acl = $user_acl;
		$this->Sort_User_Acl($company_id);
			

      return TRUE;
   }



	/**
	*
	*/
	private function Sort_User_Acl($company_id)
	{
		$this->sorted_user_acl = array();
		foreach($this->unsorted_user_acl as $user_company_id => $company_sections)
		{
			if ($user_company_id == $company_id)
			{
				$admin_resources = new Admin_Resources($company_sections, 0, 0);
				$this->sorted_user_acl = $admin_resources->Get_Indented_Sorted_Master_Tree();
			}
		}
	}




	/**
	*
	*/
   public function Acl_Access_Ok($section, $company_id)
   {

      $company_id = is_null($company_id) ? $this->company_id : $company_id;

		return isset($this->unsorted_user_acl[$company_id][$section]);
   }
   
   
   /**
	*
	*/
   public function Get_Acl_Sorted()
   {
		return $this->sorted_user_acl;
   }


   /**
	*
	*/
   public function Get_Acl_Un_Sorted()
   {
		return $this->unsorted_user_acl;
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
				$result[count($result)] = $sorted_parent;
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
						$result[count($result)] = $sorted_child_key;
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
			foreach ($array_of_names as $name => $test)
			{
				foreach($this->sorted_user_acl as $key => $value)
				{
					foreach ($value as $child_name => $child_value)
					{
						if (count($child_value) > 0 )
						{
							if (is_array($child_value))
							{
								foreach($child_value as $xxx => $yyy)
								{
									if($xxx == $test)
									{
										$result[$test] = $yyy['name'];
									}
								}
							}
						}
					}
				}
			}
		}

		return $result;
	}
	
	/**
	 * Checks to see if there are any agents assigned to the group.
	 *
	 * @param int $group_id
	 * @return bool
	 */
	public function Agents_In_Group($group_id)
	{
		$this->mysqli->Escape_String($group_id);
		
		$query = "
			SELECT
				COUNT(*) AS count
			FROM
				agent_access_group
			WHERE
				access_group_id = $group_id
			LIMIT 1";
		
		$result = $this->mysqli->Query($query);
		
		return ($result->Fetch_Object_Row()->count == 1 ? true : false);
	}
}

?>
