<?php

class FactorTrust_ECash_Autoload implements IAutoload_1
{
	public function load($class_name)
	{
		if(preg_match("/ECash_FactorTrust/is", $class_name))
		{
			$factortrust_dir = ECASH_CODE_DIR . "FactorTrust/";
			
			$file_name = str_replace("ECash_FactorTrust_Responses_", "", $class_name);
			$file_name = str_replace("ECash_FactorTrust_Requests_", "", $file_name);
			$interface_file_name = str_replace("Ecash_FactorTrust_", "", $class_name);
			
			$response_path = $factortrust_dir . "Responses/" . $file_name . ".php";
			$request_path  = $factortrust_dir . "Requests/" . $file_name . ".php";
			$interface_path = $factortrust_dir . $interface_file_name . ".php";
			
			$success = FALSE;
			
			if(file_exists($response_path))
			{
				include_once($response_path);
				$success = TRUE;
			}
			
			if(file_exists($request_path))
			{
				include_once($request_path);
				$success = TRUE;
			}
			
			if(file_exists($interface_path))
			{
				include_once($interface_path);
				$success = TRUE;
			}
			
			return $success;
		}
		
		return FALSE;
	}
}

AutoLoad_1::addLoader(new FactorTrust_ECash_AutoLoad());