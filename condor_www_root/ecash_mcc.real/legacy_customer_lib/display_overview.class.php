<?php

require_once(CLIENT_CODE_DIR . 'display_overview.class.php');

class Customer_Display_View extends Display_View
{
	
	public function __construct(ECash_Transport $transport, $module_name, $mode)
	{
	   parent::__construct($transport, $module_name, $mode);
	}
	
}

?>
