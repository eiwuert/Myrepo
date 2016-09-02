<?php

class Server_Module
{
	
	public static function Get_Server_Module(Server $server, $request, $module_name)
	{				
		// default module
		$default_module = 'admin';
		
		if ($server->transport->section_manager->parent_module == $module_name)
		{
			// set to default module if the current module = parent
			$module_name = $default_module;
			// set levels in section manager
			$server->transport->section_manager->Reset($module_name);
		}
		include_once(SERVER_MODULE_DIR . $module_name .'/module.class.php');
		
		return $module_obj = new Module($server, $request, $module_name);
	}

}
