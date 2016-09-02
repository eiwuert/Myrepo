<?php

require_once(SERVER_CODE_DIR . "master_module.class.php");
require_once(SERVER_CODE_DIR . "module_interface.iface.php");
require_once("acl.3.php");

class Module extends Master_Module
{
	protected $conversion;
	protected $edit;
	protected $search;
//	protected $documents;
	const DEFAULT_MODE = 'conversion';

	public function __construct(Server $server, $request, $module_name)
	{
        parent::__construct($server, $request, $module_name); 
		$this->_add_edit_object();


		$section_names = ECash::getACL()->Get_Acl_Access('conversion');

		$allowed_submenus = array();
		foreach($section_names as $key => $value) {
			$allowed_submenus[] = $value;
		}

		ECash::getTransport()->Set_Data((object) array('allowed_submenus' => $allowed_submenus));

		$all_sections = ECash::getACL()->Get_Company_Agent_Allowed_Sections($server->agent_id, $server->company_id);
		ECash::getTransport()->Set_Data((object) array('all_sections' => $all_sections));

      	$read_only_fields = ECash::getACL()->Get_Control_Info($server->agent_id, $server->company_id);
      	ECash::getTransport()->Set_Data((object) array('read_only_fields' => $read_only_fields));

		if (!isset($request->action)) { $request->action = NULL; }

		// This is empty b/c we never want the 'mode' to be 'search' or 'overview' or anything.
		// Set the mode since display styles are keyed off the mode.
		if (!empty($request->mode))
		{
			$mode = $request->mode;
		}
		elseif (isset($_SESSION['conversion_mode']))
		{
			$mode = $_SESSION['conversion_mode'];
		}
		else
		{
			$mode = self::DEFAULT_MODE;
			$request->action = "show_search";
		}

		$_SESSION['conversion_mode'] = $mode;

		switch(eCash_Config::getInstance()->CONVERSION_MODE)
		{
			case 'OTHER' :
				require_once(SERVER_MODULE_DIR . $module_name . "/other_conversion.class.php");
				$this->conversion = new Other_Conversion($server, $request, $module_name, $mode);
				break;
			case 'CASHLINE':
			default :
				require_once(SERVER_MODULE_DIR . $module_name . "/cashline_conversion.class.php");
				$this->conversion = new Cashline_Conversion($server, $request, $module_name, $mode);
				break;
		}
		
		require_once (LIB_DIR . "/Document/Document.class.php");

		// Push the mod name (conversion) and
		// mode onto the level stack
		ECash::getTransport()->Add_Levels($module_name, $mode);
	}

	public function Main()
	{
		switch($this->request->action)
		{
			case "get_next_app_act":
				$this->conversion->Get_Next_App('active');
				break;

			case "get_next_app_conversion_manager":
				$this->conversion->Get_Next_App('conversion_manager');
				break;		

			case "get_next_app_coll":
				$this->conversion->Get_Next_App('collection');
				break;
				
			case "get_next_app_hold":
				$this->conversion->Get_Next_App('hold');
				break;
				
			case "get_next_app_other":
				$this->conversion->Get_Next_App('other');
				break;
			
			case 'get_next_app_returns_past_due':
				$this->conversion->Get_Next_Return('past_due');
				break;
								
			case 'get_next_app_returns_collections_new':
				$this->conversion->Get_Next_Return('collections_new');
				break;
								
			case 'get_next_app_returns_collections_contact':
				$this->conversion->Get_Next_Return('collections_contact');
				break;
								
			case 'get_next_app_returns':
				$this->conversion->Get_Next_Return();
				break;
								
			case "get_Conversion_queue_status":
				$this->conversion->Get_Conversion_Queue_Status();
				break;

			case "generate_schedule":
				$this->conversion->Generate_Schedule();
				break;

			case "import_account":
				$this->conversion->Import_Account();
				break;

         	case "reset_import":
				$this->conversion->Reset_Account();
				break;

         	case "verify_import":
				$this->conversion->Import_Account();
				break;

         	case "send_managers":
				$this->conversion->Send_To_Managers();
				$this->request->action = 'show_search';
				break;
				
			// Everything else should be covered by the master_main			
			default:
				$this->master_main();
		}


		
		// We get $data, but never return it, set it... etc.  Why?
		
		
		$data = ECash::getTransport()->Get_Data();

		if ( is_array($data) )
		{
			$data = (object) $data;
		}
		else if ( !isset($data) )
		{
			$data = new stdClass();
		}

		if (($this->request->action != 'get_application_history'))
		{
			$data->queue_count = $this->conversion->Get_Conversion_Queue_Count();
		}

		return;
	}

	// [Mantis:3180] Removed function Adjustment() to master_module.class.php

	protected function	Add_Comment()
	{
		$this->conversion->Add_Comment();
	}


}
?>
