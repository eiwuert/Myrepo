<?php

/**
 * A concrete implementation class for posting to vendors defined by the Conditional_Map / dynamic vendor post
 */

include_once(BFW_CODE_DIR . "conditional_map.class.php");


class Vendor_Post_Impl_DYNAMIC extends Abstract_Vendor_Post_Implementation
{


	/**
	 * Must be passed as 2nd arg to Generic_Thank_You_Page: parent::Generic_Thank_You_Page($url, self::REDIRECT);
	 */
	const REDIRECT = 4;

	// NEW 1/18/2008: created in the constructor, as the RPC params are dependent on instantiation of
	// the conditional_map class
	protected $rpc_params  = Array
	(
	// Params which will be passed regardless of $this->mode
	 	 'ALL'     => Array(
	 	 'post_url' => '',
	),
	// Specific cases varying with $this->mode, having higher priority than ALL.
	 	 'LOCAL'   => Array(
	 	 'post_url' => ''
	 	 ),
	 	 'RC'      => Array(
	 	 'post_url' => ''
	 	 ),
	 	 'LIVE'    => Array(
	 	 'post_url' => ''
	 	 )
	 	 );

	 	 protected $static_thankyou = FALSE;

	 	 protected $condmap = NULL;

	 	 public function __construct(&$lead_data, $mode, $property_short)
	 	 {

	 	 	if (is_array($lead_data))
	 	 	{
	 	 		$this->lead_data = &$lead_data;
	 	 	}
	 	 	else
	 	 	{
	 	 		$this->lead_data = array();
	 	 	}

	 	 	$this->mode = $mode;
	 	 	$this->property_short = $property_short;
	 	 	$this->populateCondMap();
	 	 }

