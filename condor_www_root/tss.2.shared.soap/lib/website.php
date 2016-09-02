<?php

include(DIR_LIB.'OLP_Soap.php');
include(DIR_LIB.'prpc/client.php');
require_once 'utf8_convert.php';

function Transform_XML($xslt_file, $xml)
{
	
	// load the XML
	$doc = new DOMDocument();
	$doc->loadXML($xml);
	
	// load the style sheet
	$xsl = new DOMDocument();
	$xsl->load($xslt_file);
	
	// transformation!
	$proc = new XSLTProcessor();
	$proc->importStyleSheet($xsl);
	
	$content = $proc->transformToXml($doc);
	return($content);
	
}

class OLP_SOAP_Proxy
{
	
	public function PRPC_Process_Data($license_key, $site_type, $session_id = NULL, $request)
	{
		
		$olp = new Prpc_Client(EDATA_SERVER);
		$olp->Call('Process_Data', array($license_key, $site_type, $session_id, $request, NULL, NULL), $response);
		
		return($response);
		
	}
	
	public function SOAP_Process_Data($license_key, $site_type, $session_id = NULL, $request)
	{
		
		// transform this into an OLP request
		$request = $this->Transform_Request($license_key, $site_type, $session_id, $request);
		
		try
		{
			
			// send the request
			$soap = new SoapClient(WSDL_FILE);
			$response = $soap->User_Data($request->To_XML());
			
			// transform our response into
			// an EDS response look-alike
			$response = $this->Transform_Response($response);
			
		}
		catch (Exception $e)
		{
			$response = FALSE;
		}
		
		return($response);
		
	}
	
	public function Process_Data($license_key, $site_type, $session_id = NULL, $request)
	{
		
        //Clean non-UTF-8 data
        $request = UTF8_Convert::Encode($request,true);

		$response = FALSE;
		
		// what page are we trying to hit?
		$page = isset($request['page']) ? $request['page'] : NULL;
		
		if (!is_null($page))
		{
			
			// we have to run some pages
			// via PRPC, the rest by SOAP
			switch ($page)
			{
				
				// special pages that run via PRPC
				case 'remove':
				case 'info_contactus_base':
				case 'info_contactus_app':
					$response = $this->PRPC_Process_Data($license_key, $site_type, $session_id, $request);
					break;
					
				// the rest we assume use SOAP
				default:
					$response = $this->SOAP_Process_Data($license_key, $site_type, $session_id, $request);
					break;
					
			}
			
		}
        
        $response = UTF8_Convert::Decode($response,true);
        
		if (!$response)
		{
			$response->page = 'try_again';
		}
		
		return($response);
		
	}
	
	protected function Transform_Request($license_key, $site_type, $session_id = NULL, $request)
	{
		
		// build our signature: the construct "imports" relevant
		// values from the array $request (promo ID, etc.)
		$signature = new OLP_Signature($request);
		$signature->Value('site_type', $site_type);
		$signature->Value('license_key', $license_key);
		// rsk for testing only
		//$signature->Value('promo_id', 27658);
		
		if ($session_id) $signature->Value('unique_id', $session_id);
		
		// build our collection
		$collection = new OLP_Collection($request, $signature->Value('page'));
		$collection->Value('client_ip_address', $_SERVER['REMOTE_ADDR']);
		$collection->Value('client_url_root', $_SERVER['SERVER_NAME']);		
		
		// and now, the request object
		$request = new OLP_Request();
		$request->Signature($signature);
		$request->Collection($collection);
		
		return $request;
		
	}
	
	// takes a OLP_Response object and translates it into an
	// "EDS Response" (not exactly a 1:1 conversion, as some
	// things are missing, but should be close enough for this)
	protected function Transform_Response($response)
	{
		
		if (is_string($response))
		{
			$response = new OLP_Response($response);
		}
		
		// we want the merged result so we have tokens for promo_id, etc.
		$data = array_merge($response->Signature()->To_Array(), $response->Collection()->To_Array());
		
		$proxy = new stdClass();
		$proxy->session_id = $response->Unique_ID();
		$proxy->page = $response->Page();
		$proxy->data = $data;
		$proxy->errors = $response->Errors()->To_Array();
		$proxy->event = array();
		
		// if we have a <section> element, turn it into
		// the eds_page in the response, using XSLT
		if (count($response->Content()->Sections()))
		{
			
			// which style-sheet?
			$xslt = DIR_SHARED."/xsl/{$response->Page()}.xsl";
			
			if (is_file($xslt))
			{
				// transform our response
				$content = Transform_XML($xslt, $response->Received());
				$proxy->eds_page = array('content'=>$content, 'type'=>'text/html');
			}
			
		}
		
		return $proxy;
		
	}
	
}

