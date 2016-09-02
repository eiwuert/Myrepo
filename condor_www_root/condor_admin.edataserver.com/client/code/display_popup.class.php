<?php

require_once(DIR_LIB . "data_format.1.php");
require_once(CLIENT_CODE_DIR . "display_parent.abst.php");

//ecash module
class Display_View extends Display_Parent
{
	public function Get_Body_Tags() { return ""; }
	public function Get_Hotkeys() { return ""; }
	public function Get_Header() { return ""; }
	public function Get_Menu_HTML() { return ""; }
	public function Include_Template() { return false; }


	public function Get_Module_HTML()
	{
		$transport = $this->transport;

		$next_level = $transport->Get_Next_Level();

		switch($next_level)
		{
			case "application_history":
			$html = file_get_contents(CLIENT_VIEW_DIR . "application_history.html");
			$history_html = $this->Build_Application_History();
			$html = str_replace("%%%application_history%%%", $history_html, $html);
			break;

			case "wizard_error":
			$html = file_get_contents(CLIENT_VIEW_DIR . "wizard_error.html");
			break;
		}

		//echo "<pre>DATA:"; print_r($this->data); echo "</pre>";
		echo $html;
	}

	private function Build_Application_History()
	{
		if( isset($this->data['application_history']) && is_array($this->data['application_history']) )
		{
			$html = "<table width=\"500\" border=\"1\" style=\"font-size: 9pt; font-family: Arial, Verdana, Helvetica, Sans-Serif;\">
						<tr style=\"font-weight: bold; background: #F6C8A9;\">
							<td>Date Changed</td>
							<td>Status</td>
							<td>Agent</td>
						</tr>";

			$timezone = date('T');

			foreach($this->data['application_history'] as $history_obj)
			{
				if($history_obj->agent_id == 0)
				{
					$agent_name = "N/A";
				}
				else
				{
					$agent_name = ucwords(strtolower("{$history_obj->name_last}, {$history_obj->name_first}"));
				}

				$html .= "\n<tr style=\"background: #FFF3EB;\">";

				$html .= "<td title='{$history_obj->date_created} [{$timezone}]'>{$history_obj->date_created}</td>";
				$html .= "<td>{$history_obj->status}</td>";
				$html .= "<td>{$agent_name}</td>";

				$html .= "\n</tr>";
			}

			$html .= "\n</table>";
		}
		else
		{
			$html = "Could not find any application history for that application.";
		}
		return $html;
	}
}

?>