<?php

require_once(CLIENT_CODE_DIR . "client_view_parent.abst.php");
require_once(CLIENT_CODE_DIR . "display_module.iface.php");

class Client_Display extends Client_View_Parent implements Display_Module
{

	public function __construct(Transport $transport, $module_name)
	{
		$this->transport = $transport;
		$this->module_name = $module_name;
		parent::__construct($transport, $module_name);
	}

	public function Get_Hotkeys()
	{
		if (method_exists($this->display, "Get_Hotkeys"))
		{
			return $this->display->Get_Hotkeys();
		}

		return TRUE;
	}
	
	private function Make_Plot_String($array)
	{
		$str = '';
		foreach($array as $plot)
		{
			$str .= 'plots[]='.urlencode(join(',',$plot)).'&';
		}
		return rtrim($str,'&');
	}
	public function Get_Module_HTML()
	{
//		include_once CLIENT_MODULE_DIR.$this->transport->section_manager->parent_module.'/module/'.$this->module_name.'/code/add_site.php';
		
		$data = $this->transport->Get_Data();
		
		$sent_email_plots = array(
			array(
				'EMAIL',
				'SENT',
				'seagreen',
				'Sent Emails',
			),
		);
		$fail_email_plots = array(
			array(
				'EMAIL',
				'FAIL',
				'red',
				'Failed Emails',
			),
		);
		$sent_fax_plots = array(
			array(
				'FAX',
				'SENT',
				'seagreen',
				'Sent Faxes',
			),
		);
		$fail_fax_plots = array(
			array(
				'FAX',
				'FAIL',
				'red',
				'Failed Faxes',
			)
		);

		$tokens = array(
			'date' => $data->date,
			'company_id' => $this->transport->company_id,
			'mode' => EXECUTION_MODE,
			'sent_email_plots' => $this->Make_Plot_String($sent_email_plots),
			'fail_email_plots' => $this->Make_Plot_String($fail_email_plots),
			'sent_fax_plots' => $this->Make_Plot_String($sent_fax_plots),
			'fail_fax_plots' => $this->Make_Plot_String($fail_fax_plots),
		);
		
		$page = file_get_contents(CLIENT_MODULE_DIR.$this->transport->section_manager->parent_module.'/module/'.$this->module_name.'/view/view_stats.html');
		$page = $this->Replace_All($page, $tokens);
		
		return $page;
	}
}