class Website
{
	
	var $response;
	var $token;
	var $layout;
	var $skin;
	
	var $dir_skins;
	var $dir_shared;
	var $dir_lib;
	
	function Website ($layout = NULL)
	{
		$this->token = array();
		$this->layout = is_array($layout) ? $layout : array();
	}
	
	function Skin($skin = NULL)
	{
		
		if (is_null($skin)) $skin = $this->skin;
		else $this->skin = $skin;
		
		return $skin;
		
	}
	
	function Skins_Directory($dir = NULL)
	{
		
		if (!is_null($dir))
		{
			
			if (substr($dir, -1) !== '/') $dir .= '/';
			$this->dir_skins = $dir;
			
		}
		
		return $this->dir_skins;
		
	}
	
	function Shared_Directory($dir = NULL)
	{
		
		if (!is_null($dir))
		{
			
			if (substr($dir, -1) !== '/') $dir .= '/';
			$this->dir_shared = $dir;
			
		}
		
		return $this->dir_shared;
		
	}
	
	function Lib_Directory($dir = NUL)
	{
		
		if (!is_null($dir))
		{
			
			if (substr($dir, -1) !== '/') $dir .= '/';
			$this->dir_lib = $dir;
			
		}
		
		return $this->dir_lib;
		
	}
	
	function Process_Request($request)
	{
		
		$proxy = new OLP_SOAP_Proxy();
		$this->response = $proxy->Process_Data(LICENSE_KEY, SITE_TYPE, session_id(), $request);
		
		if (isset($this->response->session_id))
		{
			// save the OLP session ID -- don't need to do this anymore; we're
			// using the same session ID on our side as on the back-end
			//$_SESSION['unique_id'] = $this->response->session_id;
		}
		
		if (isset($this->response->page))
		{
			// render the page
			$data = $this->Render_Page();
		}
		else
		{
			// page not found error
			$data = FALSE;
		}
		
		return $data;
		
	}
	
	function Render_Page($page = NULL)
	{
		
		if (!is_null($page))
		{
			// this is gay -- can't decide
			// how else to do it, though
			$this->response->page = $page;
		}
		else
		{
			$page = $this->response->page;
		}
		
		// which template are we using?
		$layout = $this->Get_Layout($page);
		
		$try = array(
			$this->dir_skins.$this->skin.'/templates/'.$layout->template.'.php',
			$this->dir_shared.'templates/'.$layout->template.'.php',
		);
		
		foreach ($try as $filename)
		{
			$data = $this->Get_File($filename);
			if ($data !== FALSE) break;
		}
		
		if ($data !== FALSE)
		{
			
			// build our page
			$valid = $this->Build_Main_Block();
			
			if ($valid !== FALSE)
			{
				
				$this->Add_Token('skin', $this->skin);
				$this->Add_Token('template_css', $layout->css);
				
				// build the rest of our tokens
				$this->Build_Tokens();
				$this->Build_Error_Block();
				$this->Format_Data();
				
				// do the token replacement
				$data = $this->Replace_Tokens($data);
				
			}
			else
			{
				$data = FALSE;
			}
			
		}
		else
		{
			// invalid page
			$data = FALSE;
		}
		
		return $data;
		
	}
	
	function Get_Layout($page)
	{
		
		$page_info = new stdClass();
		
		if (isset($this->layout[$page]))
		{
			
			$layout = $this->layout[$page];
			
			if (!is_array($layout))
			{
				$page_info->template = $layout;
			}
			else
			{
				if (isset($layout['template'])) $page_info->template = $layout['template'];
				if (isset($layout['css'])) $page_info->css = @$this->layout[$page]['css'];
			}
			
		}
		
		// defaults
		if (!isset($page_info->template)) $page_info->template = 'default';
		if (!isset($page_info->css)) $page_info->css = 'main';
		
		return $page_info;
		
	}
	
