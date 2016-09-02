<?php

//module factory -- Account Module
class Client_Module
{
	public static function Get_Display_Module(Transport $transport, $module_name)
	{
		if ($transport->section_manager->current_module != $module_name)
		{

			require_once(CLIENT_MODULE_DIR . $transport->section_manager->parent_module."/module/". $module_name ."/code/client_display.class.php");
		}
		else 
		{
			require_once("client_display.class.php");
		}

		$application_object = new Client_Display($transport, $module_name);
		return ($application_object);
	}
	
	private function Prepare_Class_Name($module_name)
	{
		foreach (split('_', $module_name) as $i)
		{
			$us = ($class_name) ? '_' : '';
			if ($i)
				$class_name .= $us.ucwords(strtolower($i));
		}
		return $class_name;
	}
}

?>