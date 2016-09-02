<?php

class Server_Module
{
	
	public static function Get_Server_Module(Server $server, $request, $module_name)
	{				
		if ($server->transport->section_manager->parent_module == $module_name)
		{
			// set to default module if the current module = parent
			// default module
			$module_name = 'archives_search';
			// set levels in section manager
			
			$server->transport->section_manager->Reset($module_name);
		}
		
		include_once(SERVER_MODULE_DIR . $server->transport->section_manager->parent_module .'/module/'. $module_name .'/module.class.php');

		//echo $module_name; exit;
		return $module_obj = new Module($server, $request, $module_name);
	}

}
