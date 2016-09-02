<?php

$mode = BFW_MODE;

if ($mode == 'RC')
	ini_set('include_path', '../pear:/virtualhosts/rc_lib5:/virtualhosts/rc_lib:'.ini_get('include_path'));
else
	ini_set('include_path', '../pear:'.ini_get('include_path'));

require_once('maintenance_mode.php');
require_once ('config.php');

require_once ('prpc/server.php');
require_once ('prpc/client.php');
require_once ('bfw.1.php');
require_once ('../include/code/OLP_Applog.php');

//Reset MySQL Timer if on
if(MYSQL4_LOG)
{
    $_SESSION["mysql4_timer"] = (float)0;
    $_SESSION["mysql4_query_count"] = 0;
}
if(STATPRO_LOG)
{
    $_SESSION["statpro_timer"] = (float)0;
}

class CM_PRPC extends Prpc_Server
{
	function CM_PRPC()
	{
		parent:: __construct();
	}

	function Process_Data($license_key, $site_type, $session_id, $collected_data, $debug, $extra=0, $ajax_request = FALSE)
	{
		$maintenance_mode = new Maintenance_Mode();
		 if(!$maintenance_mode->Is_Online())
         {
            $return = new stdClass();
			$return->page = "maintenance_mode";
			return $return;
        }

		// cleanup the session_id because the front end is dirty
		$session_id = substr($session_id, 0, 32);

		// HACKS for "SOAP" sample sites
		//
		if (preg_match('/^(?:rc\.)?nms\./', $_SERVER['HTTP_HOST']))
		{

			// we ARE a soap sample site
			define('SOAP_SAMPLE', TRUE);

			if (!array_key_exists('client_ip_address', $collected_data))
			{
				$collected_data['client_ip_address'] = $_SERVER['REMOTE_ADDR'];
			}

			if ( (@$collected_data['page']=='app_2part_page01') && (!array_key_exists('income_direct_deposit', $collected_data)) )
			{
				$collected_data['income_direct_deposit'] = 'FALSE';
			}

		}

		try
		{

            $this->mode = strtolower(BFW_MODE);

			// set the framework
			$bfw = new Base_Frame_Work($license_key,
                                       $collected_data,
                                       $this->mode,
                                       $site_type,
                                       $session_id);

			if($ajax_request === true)
			{
				//We want to run the Ajax Handler instead
				$return = $bfw->module->Ajax_Handler($collected_data);
			}
			elseif($bfw->inTimeCap()) 
			{ // Mantis #8617: Time of day capping [MJ]
				// run and return response from page handler
				$return = $bfw->module->Page_Handler($collected_data);
			} 
			else 
			{
				$return =  new stdClass();
				$return->page = 'auto_rejected'; // new added page at tss.2.shared.text/nms/all_nms/text.apps.auto_rejected.html
			}
		}

		catch ( Exception $e )
		{

			if( BFW_MODE == 'LIVE' )
			{

				$applog = new OLP_Applog("olp", "1000000000", 20, "CM_PRPC", true);
				$applog->Write($e->getMessage() . "\nException caught at line \"" . $e->getLine() . "\" in file \"" . $e->getFile() . "\".\n");

				$return = new stdClass();
				$return->page = "try_again";

			}
			else
			{
				echo "<pre>";
				print_r( $e );
				exit;
			}

		}

        //Write MySQL Timer if on
        if(MYSQL4_LOG)
        {
            if(!isset($collected_data['page']) || $collected_data['page']=="")
            {
                $page = "default";
            }
            else
            {
                $page = $collected_data['page'];
            }

            session_write_close();

            $context = $_SESSION["mysql4_query_count"] . " queries";

            $applog = new OLP_Applog("mysql4", "1000000000", 20, $context, true);
            $applog->Write("Elapsed time for [Total Time:" . $page . "]  is " .
                           $_SESSION["mysql4_timer"] . " seconds.");
        }

        if(STATPRO_LOG)
        {
            if(!isset($collected_data['page']) || $collected_data['page']=="")
            {
                $page = "default";
            }
            else
            {
                $page = $collected_data['page'];
            }

            $applog = new OLP_Applog("statpro", "1000000000", 20, "all", true);
            $applog->Write("Elapsed time for [Total Time:" . $page . "]  is " .
                            $_SESSION["statpro_timer"] . " seconds.");
        }

        if(SESSION_SIZE_LIMIT)
		{
			$session_size = strlen(gzcompress(session_encode()));

			if($session_size >= MAX_SESSION_SIZE)
			{
				$return->page = 'try_again';
				
				$applog = OLP_Applog_Singleton::Get_Instance('olp', 1000000, 20, $context, true, APPLOG_UMASK);

				$applog->Write("Session size limit reached; SSID: " . session_id());
			}
		}

		return $return;

	}
}

$cm_prpc = new CM_PRPC();
$cm_prpc->_Prpc_Strict = TRUE;
$cm_prpc->Prpc_Process();

?>