	 	 /**
	 	  * Generate field values for post request.
	 	  *
	 	  * @param array $lead_data User input data.
	 	  * @param array $params Values from $this->rpc_params.
	 	  * @return array Field values for post request.
	 	  */
	 	 public function Generate_Fields(&$lead_data, &$params)
	 	 /*	Additions to the reqfields should be reflected in table vendor_post_fields
	 	  */
	 	 {
	 	 	if(is_null($this->condmap))
	 	 	$this->populateCondMap();
	 	 	
	 	 	//flatten the submitted data into a 1-dim array
	 	 	$reqfields=$this->flatten2deep($lead_data['data']);

	 	 	//preparse the defined fields for the dynamic vendor post

	 	 	$reqfields['dob_mdy_slashed'] = $lead_data['data']['date_dob_m'].'/'.$lead_data['data']['date_dob_d'].'/'.$lead_data['data']['date_dob_y'];
	 	 	$reqfields['dob'] = $lead_data['data']['date_dob_m'].'/'.$lead_data['data']['date_dob_d'].'/'.$lead_data['data']['date_dob_y'];

	 	 	$reqfields['over_18_YN'] = (strtotime($reqfields['dob']) < strtotime('-18 years')) ? 'Y' : 'N';

	 	 	//parse the phone(s) into 3 separate lumps @@TF
	 	 	$ltemp = $lead_data['data']['phone_home'];
	 	 	$reqfields['ph_area_code'] = substr($ltemp,0,3);
	 	 	$reqfields['ph_prefix'] = substr($ltemp,3,3);
	 	 	$reqfields['ph_exchange'] = substr($ltemp,6,4);


	 	 	$ltemp2 = $lead_data['data']['phone_work'];
	 	 	$reqfields['ph2_area_code'] = substr($ltemp2,0,3);
	 	 	$reqfields['ph2_prefix'] = substr($ltemp2,3,3);
	 	 	$reqfields['ph2_exchange'] = substr($ltemp2,6,4);

	 	 	$reqfields['nextpaydate'] = date("m/d/Y", strtotime(reset($lead_data['data']['paydates'])));

	 	 	// second paydate @TF
	 	 	$reqfields['secondpaydate'] = date("m/d/Y", strtotime(next($lead_data['data']['paydates'])));

	 	 	if (!empty($lead_data['data']['paydate']['frequency']))
	 	 	$reqfields['payrollfreq'] = $payperiod[$lead_data['data']['paydate']['frequency']];
	 	 	elseif (!empty($lead_data['data']['income_frequency']))
	 	 	$reqfields['payrollfreq'] = $payperiod[$lead_data['data']['income_frequency']];
	 	 	elseif (!empty($lead_data['data']['paydate_model']['income_frequency']))
	 	 	$reqfields['payrollfreq'] = $payperiod[$lead_data['data']['paydate_model']['income_frequency']];
	 	 	else
	 	 	$reqfields['payrollfreq'] = ''; // NO such type, but this is a required field.

	 	 	
	 	 	//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	 	 	//GFORGE_10244 parse the references phone nums (ims)
	 	 	$ltemp3 = $lead_data['data']['ref_01_phone_home'];
	 	 	$reqfields['ref_01_area_code'] = substr($ltemp3,0,3);
	 	 	$reqfields['ref_01_prefix'] = substr($ltemp3,3,3);
	 	 	$reqfields['ref_01_exchange'] = substr($ltemp3,6,4);
	 	 	
	 	 	$ltemp4 = $lead_data['data']['ref_02_phone_home'];
	 	 	$reqfields['ref_02_area_code'] = substr($ltemp4,0,3);
	 	 	$reqfields['ref_02_prefix'] = substr($ltemp4,3,3);
	 	 	$reqfields['ref_02_exchange'] = substr($ltemp4,6,4);

	 	 	//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	 	 	//GFORGE_9753 parse current time and date (dpl)
	 	 	$reqfields['current_date_mdy_slashed'] = date("m/d/Y");
	 	 	$reqfields['current_datetime_ISO_8601']=date("c");
	 	 	
	 	 	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	 	 	//Paydate Freq verbose
	 	 	//courtest of archaic cm2
	 	 	if(isset($lead_data['data']['paydate_model']) &&
	 	 	isset($lead_data['data']['paydate_model']['income_frequency']) &&
	 	 	$lead_data['data']['paydate_model']['income_frequency'] != "")
	 	 	{
	 	 		$freq_temp = $lead_data['data']['paydate_model']['income_frequency'];
	 	 	}
	 	 	elseif(isset($lead_data['data']['income_frequency']) &&
	 	 	$lead_data['data']['income_frequency'] != "")
	 	 	{
	 	 		$freq_temp = $lead_data['data']['income_frequency'];
	 	 	}
	 	 	elseif(isset($lead_data['data']['paydate']) &&
	 	 	isset($lead_data['data']['paydate']['frequency']) &&
	 	 	$lead_data['data']['paydate']['frequency'] != "")
	 	 	{
	 	 		$freq_temp = $lead_data['data']['paydate']['frequency'];
	 	 	}

	 	 	if(isset($freq_temp))
	 	 	{
	 	 		//convert income frequency to cm2 requested format
	 	 		switch($freq_temp)
	 	 		{
	 	 			case 'WEEKLY':
	 	 				$reqfields['income_freq_verbose'] = 'Weekly';
	 	 				break;
	 	 			case 'BIWEEKLY':
	 	 			case 'BI_WEEKLY':
	 	 				$reqfields['income_freq_verbose'] = 'Bi Weekly';
	 	 				break;
	 	 			case 'TWICE_MONTHLY':
	 	 				$reqfields['income_freq_verbose'] = 'Twice Monthly';
	 	 				break;
	 	 		}
	 	 	}

	 	 	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	 	 	$ref1Array = split(" ", $lead_data['data']['ref_01_name_full']);
	 	 	$reqfields['ref_01_first_name'] = $ref1Array[0];
	 	 	$reqfields['ref_01_last_name'] = "";
	 	 	for($i = 1; $i < count($ref1Array); $i++){
	 	 		if($i > 1){
	 	 			$reqfields['ref_01_last_name'] .= " ";
	 	 		}
	 	 		$reqfields['ref_01_last_name'] .= $ref1Array[$i];
	 	 	}

	 	 	$ref2Array = split(" ", $lead_data['data']['ref_02_name_full']);
	 	 	$reqfields['ref_02_first_name'] = $ref2Array[0];
	 	 	$reqfields['ref_02_last_name'] = "";
	 	 	for($i = 1; $i < count($ref2Array); $i++){
	 	 		if($i > 1){
	 	 			$reqfields['ref_02_last_name'] .= " ";
	 	 		}
	 	 		$reqfields['ref_02_last_name'] .= $ref2Array[$i];
	 	 	}
	 	 	//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	 	 	$reqfields['paydate_first_paydate_mdy'] = date("m/d/Y", strtotime($lead_data['data']['paydates'][0]));
	 	 	$reqfields['paydate_second_paydate_mdy'] = date("m/d/Y", strtotime($lead_data['data']['paydates'][1]));


	 	 	//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	 	 	
	 	 	//get the pay-per-paycheck 
	 	 	
	 	 	$qualify = new Qualify_2(NULL);
			$reqfields['paycheck_net'] = $qualify->Calculate_Monthly_Net($freq_temp, $lead_data['data']['income_monthly_net']);
	 	 	
	 	 	//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	 	 	
			//add the promo_id as per [CB]
			
			//$promo_id = SiteConfig::getInstance()->promo_id;
			$reqfields['promo_id'] = SiteConfig::getInstance()->promo_id;
			//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
			
			//GFORGE_9753 add the application_id 
			
			//$promo_id = SiteConfig::getInstance()->promo_id;
			$reqfields['application_id'] = $lead_data['application_id'];
			//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	 	 	$fields=array();

	 	 	//following deals with the hassles created by sleep mode
	 	 	if (is_null($this->condmap))
	 	 	$this->populateCondMap();

	 	 	$fields=$this->condmap->getFields($reqfields);

	 	 	if (strcasecmp($this->condmap->getPostType(),'xml')==0){
	 	 		$fields=$this->condmap->getFields($reqfields, TRUE);
	 	 		$arraytosmash=$fields;

	 	 		// bizarre fix to make sure the array is not passed by reference to the destructive function
	 	 		// @see http://bugs.php.net/bug.php?id=20993
	 	 		$arraytosmash=unserialize(serialize($arraytosmash));
	 	 		$tempdom=$this->arrayToXml($arraytosmash);
	 	 		$fields=$tempdom->saveXML();
	 	 	}
	 	 		
	 	 	// added for custom wrappers to XML stuff 
	 	 	if (substr($this->condmap->getPostType(),0,8)=='wrapped#'){
	 	 		$fields=$this->condmap->getFields($reqfields, TRUE);
	 	 		$arraytosmash=$fields;

	 	 		// bizarre fix to make sure the array is not passed by reference to the destructive function
	 	 		// @see http://bugs.php.net/bug.php?id=20993
	 	 		$arraytosmash=unserialize(serialize($arraytosmash));
	 	 		$tempdom=$this->arrayToXml($arraytosmash);
	 	 		
	 	 		$wrapname=str_ireplace("wrapped#","",$this->condmap->getPostType());
	 	 		
	 	 		$fields="$wrapname=" . urlencode(trim(str_ireplace("<?xml version=\"1.0\" encoding=\"utf-8\"?>","",$tempdom->saveXML())));
	 	 		
	 	 	}

	 	 	//socketbroadcast("LOCAL_rpc_from_THIS: ",$this->rpc_params);

	 	 	return $fields;
	 	 }
	 	 	
