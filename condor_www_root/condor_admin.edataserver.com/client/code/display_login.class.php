<?php

require_once(CLIENT_CODE_DIR . "display.iface.php");

class Display_Login implements Display
{
	public function Do_Display(Transport $object)
	{
		//set other variables
		$display_data = To_String($object->Get_Data());
		
		$errors = $object->Get_Errors();
		
		$html = file_get_contents(CLIENT_VIEW_DIR . "login.html");
			
		$error = count($errors) ? "<tr><th colspan=\"2\" style=\"background: red;\">{$errors[0]}</th><tr>": "";
		
		$html = str_replace("%%%login_errors%%%", $error, $html);

		$html = str_replace("%%%current_server%%%", EXECUTION_MODE, $html);


		if (!empty($_REQUEST['default_company']))
		{
			$company_drop[$_REQUEST['default_company']] = ' selected';
		}

		if ($company_drop)
		{
			foreach ($company_drop as $abbrev => $tag)
			{
				$html = str_replace("%%%{$abbrev}%%%", $tag, $html);				
			}
			
			// Finish the js needed to direct the login location
			$js_destinations = "";
			foreach ( $company_drop as $name_short => $not_used )
			{
				if ( defined(strtoupper($name_short) . "_SITE_LOCATION") )
				{
					$js_destinations .= "href[\"{$name_short}\"] = \"" . constant(strtoupper($name_short) . "_SITE_LOCATION") . "\";\n";
				}
				else
				{
					$js_destinations .= "href[\"{$name_short}\"] = \"" . (defined("DEFAULT_SITE_LOCATION")?constant("DEFAULT_SITE_LOCATION"):"/") . "\";\n";
				}
			}
			$html  = str_replace( "%%%js_destinations%%%", $js_destinations, $html );
		}

		if (EXECUTION_MODE == 'LIVE')
		{
			$html = str_replace("%%%current_bg%%%", 'bg_live', $html);
		}
		else if (EXECUTION_MODE == 'RC')
		{
			$html = str_replace("%%%current_bg%%%", 'bg_rc', $html);
		}
		else // local
		{
			$html = str_replace("%%%current_bg%%%", 'bg_local', $html);
		}

		echo $html;
	}
}

?>
