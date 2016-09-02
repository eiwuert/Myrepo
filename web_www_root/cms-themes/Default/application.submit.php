<?php
/*
Template: Submit Application
Description: Page that submits the application to the backend
*/
?>
<?php
	include 'cms-includes/recaptchalib.php';
	include(TEMPLATES_PATH.'application.func.php'); 
	
	function randomString($size) {
		$character_set_array = array();
		$character_set_array[] = array('count' => $size, 'characters' => 'abcdefghijklmnopqrstuvwxyz0123456789');
		$temp_array = array();
		foreach ($character_set_array as $character_set) {
			for ($i = 0; $i < $character_set['count']; $i++) {
				$temp_array[] = $character_set['characters'][rand(0, strlen($character_set['characters']) - 1)];
			}
		}
		shuffle($temp_array);
		return implode('', $temp_array);
	}

	function constructPaydateInfo (){
		$day_of_week = '';
		$last_paydate = '';
		$day_of_month_1 = '';
		$day_of_month_2 = '';
		$week_1 = '';
		$week_2 = '';
		$frequency = strtolower($_POST['paydate']['frequency']);
                switch ($_POST['paydate']['frequency']) {
		    case "WEEKLY":
			$paydate_model = "DW";
			$day_of_week = $_POST['paydate']['weekly_day'];
			break;
		    case "BI_WEEKLY":
			$paydate_model = "DWPD";
			$day_of_week = $_POST['paydate']['biweekly_day'];
			$last_paydate = $_POST['paydate']['biweekly_date'];
			break;
		    case "TWICE_MONTHLY":
			switch ($_POST['paydate']['twicemonthly_type']) {
			    case "biweekly":
				$frequency = "bi_weekly";
				$paydate_model = "DWPD";
				$day_of_week = $_POST['paydate']['biweekly_day_mnth'];
				$last_paydate = $_POST['paydate']['biweekly_date_mnth'];
				break;
			    case "date":
				$paydate_model = "DMDM";
				$day_of_month_1 = $_POST['paydate']['twicemonthly_date1'];
				$day_of_month_2 = $_POST['paydate']['twicemonthly_date2'];
				break;
			    case "week":
				$paydate_model = "WWDW";
				$day_of_week = $_POST['paydate']['twicemonthly_day'];
				$week_1 = substr($_POST['paydate']['twicemonthly_week'],0,1);
				$week_2 = substr($_POST['paydate']['twicemonthly_week'],2,1);
				break;
			}
			break;
		case "MONTHLY":
			switch ($_POST['paydate']['monthly_type']) {
			    case "date":
				$paydate_model = "DM";
				$day_of_month_1 = $_POST['paydate']['monthly_date'];
				break;
			    case "day":
				$frequency = "bi_weekly";
				$paydate_model = "WDW";
				$day_of_week = $_POST['paydate']['monthly_day'];
				$week_1 = $_POST['paydate']['monthly_week'];
				break;
			    case "after":
				$paydate_model = "DWDM";
				$day_of_week = $_POST['paydate']['monthly_after_day'];
				$day_of_month_1 = $_POST['paydate']['monthly_after_date'];
				break;
			}
			break;
		}
		
		return(array($frequency, $paydate_model, $day_of_week, $last_paydate, $day_of_month_1, $day_of_month_2, $week_1, $week_2));
	}

	function BuildSoapResponse($vapi_response)
	{
		$soap_response = new StdClass();
		$soap_response->signature = new StdClass();
		$soap_response->content = new StdClass();
		$soap_response->content->section = new StdClass();
		$soap_response->errors = new StdClass();
	
		if (!isset($vapi_response->outcome)){
			$soap_response->signature->data = 'system_error';
			$soap_response->errors->data = 'SOAP system error, ';
		} elseif ($vapi_response->outcome == 1 && $vapi_response->result['qualified'] != "0") {
			$soap_response->signature->data = 'success';
			$soap_response->content->application_id = $vapi_response->result['application_id'];
			$soap_response->content->redirect_url = $vapi_response->result['redirect_url'];
		} elseif ($vapi_response->outcome == 1) {
			$soap_response->signature->data = 'app_declined';
			$soap_response->errors->data = $vapi_response->result['fail']['short'] . "\n" . $vapi_response->result['fail']['comment'];
		} else {
			$soap_response->signature->data = 'app_error';
			$soap_response->errors->data = "An error occurred processing the requested application submission: \n".
				(is_array($vapi_response->error) ? implode("\n", $vapi_response->error) : $vapi_response->error);
		}
		return $soap_response;
	}
	
	// Test captcha first
	$resp = recaptcha_check_answer(lum_getString("[CAPTCHA_SERVER_KEY]"),
		$_SERVER["REMOTE_ADDR"],
		$_POST["recaptcha_challenge_field"],
		$_POST["recaptcha_response_field"]);

	if (!$resp->is_valid) {
		$soap_response->signature->data = 'app_error';
		$soap_response->errors->data = "An error occurred processing the requested application submission: \n".
			"Captcha Error \n".
			(is_array($resp->error) ? implode("\n", $resp->error) : $resp->error);
	}

	// Below is the soap client version
	list($frequency, $paydate_model, $day_of_week, $last_paydate, $day_of_month_1, $day_of_month_2, $week_1, $week_2) = constructPaydateInfo();
	
	$contact_info = new stdClass();
	$contact_info->address = array(
		'street' => $_POST['address'],
		'city' => $_POST['city'],
		'state' => $_POST['state'],
		'zip_code' => $_POST['zip_code']
	);
	$contact_info->email = $_POST['email_primary'];
	$contact_info->phone_home = $_POST['phone_home'];
	$contact_info->phone_cell = $_POST['phone_cell'];
	$contact_info->phone_work = $_POST['phone_work'];

	if (($_POST['direct_deposit'] == 'TRUE') || ($_POST['direct_deposit'] === true)) $dd = true; else $dd = false;
	
	$employment = new stdClass();
	$employment->paydate_info = array(
		'paydate_model' => $paydate_model,
		'last_paydate' => $last_paydate,
		'day_of_week' => $day_of_week,
		'day_of_month_1' => $day_of_month_1,
		'day_of_month_2' => $day_of_month_2,
		'week_1' => $week_1,
		'week_2' => $week_2,
		'income_direct_deposit' => $dd,
		'income_source' => $_POST['income_source'],
		'income_frequency' => $frequency,
		'income_monthly' => $_POST['monthly_income'],
	);
	$employment->employer_name = $_POST['employer'];
	$employment->phone_work = $_POST['phone_work'];

	if (($_POST['military'] == 'TRUE') || ($_POST['military'] === true)) $mil = true; else $mil = false;

	$applicant = array (
		'name_first' => $_POST['name_first'],
		'name_last' => $_POST['name_last'],
		'dob' => $_POST['date_of_birth'],
		'ssn' => str_replace('-','',$_POST['ssn']),
		'military' => $mil,
		'legal_id' => array(
			'legal_id_type' => 'dl',
			'legal_id_state' => $_POST['legal_id_state'],
			'legal_id_number' => $_POST['legal_id_number'],
		),
		'contact_info' => $contact_info,
		'employment' => $employment,
		'bank_account' => array(
			'bank_account_type' => $_POST['bank_type'],
			'bank_name' => $_POST['bank_name'],
			'bank_aba' => $_POST['bank_aba'],
			'bank_account' => $_POST['bank_account'],
		),
	);
	
	$references = false;
	if (!empty($_POST['ref_01_name_full']) && !empty($_POST['ref_01_phone']) && !empty($_POST['ref_01_relationship'])){
		$reference = array(
			'name_full' => $_POST['ref_01_name_full'],
			'phone_home' => $_POST['ref_01_phone'],
			'relationship' => $_POST['ref_01_relationship'],
		);
		$references[] = $reference;
	}
	
	if (!empty($_POST['ref_02_name_full']) && !empty($_POST['ref_02_phone']) && !empty($_POST['ref_02_relationship'])){
		if (!$references) $references = array();
		$reference = array(
			'name_full' => $_POST['ref_02_name_full'],
			'phone_home' => $_POST['ref_02_phone'],
			'relationship' => $_POST['ref_02_relationship'],
		);
		$references[] = $reference;
	}
	if ($references){
		$applicant['personal_references'] = $references;
	}
	
	$request = array (
		'application_id' => 0,
		'vendor_customer_id' => lum_getString('[VENDOR_ID]'),
		'applicant' => $applicant,
		'is_react' => isset($_POST['is_react']) ? TRUE : FALSE,
		'is_enterprise' => TRUE,
		'track_id' => randomString(32),
		'is_enterprise' => lum_getString('[ENTERPRISE_SITE]'),
		'ip_address' => $_SERVER['REMOTE_ADDR'],
		'target' => lum_getString('[CAMPAIGN_TARGET]'),
		'campaign' => lum_getString('[CAMPAIGN_NAME]'),
		'loan_amount_desired' => $_POST['requested_amount'],
		'loan_type' => lum_getString('[CAMPAIGN_LOAN_TYPE]'),
		'price_point' => lum_getString('[CAMPAIGN_PRICE_POINT]'),
		'olp_process' => lum_getString('[CAMPAIGN_OLP_PROCESS]'),
		'campaigns' => array(
			'license_key' => lum_getString("[CAMPAIGN_LICENSE_KEY]"),
			'promo_id' => lum_getString("[CAMPAIGN_ID_CODE]"),
			'promo_sub_code' => lum_getString("[CAMPAIGN_SUB_CODE]"),
			'page_id' => lum_getString("[CAMPAIGN_PAGE_ID]"),
			'campaign_name' => lum_getString("[CAMPAIGN_NAME]"),
			'name' => $_SERVER['SERVER_NAME'],
			'publisher_id' => lum_getString("[CAMPAIGN_PUBLISHER_ID]"),
		),
	);
	
	$post_url = lum_getString("[CAMPAIGN_POST_URL]").
		"?enterprise=".lum_getString("[CAMPAIGN_ENTERPRISE]").
		"&company=".lum_getString("[CAMPAIGN_COMPANY]");

	$options = array(
		'login' => lum_getString("[CAMPAIGN_POST_ID]"),
		'password' => lum_getString("[CAMPAIGN_POST_LC]"),
	);

	$client = new SoapClient($post_url."&wsdl",$options);
	$response_xml = $client->post($request);
	
	$response = BuildSoapResponse($response_xml);

        CheckResponseForError($response);

	$result_text = "app success";
	if(!isset($_SESSION)) session_start();
	$_SESSION['language'] = getCurrentLanguage();
	lum_redirect($response->content->redirect_url);
?>
<?php include(TEMPLATES_PATH.'header_html.php'); ?>
<body>
	<div class="wrapper">
		<div class="interior">
			<h1><?php
				echo $result_text;
			?></h1>
			<div><?php
				echo $response->errors->data;
			?></div>
		</div>
	</div>
[cmsinclude Footer]
<?php include(TEMPLATES_PATH.'footer_html.php');?>