	 	 /*
	 	  * Lights up the relevant conditional_map from DB
	 	  */
	 	 protected function populateCondMap()
	 	 {
	 	 	//$post_implementation = new $class_name($lead_data, $mode, $property_short);
	 	 	$this->condmap=new Conditional_Map();
	 	 	$currentmode=$this->mode;
	 	 	//transpose LOCAL or anything non-LIVE to RC, there's no interface for Monster

	 	 	if(strcmp($currentmode,'LIVE')!=0)
	 	 	{
	 	 		$currentmode='RC';
	 	 	}
	 	 	$sqlconn=Setup_DB::Get_Instance('blackbox', $currentmode);  //Server::Get_Server($currentmode, 'BLACKBOX');
	 	 	$mydb = $sqlconn->db_info['db'];	//new MySQL_4 ($db['host'], $db['user'], $db['password']);
	 	 	$sqlconn->Connect();

	 	 	//socketbroadcast("**Cond_Map hit in post_impl_dynamic.  Trying reconstitute:");

	 	 	if($this->condmap->reconstitute($sqlconn, $this->property_short, $mydb))
	 	 	{

	 	 		if($this->condmap->isRpcSet()>0)
	 	 		{
	 	 			$this->rpc_params=$this->condmap->getRpc();
	 	 		}
	 	 		//socketbroadcast("LOCAL_rpc_from_THIS: ",$this->rpc_params);
	 	 	}
	 	 	else
	 	 	{
	 	 		//the conditional map is not in the db-- shouldn't ever get to this, error clause removed
	 	 	}

	 	 }

