<?php
/**
 * Author: Vinh Trinh
 * Description: Interface to allow publishers to check leads before actually submitting to OLP.
 * 
 * 
 * 
 * 
 * 
 // Sample Code you can give to publishers so they know how to submit.
$user_data = '
<submission_verification>
	<post>
		<id>1234</id>
		<name_first>John</name_first>
		<name_middle>Peter</name_middle>
		<name_last>Doe</name_last>
		<email>john.doe@fakeemail.com</email>
		<aba>123123123</aba>
	</post>
</submission_verification>
';


$client = new SoapClient("http://bfw.1.edataserver.com/cm_svr.php?wsdl",
	array("trace"=>1, "exceptions"=>0)
);
print($client->User_Data($user_data));
 * 
 * 
 * 
 */

define(EXPIRE_DAYS,60); // Days for bad email & aba's to be purged

require_once('config.php');
require_once(BFW_CODE_DIR."setup_db.php");
require_once(BFW_CODE_DIR."Memcache_Singleton.php");
require_once('mysql.4.php');

switch(BFW_MODE)
{
	case 'LOCAL':
		ini_set('soap.wsdl_cache_enabled',"0");
		break;
	case 'RC':
		ini_set('soap.wsdl_cache_enabled',"0");
		break;
	case 'LIVE':
	default:	
		break;
}

function User_Data($xml_input) 
{
	try {
		$maintenance_mode = new Maintenance_Mode();
		if($maintenance_mode->Is_Online())
		{
			$mClass = new CM_SVR();
			return($mClass->process($xml_input));
		}
		else
		{
			$simpleXML = new SimpleXMLElement(trim($xml_input));
			$simpleXML->addChild('response');
			$simpleXML->response->addChild('maintence_mode','FAIL');
			$simpleXML->response->addChild('valid', 'FAIL');
			return trim($simpleXML->asXML());
		}
	}
	catch (Exception $e)
	{
		return "Your input string is ill Formatted, here is an example of how we expect to see the input

		<submission_verification>
			<post>
				<email>john.doe@fakeemail.com</email>
			</post>
		</submission_verification>
		
		";
	}

}


$server = new SoapServer("svr.wsdl");
$server->addFunction("User_Data");
$server->handle();

class CM_SVR
{
	private $sql;
	private $server;
	
	public function __construct()
	{
		$this->sql = Setup_DB::Get_Instance('BLACKBOX',BFW_MODE);
	}
	
	// Public Functions ==============================================
	
	public function process($xml_input)
	{
		$simpleXML = new SimpleXMLElement(trim($xml_input));
		
		//Check against duplicate leads
		$response['duplicate'] = $this->duplicate($simpleXML);
	
		// Check against ABA suppresion lists
		$response['bad_aba'] = $this->bad_aba($simpleXML);
				
		$this->purge_apps_older_than(EXPIRE_DAYS);
		
		return $this->generate_return_xml_string($simpleXML,$response);
	}
	

	// Private Functions ==============================================

	private function purge_apps_older_than($days)
	{
		$expire_time = date("YmdHis",strtotime("-$days days",time()));
		$query = "
			DELETE FROM 
				bad_email_aba
			WHERE
				date_modified < $expire_time
		";
		return ($this->sql->Query($this->sql->db_info['db'],$query)) ? 1: 0;
	}
	
	//Checks to see if a lead given an application id already exhists inside the memcache.
	private function duplicate($simpleXML)
	{
		$email = trim((string) $simpleXML->post->email);		
		
		//Taken From Cache_Duplicate_Leads.php
		$memcache_key = "DUPLICATE_LEAD";
		$memcache_key .= ":".$email;	
		$memcache_key .= ":default";	
		$memcache_key = strtoupper($memcache_key);
		
		$result = Memcache_Singleton::Get_Instance()->get($memcache_key);
		
		return ($result) ? 1 : 0;
	}	
	
	private function bad_aba($simpleXML)
	{
		$email = strtoupper(trim((string) $simpleXML->post->email));	
		$email = mysql_escape_string($email);
		
		$query = "
			SELECT
				email_primary
			FROM
				bad_email_aba
			WHERE
				email_primary = '$email'
		";
		
		$result = $this->sql->Query($this->sql->db_info['db'],$query);
		
		return ($row = $this->sql->Fetch_Array_Row($result)) ? 1 : 0;
	}
	
	private function generate_return_xml_string($simpleXML,$response)
	{
		//Generate Return XML String	
		$simpleXML->addChild('response');
		
		($response['duplicate']) ?
			$simpleXML->response->addChild('duplicate_check','FAIL') :
			$simpleXML->response->addChild('duplicate_check','PASS') ;

		($response['bad_aba']) ?
			$simpleXML->response->addChild('aba_check','FAIL') :
			$simpleXML->response->addChild('aba_check','PASS') ;			

		if ($response['maint_mode']) $simpleXML->response->addChild('maintence_mode','FAIL'); 
		// If a 1 shows up in the response array, this lead failed some sort of check
		(in_array(1,$response)) ?
			$simpleXML->response->addChild('valid', 'FAIL') :
			$simpleXML->response->addChild('valid','PASS') ;

		$xml_output = trim($simpleXML->asXML());
		return $xml_output;
	}
}
