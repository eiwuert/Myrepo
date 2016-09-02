<?php

require_once(SQL_LIB_DIR."app_stat_id_from_chain.func.php");
require_once(SQL_LIB_DIR . "fetch_status_map.func.php");

/**
 * A bunch of shared functions for the report queries
 */
class Base_Report_Query
{
	protected $acl;
	protected $db;
	protected $timer;
	protected $log;
	protected $status_ids;
	protected $permissions;
	protected $company_id;
	protected $max_display_rows;
	protected $control_options;
	protected $auth_companies;
	protected $server;
	private   $locations;
	private   $protected_status_ids;
	private   $event_type_map;

	public function __construct(Server $server)
	{
		//Using main DB instead of slave DB now [AGEAN!]
		$this->db          = ECash_Config::getSlaveDbConnection(); 
		$this->log         = ECash::getLog();
		$this->timer       = ECash::getMonitoring()->getTimer();
		$this->company_id  = ECash::getCompany()->company_id;
		$this->status_ids  = array();
		$this->locations   = array();
		$this->server		= $server;
		$this->max_display_rows = eCash_Config::getInstance()->MAX_REPORT_DISPLAY_ROWS;
		$this->acl 			= ECash::getACL();

		if (empty($this->acl))
		{
			// Nightly has no ACL
			$this->acl             = NULL;
			$this->permissions     = NULL;

			$this->control_options = NULL;
			$this->auth_companies  = NULL;
		}
		else
		{
			$this->permissions    = $this->acl->getAllowedSections($server->agent_id);
			$this->auth_companies = $this->acl->getAllowedCompanyIDs(); 

			$this->control_options  = array();

			foreach($this->auth_companies as $company)
			{
				$this->control_options[$company] = $this->acl->Get_Control_Info($server->agent_id, $company);
			}
		}

		$this->protected_status_ids = array();

		// By default only get those status ids that are required for link redirection
		$this->Add_Status_Id('reverified',      array('queued',         'verification', 'applicant', '*root'));
		$this->Add_Status_Id('in_verify',       array('dequeued',       'verification', 'applicant', '*root'));
		$this->Add_Status_Id('vfollowup',       array('follow_up',      'verification', 'applicant', '*root'));
		$this->Add_Status_Id('approved',        array('queued',         'underwriting', 'applicant', '*root'));
		$this->Add_Status_Id('in_underwriting', array('dequeued',       'underwriting', 'applicant', '*root'));
		$this->Add_Status_Id('ufollowup',       array('follow_up',      'underwriting', 'applicant', '*root'));
		$this->Add_Status_Id('funded',          array('approved',       'servicing',    'customer',  '*root'));
		$this->Add_Status_Id('active',          array('active',         'servicing',    'customer',  '*root'));
		$this->Add_Status_Id('fund_failed',     array('funding_failed', 'servicing',    'customer',  '*root'));
		$this->Add_Status_Id('hold',            array('hold',           'servicing',    'customer',  '*root'));
		$this->Add_Status_Id('past_due',        array('past_due',       'servicing',    'customer',  '*root'));
		$this->Add_Status_Id('high_risk',       array('queued',         'high_risk',    'applicant', '*root'));
		$this->Add_Status_Id('in_high_risk',    array('dequeued',       'high_risk',    'applicant', '*root'));		
		$this->Add_Status_Id('high_risk_followup',array('follow_up',    'high_risk',    'applicant', '*root'));
		$this->Add_Status_Id('fraud',           array('queued',         'fraud',        'applicant', '*root'));
		$this->Add_Status_Id('in_fraud',        array('dequeued',       'fraud',        'applicant', '*root'));
		$this->Add_Status_Id('fraud_followup',  array('follow_up',      'fraud',        'applicant', '*root'));
		$this->Add_Status_Id('fraud_confirmed', array('confirmed',        'fraud',        'applicant', '*root'));
 		$this->Add_Status_Id('withdrawn', 		array('withdrawn', 		'applicant', 	'*root'));
		$this->Add_Status_Id('denied',    		array('denied',    		'applicant', 	'*root'));
		$this->Add_Status_Id('indef_dequeue',    array('indef_dequeue',  'collections',  'customer',    '*root'));
		$this->Add_Status_Id('collections_new',  array('new',            'collections',  'customer',    '*root'));
		$this->Add_Status_Id('dequeued',         array('dequeued',       'contact',      'collections', 'customer', '*root'));
		$this->Add_Status_Id('queued',           array('queued',         'contact',      'collections', 'customer', '*root'));

		// Protect the status ids used in this class so they cannot be overwritten
		$this->protected_status_ids = $this->status_ids;
	}