	 	 /**
	 	  * Retrieve 1-dimensional array with concat keys
	 	  *
	 	  * @param array $lead_data User input data.
	 	  * @param array $params Values from $this->rpc_params.
	 	  * @return array Field values for post request.
	 	  */
	 	 public function flatten2deep($flatme)
	 	 {
	 	 	$retarray=array();
	 	 	$prefix="";
	 	 	foreach($flatme as $k=>$v)
	 	 	{
	 	 		if(is_array($v))
	 	 		{
	 	 			$prefix=$k;
	 	 			foreach($v as $k2=>$v2)
	 	 			{
	 	 				if(!is_array($v2))
	 	 				{
	 	 					$retarray[$prefix . "_" . $k2]=$v2;
	 	 				}
	 	 			}
	 	 		}
	 	 		else
	 	 		{
	 	 			//just add the item
	 	 			$retarray[$k]=$v;
	 	 		}
	 	 	}
	 	 	return $retarray;
	 	 }
	 	 	
	 	 /*
	 	  * Converts a fieldmap (associative array) into an XML output.  Magic key values of '#XML_ELEMENT_...'
	 	  *	define the wrappers/envelopes/sections for the XML output.  IMPORTANT: the passed-in array will be
	 	  * destroyed, so make sure the function is not passed a reference to an array that is to be reused.
	 	  *
	 	  * @param array $array the associative array, ala 'firstname'=>'Fred' *will be destroyed*
	 	  * @param DOMDocument $DOM an optional param, not needed
	 	  * @param int $maxdepth the maximum depth to traverse the array (should not be changed) for
	 	  * safe recursion
	 	  * @param in $depth the standing depth of traversal **don't change explicitly**
	 	  *
	 	  * @return DOMDocument the constructed XML document
	 	  */
	 	 protected function arrayToXml(&$array, $DOM=null, $root=null, $maxdepth=5, $depth=0)
	 	 {

	 	 	$element_level=0;
	 	 	$xml_start_flag="#XML_ELEMENT_START_" . $element_level;

	 	 	$xml_end_flag="#XML_ELEMENT_END_" . $element_level;

	 	 	if($DOM  == null)
	 	 	{
	 	 		$DOM  = new DOMDocument('1.0', 'utf-8');
	 	 	}
	 	 	
	 	 	if($root==null)
	 	 	{
	 	 		$root = $DOM; //->appendChild($DOM->createElement('APPLICATION'));
	 	 	}

	 	 	//quit if we're over-recursing or the passed in array is out of elements
	 	 	if ($depth>=$maxdepth || count($array)<1)
	 	 	{
	 	 		return $DOM;
	 	 	}
	 	 		
	 	 	// set to prevent recursion in case an int key sneaks in
	 	 	$name = NULL;

	 	 	while($map = array_shift($array))
	 	 	{

	 	 		if (is_null($map)) return $DOM;
	 	 		
	 	 		$key=key($map);
	 	 		$value=$map[$key];

	 	 		if(is_int($key) && $name != null)
	 	 		{   //this condition should not occur within the submitted array
	 	 			if(is_array($value)){
	 	 				$subroot = $root->appendChild($DOM->createElement($name));
	 	 				$this->arrayToXml($value, $DOM, $subroot, $maxdepth, $depth++);
	 	 			}
	 	 			elseif(is_scalar($value)){
	 	 				$root->appendChild($DOM->createElement($name, $value));
	 	 			}
	 	 		}
	 	 		elseif(is_string($key) && stristr($key,"#XML_")===FALSE){
	 	 			//this is a legit field to be mapped
	 	 			if(is_array($value))
	 	 			{
	 	 				$subroot = $root->appendChild($DOM->createElement($key));
	 	 				$this->arrayToXml($value, $DOM, $subroot, $maxdepth, $depth++);
	 	 			}
	 	 			else if(is_scalar($value))
	 	 			{
	 	 				$root->appendChild($DOM->createElement($key, $value));
	 	 			}
	 	 		}
	 	 		elseif(is_string($key) && stristr($key,"#XML_")!==FALSE){
	 	 			// one of the xml flags is set
	 	 			if(is_array($value))
	 	 			{
	 	 				$subroot = $root->appendChild($DOM->createElement($key));
	 	 				$this->arrayToXml($value, $DOM, $subroot, $maxdepth, $depth++);
	 	 			}
	 	 			else if(is_scalar($value))
	 	 			{
	 	 				//handle opening
	 	 				if(stristr($key,"#XML_ELEMENT_END_")!==FALSE)
	 	 				{
	 	 					// this is a defined element ending-- end recursion
	 	 					return $DOM;
	 	 				}
	 	 				elseif(stristr($key,"#XML_ATT_NAME_")!==FALSE)
	 	 				{
	 	 					//special case, we need to grab the next tag for the value
	 	 					$latestAtt = $value;
	 	 					continue;
	 	 				}
	 	 				elseif(stristr($key,"#XML_ATT_VAL_")!==FALSE)
	 	 				{
	 	 					$root->setAttribute($latestAtt,$value);
	 	 				}
	 	 				else
	 	 				{
	 	 					// this is a defined element beginning-- recurse
	 	 					$subroot = $root->appendChild($DOM->createElement($value));
	 	 					$this->arrayToXml($array, $DOM, $subroot, $maxdepth, $depth++);

	 	 				}
	 	 			}
	 	 		}

	 	 	}
	 	 	return $DOM;
	 	 }


