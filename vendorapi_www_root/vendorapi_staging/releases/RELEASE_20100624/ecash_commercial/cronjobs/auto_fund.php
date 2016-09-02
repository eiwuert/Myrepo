<?php
require_once(SERVER_CODE_DIR . "bureau_query.class.php");
require_once(SERVER_CODE_DIR . "loan_data.class.php");
function Main()
{
	global $server;
	//1. get apps that are confirmed or approved
	//2. check if they have a loan document
	//3. check bureau inquiry for A4 value
	//4. move to prefund and build a schedule
	$Loan_Doc_Template = 'Loan Document';

	$status_list = "'queued::verification::applicant::*root','queued::servicing::customer::*root'";

	$mysql_query = 'EXECECUTE sp_commercial_authoritative_for_status "{$status_list}"';

	$app_service_result = ECash::getAppSvcDB()->query($mysql_query);
	$bureau_query = new Bureau_Query(ECash::getMasterdb(), ECash::getlog());
	$loan_data = new Loan_Data($server);
	while ($row = $app_service_result->fetch(DB_IStatement_1::FETCH_OBJ))
	{

		$app_id = $row->application_id;
		
		$app = ECash::getApplicationById($app_id);
		if(!$app->exists())
			continue;
		$docs = $app->getDocuments()->getSentandRecieved();
		$fund = false;
		foreach($docs as $doc)
		{
			$doc_name = $doc->getName();
			
			if($doc_name == $Loan_Doc_Template)
			{
				
				$inquiry_packages = $bureau_query->getData($app_id, $server->company_id);
				$autofund_eligible = false;
				if(count($inquiry_packages))
				{
					/**
					 * We retrieve packages Newest to Oldest, so stop on the first match
					 */
					foreach($inquiry_packages as $package)
					{
                        $call_type = strtolower($package->inquiry_type);
                        if((strpos($call_type, 'perf') !== FALSE))
						{
							$dataxResponse = new ECash_DataX_Responses_Perf();
							$dataxResponse->parseXML($package->received_package);
							$autofund_eligible = $dataxResponse->getAutoFundDecision();
							break;
						}
					}
				}
				//check if is a react and previous app wasn't in a collections status
				if($app->is_react == 'yes')
				{
					ECash::getlog()->Write('Check React ' . $app_id );
					$customer = ECash_Customer::getBySSN(ECash::getMasterDB(), $app->ssn, $app->company_id);

					$application_list = $customer->getApplications();
					$autofund_eligible = TRUE;
					foreach($application_list as $customer_app)
					{
						$status_history = $customer_app->getStatusHistory();
						ECash::getlog()->Write('PUlling parent app ' . $customer_app->application_id );
						foreach($status_history as $name => $entry)
						{
							//check if collections status in history
							if($entry->status->level1 == 'collections' || $entry->status->level2 == 'collections') 
							{
								ECash::getlog()->Write('Parent application had a collections status ' . $customer_app->application_id);
								$autofund_eligible = FALSE;	
								break;
							}
						}
					}
				}
				if($autofund_eligible)
				{
					$fund = true;
				}
				break;
			}
			
		
		}
		if($fund)
		{
			ECash::getlog()->Write('Auto Funding App ' . $app_id );
			try {
				$return = $loan_data->Fund($app_id, 'Fund');
			}
			catch(Exception $e)
			{
				ECash::getlog()->Write('Exception thrown attempting to auto fund ' . $app_id . ' Exception: ' . $e->getMessage());
			}
			$app->getComments()->add('Application was Auto Funded', ECash::getAgent()->getAgentId());

		}
	}

	//make sure no prefund apps are in a queue, this happens because the vapi scrubbers could run after the autofund happens,
	//so the cfe events for confirmed run after the funding happens
	$status_list = "'approved::servicing::customer::*root'";

	$mysql_query = 'EXECECUTE sp_commercial_authoritative_for_status ("'.$status_list.'")';

	$app_service_result = ECash::getAppSvcDB()->query($mysql_query);

	$queue_manager = ECash::getFactory()->getQueueManager();
	while ($row = $app_service_result->fetch(DB_IStatement_1::FETCH_OBJ))
	{
		$app_id = $row->application_id;
		$queue_item = new ECash_Queues_BasicQueueItem($app_id);
		$queue_manager->getQueueGroup('automated')->remove($queue_item);
	}


}

?>