	function Process_Events ()
	{
		
		if (function_exists ("Olp_Event_Handler") && is_array($this->response->event) && count($this->response->event))
		{
			foreach ($this->response->event as $e)
			{
				Olp_Event_Handler($e, $this->response->data, $this);
			}
		}
		
	}

	function Build_Tokens ()
	{
		
		require_once ('build.tokens.php');
		require_once ('tabindex.php');
		
		if (isset($this->response->data))
		{
			$this->token = array_merge($this->token, $this->response->data);
		}
		else
		{
			$this->response->data = array();
		}
		
		$this->token['JS_MAIN'] = $this->Get_File($this->dir_lib.'/javascript.php');
		
		if (isset($tabindex[$this->response->page]))
		{
			
			// the tab index is not 0-based, so we have
			// to get a dummy entry on the front
			$index = $tabindex[$this->response->page];
			array_unshift($index, '');
			
			// the tokens are array values - flip will make them
			// keys with their indexes as values
			$index = array_flip($index);
			$this->token = array_merge($this->token, $index);
			
		}

		$state = new State_Selection();
		$state->State_Pulldown ("home_state", 0, 0, $this->response->data['home_state'], "", "", "", "", "", "", $this->token['TABINDEX_HOME_STATE'], 0);
		$this->token['new_state'] = $state->state_select_html;

		$newbd = new birthdate (18, 99, "date_dob_m/date_dob_d/date_dob_y", $this->token['TABINDEX_DATE_DOB_M']);
		$newbd->write_javascript ();
		$this->token['new_dob'] = $newbd->create_dob_string ($this->response->data["date_dob_m"], $this->response->data["date_dob_d"], $this->response->data["date_dob_y"]);

		$inputs_radio_check = array (
			"income_stream" => "radio",
			"monthly_1200" => "radio",
			"checking_account" => "radio",
			"citizen" => "radio",
			"offers" => "radio",
			"employer_length" => "radio",
			"income_type" => "radio",
			"income_direct_deposit" => "radio",
			"legal_notice_1" => "checkbox",
			"ezm_nsf_count" => "radio",
			"ezm_terms" => "checkbox",
		);
		
		foreach ($inputs_radio_check as $field_name => $input_type)
		{
			
			$value = isset($this->response->data[$field_name]);
			{
				
				switch ($input_type)
				{
					
					case "radio":
						
						switch (strtoupper($value))
						{
							case "FALSE":
							case "BENEFITS":
								$this->token[$field_name."_t"] = '';
								$this->token[$field_name."_f"] = 'checked="checked"';
								break;
								
							default:
								$this->token[$field_name."_t"] = 'checked="checked"';
								$this->token[$field_name."_f"] = '';
								break;
						}
						break;
						
					case "checkbox":
						$this->token[$field_name] = 'checked="checked"';
						break;
				}
				
			}
		}
		
		// create the best call time token
		$best_call = strtoupper($this->response->data['best_call_time']);
		$this->token["best_call_select"] = Tokens::Select('best_call_time', Tokens::$best_call, $best_call, NULL, $this->token['TABINDEX_BEST_CALL_TIME']);
		
		$direct_deposit = strtoupper($this->response->data['income_direct_deposit']);
		$this->token['direct_deposit_select'] = Tokens::Select('income_direct_deposit', Tokens::$direct_deposit, $direct_deposit, NULL, $this->token['TABINDEX_INCOME_DIRECT_DEPOSIT']);
		
		$account_type = strtoupper($this->response->data['bank_account_type']);
		$this->token['bank_account_type_select'] = Tokens::Select('bank_account_type', Tokens::$account_type, $account_type, NULL, $this->token['TABINDEX_BANK_ACCOUNT_TYPE']);
		
		$income_freq = strtoupper($this->response->data['income_frequency']);
		$this->token['income_frequency_select'] = Tokens::Select('income_frequency', Tokens::$income_frequency, $income_freq, NULL, $this->token['TABINDEX_INCOME_FREQUENCY']);
		
		$this->token['income_date1_select_m'] = new Date_Select('income_date1_m', time(), strtotime('+2 months'), '1 day', $this->response->data['income_date1_m'], 'n', 'n', FALSE, TRUE, $this->token['TABINDEX_INCOME_DATE1_M']);
		$this->token['income_date1_select_d'] = new Range_Select('income_date1_d', 1, 31, 1, $this->response->data['income_date1_d'], TRUE, $this->token['TABINDEX_INCOME_DATE1_D']);
		$this->token['income_date1_select_y'] = new Date_Select('income_date1_y', time(), strtotime('+2 months'), '1 year', $this->response->data['income_date1_y'], 'Y', 'Y', FALSE, TRUE, $this->token['TABINDEX_INCOME_DATE1_Y']);
		
		$this->token['income_date2_select_m'] = new Date_Select('income_date2_m', time(), strtotime('+2 months'), '1 day', $this->response->data['income_date2_m'], 'n', 'n', FALSE, TRUE, $this->token['TABINDEX_INCOME_DATE2_M']);
		$this->token['income_date2_select_d'] = new Range_Select('income_date2_d', 1, 31, 1, $this->response->data['income_date2_d'], TRUE, $this->token['TABINDEX_INCOME_DATE2_D']);
		$this->token['income_date2_select_y'] = new Date_Select('income_date2_y', time(), strtotime('+2 months'), '1 year', $this->response->data['income_date2_y'], 'Y', 'Y', FALSE, TRUE, $this->token['TABINDEX_INCOME_DATE2_Y']);
		
		require_once("dropdown.1.months.php");
		$months = new Dropdown_Months();
		$months->setSelectTags(false);
		$months->setUnselected("(Month)");
		$months->keyShortMonths();
		$months->setSelected(@$this->response->data["date_dob_m"]);
		$this->token["date_dob_m_dropdown"] = $months->display(true);
		
		require_once("dropdown.1.numeric.php");
		$day_array = array('START' =>1,
								'END' => 31,
								'INCREMENT' =>1);
		$days = new Dropdown_Numeric($day_array);
		$days->setSelectTags(false);
		$days->setUnselected("(Day)");
		$days->zeroPad(true);
		$days->setSelected(@$this->response->data["date_dob_d"]);
		$this->token["date_dob_d_dropdown"] = $days->display(true);

		$year_array = array('STOP' => date("Y")-18,
							'START' => date("Y")-118,
							'INCREMENT' => 1);
		$years = new Dropdown_Numeric($year_array);
		$years->setSelectTags(false);
		$years->setUnselected("(Year)");
		$years->setSelected(@$this->response->data["date_dob_y"]);
		$this->token["date_dob_y_dropdown"] = $years->display(true);

		$this->token["date_today"] = date ("m/d/Y");
		//$this->token['template_css'] = 'main';
		$this->token['SHARED_IMAGE'] = 'image';

		$this->token['eds_noticesanddisclosures'] = $this->Get_File ($this->dir_lib.'text.noticeanddisclosure.php');
		
    $this->token['NAV_DROPDOWN'] = "<form name=\"form1\" action=\"\">\n";
    $this->token['NAV_DROPDOWN'] .= "\t<select name=\"menu1\" onchange=\"pop_dropdown(this)\">\n";
    $this->token['NAV_DROPDOWN'] .= "\t\t<option selected=\"selected\">Quick Navigation</option>\n";
    $this->token['NAV_DROPDOWN'] .= "\t\t<option value=\"?page=info_contactus_base\">Contact Us</option>\n";
    $this->token['NAV_DROPDOWN'] .= "\t\t<option value=\"?page=info_privacy\">Privacy Policy</option>\n";
    $this->token['NAV_DROPDOWN'] .= "\t\t<option value=\"?page=cs_removeme\">Remove Me</option>\n";
    $this->token['NAV_DROPDOWN'] .= "\t\t<option value=\"?page=info_spam\">Spam Concerns</option>\n";
    $this->token['NAV_DROPDOWN'] .= "\t</select>\n";
    $this->token['NAV_DROPDOWN'] .= "</form>\n";
		
		$this->token['URL_ROOT'] = URL_ROOT;
		$this->token['eds_name_view'] = SITE_NAME;
		$this->token['eds_site_name'] = SITE_URL;

	}
	
