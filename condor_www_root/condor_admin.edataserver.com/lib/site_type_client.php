<?

require_once('prpc/client.php');  // This cannot be prefix with DIR_LIB because it MUST come from /virtualhosts/lib5/

class Site_Type_Client
{

		public static function Get_Site_Type($site_type)
		{
			switch(strtoupper(EXECUTION_MODE))
			{
				case 'LIVE':
					$url = 'prpc://bfw.1.edataserver.com/site_type.php';
				break;
				
				case 'RC':
					$url = 'prpc://rc.bfw.1.edataserver.com/site_type.php';
				break;
				
				case 'LOCAL':
				default:
					$url = 'prpc://rc.bfw.1.edataserver.com/site_type.php';
				break;
			}
//die($url);
			try
			{
				$site_type_manager = new Prpc_Client($url);
		
				if ($site_type_obj = $site_type_manager->Get_Site_Type($site_type, EXECUTION_MODE))
				{
					return $site_type_obj;
				}
			}
			catch (Exception $e)
			{
				return false;
			}

			return FALSE;
		}
}
