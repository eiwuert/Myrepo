<?php

// These defines are needed for the login on ecash 2 and 2.5
function Set_Login_Defines($mode, $local_name)
{
	switch($mode)
	{
		case 'RC':
			defined("DEFAULT_SITE_LOCATION") || define ("DEFAULT_SITE_LOCATION", "/" );
			/*defined("UFC_SITE_LOCATION") || define ("UFC_SITE_LOCATION", "http://rc.ecash.clkonline.com" );
			defined("D1_SITE_LOCATION")  || define ("D1_SITE_LOCATION",  "http://rc.ecash.clkonline.com"  );
			defined("CA_SITE_LOCATION")  || define ("CA_SITE_LOCATION",  "http://rc.ecash.clkonline.com"  );
			defined("UCL_SITE_LOCATION") || define ("UCL_SITE_LOCATION", "http://rc.ecash.clkonline.com"  );
			defined("PCL_SITE_LOCATION") || define ("PCL_SITE_LOCATION", "http://rc.ecash.clkonline.com"  );
			*/
			break;
		case 'LOCAL':
			// When a user logs in, where should they log in to?
			//   preferred order: <company>_SITE_LOCATION, then DEFAULT_SITE_LOCATION, then /
			//   <company>_SITE_LOCATION     <company> = same name used in display_login.class.php $company_drop array
			defined("DEFAULT_SITE_LOCATION") || define ("DEFAULT_SITE_LOCATION", "/" );
//			defined("UFC_SITE_LOCATION") || define ("UFC_SITE_LOCATION", "http://ecash2.5.{$local_name}.tss/" );
//			defined("D1_SITE_LOCATION")  || define ("D1_SITE_LOCATION",  "http://ecash.2.{$local_name}.tss/"  );
//			defined("CA_SITE_LOCATION")  || define ("CA_SITE_LOCATION",  "http://ecash.2.{$local_name}.tss/"  );
//			defined("UCL_SITE_LOCATION") || define ("UCL_SITE_LOCATION", "http://ecash.2.{$local_name}.tss/"  );
//			defined("PCL_SITE_LOCATION") || define ("PCL_SITE_LOCATION", "http://ecash.2.{$local_name}.tss/"  );
			break;
		case 'LIVE':
		default:
			defined("DEFAULT_SITE_LOCATION") || define ("DEFAULT_SITE_LOCATION", "/" );
			defined("UFC_SITE_LOCATION") || define ("UFC_SITE_LOCATION", "http://ecash.edataserver.com" );
			defined("D1_SITE_LOCATION")  || define ("D1_SITE_LOCATION",  "http://ecash.edataserver.com"  );
			defined("CA_SITE_LOCATION")  || define ("CA_SITE_LOCATION",  "http://ecash.edataserver.com"  );
			defined("UCL_SITE_LOCATION") || define ("UCL_SITE_LOCATION", "http://ecash.edataserver.com"  );
			defined("PCL_SITE_LOCATION") || define ("PCL_SITE_LOCATION", "http://ecash.edataserver.com"  );
			break;
	}
}

?>