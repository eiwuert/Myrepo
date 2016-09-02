<?php
require_once SERVER_MODULE_DIR . 'fraud/module.class.php' ;


// Collections module
class Server_Module extends Module
{
		
	public function __construct(Server $server, $request, $module_name)
	{
		$this->OVERRIDE_DEFAULT_MODE = 'watch';
		parent::__construct($server, $request, $module_name); 
     }
}
?>
