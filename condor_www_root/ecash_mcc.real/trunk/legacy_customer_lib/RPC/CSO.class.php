<?php

require_once LIB_DIR . "/common_functions.php";
require_once SQL_LIB_DIR . "/scheduling.func.php";

require_once LIB_DIR . "/RPC/RPC.class.php";
require_once eCash_RPC_DIR . "/SOAP.class.php";

class eCash_Custom_RPC_CSO implements eCash_iWSDL
{

	
	public static function getRPCMethods()
	{
		//Define the list of methods.
		$methods = array();
		
		
		$methods[] = (object) array("name" => "getRolloverEligible",
								 	"args" => array ( (object) array (	"name" => "application_id",
								 						  				"type" => "string"),
								 					),
								 	"response" => (object) array ("type" => "array")
								 	);

								 	
		$methods[] = (object) array("name" => "createRollover",
								 	"args" => array ( (object) array (	"name" => "application_id",
								 						  				"type" => "string"),
								 					  (object) array (  'name' => 'paydown_amount',
								 					  				 	'type' => 'string'),
								 					),
								 	"response" => (object) array ("type" => "array")
								 	);
								 	
		$methods[] = (object) array("name" => "fakeIt",
						 	"args" => array ( (object) array (	"name" => "application_id",
						 						  				"type" => "string"),
						 					),
						 	"response" => (object) array ("type" => "array")
						 	);
						 	
		$methods[] = (object) array("name" => "getCSOFeeDescription",
						 	"args" => array ( (object) array (	"name" => "fee",
						 						  				"type" => "string"),
						 						(object) array ( "name" => "application_id",
						 						  				"type" => "string"),
						 						 (object) array( "name" => "company_id",
						 						 				"type"  =>  "string"),
						 					),
						 	"response" => (object) array ("type" => "string")
						 	);				 									 	

		$methods[] = (object) array("name" => "getCSOFeeAmount",
						 	"args" => array ( (object) array (	"name" => "fee",
						 						  				"type" => "string"),
						 						(object) array ( "name" => "application_id",
						 						  				"type" => "string"),
						 					),
						 	"response" => (object) array ("type" => "string")
						 	);				 									 	
		
		return $methods;
		
	}
	
	public function createRollover($application_id, $paydown_amount = 0)
	{
		
		eCash_RPC::Log()->write(__METHOD__ . "({$application_id}) Called");
		//create the response array:
		$response = array();
		$response['application_id'] = $application_id;
		
		//See if application exists!	
		$application =  ECash::getApplicationByID($application_id);
		
		if(!$application->exists())
		{
			$response['success'] = false;
			$response['reason'] = 'Invalid application ID';
			
			return $response;
		}
		

		//The real way to do it.
		$response = ECash_CSO::createRollover($application_id, $paydown_amount);
		
		
		//The totally fake way to do it.
		//$this->fakeIt($application_id);
		
		return $response;
	}
	
	
	public function fakeIt($application_id)
	{
		$response = array();
		
		//Default values
		$response['eligible'] = true;
		$response['reason'] = null;
		$response['application_id'] = $application_id;
		$response['method'] = __METHOD__;
		
		
		//let's check to see if the app_id exists!!!!
		

		//For now, let's randomize this mofo.
		$result = rand(0,15);
		switch ($result) {
			case 7:
				$response['eligible'] = false;
				$response['reason'] = 'Is a vampire';
				
				break;

			case 4:
			case 5:
				$response['eligible'] = false;
				$response['reason'] = 'ACH Pending';
				
				break;
				
			case 1:
				$response['eligible'] = false;
				$response['reason'] = 'No Rollovers Left';
				
			case 9:
			case 11:
				$response['eligible'] = false;
				$response['reason'] = 'Not in Eligible Status';
				
			case 10:
				$response['eligible'] = false;
				$response['reason'] = 'Has failed rollovers';
				
				
				break;
				
			default:
				break;
		}
		
		if(!$response['eligible'])
		{
			$response['success'] = false;
			$response['reason'] = "Eligibility Check failed -".$eligibility['reason'];
		}
		else 
		{

			$response['next_due_date'] = date('Y-m-d',strtotime('+2 weeks'));
			$response['rollover_amount_due'] = 110.50;
			$response['loan_due_date'] = date('Y-m-d',strtotime('+4 weeks'));
			$response['loan_amount_due'] = 350.49;
		}	
		
		return $response;
	}
	
	
	
	/**
	 * This looks for Rollover eligibility based on the application_id
	 *
	 * @param unknown_type $application_id
	 * @return unknown
	 */
	public function getRolloverEligible($application_id)
	{
		eCash_RPC::Log()->write(__METHOD__ . "({$application_id}) Called");
		//create the response array:
		$response = array();
		$response['application_id'] = $application_id;
		
		//See if application exists!	
		$application =  ECash::getApplicationByID($application_id);
		
		if(!$application->exists())
		{
			$response['success'] = false;
			$response['reason'] = 'Invalid application ID';
			
			return $response;
		}
		
		$response = ECash_CSO::getRolloverEligibility($application_id);
		

		
		return $response;
		
	}
	
	
	public function getCSOFeeDescription($fee,$application_id,$company_id = null)
	{
		
		return ECash_CSO::getCSOFeeDescription($fee,$application_id,$company_id);
		
	}
	
	public function getCSOFeeAmount($fee,$application_id)
	{
		return ECash_CSO::getCSOFeeAmount($fee,$application_id);
	}
	
	protected function getDB()
	{
		$db = ECash_Config::getMasterDbConnection();
		return $db;
	}

	
	
	
	
	
}