	/**
	 * figures out the module and mode an application should be viewed in base on the app_status_id
	 * @param array   &$row must include application_status_id & company_id, function adds module & mode indices
	 * @returns string $row['module']
	 * @returns string $row['mode']
	 * @access protected
	 */
	protected function Get_Module_Mode(&$row, $respect_current_company = TRUE)
	{
		if( $this->permissions == NULL)
		{
			return false;
		}
			
		if( ! isset($row['company_id']) )
			throw new Exception( "Need company_id for " . __METHOD__ . "." );
		if( ! isset($row['application_status_id']) )
			throw new Exception( "Need application_status_id for " . __METHOD__ . "." );

		if($respect_current_company && ($row['company_id'] != $this->company_id))
		{
			return false;
		}

		// GF #13303: If the company has a loan type restriction, evaluate this code to decide whether
		// to display the link.
		if ((isset(ECash_Config::getInstance()->HAS_LOANTYPE_RESTRICTION)) && ECash_Config::getInstance()->HAS_LOANTYPE_RESTRICTION == TRUE)
		{
			$loan_type 		 = $row['loan_type'];
			$loan_type_short = $row['loan_type_short'];

			if ((is_array($this->control_options[$row['company_id']])) && !empty($this->control_options[$row['company_id']]))
			{
				if ($loan_type == NULL || (!in_array($loan_type, $this->control_options[$row['company_id']])
									   || !in_array($loan_type_short, $this->control_options[$row['company_id']])))
				{
					unset($row['module']);
					unset($row['mode']);
					return false;
				}
			}
		}

		if ( isset($row['queue_name']) && $row['queue_name'] === 'Watch' ) // mantis:5776
		{
				$row['module'] = 'watch';
				$row['mode']   = 'watch';
		}
		else
		{
			switch($row['application_status_id'])
			{
				case $this->approved:
				case $this->in_underwriting:
				case $this->ufollowup:
					$row['module'] = 'funding';
					$row['mode']   = 'underwriting';
					break;

				case $this->reverified:
				case $this->in_verify:
				case $this->vfollowup:
					$row['module'] = 'funding';
					$row['mode']   = 'verification';
					break;

				case $this->funded:
				case $this->active:
				case $this->fund_failed:
				case $this->past_due:
				case $this->hold:
					$row['module'] = 'loan_servicing';
					$row['mode']   = 'account_mgmt';
					break;

				case $this->fraud:
				case $this->fraud_confirmed:
					$row['module'] = 'fraud';
					$row['mode']   = 'fraud_queue';
					break;

				case $this->high_risk:
					$row['module'] = 'fraud';
					$row['mode']   = 'high_risk_queue';
					break;

				// catch Collections statuses [mantis:5779]
				case $this->status_ids['collections']:
				case $this->status_ids['indef_dequeue']:
				case $this->status_ids['collections_new']:
				case $this->status_ids['dequeued']:
				case $this->status_ids['queued']:
				case $this->queued:
				case $this->dequeued:
				case $this->collections_new:
				case $this->indef_dequeue:
					
					$row['module'] = 'collections';
					$row['mode']   = '';
					break;

				default:
					if( isset($this->locations[$row['application_status_id']]) )
					{
						$row['module'] = $this->locations[$row['application_status_id']]['module'];
						$row['mode']   = $this->locations[$row['application_status_id']]['mode'];
					}
					else
					{
						$row['module'] = 'loan_servicing';
						$row['mode']   = 'customer_service';
					}
					break;
			}
		}

		// Now compare to acl

		// GF 8869:
		// This part has changed around a bit. When the method GetAllowedSections() is called
		// which populates $this->permissions another dimension was added to the array.
		// With the new ACL system, we can do a little something like this.
		
		// If no permission to use that section
		   // then try customer service
		// If no permission to use customer service
		   // send them to the section handling this app's status
		   // agent will get insufficient permissions error
		$section_id = $this->acl->Get_Section_Id($this->company_id, $row['module'], $row['mode']);

		// They're authorized for that section 
		if (isset($this->permissions[$this->company_id][$section_id]))
		{
			return true;
		}
			

		// If they're not authorized for the module deemed fit in the previous code, try this sane
		// pair.
		$row['module'] = 'loan_servicing';
		$row['mode']   = 'customer_service';
		
		// GF #13033: See if they're authorized to view the failover module/mode, if not, strip the
		// module and mode so no link is displayed
		$section_id = $this->acl->Get_Section_Id($this->company_id, $row['module'], $row['mode']);
		
		// They're authorized for the failover section.
		if (isset($this->permissions[$this->company_id][$section_id]))
		{
			return true;
		}

		// They're not even authorized for the failover, don't display a link.
		unset($row['module']);
		unset($row['mode']);

		return false;
	}