	function Build_Main_Block()
	{
		
		$page = $this->response->page;
		
		// this is the page override order
		$try = array(
			$this->dir_skins.$this->skin.'/pages/'.$page.'.html',
			$this->dir_skins.$this->skin.'/pages/'.$page.'.php',
			$this->dir_shared.'pages/'.$page.'.html',
			$this->dir_shared.'pages/'.$page.'.php',
		);
		
		foreach ($try as $filename)
		{
			$data = $this->Get_File($filename);
			if ($data !== FALSE) break;
		}
		
		// check for the eds_page in the response
		if (($data === FALSE) && isset($this->response->eds_page['content']))
		{
			$data = $this->response->eds_page['content'];
		}
		
		if ($data !== FALSE)
		{
			$this->Add_Token('BLOCK_MAIN', $data);
			$data = TRUE;
		}
		
		return($data);
		
	}
	
	function Build_Error_Block ()
	{
		
		$this->token['BLOCK_ERRORS'] = new Block_Errors($this->response->errors);
		
	}

	function Add_Token ($key, $value)
	{
		$this->token[$key] = $value;
	}

	function Replace_Tokens($data)
	{
	
		// render the main block first: otherwise,
		// it could rely on tokens which are above it
		// in the token "stack"
		if (isset($this->token['BLOCK_MAIN']))
		{
			$data = str_replace('@@BLOCK_MAIN@@', $this->token['BLOCK_MAIN'], $data);
			unset($this->token['BLOCK_MAIN']);
		}
		
		foreach ($this->token as $key=>$value)
		{
			
			if ($value instanceof Token)
			{
				$value = $value->Value();
			}
			
			$data = str_replace('@@'.$key.'@@', $value, $data);
			
		}
		
		$data = preg_replace('/@@(.*?)@@/', '', $data);
		
		return($data);
		
	}