	 	 /**
	 	  * Generate post request results.
	 	  *
	 	  * @param string $data_received Data received after post request is sent.
	 	  * @param unknown $cookies a useless parameter.
	 	  * @return object a Vendor_Post_Result object.
	 	  */
	 	 public function Thank_You_Content(&$data_received)
	 	 {

	 	 	$startm=$this->condmap->getRedirectStart();
	 	 	$endm=$this->condmap->getRedirectEnd();
	 	 	//look for the flag for static redirect
	 	 	if(substr($endm,0,1)=="#")
	 	 	{
	 	 		$url=substr($endm,1);
	 	 		$content = parent::Generic_Thank_You_Page($url, self::REDIRECT);
	 	 		return($content);
	 	 	}
	 	 	$holdit="";

	 	 	$holdit=$this->interDelim($data_received, $startm, $endm);

	 	 	$url=html_entity_decode($holdit);

	 	 	$content = parent::Generic_Thank_You_Page($url, self::REDIRECT);
	 	 	return($content);

	 	 }

	 	 public function Generate_Result(&$data_received, &$cookies)
	 	 {

	 	 	$result = new Vendor_Post_Result();
	 	 	if(is_null($this->condmap))
	 	 	$this->populateCondMap();
	 	 	$needle=$this->condmap->getAcceptMatch();

	 	 	//short circuit for auto-accept
	 	 	if(strcasecmp($needle,"#FORCE") == 0)
	 	 	{
	 	 		$alturl=$this->condmap->getRedirectStart();
	 	 		$result->Set_Message("Accepted");
	 	 		$result->Set_Success(TRUE);
	 	 		$result->Set_Thank_You_Content(parent::Generic_Thank_You_Page($alturl, self::REDIRECT));
	 	 		$result->Set_Vendor_Decision('ACCEPTED');

	 	 		return $result;
	 	 	}

	 	 	if (!strlen($data_received))
	 	 	{
	 	 		$result->Empty_Response();
	 	 		$result->Set_Vendor_Decision('TIMEOUT');
	 	 	}
	 	 	elseif(strstr($data_received, $needle)!==FALSE)
	 	 	{
	 	 		$result->Set_Message("Accepted");
	 	 		$result->Set_Success(TRUE);
	 	 		$result->Set_Thank_You_Content( self::Thank_You_Content($data_received));
	 	 		$result->Set_Vendor_Decision('ACCEPTED');
	 	 	}
	 	 	else
	 	 	{
	 	 		$result->Set_Message("Rejected");
	 	 		$result->Set_Success(FALSE);
	 	 		$result->Set_Vendor_Decision('REJECTED');
	 	 	}

	 	 	return $result;
	 	 }

	 	 /**
	 	  * returns the text occurring between two strings
	 	  *
	 	  * @param string $text the text to search in
	 	  * @param string $s1 the opening delimiter
	 	  * @param string $s2 the ending delimiter
	 	  * @return string if both delims found (empty string if either is missing)
	 	  */
	 	 public function interDelim(&$text, $s1, $s2)
	 	 {
	 	 	$mid_txt = "";
	 	 	$pos_s = strpos($text,$s1);
	 	 	$pos_e = strpos($text,$s2);
	 	 	for ( $i=$pos_s+strlen($s1) ; ( ( $i < ($pos_e)) && $i < strlen($text) ) ; $i++ )
	 	 	{
	 	 		$mid_txt .= $text[$i];
	 	 	}
	 	 	return $mid_txt;
	 	 }


	 	 public function __toString()
	 	 {
	 	 	return "Vendor Post Implementation [dynamic]";
	 	 }
	 	  
}