	public function __get($var)
	{
		// Check this first, no telling how many will be set
		if( isset($this->status_ids[$var]) )
			return $this->status_ids[$var];

		switch(strtolower($var))
		{
			case 'status_ids':
				return $this->status_ids;

			case 'db':
				return $this->db;

			case 'log':
				return $this->log;

			case 'timer':
				return $this->timer;

			case 'permissions':
				return $this->permissions;

			default:
				throw new Exception( "Unrecognized property: $var." );
		}
	}

	protected function Add_Status_Id( $name, $chain )
	{
		if( ! is_string($name) || ! is_array($chain) )
			throw new Exception( "Invalid status parameters." );

		// Search_Status_Map() wants a string
		if (is_array($chain) )
		{
			$chain = implode("::", $chain);
		}

		$id = Search_Status_Map($chain, Fetch_Status_Map(FALSE) );

		// Handle non-existent statuses gracefully
		if (empty($id))
		{
			$this->status_ids[$name] = 0;
			return;
		}
		//if( ! in_array($id, $this->protected_status_ids) )
	//		if(!empty($id))
			$this->status_ids[$name] = $id;
		//else
			//throw new Exception( "Sorry, the status short name {$name} can not be overwritten." );
	}

	protected function Add_Application_Link_Destination( $name, $module, $mode )
	{
		if( ! isset($this->status_ids[$name]) )
			throw new Exception( "Unrecognized status: {$name}." );

		$this->locations[$this->status_ids[$name]]['module'] = $module;
		$this->locations[$this->status_ids[$name]]['mode']   = $mode;
	}

