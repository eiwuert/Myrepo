<?php
	
	include('prpc/server.php');
	
	class Blah extends PRPC_Server
	{
		
		public function Done($arg1)
		{
			
			file_put_contents('/tmp/return', $arg1);
			
		}
		
	}
	
	$b = new Blah();
	
?>