<?php

// This class sucks.  It has major problems differentiating between the agent/user logged in and a user being edited.
// It is in serious need of a rewrite.  Use it at your own risk until then. 

require_once 'applog.1.php';
require_once 'db2.2.php';

define ('DATE_ZERO','0001-01-01');

define ('FETCH_USER_VIA_LOGIN', "
	select
		agent.agent_id as id
		,agent.name_first
		,agent.name_last
		,agent.crypt_password
		,section.name as section
		,acl.acl_mask as mask
		,agent.login
	from
		agent
		left join	acl		on acl.agent_id = agent.agent_id
		left join	section	on (section.section_id = acl.section_id and section.version_id = ?)
	where 
		agent.login = ?		
	");

define ('FETCH_USER_VIA_ID',"
	select
		agent.agent_id as id
		,agent.name_first
		,agent.name_last
		,agent.crypt_password
		,section.section_id as section_id
		,section.name as section
		,acl.acl_mask as mask
		,acl.company_id
		,agent.login
	from
		agent
		left join	acl		on acl.agent_id = agent.agent_id
		left join	section	on (section.section_id = acl.section_id and section.version_id = ?)
	where 
		agent.agent_id = ?		
	");



define ('FETCH_ADMIN_COMPANY_LIST',"
		select acl.company_id
		from
			acl
			right join section on acl.section_id = section.section_id
		where
			acl.agent_id = ?
		and
			section.name = 'ADMIN'
	");
		



define ('FETCH_USER_LIST',"
	select
		agent.agent_id as id
		,agent.login
		,agent.crypt_password
		,agent.name_first
		,agent.name_last
		,agent.date_expire_account
	from agent
	order by agent.login
	");


define ('FETCH_SECTION_LIST',"
	select
		section.section_id as id,
		section.name as section
	from 
		section,
		version
	where
		section.version_id = ?
	order by section

	");


define ('ADD_USER',"
	insert into agent (
		date_modified
		,date_created
		,date_expire_account
		,date_expire_password
		,logged_in
		,login
		,name_first
		,name_last
		,crypt_password
	) values (
		current timestamp
		,current timestamp
		,'".DATE_ZERO."'
		,'".DATE_ZERO."'
		,0
		,?
		,?
		,?
		,?
	)");

define ('MOD_USER',"
	update agent set
		 date_modified = current timestamp
		,name_first = ?
		,name_last = ?
		,crypt_password = ?
	where agent_id = ?
	");

define ('ADD_SECTION',"
	insert into section (
		date_modified
		,date_created
		,name
		,description
		,version_id
	) values (
		current timestamp
		,current timestamp
		,?
		,?
		,?
	)");


define ('ADD_USER_SECTION',"
	insert into acl (
		date_modified
		,date_created
		,agent_id
		,section_id
		,acl_mask
		,company_id
	) values (
		current timestamp
		,current timestamp
		,?
		,?
		,?
		,?
	)");

define ('DEL_USER_SECTION',"delete from acl where acl.agent_id = ? and acl.section_id = ?");
define ('DEL_ALL_USER_SECTION',"delete from acl where agent_id = ?");
define ('DEL_USER',"delete from agent where agent_id = ?");
define ('DEL_SECTION',"delete from section where section_id = ?");
define ('FETCH_VERSION_ID', "select version_id from version where name = ?");


class ACL_1
{
	protected $err;
	protected $info;

	private $db2;
	private $version_id;

	private $q_fetch_user_via_login;
	private $q_fetch_user_via_id;
	private $q_fetch_user_list;
	private $q_fetch_section_list;
	private $q_fetch_section_acl_list;
	private $q_fetch_company_list;
	private $q_add_user;
	private $q_mod_user;
	private $q_del_user;
	private $q_add_section;
	private $q_del_section;
	private $q_add_user_section;
	private $q_del_user_section;
	private $q_del_all_user_section;
	private $q_fetch_admin_companies;


	function __construct($db2, $version = FALSE)
	{
		$this->db2 = $db2;
						
		if( $version !== FALSE )
		{	
			$q_version = $this->db2->Query(FETCH_VERSION_ID);
			
			$q_version->Execute($version);
										
			$result = $q_version->Fetch_Object();
																			
			if( is_object($result) )
			{
				$this->version_id = $result->VERSION_ID;
			}
			else 
			{
				throw new Exception('The ACL class requires a valid application version');
			}
		}
		else
		{
			throw new Exception('The ACL class requires a valid application version');
		}				
		
		if ( isset($_SESSION['acl_info']) )
		{
			$this->info = $_SESSION['acl_info'];
		}
		else
		{
			$this->info = new stdClass();
			$this->info->login = '';
		}	
	}



	/**
	 * @return boolean
	 * @param string $user_login
	 * @param string $password (optional)
	 * @desc fetch $user_id and make the current user; authenticate if password is present;
	 */
	public function Fetch_User($user_source, $password=null, $company=null, $mode = "login")
	{	
		$this->info = (object) array();
			
		if($mode == "login")
		{
			$q_fetch_user = $this->db2->Query(FETCH_USER_VIA_LOGIN);
		}
		else
		{
			$q_fetch_user = $this->db2->Query(FETCH_USER_VIA_ID);			
		}
				
		$q_fetch_user->Execute($this->version_id, $user_source);
			
		while( $dbd = $q_fetch_user->Fetch_Object() ) 
		{
			$this->_collect_user_info($this->info,$dbd);
		}
						
		$_SESSION['acl_info'] = $this->info;

		if ( isset($password) )
		{
			if ( self::Password_Ok($password) )
			{
				return true;
			}
			else
			{
				return false;
			}
		}
	}



	/**
	 * @return boolean
	 * @param string $password
	 * @desc authenticate the current user
	 */
	public function Password_Ok($password)
	{
		if ( empty($this->info->crypt_password) || (md5($password) != $this->info->crypt_password) )
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	
	/**
	 * @return array
	 * @param none
	 * @desc return a list of all users
	 */
	public function Fetch_User_List()
	{
		if ( !isset($this->q_fetch_user_list) )
		{
			$this->q_fetch_user_list = $this->db2->Query(FETCH_USER_LIST);
		}

		$this->q_fetch_user_list->Execute();

		$users = array();

		while ( $dbd = $this->q_fetch_user_list->Fetch_Object() )
		{
			if ( isset($user->login) && ($user->login != $dbd->LOGIN) )
			{
				$users[] = $user;
				unset($user);
			}
			self::_collect_user_info($user,$dbd);
		}
		$users[] = $user;

		return $users;
	}



	/**
	 *
	 */
	public function Fetch_Admin_Companies($user_id)
	{
		$companies = array();

		if ( !isset($this->q_fetch_admin_companies) )
		{
			$this->q_fetch_admin_companies = $this->db2->Query(FETCH_ADMIN_COMPANY_LIST);
		}

		$this->q_fetch_admin_companies->Execute($user_id);

		while ($dbd = $this->q_fetch_admin_companies->Fetch_Object())
		{
			$companies[$dbd->COMPANY_ID] = $dbd->COMPANY_ID;
		}

		return $companies;
	}





	/**
	 * @return array
	 * @param none
	 * @desc return a list of all ecash 2 sections and id
	 */
	public function Fetch_Section_ACL_List($company_list = null)
	{
		$sections_acl = array();

		$query = "select
						acl.acl_id as acl_id,
						acl.agent_id,
						acl.company_id,
						section.section_id,
						acl.acl_mask as mask
					from
						section,
						acl
					where
						section.version_id = ?
					and
						acl.section_id = section.section_id ";

					/*
					$this->q_fetch_section_acl_list = $this->db2->Query( "select
					acl.acl_id as acl_id ,acl.agent_id ,acl.company_id ,section.section_id
					,acl.acl_mask as mask from section, acl where section.version_id = ?
					and acl.section_id = section.section_id");
					*/
					//$this->q_fetch_section_acl_list = $this->db2->Query($query);

		if (!is_null($company_list))
		{
			// if more then one item in company list
			if (count($company_list) > 1)
			{
				$company_ids = "(" . implode(",", $company_list) . ")";

				$query .= " and
								acl.company_id in " . $company_ids;
					/*
					$this->q_fetch_section_acl_list = $this->db2->Query( "select
					acl.acl_id as acl_id ,acl.agent_id ,acl.company_id ,section.section_id
					,acl.acl_mask as mask from section, acl where section.version_id = ?
					and acl.section_id = section.section_id and acl.company_id in" . $company_ids);
					*/
			}
			else // only one company
			{
				$temp_array = array_values($company_list);
				$query .= " and
								acl.company_id = " . $temp_array[0];

					/*
					$this->q_fetch_section_acl_list = $this->db2->Query( "select
					acl.acl_id as acl_id ,acl.agent_id ,acl.company_id ,section.section_id
					,acl.acl_mask as mask from section, acl where section.version_id = ?
					and acl.section_id = section.section_id and acl.company_id = " . key($company_list));
					*/
			}
		}

		$this->q_fetch_section_acl_list = $this->db2->Query($query);
		$this->q_fetch_section_acl_list->Execute($this->version_id);
		while ( $dbd = $this->q_fetch_section_acl_list->Fetch_Object() )
		{
			$sections_acl[$dbd->ACL_ID] = $dbd;
		}

		return $sections_acl;
	}





	/**
	 * @return array
	 * @param none
	 * @desc return a list of all sections
	 */
	public function Fetch_Section_List()
	{
		if ( !isset($this->q_fetch_section_list) )
		{
			$this->q_fetch_section_list = $this->db2->Query(FETCH_SECTION_LIST);
		}

		$this->q_fetch_section_list->Execute($this->version_id);

		$sections = array();

		while ( $dbd = $this->q_fetch_section_list->Fetch_Object() )
		{
			$sections[$dbd->ID] = $dbd->SECTION;
		}

		return $sections;
	}



	/**
	 * @return array
	 * @param none
	 * @desc return a list of all companies
	 */
	public function Fetch_Company_List($company_list = null)
	{
		$companies = array();

		$query = "select
						company.company_id as id,
						company.name as company
					from
						company ";

		// get companies for non admin calls
		if (is_null($company_list))
		{
			$query .= " order by company";
				/*
				$this->q_fetch_company_list = $this->db2->Query( 'select
				company.company_id as id ,company.name as company
				from company order by company');
				*/
		}
		else // get companies for admin
		{
			if (count($company_list) > 1)
			{
				$company_ids = implode(",", $company_list);

				$query .= " where
									company_id in ( " . $company_ids . " )
								order by
									company";

					/*
					$this->q_fetch_company_list = $this->db2->Query( 'select
					company.company_id as id ,company.name as company
					from company where company_id in ('. $company_ids . ' )
					order by company');
					*/
			}
			else
			{

				$query .= " where
									company_id = ". key($company_list) . "
								order by
									company";
				
					/*	
					$this->q_fetch_company_list = $this->db2->Query( 'select
					company.company_id as id ,company.name as company
					from company where company_id = '. key($company_list) . '
					order by company');
					*/
			}
		}

		$this->q_fetch_company_list = $this->db2->Query($query);
		$this->q_fetch_company_list->Execute();
		while ( $dbd = $this->q_fetch_company_list->Fetch_Object() )
		{
			$companies[$dbd->ID] = $dbd->COMPANY;
		}

		return $companies;
	}





	/**
	 * @return boolean
	 * @param object $user_info
	 * @desc add a new user
	 */
	public function Add_User($user_info)
	{
		if ( !isset($this->q_add_user) )
		{
			$this->q_add_user = $this->db2->Query(ADD_USER);
		}

		// should be an object, but let slackers pass it as an array;
		$info = is_object($user_info) ? $user_info : (object)$user_info;

		if ( !isset($this->q_add_user) )
		{
			$this->q_add_user = $this->db2->Query(ADD_USER);
		}

		$this->q_add_user->Execute(
			$info->login
			,$info->name_first
			,$info->name_last
			,md5($info->password)
			);
		$user_id = (integer)$this->db2->Insert_Id();

		if ( !isset($this->q_add_user_section) )
		{
			$this->q_add_user_section = $this->db2->Query(ADD_USER_SECTION);
		}
		
		$mask = 1;

		foreach ($info->company_privs as $company_id => $section_list)
		{
			foreach($section_list as $section_id)
			{
				$this->q_add_user_section->Execute(
					$user_id
					,$section_id
					,$mask
					,$company_id
					);
			}
		}

		foreach ( $info as $property => $value )
		{
			$this->info->$property = $value;
		}

		return true;
	}


	/**
	 * @return boolean
	 * @param object $user_info
	 * @param boolean $replace_acl
	 * @desc modify user info, including acl;  if $replace_acl is true then delete the old acl else add to the current acl;
	 */
	public function Modify_User($user_info,$replace_acl=false)
	{
		$this->Fetch_User($user_info->agent, NULL, NULL, "agent_id");
		
		// should be an object, but let slackers pass it as an array;
		$info = is_object($user_info) ? $user_info : (object)$user_info;
			
		if( isset($info->name_first)  )
		{
			$this->info->name_first = $info->name_first;
		}
		
		if( isset($info->name_last)  )
		{
			$this->info->name_last = $info->name_last;
		}
		
		if ( !isset($info->company_privs) )
		{
			$info->company_privs = array();
		}
		
		if ( isset($info->password) )
		{
			$this->info->crypt_password = md5($info->password);		
		}
		
		foreach ( $info as $property => $value )
		{
			$this->info->$property = $value;
		}
				
		if ( !isset($this->q_mod_user) )
		{
			$this->q_mod_user = $this->db2->Query(MOD_USER);
		}
				
		$this->q_mod_user->Execute(
			 $this->info->name_first
			,$this->info->name_last
			,$this->info->crypt_password
			,$info->agent
			);
				
		if ( $replace_acl )
		{
			$all_sections = array_keys($this->Fetch_Section_List());
									
			$query = "DELETE FROM acl WHERE agent_id = {$info->agent} AND section_id IN (" . implode(",", $all_sections) . ")";
			
			$this->db2->Execute($query);			
		}

		if ( !isset($this->q_add_user_section) )
		{
			$this->q_add_user_section = $this->db2->Query(ADD_USER_SECTION);
		}
				
		$mask = 1;

		foreach ($info->company_privs as $company_id => $section_list)
		{
			foreach($section_list as $section_id)
			{
				$this->q_add_user_section->Execute(
					$this->info->id
					,$section_id
					,$mask
					,$company_id
					);
			}
		}

		return true;
	}


	/**
	 * @return boolean
	 * @param integer $user_id (optional)
	 * @desc deletes $user_id, if not present deletes current user
	 */
	public function Delete_User($user_id_list=null)
	{
		#print "<pre>Delete_User: \$user_id_list: ".print_r($user_id_list,true)."</pre>";
		if ( isset($user_id_list) )
		{
			// should be an array, but let slackers pass it as a single id;
			$list = is_array($user_id_list) ? $user_id_list : array($user_id_list);
		}
		else
		{
			if ( !empty($this->info->id) )
			{
				$list = array($this->info->id);
			}
			else
			{
				return false;
			}
		}		
	
		if ( !isset($this->q_del_all_user_section) )
		{
			$this->q_del_all_user_section = $this->db2->Query(DEL_ALL_USER_SECTION);
		}
		if ( !isset($this->q_del_user) )
		{
			$this->q_del_user = $this->db2->Query(DEL_USER);
		}

		foreach ( $list as $id )
		{
			if ( empty($id) ) continue;
			$this->q_del_all_user_section->Execute($id);
			$this->q_del_user->Execute($id);
		}
		return true;
	}


	/**
	 * @return boolean
	 * @param array $section_info
	 * @desc add a new section
	 */
	public function Add_Section($section_info)
	{
		// should be an object, but let slackers pass it as an array;
		$info = is_object($section_info) ? $section_info : (object)$section_info;

		if ( !isset($this->q_add_section) )
		{
			$this->q_add_section = $this->db2->Query(ADD_SECTION);
		}

		foreach ($info as $name => $description)
		{
			$this->q_add_section->Execute($name,$description);
		}

		return true;
	}


	/**
	 * @return boolean
	 * @param array $section_id_list
	 * @desc delete all sections in $section_id_list
	 */
	public function Delete_Section($section_id_list)
	{
		// should be an array, but let slackers pass it as a single id;
		$list = is_array($section_id_list) ? $section_id_list : array($section_id_list);

		if ( !isset($this->q_del_section) )
		{
			$this->q_del_section = $this->db2->Query(DEL_SECTION);
		}

		foreach ( $list as $id )
		{
			$this->q_del_section->Execute($id);
		}

		return true;
	}


	/**
	 * @return boolean
	 * @param integer $from_id
	 * @desc copy acl from $from_id to current user
	 */
	public function Copy_ACL($from_id)
	{
		return self::Modify_User(self::User_Acl($from_id),true);
	}

	/**
	 * @return boolean
	 * @param object $user_info
	 * @param object $dbd
	 * @desc collect the user info from the db2 object into a user object
	 *
	 * the sql statement to gather the user info generates one record for each access privilege.  each record
	 * consists of some static fields, i.e., they are the same for each record, and some varying fields.  i could
	 * structure it differently but that would require multiple db2 calls which is expensive.  so instead, i simply
	 * separate the static and varying stuff.
	 */
	private function _collect_user_info(&$user_info,&$dbd)
	{
		if ( !isset($user_info) )
		{
			$user_info = new stdClass();
			$user_info->acl = array();
		}

		foreach ( $dbd as $column => $value )
		{
			switch ($column)
			{
			case 'SECTION':
				if ( !empty($dbd->MASK) )
				{
					$user_info->acl[$dbd->SECTION] = "{$dbd->MASK}";
				}
				break;
			case 'MASK':
				break;
			default:
				$c = strtolower($column);
				if ( !isset ($user_info->$c) )
					$user_info->$c = $dbd->$column;
				break;
			}
		}

		return true;
	}

	/**
	 * @return object
	 * @param integer $user_id
	 * @desc return the acl for $user_id
	 */
	public function User_Acl($user_id)
	{
		if ( !isset($this->q_fetch_user_via_id) )
		{
			$this->q_fetch_user_via_id = $this->db2->Query(FETCH_USER_VIA_ID);
		}
				
		$this->q_fetch_user_via_id->Execute($this->version_id, $user_id);

		$info = new stdClass();
		while( $dbd = $this->q_fetch_user_via_id->Fetch_Object() )
		{
			$info->acl[$dbd->COMPANY_ID][$dbd->SECTION] = $dbd->MASK;
		}

		return $info;
	}
}


?>
