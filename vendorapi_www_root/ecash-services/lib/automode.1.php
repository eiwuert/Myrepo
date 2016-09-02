<?php
	
	if (!defined('MODE_LIVE')) define('MODE_LIVE', 'LIVE');
	if (!defined('MODE_RC')) define('MODE_RC', 'RC');
	if (!defined('MODE_DEV')) define('MODE_DEV', 'LOCAL');
	
	class Auto_Mode
	{
		private $local_name;
	
		public function Get_Local_Name()
		{
			return $this->local_name;
		}
		
		public function Fetch_Mode($server_url)
		{
			
			switch (TRUE)
			{
				
				case (preg_match("/\.(cubisqa|gambit|jubilee|ds\d{2}|dev\d{2})\.tss:?\d*$/i", $server_url, $matched)):
					$mode = "LOCAL";
					$this->local_name = $matched[1];
					break;
				
				case (preg_match ("/^rc\d*\./i", $server_url)):
				case (preg_match('/\.dev\d\.clkonline\.com$/', $server_url));
				case (preg_match('/^live\.mqrc\./', $server_url));
					$mode = "RC";
					break;
				
				default:
					$mode = "LIVE";
					break;
					
			}
			
			return $mode;
			
		}
		
	}

?>