	function Format_Data()
	{
    
		$phone_fields = array("phone_home", "phone_work", "phone_cell", "phone_fax", "ref_01_phone_home", "ref_02_phone_home", "fax_number", "eds_phone_fax", "eds_phone_support");
    foreach ($phone_fields as $field)
    {
    	if (array_key_exists($field, $this->response->data))
    	{
        $this->response->data[$field] =  preg_replace("/^1?(\d{3})(\d{3})(\d{4})$/", "\\1-\\2-\\3", $this->response->data[$field]);
    	}
    }
    
		$mixedcase_fields = array( "name_first", "name_last", "home_street", "home_city", "employer_name", "bank_name", "ref_01_name_full", "ref_01_relationship", "ref_02_name_full", "ref_02_relationship", "eds_name_property", "app_status");
    foreach ($mixedcase_fields as $field)
    {
    	if (array_key_exists($field, $this->response->data))
    	{
        $this->response->data[$field] = ucwords(strtolower($this->response->data[$field]));
    	}
    }
    
		$lowercase_fields = array("email_primary","email_primary","username","eds_site_name");
    foreach ($lowercase_fields as $field)
    {
    	if (array_key_exists($field, $this->response->data))
    	{
        $this->response->data[$field] = strtolower($this->response->data[$field]);
    	}
    }
    
	}
	
	function Get_File ($file)
	{
		
		if (file_exists($file) && is_readable($file))
		{
			
			if (strtolower(strrchr($file, '.')) === '.php')
			{
				
				ob_start();
				@include($file);
				$page = ob_get_contents();
				ob_end_clean();
				
			}
			else
			{
				$page = @file_get_contents($file);
			}
			
		}
		else
		{
			$page = FALSE;
		}
		
		return($page);
		
	}
	
	function Debug_Info()
	{
		
		$info = '';
		$info .= 'MODE: '.MODE."<br/>\n";
		$info .= 'SHARED DIRECTORY: '.$this->dir_shared."<br/>\n";
		$info .= 'SKINS DIRECTORY: '.$this->dir_skins."<br/>\n";
		$info .= 'CURRENT SKIN: '.$this->skin."<br/>\n";
		$info .= 'RESPONSE: <br/><pre>'.htmlentities(print_r($this->response, TRUE))."</pre><br/>\n";
		$info .= 'TOKENS: <br/><pre>'.htmlentities(print_r($this->token, TRUE))."</pre><br/>\n";
		
		return $info;
		
	}
	
}

?>