	/**
	 * Gets the loan type list (universal function in case the database changes
	 * @param string $loan_type as given by the report front end
	 * @returns string
	 */
	protected function Get_Loan_Type_List($loan_type, $get_ids = FALSE)
	{
		$loan_types = '';
		$map = $this->Get_Loan_Type_Map();
		if( strtolower($loan_type) === 'all' )
		{
			foreach($map as $loan_type)
			{
				if($get_ids)
				{
					$loan_types .= "'" . $loan_type['loan_type_id'] . "', ";
				}
				else 
				{
					$loan_types .= "'" . $loan_type['name_short'] . "', ";
				}
				
			}
			$loan_types = rtrim($loan_types, ", ");
			return $loan_types;
		}
		else
		{
			if($get_ids)
			{
				return "'".$map[$loan_type]['loan_type_id'] . "'";
			}
			else 
			{
				return strtolower("'{$loan_type}'");
			}
		}
	}
	
	
	/**
	 * Gets the loan type map
	 * @return array Multi-dimensional array of loan type data
	 */
	public static function Get_Loan_Type_Map($company_ids = NULL)
	{

		if(!empty($company_ids))
		{
			$company_where = "AND company_id IN (" . implode(", ", $company_ids) . ")";
		}
		else
		{
			$company_where = "";
		}
		
		$loan_types = array();
		
		$sql = "
		-- eCash 3.0, File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
		SELECT DISTINCT name_short, name, loan_type_id
		FROM loan_type
		WHERE active_status = 'active'
		$company_where
		AND name_short NOT IN ('queue_configuration','company_level','offline_processing') ";

		$db = ECash_Config::getMasterDbConnection();
		$result = $db->query($sql);
		
		while ($row = $result->fetch(PDO::FETCH_OBJ))
		{
			//$loan_types[$row->loan_type_id]['loan_type_id'] =$row-loan_type_id;
			$loan_types[$row->name_short]['loan_type_id'] = $row->loan_type_id;
			$loan_types[$row->name_short]['name'] =$row->name;
			$loan_types[$row->name_short]['name_short'] =$row->name_short;
			//$loan_types[$row->loan_type_id]['company_id'] =$row->company_id;
		}
		
		return $loan_types;

	}
	
	/**
	 * Gets the event_id based on the name_short
	 * @param string $short_name string with name_short value in event_table
	 * @returns integer application_status_id
	 * @access  private
	 */
	protected function Get_Event_Type_Id( $short_name,$company_id=null )
	{
		if(empty($short_name))
			throw new Exception( "Empty name!");
		if (!$company_id) 
		{
			$company_id = $this->company_id;	
		}
			
			
		if(!(is_array($this->event_type_map[$company_id])))

		{
			$this->event_type_map[$company_id]	= Load_Event_Type_Map($company_id);
		}

		return $this->event_type_map[$company_id][$short_name];
	}

	
	/**
	 * Gets all the company ids/name_shorts
	 * @returns array
	 * @access  private
	 */
	protected function Get_Company_Ids($map_id_to_name = FALSE)
	{
		
		$co_query = "
			SELECT
				name_short,
				company_id
			FROM
				company
			";
		$co_result = $this->db->query($co_query);
		
		$company = array();
		
		while ($line = $co_result->fetch(PDO::FETCH_OBJ))
		{
			$company[strtolower($line->name_short)] = $line->company_id;
			$company[strtoupper($line->name_short)] = $line->company_id;
			
			if ($map_id_to_name)
			{
				$company[(int)$line->company_id] = $line->name_short;
			}
			
		}
		
		return $company;
		
	}

	// GF #8997: Added check whether given non-zero company_id is in the authorized list
	protected function Format_Company_IDs($company_id)
	{
		if ($this->auth_companies != NULL)
		{
			$auth_company_ids = $this->auth_companies;
		} 
		else if(isset($_SESSION) && is_array($_SESSION['auth_company']['id']) && count($_SESSION['auth_company']['id']) > 0)
		{
			$auth_company_ids = $_SESSION['auth_company']['id'];
		}
		else
		{
			$auth_company_ids = array(-1);
		}

		if( $company_id > 0 )
		{
			// Check if they're authorized for those company ids
			if (in_array($company_id, $auth_company_ids))
			{
				$companies = array($company_id);
			}
		else
			{
				$companies = array(-1);
			}
		}
		else
		{
			$companies = $auth_company_ids;
		}

		return "('" . implode("','", $companies) . "')";
	}
	

	
	/**
	 * Gets all the status ids/names
	 * @returns array
	 * @access  private
	 */
	protected function Get_Status_Names()
	{
		$co_query = "
			SELECT
				name,
				application_status_id
			FROM
				application_status
		";
		$co_result = $this->db->query($co_query);
		
		$status = array();
		
		while ($line = $co_result->fetch(PDO::FETCH_OBJ))
		{
			$status[$line->application_status_id] = $line->name;
		}
		
		return $status;
		
	}
	
}

?>
