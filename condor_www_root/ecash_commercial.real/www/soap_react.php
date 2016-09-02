<?
//ini_set('error_reporting', 'E_ALL & ~E_NOTICE & ~E_WARNING');


require_once("config.php");
//require_once(LIB_DIR."/Config.class.php");
require_once("dropdown.1.generic.php");
require_once("state_selection.2.php");
require_once("dropdown_dates.1.php");
require_once(SERVER_CODE_DIR."paydate_handler.class.php");
require_once(SQL_LIB_DIR."util.func.php");
require_once(SQL_LIB_DIR."application.func.php");
require_once(LIB_DIR."soap_log.class.php");

require_once(LIB_DIR."business_rules.class.php");
require_once(ECASH_COMMON_DIR."ecash_api/loan_amount_calculator.class.php");
require_once(SERVER_CODE_DIR . "vehicle_data.class.php");
require_once(ECASH_COMMON_DIR . 'nada/NADA.php');
require_once(SERVER_CODE_DIR . "server_factory.class.php");

/*
SOAP Interface for Reactivations
*/

function QualifyAmount($income_monthly_net, $application_id, $selected_amount = null)
{
	$db = ECash_Config::getMasterDbConnection();

	$business_rules = new ECash_BusinessRules($db);
	$app_data = Fetch_Application_Info($application_id);
	$app_data->application_list = Fetch_Application_List($app_data->company_id, $app_data->ssn, 'ssn');

	$app_data->business_rules = $business_rules->Get_Rule_Set_Tree($app_data->rule_set_id);
	$app_data->is_react='yes';
	$app_data->income_monthly = $income_monthly_net;
	$app_data->payperiod = $app_data->income_frequency;
	$app_data->react_app_id = $application_id;
	
	if(isset($app_data->business_rules['loan_type_model']) && $app_data->business_rules['loan_type_model'] == 'Title')
	{
		$vehicle_data = Vehicle_Data::fetchVehicleData($app_data->application_id);
		$app_data = (object) array_merge((array) $app_data, (array) $vehicle_data);
		$app_data->loan_type_model = 'Title';
	}
	else
	{
		$app_data->loan_type_model = 'Payday';
	}

	$calculator = LoanAmountCalculator::Get_Instance($db, $app_data->display_short);

	$app_data->fund_amount_array   = $calculator->calculateLoanAmountsArray($app_data);
	$app_data->loan_amount_allowed = $calculator->calculateMaxLoanAmount($app_data);

	if (!empty($app_data->fund_amount_array))
	{
		foreach ($app_data->fund_amount_array as $amount)
		{
			$amount_select .= "<option value='{$amount}' $SELECTED>$".$amount.".00</option>\n";
		}
	}
	else
	{
		$amount_select = 0;
	}

	return $amount_select;

}

// GF #13190: Attribute information was not being passed to the reacted application
// Ideally we'd be transporting this through OLP, but the capability is not currently
// there, and this is how CLK currently does it. [benb]
function Copy_Application_Attributes_To_React($server, $company_id, $old_app_id, $new_app_id)
{
	$loan_data      = new Loan_Data($server);

	// Get the loan data for the old loan
	$inactive_app   = $loan_data->Fetch_Loan_All($old_app_id);

	if (is_array($inactive_app->notifications))
	{
		// This class really is ugly, but it's what loan_data is using.
		$app_attrib = new eCash_Application_FieldAttribute(ECash_Config::getMasterDbConnection());

		// Get the old app's attributes
		// This makes an array attrib_name => field_list
		$app_notifications = array();
		foreach ($inactive_app->notifications as $notification)
		{
			$app_notifications[$notification->field_name][] = $notification->column_name;
		}

		// Foreach attribute name, add the fields for that attribute
		foreach($app_notifications as $attrib_name => $field_list)
		{
			// Add the attributes to the reacted application
			$app_attrib->Change_Attribute($company_id, $new_app_id, $attrib_name, $app_notifications[$attrib_name]);
		}

	}
}

function Process_React($server)
{
	unset($_SESSION["RESPONSE_ERRORS"]);
	unset($_SESSION["SOAP_RESPONSE"]);

	$soap_log = new Soap_Log();

	$result = PostToSoap($soap_log);

	if((string)$result->signature->data == "agent_react_confirm")
	{
		//Good
		$new_app_id = (string)$result->content->section->application_id;
		//$change_app_url = "/?module=funding&mode=underwriting&action=show_applicant&application_id={$new_app_id}&ecash_react=true";
		$change_app_url = "/?module=loan_servicing&mode=customer_service&action=show_applicant&application_id={$new_app_id}&ecash_react=true"; // mantis:3800
		$_SESSION["SOAP_RESPONSE"] = "<center>Application has been approved.<br>";
		$_SESSION["SOAP_RESPONSE"] .= "Application ID: <b>$new_app_id</b><br>";
		$_SESSION["SOAP_RESPONSE"] .= "<a href='#' onclick='parent.location.href = \"$change_app_url\"'>Click Here to Go to New Loan.</a></center>";

		// GF #13190: Copy over the old application's attributes [benb]
		Copy_Application_Attributes_To_React($server, $_REQUEST['company_id'], $_REQUEST["react_app_id"], $new_app_id);
		return true;
	}
	else if((string)$result->signature->data == "app_declined")
	{
		//Bad
		$_SESSION["SOAP_RESPONSE"] = "After carefully reviewing your application, we are sorry to advise you
that we cannot grant a loan to you at this time. Within 10 days you will
receive a statement of specific reasons for your denial, via email.  If
you have additional questions after receiving this letter please follow
the instructions on the email to receive additional information.";
		return true;
	}
	else if(count($result->errors->data) > 0)
	{
		// Display The Errors
		$_SESSION["RESPONSE_ERRORS"] = (string)$result->errors->data;
		$soap_log->Set_Failed();
		return false;
	}
	else
	{
		// Everything else something is wrong and they dont qualify
		$_SESSION["SOAP_RESPONSE"] = "This customer does not qualify for a reactivation. They may apply for a new loan.";
		return true;
	}
}

function PostToSoap($soap_log)
{
	$xml_package = CreatePackage();
	$soap_log->Insert_Request($_REQUEST['company_id'], $_REQUEST["react_app_id"], $_REQUEST["agent_id"], $xml_package, $type = "soap_react");
	if(class_exists("SoapClient"))
	{
		$client = new SoapClient(eCash_Config::getInstance()->REACT_SOAP_URL,array("trace"=>true));
		$output = $client->User_Data($xml_package);
		$result = simplexml_load_string($output);
		$soap_log->Set_Success($output);
	}
	else
	{
		$wsdl = new SOAP_WSDL(eCash_Config::getInstance()->REACT_SOAP_URL);
		$client = $wsdl->getProxy();
		$result = $client->User_Data($xml_package);
		$soap_log->Set_Success(var_export($result,true));
	}
	return $result;
}

function RequestFormat($req)
{
	foreach ($req as $key => $value)
	{	if (get_magic_quotes_gpc() && strstr($value,"'"))
	$value = stripslashes($value);

	if(strstr($value,"'"))
	$req[$key] = htmlspecialchars($value,ENT_QUOTES);
	}
	return $req;
}



function CreatePackage()
{
	$req = RequestFormat($_REQUEST);

	// GF #12703: Added bank_account_type to packet

	$is_checking = ($req['bank_account_type'] == "CHECKING") ? 'TRUE' : 'FALSE';
	$dob_day = isset($req['date_dob_d']) ? $req['date_dob_d'] : $req['date_dob_day'];
	$dob_month = isset($req['date_dob_m']) ? $req['date_dob_m'] : $req['date_dob_month'];
	$dob_year = isset($req['date_dob_y']) ? $req['date_dob_y'] : $req['date_dob_year'];
	$req['income_monthly_net'] = intval($req['income_monthly_net']);
	$ca_agree = ($req['home_state'] == "CA") ? "1" : "";
	$react_type = isset($req['react_type']) ? $req['react_type'] : "react";
	$site_type = isset($req['site_type']) ? $req['site_type'] : "blackbox.valucash.one.page";


	$xml_request = "<tss_loan_request>
	<signature>
		<data name=\"site_type\">$site_type</data>
		<data name=\"license_key\">".eCash_Config::getInstance()->REACT_SOAP_KEY."</data>
		<data name=\"page\">app_allinone</data>
		<data name=\"promo_id\">{$req['promo_id']}</data>
		<data name=\"promo_sub_code\"></data>
	</signature>
	<collection>
		<data name=\"work_title\">{$req['work_title']}</data>
		<data name=\"date_of_hire\">{$req['date_of_hire']}</data>
		<data name=\"residence_start_date\">{$req['residence_start_date']}</data>
		<data name=\"banking_start_date\">{$req['banking_start_date']}</data>
		<data name=\"vehicle_year\">{$req['vehicle_year']}</data>
		<data name=\"vehicle_make\">{$req['vehicle_make']}</data>
		<data name=\"vehicle_model\">{$req['vehicle_model']}</data>
		<data name=\"vehicle_series\">{$req['vehicle_series']}</data>
		<data name=\"vehicle_style\">{$req['vehicle_body']}</data>
		<data name=\"vehicle_mileage\">{$req['vehicle_mileage']}</data>
		<data name=\"vehicle_vin\">{$req['vehicle_vin']}</data>
		<data name=\"vehicle_value\">{$req['vehicle_value']}</data>
		<data name=\"vehicle_color\">{$req['vehicle_color']}</data>
		<data name=\"vehicle_license_plate\">{$req['vehicle_license_plate']}</data>
		<data name=\"vehicle_title_state\">{$req['vehicle_title_state']}</data>
	    <data name=\"ecashapp\">{$req['ecashapp']}</data>
	    <data name=\"no_checks\">{$req['no_checks']}</data>
	    <data name=\"agent_id\">{$req['agent_id']}</data>
	    <data name=\"react_app_id\">{$req['react_app_id']}</data>
	    <data name=\"ecashdn\">{$req['ecashdn']}</data>
		<data name=\"bank_aba\">{$req['bank_aba']}</data>
		<data name=\"bank_account\">{$req['bank_account']}</data>
		<data name=\"bank_name\">{$req['bank_name']}</data>
		<data name=\"best_call_time\">{$req['best_call_time']}</data>
		<data name=\"checking_account\">{$is_checking}</data>
		<data name=\"bank_account_type\">{$req['bank_account_type']}</data>
		<data name=\"citizen\">TRUE</data>
		<data name=\"client_ip_address\">{$_SERVER["SERVER_ADDR"]}</data>
		<data name=\"client_url_root\">ecashapp.com</data>
		<data name=\"date_dob_d\">{$dob_day}</data>
		<data name=\"date_dob_m\">{$dob_month}</data>
		<data name=\"date_dob_y\">{$dob_year}</data>
		<data name=\"email_primary\">{$req['email_primary']}</data>
		<data name=\"employer_length\">TRUE</data>
		<data name=\"employer_name\">{$req['employer_name']}</data>
		<data name=\"home_city\">{$req['home_city']}</data>
		<data name=\"home_state\">{$req['home_state']}</data>
		<data name=\"home_street\">{$req['home_street']}</data>
		<data name=\"home_unit\">{$req['home_unit']}</data>
		<data name=\"home_zip\">{$req['home_zip']}</data>
		<data name=\"income_direct_deposit\">{$req['income_direct_deposit']}</data>
		<data name=\"income_frequency\">{$req['paydate']['frequency']}</data>
		<data name=\"income_monthly_net\">{$req['income_monthly_net']}</data>
		<data name=\"legal_notice_1\">TRUE</data>
		<data name=\"income_stream\">TRUE</data>
		<data name=\"income_type\">{$req['income_type']}</data>
		<data name=\"name_first\">{$req['name_first']}</data>
		<data name=\"name_last\">{$req['name_last']}</data>
		<data name=\"name_middle\">{$req['name_middle']}</data>
		<data name=\"offers\">FALSE</data>
		<data name=\"phone_home\">{$req['phone_home']}</data>
		<data name=\"phone_cell\">{$req['phone_cell']}</data>
		<data name=\"phone_work\">{$req['phone_work']}</data>
		<data name=\"work_ext\">{$req['phone_work_ext']}</data>
		<data name=\"ref_01_name_full\">{$req['ref_01_name_full']}</data>
		<data name=\"ref_01_phone_home\">{$req['ref_01_phone_home']}</data>
		<data name=\"ref_01_relationship\">{$req['ref_01_relationship']}</data>
		<data name=\"ref_02_name_full\">{$req['ref_02_name_full']}</data>
		<data name=\"ref_02_phone_home\">{$req['ref_02_phone_home']}</data>
		<data name=\"ref_02_relationship\">{$req['ref_02_relationship']}</data>
		<data name=\"ssn_part_1\">{$req['ssn_part_1']}</data>
		<data name=\"ssn_part_2\">{$req['ssn_part_2']}</data>
		<data name=\"ssn_part_3\">{$req['ssn_part_3']}</data>
		<data name=\"state_id_number\">{$req['state_id_number']}</data>
		<data name=\"state_issued_id\">{$req['state_issued_id']}</data>
		<data name=\"cali_agree\">$ca_agree</data>
		<data name=\"fund_amount\">{$req['fund_amount']}</data>
		<data name=\"react_type\">{$react_type}</data>
		<data name=\"loan_type\">{$req['loan_type']}</data>
		<data name=\"military\">{$req['military']}</data>
		<data name=\"paydate\">";
	foreach ($req['paydate'] as $key => $value)
	{
		$xml_request .= "<subdata name=\"{$key}\">{$value}</subdata>";
	}
	$xml_request .= "</data>
	</collection>
</tss_loan_request>";

	return $xml_request;
}

function ReactForm()
{
	$db = ECash_Config::getMasterDbConnection();

	$business_rules = new ECash_BusinessRules($db);
	$app_data = Fetch_Application_Info($_REQUEST['react_app_id']);
	$app_data->application_list = Fetch_Application_List($app_data->company_id, $app_data->ssn, 'ssn');

	$app_data->business_rules = $business_rules->Get_Rule_Set_Tree($app_data->rule_set_id);
	$app_data->is_react='yes';
	$app_data->payperiod = $app_data->income_frequency;
	$app_data->react_app_id = $_REQUEST['react_app_id'];


	if(isset($app_data->business_rules['loan_type_model']) && $app_data->business_rules['loan_type_model'] == 'Title')
	{
		$vehicle_data = Vehicle_Data::fetchVehicleData($app_data->application_id);
		$nada = new NADA_API($db);
		//pull Value directly from NADA API.  This value may be different from what is currently stored in the vehicle
		//table.  DEAL WITH IT!
		if(isset($vehicle_data->vehicle_vin) && strlen($vehicle_data->vehicle_vin) > 8)
		{
			$nada_value = $nada->getVehicleByVin($vehicle_data->vehicle_vin)->value;
		}

		if(!isset($nada_value) || $nada_value == null)
		{

			// Get max loan amount based on NADA check
			$nada_value = $nada->getValueFromDescription(
				$vehicle_data->vehicle_make,
				$vehicle_data->vehicle_model,
				$vehicle_data->vehicle_series,
				$vehicle_data->vehicle_style,
				$vehicle_data->vehicle_year);

		}

		$vehicle_data->value = $nada_value;
		$app_data = (object) array_merge((array) $app_data, (array) $vehicle_data);
		$app_data->loan_type_model = 'Title';
	}
	else
	{
		$app_data->loan_type_model = 'Payday';
	}

	$calculator = LoanAmountCalculator::Get_Instance($db, $app_data->display_short);
	$app_data->fund_amount_array   = $calculator->calculateLoanAmountsArray($app_data);
	$app_data->loan_amount_allowed = $calculator->calculateMaxLoanAmount($app_data);


	$req = RequestFormat($_REQUEST);

	/**
	 * This is a hack.  This needs to be put into a business rule, which is why I'm plugging it
	 * into the rule set, but this needs to go out tonight before I'll have chance to create the
	 * new rules. [BrianR]
	 */
	switch($req['loan_type'])
	{
		case 'delaware_payday' :
		case 'california_payday' :
			$app_data->business_rules['react_site_type'] = "soap.agean.react";
			break;
		case 'delaware_title' :
			$app_data->business_rules['react_site_type'] = "soap.agean.title.react";
			break;
		default:
			break;
	}

	/**
	 * If we're lucky, there will be a site type in the business rules,
	 * if not fall back to CLK style site type.
	 */
	if(isset($app_data->business_rules['react_site_type']))
	{
		$site_type = $app_data->business_rules['react_site_type'];
	}
	else
	{
		$site_type = "blackbox.valucash.one.page";
	}

	$state_dd = new State_Selection();
	$state_drop_disp = $state_dd->State_Pulldown("home_state", 0, 0, $req['home_state']);
	$legal_state_drop_disp = $state_dd->State_Pulldown('state_issued_id',0,0,$req['state_issued_id']);
	$vehicle_state_drop_disp = $state_dd->State_Pulldown('vehicle_title_state',0,0,$req['vehicle_title_state']);
	$dob_drop = new Dropdown_Dates();
	$dob_drop->Set_Prefix("date_dob_");
	$dob_drop->Set_Day(isset($req['date_dob_d']) ? $req['date_dob_d'] : $req['date_dob_day']);
	$dob_drop->Set_Month(isset($req['date_dob_m']) ? $req['date_dob_m'] : $req['date_dob_month']);
	$dob_drop->Set_Year(isset($req['date_dob_y']) ? $req['date_dob_y'] : $req['date_dob_year']);
	$dob_drop_disp = $dob_drop->Fetch_Drop_All();
	$is_morn 	= ($req['best_call_time'] == "MORNING") ? "SELECTED" : "";
	$is_noon 	= ($req['best_call_time'] == "AFTERNOON") ? "SELECTED" : "";
	$is_night 	= ($req['best_call_time'] == "EVENING") ? "SELECTED" : "";
	$military_yes 	= ($req['military'] == "TRUE") ? "CHECKED" : "";
	$military_no 	= ($req['military'] == "FALSE" || empty($req['military'])) ? "CHECKED" : "";

	$is_checking 	= ($req['bank_account_type'] == "CHECKING") ? "CHECKED" : "";
	$is_savings 	= ($req['bank_account_type'] == "SAVINGS") ? "CHECKED" : "";
	$amount_select = "<select name='fund_amount' onFocus='javascript:QualifyAmount(this.form);'>\n";

	if (!empty($app_data->fund_amount_array))
	{
		foreach ($app_data->fund_amount_array as $amount)
		{
			$SELECTED = "";
			if(isset($req['fund_amount']))
			{
				if($amount == $req['fund_amount']) $SELECTED = "SELECTED";
			}
			$amount_select .= "<option value='{$amount}' $SELECTED>$".$amount.".00</option>\n";
		}

	}
	$amount_select .= "</select>\n";






	// Set teh paydate model if we have it
	if(isset($req['paydate_model']))
	{
		$pdh = new Paydate_Handler();
		$freq_display = $pdh->Get_Paydate_String($req['paydate_model']);
	}



	print('<html>');
	print('<head>');
	print('<link rel="stylesheet" href="css/style.css">');
	print('<link rel="stylesheet" href="css/transactions.css">');
	print("<script language=javascript>\n");
	print("

            function clear_radio (name_fragment)
            {
                    button_name = 'paydate['+name_fragment+']';
                    radio_buttons = document.getElementsByName(button_name);
                    num_buttons = radio_buttons.length;
                    if (num_buttons > 0)
                    {
                            for (i=0; i<num_buttons; i++)
                            {
                                    radio_buttons[i].checked = false;
                            }
                    }
            }

			function processReqChange()
			{
			    // only if req shows loaded
			    if (req.readyState == 4) {
			    	if (req.status == 200) {
			    	var strResp = (req.responseText);

			    	if(strResp.length>=10)
			           	{
							document.forms[0].fund_amount.innerHTML = req.responseText;

							document.forms[0].Reactivate.disabled = false;
							if(typeof(olddiv) != 'undefined')
							{
								 olddiv.style.visibility = 'hidden';
							}
				        }
			           	else
			           	{
			           		olddiv = getRefToDivNest('error_div');
		           			olddiv.style.visibility = 'visible';
		           			document.getElementById('error_text').innerHTML = '<font color=white>This application will not qualify for a Reactivation.</font>';
		          			document.forms[0].income_monthly_net.focus();
		          			document.forms[0].Reactivate.disabled=true;
			           	}
			        } else {
			        	alert('There was a problem retrieving the XML data:\\n' +  req.statusText);
			        	document.forms[0].income_monthly_net.focus();
			        }
				}
			}


			function processPayDateData()
			{
				if (req.readyState == 4) {
					if (req.status == 200) {

					} else {
			        	alert('There was a problem retrieving the XML data:\\n' +  req.statusText);
					}

				}
			}

			function clearLoanAmount(frm)
			{
				frm.fund_amount.options.length = 0;
				QualifyAmount(frm);
				document.getElementById('income_remind').innerHTML = '';
				document.getElementById('loam_amt_remind').innerHTML = '<font color=red><- Set Desired Loan Amount</font>';
			}

			function validateLoanAmount(frm)
			{
				document.getElementById('loam_amt_remind').innerHTML = '';
			}



			function QualifyAmount(frm)
			{
				req = false;
				document.getElementById('loam_amt_remind').innerHTML = '';
				if(isNaN(parseFloat(frm.income_monthly_net.value).toFixed(2)))
				{
					document.getElementById('income_remind').innerHTML = '<font color=red><- Invalid Monthly Income amount.</font>';
					return;
				}
				frm.income_monthly_net.value = parseFloat(frm.income_monthly_net.value).toFixed(2);
			    // branch for native XMLHttpRequest object
			    if(window.XMLHttpRequest) {
			    	try {
						req = new XMLHttpRequest();
			        } catch(e) {
						req = false;
			        }
			    // branch for IE/Windows ActiveX version
			    } else if(window.ActiveXObject) {
			       	try {
			        	req = new ActiveXObject(\"Msxml2.XMLHTTP\");
			      	} catch(e) {
			        	try {
			          		req = new ActiveXObject(\"Microsoft.XMLHTTP\");
			        	} catch(e) {
			          		req = false;
			        	}
					}
			    }
			    			//req = new XMLHttpRequest();
				if(req) {
					req.onreadystatechange = processReqChange;
					frm.fund_amount.options.length = 0;
					var loanAmt = parseFloat(frm.income_monthly_net.value).toFixed(2);
					var url = \"/soap_react.php?process=qualify&prop={$req['ecashapp']}&loan_type={$req['loan_type']}&income_monthly_net=\"+loanAmt+\"&application_id={$req['react_app_id']}\";
					req.open(\"GET\", url, true);

					req.send(\"\");
					frm.fund_amount.innerHTML='';
				}


			}

			function PayDate_To_String()
			{
				var disp_paydat = '';
				var result = false;
				//alert(document.getElementById('how_often').value);
				switch(document.getElementById('how_often').value)
				{
				case 'WEEKLY':
					if(document.getElementById('paydate[weekly_day]').value == '')
					{
						alert('Please select a day of the week.');
						result = false;
					}
					else
					{
						var disp_paydat = '<b>WEEKLY</b> on weekday <b>' + document.getElementById('paydate[weekly_day]').value + '</b>';
						result = true;
					}
					break;
				case 'BI_WEEKLY':

					if(document.getElementById('biweekly_day').value == '')
					{
						alert('Please select a day of the week.');
						result = false;
					}
					else
					{
						var len = document.getElementsByName('paydate[biweekly_date]').length;

						for(var i=0; i<len; i++)
						{
							if(document.getElementsByName('paydate[biweekly_date]')[i].checked && document.getElementsByName('paydate[biweekly_date]')[i].parentNode.style.display != 'none')
							{
								var disp_paydat = '<b>BI-WEEKLY</b> on weekday <b>' + document.getElementById('biweekly_day').value + '</b>';
								result = true;
								break;
							}

						}
						if(result == false)
						{
							HideAllShowPayDate();
							alert('Please select last paydate.');
						}
					}
					break;
				case 'TWICE_MONTHLY':
					var val = '';
					var len = document.forms[0].elements['paydate[twicemonthly_type]'].length;
					for(var i=0; i<len; i++)
					{
						if(document.forms[0].elements['paydate[twicemonthly_type]'][i].checked)
						{
							var val = document.forms[0].elements['paydate[twicemonthly_type]'][i].value;
							if(val == 'date')
							{

								var select_obj = document.forms[0].elements['paydate[twicemonthly_date1]'];
								for(var s=0; s<select_obj.length; s++)
								{
									if(select_obj[s].selected)
										var val_one = select_obj[s].value;
								}
								var select_obj = document.forms[0].elements['paydate[twicemonthly_date2]'];
								for(var s=0; s<select_obj.length; s++)
								{
									if(select_obj[s].selected)
										var val_two = select_obj[s].value;
								}

								if(val_one == '')
								{
									alert('Please select first pay date.');
									break;
								}

								if(val_two == '')
								{
									alert('Please select last pay date.');
									break;
								}

								if(val_two == 32)
								{
									val_two = 'last day';
								}
								var disp_paydat = '<b>TWICE-MONTHLY</b> on the <b>' + val_one + ' and ' + val_two + '</b>';
								result = true;
								break;
							}
							else if(val == 'week')
							{

								var select_obj = document.forms[0].elements['paydate[twicemonthly_week]'];
								for(var s=0; s<select_obj.length; s++)
								{
									if(select_obj[s].selected)
										var val_one = select_obj[s].value;
								}
								var select_obj = document.forms[0].elements['paydate[twicemonthly_day]'];
								for(var s=0; s<select_obj.length; s++)
								{
									if(select_obj[s].selected)
										var val_two = select_obj[s].value;
								}
								if(val_one == '')
								{
									alert('Please select first pay date.');
									break;
								}

								if(val_two == '')
								{
									alert('Please select last pay date.');
									break;
								}
								if(val_two == 32)
								{
									val_two = 'last day';
								}
								var disp_paydat = '<b>TWICE-MONTHLY</b> on weeks <b>' + val_one + ' and ' + val_two + '</b>';
								result = true;
								break;
							}
							else
							{
								alert('Please select a pay schedule');
								break;
							}
						}
					}
					if(val == '')
					{
						alert('Please select a pay schedule');
					}
					break;
				case 'MONTHLY':
					var val = '';
					var len = document.forms[0].elements['paydate[monthly_type]'].length;
					for(var i=0; i<len; i++)
					{
						if(document.forms[0].elements['paydate[monthly_type]'][i].checked)
						{
							var val = document.forms[0].elements['paydate[monthly_type]'][i].value;
							if(val == 'date')
							{
								var select_obj = document.forms[0].elements['paydate[monthly_date]'];
								for(var s=0; s<select_obj.length; s++)
								{
									if(select_obj[s].selected)
										var val_one = select_obj[s].value;
								}

								if(val_one == '')
								{
									alert('Please select last pay date.');
									break;
								}
								else
								{
									if(val_one == 32)
									{
										val_one = 'last day';
									}
									var disp_paydat = '<b>MONTHLY</b> on the <b>' + val_one + '</b> day of every month';
									result = true;
									break;
								}
							}
							else if(val == 'day')
							{

								var select_obj = document.forms[0].elements['paydate[monthly_week]'];
								for(var s=0; s<select_obj.length; s++)
								{
									if(select_obj[s].selected)
										var val_one = select_obj[s].value;
								}
								var select_obj = document.forms[0].elements['paydate[monthly_day]'];
								for(var s=0; s<select_obj.length; s++)
								{
									if(select_obj[s].selected)
										var val_two = select_obj[s].value;
								}
								if(val_one == '')
								{
									alert('Please select first pay date.');
									break;
								}

								if(val_two == '')
								{
									alert('Please select last pay date.');
									break;
								}
								if(val_two == 32)
								{
									val_two = 'last day';
								}
								var disp_paydat = '<b>MONTHLY</b> on the <b>' + val_one + ' and ' + val_two + '</b> day of every month';
								result = true;
								break;
							}
							else if(val == 'after')
							{

								var select_obj = document.forms[0].elements['paydate[monthly_after_day]'];
								for(var s=0; s<select_obj.length; s++)
								{
									if(select_obj[s].selected)
										var val_one = select_obj[s].value;
								}
								var select_obj = document.forms[0].elements['paydate[monthly_after_date]'];
								for(var s=0; s<select_obj.length; s++)
								{
									if(select_obj[s].selected)
										var val_two = select_obj[s].value;
								}
								if(val_one == '')
								{
									alert('Please select first pay date.');
									break;
								}

								if(val_two == '')
								{
									alert('Please select last pay date.');
									break;
								}
								if(val_two == 32)
								{
									val_two = 'last day';
								}
								var disp_paydat = '<b>MONTHLY</b> on the <b>' + val_one + ' and ' + val_two + '</b> day of every month';
								result = true;
								break;
							}
							else
							{
								alert('Please select a pay schedule');
								break;
							}
						}
					}
					if(val == '')
					{
						alertalert('Please select a pay schedule');
					}
					break;
				}
				if(disp_paydat != '' && result != false)
					document.getElementById('freq_display').innerHTML = disp_paydat;
				return result;
			}

	function getRefToDivNest( divID, oDoc ) {
		if( !oDoc ) { oDoc = document; }
		if( document.layers ) {
			if( oDoc.layers[divID] ) { return oDoc.layers[divID]; } else {
				for( var x = 0, y; !y && x < oDoc.layers.length; x++ ) {
					y = getRefToDivNest(divID,oDoc.layers[x].document); }
				return y; } }
		if( document.getElementById ) { return document.getElementById(divID); }
		if( document.all ) { return document.all[divID]; }
		return document[divID];
	}


	function HideAllShowPayDate()
	{
		//olddiv = getRefToDivNest('personal_div');
		//olddiv.style.visibility = 'hidden';
		//olddiv = getRefToDivNest('employment_div');
		//olddiv.style.visibility = 'hidden';
		olddiv = getRefToDivNest('submit_div');
		olddiv.style.visibility = 'hidden';
		olddiv = getRefToDivNest('paydate_div');
		olddiv.style.visibility = 'visible';
	}

	function ShowAllHidePayDate()
	{
		//olddiv = getRefToDivNest('personal_div');
		//olddiv.style.visibility = 'visible';
		//olddiv = getRefToDivNest('employment_div');
		//olddiv.style.visibility = 'visible';
		olddiv = getRefToDivNest('submit_div');
		olddiv.style.visibility = 'visible';
		olddiv = getRefToDivNest('paydate_div');
		olddiv.style.visibility = 'hidden';
	}

	");
	print("</script>");
	print('</head>');
	print('<body bgcolor="#E3DFF2" onLoad="HideAllShowPayDate();if(PayDate_To_String()){ShowAllHidePayDate();}"');

	$qs = array();
	if (isset($req['paydate']))
	{

		// We need to fix the dats for some werid reasons
		if(!isset($req['Reactivate'])){
			// Check the data format to work with the widget
			if( isset($_REQUEST['paydate']['biweekly_date']) )
			{
				$temp  = explode( "/", $req['paydate']['biweekly_date'] );
				$stamp = mktime( 0, 0, 0, $temp[0], $temp[1], $temp[2] );
				// Forward the date in the database to either this week or last week
				while( $stamp < strtotime("-2 weeks") )
				{
					$stamp = strtotime( "+2 weeks", $stamp );
				}

				$req['paydate']['biweekly_date'] = date( "m/d/Y", $stamp );

			}
			// Some should be upper case, some lower...
			isset($req['paydate']['biweekly_day'])      && $req['paydate']['biweekly_day']      = strtoupper($req['paydate']['biweekly_day']);
			isset($req['paydate']['twicemonthly_type']) && $req['paydate']['twicemonthly_type'] = strtolower($req['paydate']['twicemonthly_type']);
			isset($req['paydate']['monthly_type'])      && $req['paydate']['monthly_type']      = strtolower($req['paydate']['monthly_type']);

		}

		foreach( $req['paydate'] as $k => $v )
		{
			$qs[] = urlencode("paydate[" . $k . "]") . "=" . urlencode($v);
		}
	}

	print('<form>');
	$is_morn 	= ($req['best_call_time'] == "MORNING") ? "SELECTED" : "";
	$is_noon 	= ($req['best_call_time'] == "AFTERNOON") ? "SELECTED" : "";
	$is_night 	= ($req['best_call_time'] == "EVENING") ? "SELECTED" : "";
	print("
	<div id='personal_div' style='position: absolute; left: 0px; top: 0px;'>
		<table cellpadding='0' cellspacing='0'>
	<tr>
		<td class='border' align='left'' valign='top'>
	<table class='customer_service' width=340 cellpadding='0' cellspacing='0'>
	<tr class='height'><th colspan=2 class='customer_service'>Personal Details</th></tr>
	<tr class='height'><td class='align_left_alt_bold'>Name:</td><td class='align_left_alt'><input type='text' name='name_first' value='{$req['name_first']}' size=15><input type='text' name='name_middle' value='{$req['name_middle']}' size=2><input type='text' name='name_last' value='{$req['name_last']}' size=15></td></tr>
	<tr class='height'><td class='align_left_bold'>SSN:</td><td class='align_left'><input type='text' name='ssn_part_1' value='{$req['ssn_part_1']}' SIZE=3><input type='text' name='ssn_part_2' value='{$req['ssn_part_2']}' SIZE=2><input type='text' name='ssn_part_3' value='{$req['ssn_part_3']}' SIZE=3><td></tr>
	<tr class='height'><td class='align_left_alt_bold'>Legal ID:</td><td class='align_left_alt'><input type='text' name='state_id_number' value='{$req['state_id_number']}'>{$legal_state_drop_disp}</td></tr>
	<tr class='height'><td class='align_left_bold'>Date Of Birth:</td><td class='align_left'>{$dob_drop_disp}</td></tr>
	<tr class='height'><td class='align_left_alt_bold'>Address/Unit:</td><td class='align_left_alt'><input type='text' name='home_street' value='{$req['home_street']}'><input type='text' name='home_unit' value='{$req['home_unit']}' size=5></td></tr>
	<tr class='height'><td class='align_left_bold'>City/State:</td><td class='align_left'><input type='text' name='home_city' value='{$req['home_city']}'>{$state_drop_disp}</td></tr>
	<tr class='height'><td class='align_left_alt_bold'>Zip:</td><td class='align_left_alt'><input type='text' name='home_zip' value='{$req['home_zip']}'></td></tr>

	<tr class='height'><td class='align_left_bold'>Residing Since:</td><td class='align_left'><input type='text' name='residence_start_date' value='{$req['residence_start_date']}'></td></tr>
	<tr class='height'><td class='align_left_alt_bold'>Home Phone:</td><td class='align_left_alt'><input type='text' name='phone_home' value='{$req['phone_home']}'></td></tr>
	<tr class='height'><td class='align_left_bold'>Cell Phone:</td><td class='align_left'><input type='text' name='phone_cell' value='{$req['phone_cell']}'></td></tr>
	<tr class='height'><td class='align_left_alt_bold'>Work Phone/Ext:</td><td class='align_left_alt'><input type='text' name='phone_work' value='{$req['phone_work']}'><input type='text' name='phone_work_ext' value='{$req['phone_work_ext']}' size=5></td></tr>
	<tr class='height'><td class='align_left_bold'>Best Time to Call:</td>
	<td class='align_left'>
			<select name='best_call_time' id='best_call_time'>\n
			<option value='NO PREFERENCE'>No preference</option>\n
			<option value='MORNING' {$is_morn}>Morning (9:00 to 12:00)</option>\n
			<option value='AFTERNOON' {$is_noon}>Afternoon (12:00 to 5:00)</option>\n
			<option value='EVENING' {$is_night}>Evening (5:00 - 9:00)</option>\n
			</select>
	</td></tr>
	<tr class='height'><td class='align_left_alt_bold'>Email:</td><td class='align_left_alt'><input type='text' name='email_primary' value='{$req['email_primary']}'></td></tr>

	<tr class='height'><th colspan=2 class='customer_service'>References</th></tr>
	<tr class='height'><td class='align_left_alt_bold'>#1 Name:</td><td class='align_left_alt'><input type='text' name='ref_01_name_full' value='{$req['ref_01_name_full']}'/></td></tr>
	<tr class='height'><td class='align_left_bold'>#1 Phone:</td><td class='align_left'><input type='text' name='ref_01_phone_home' value='{$req['ref_01_phone_home']}'/><td></tr>
	<tr class='height'><td class='align_left_alt_bold'>#1 Relation:</td><td class='align_left_alt'><input type='text' name='ref_01_relationship' value='{$req['ref_01_relationship']}'/></td></tr>
	<tr class='height'><td class='align_left_bold'>#2 Name:</td><td class='align_left'><input type='text' name='ref_02_name_full' value='{$req['ref_02_name_full']}'/></td></tr>
	<tr class='height'><td class='align_left_alt_bold'>#2 Phone:</td><td class='align_left_alt'><input type='text' name='ref_02_phone_home' value='{$req['ref_02_phone_home']}'/><td></tr>
	<tr class='height'><td class='align_left_bold'>#2 Relation:</td><td class='align_left'><input type='text' name='ref_02_relationship' value='{$req['ref_02_relationship']}'/></td></tr>
	</table>
	</td></tr>


	</table>
	</div>
	");

	$emp_select = ($req['income_type'] == "EMPLOYMENT") ? "CHECKED" : "";
	$ben_select = ($req['income_type'] == "BENEFITS") ? "CHECKED" : "";
	$is_dd		= (in_array($req['income_direct_deposit'],array("yes","1","TRUE"))) ? "CHECKED" : "";
	$is_not_dd	= (in_array($req['income_direct_deposit'],array("no","0","FALSE"))) ? "CHECKED" : "";
	$mil_select = ($req['income_type'] == "MILITARY") ? "CHECKED" : "";


	if ($req['loan_type']=='delaware_title')
	{
		print("
				<div id='vehicle_data'  style='position: absolute; left: 350px; top: 0px;'>
				<tr><td>
				<table cellpadding=0 cellspacing=0 width='100%'>
					<tr>
						<td class='border' align='left' valign='top'>
						<table cellpadding=0 cellspacing=0 width='100%'>
							<tr class='height'>
								<th class='customer_service'>
								<div style='float: left;'>Vehicle Data</div>
									<div class='vehicle_value'>Value: <span id='vehicle_value'>\${$vehicle_data->value}</span>
									<input type='hidden' name='vehicle_value' value='{$vehicle_data->value}'> </div>
								</th>
							</tr>

							<tr class='height'>
								<td class='align_left_alt'>
									<div class='vehicle'>
										<span class='title' id='license_plate_span'>
											License Plate:
										</span>
										<br>
										<span class='data' >
											{$req['vehicle_license_plate']}
											<input type='hidden' name='vehicle_license_plate' value='{$req['vehicle_license_plate']}'>
										</span>
									</div>
									<div class='vehicle'>
										<span class='title' id='title_state_span'>
											Title State:
										</span>
										<br>
										<span class='data'>
											{$req['vehicle_title_state']}
											<input type='hidden' name='vehicle_title_state' value='{$req['vehicle_title_state']}'>
										</span>
									</div>

									<div class='vehicle'>
										<span class='title' id='vehicle_vin_span'>
											VIN:
										</span>
										<br>
										<span class='data' >
											{$req['vehicle_vin']}
											<input type='hidden' name='vehicle_vin' value='{$req['vehicle_vin']}'>
										</span>
									</div>
								</td>
							</tr>

							<tr class='height'>
								<td class='align_left'>
									<div class='vehicle'>
										<span class='title' id='vehicle_year_span'>
											Year:
										</span>
										<br>
										<span class='data'>
											{$req['vehicle_year']}
											<input type='hidden' name='vehicle_year' value='{$req['vehicle_year']}'>
										</span>
									</div>

									<div class='vehicle'>
										<span class='title' id='vehicle_make_span'>
											Make:
										</span>
										<br>
										<span class='data'>
											{$req['vehicle_make']}
											<input type='hidden' name='vehicle_make' value='{$req['vehicle_make']}'>
										</span>
									</div>
								</td>
							</tr>
							<tr class='height'>
								<td class='align_left_alt'>
									<div class='vehicle' >
										<span class='title' id='vehicle_series_span'>
											Series:
										</span>
										<br>
										<span class='data'>
										{$req['vehicle_series']}
										<input type='hidden' name='vehicle_series' value='{$req['vehicle_series']}'>
										<input type='hidden' name='vehicle_model' value='{$req['vehicle_model']}'>
										</span>
									</div>

									<div class='vehicle'>
										<span class='title' id='vehicle_body_span'>
											Style:
										</span>
										<br>
										<span class='data' >
										{$req['vehicle_body']}
										<input type='hidden' name='vehicle_body' value='{$req['vehicle_body']}'>
										</span>
									</div>
								</td>
							</tr>

							<tr class='height'>
								<td class='align_left'>
									<div class='vehicle'>
										<span class='title' id='mileage_span'>
											Mileage:
										</span>

										<br>
										<span class='data' >
										{$req['vehicle_mileage']}
										<input type='hidden' name='vehicle_mileage' value='{$req['vehicle_mileage']}'>
										</span>
									</div>
									<div class='vehicle'>
										<span class='title' id='color_span'>
											Color:
										</span>
										<br>
										<span class='data'>
										{$req['vehicle_color']}
										<input type='hidden' name='vehicle_color' value='{$req['vehicle_color']}'>
										</span>
									</div>
								</td>
							</tr>
				");
	}

	print("
	<div id='employment_div' style='position: absolute; left: 350px;'>
	<table cellpadding='0' cellspacing='0' width='100%'>
	<tr>
		<td class='border' align='left'' valign='top'>
	<table class='customer_service' cellpadding='0' cellspacing='0' width=100%>
	<tr class='height'><th colspan=2 class='customer_service'>Employment Info</th></tr>

	<tr class='height'><td class='align_left_alt_bold'>Employer Name:</td><td class='align_left_alt'><input type='text' name='employer_name' value='{$req['employer_name']}'></td></tr>
	<tr class='height'><td class='align_left_bold'>Job Title:</td><td class='align_left'><input type='text' name='work_title' value='{$req['job_title']}'></td></tr>
	<tr class='height'><td class='align_left_alt_bold'>Military Customer:</td><td class='align_left_alt'>
		<input type=radio name=military value=TRUE {$military_yes}> Yes
		<input type=radio name=military value=FALSE {$military_no}> No
	</td></tr>
	<tr class='height'><td class='align_left_bold'>Income Source:</td><td class='align_left'>
		<input type='radio' name='income_type' value='EMPLOYMENT' $emp_select />&nbsp;Job Income
		<input type='radio' name='income_type' value='BENEFITS' $ben_select />&nbsp;Benefits
		<input type='radio' name='income_type' value='MILITARY' $mil_select />&nbsp;Military
	</td></tr>
	<tr class='height'><td class='align_left_alt_bold'>Direct Deposit:</td><td class='align_left_alt'>
		<input type=radio name=income_direct_deposit value=TRUE {$is_dd}> Yes
		<input type=radio name=income_direct_deposit value=FALSE {$is_not_dd}> No
	</td></tr>

	<tr class='height'><td class='align_left_bold'>Monthly Income:</td><td class='align_left'>
	<table cellpadding='0' cellspacing='0'><tr>
	<td class='align_left'><input type='text' name='income_monthly_net' value='{$req['income_monthly_net']}' onChange='clearLoanAmount(this.form);' SIZE=10></td>
	<td class='align_left'><div id=income_remind></div></td>
	</td></tr></table>
	</td></tr>
	<tr class='height'><td class='align_left_alt_bold'>Employed Since:</td><td class='align_left_alt'><input type='text' name='date_of_hire' value='{$req['date_of_hire']}'></td></tr>
	<tr class='height'><td class='align_left_bold'>Pay Freq:</td><td class='align_left'><div id=freq_display>{$freq_display}</div></td></td></tr>
	<tr class='height'><td class='align_left_alt_bold'>PayDate Wizard:</td><td class='align_left_alt'>[<a href='#' onClick='HideAllShowPayDate();'>Change PayDate Frequency</a>]</td></td></tr>
	<tr class='height'><th colspan=2 class='customer_service'>Loan Info</th></tr>
	<tr class='height'><td class='align_left_alt_bold'>ABA #:</td><td class='align_left_alt'><input type='text' name='bank_aba' value='{$req['bank_aba']}'></td></tr>
	<tr class='height'><td class='align_left_bold'>Bank Name:</td><td class='align_left'><input type='text' name='bank_name' value='{$req['bank_name']}'></td></tr>
	<tr class='height'><td class='align_left_alt_bold'>Account #:</td><td class='align_left_alt'><input type='text' name='bank_account' value='{$req['bank_account']}'></td></tr>
	<tr class='height'><td class='align_left_bold'>Account Type:</td><td class='align_left'>
		<input type=radio name=bank_account_type value='CHECKING' {$is_checking}> Checking
		<input type=radio name=bank_account_type value='SAVINGS' {$is_savings}> Savings
	</td></tr>
	<tr class='height'><td class='align_left_alt_bold'>Banking Since:</td><td class='align_left_alt'><input type='text' name='banking_start_date' value='{$req['banking_start_date']}'></td></tr>
	<tr class='height'><td class='align_left_bold'>Desired Loan Amount:</td><td class='align_left'>
		<table cellpadding='0' cellspacing='0'><tr>
		<td class='align_left'>{$amount_select}</td>
		<td class='align_left'><div id=loam_amt_remind></div></td></tr></table>
	</td></tr>

	</table>
	</td></tr>
");




	print("

			</table>
			</td>
		</tr>
	</table>


	</td></tr>
	<tr><td>

	<div id='submit_div' >
	<input type='hidden' name='legal_notice_1' value='TRUE'>\n
	<input type='hidden' name='income_stream' value='TRUE'>\n
	<input type='hidden' name='employer_length' value='TRUE'>\n
	<input type='hidden' name='citizen' value='TRUE'>\n
	<input type='hidden' name='client_ip_address' value='{$_SERVER["SERVER_ADDR"]}'>\n
	<input type='hidden' name='client_url_root' value='ecashapp.com'>\n
	<input type='hidden' name='offers' value='FALSE'>\n
	<input type='hidden' name='ecashapp' value='{$req['ecashapp']}'>\n
	<input type='hidden' name='no_checks' value='{$req['no_checks']}'>\n
	<input type='hidden' name='agent_id' value='{$req['agent_id']}'>\n
	<input type='hidden' name='ecashdn' value='{$req['ecashdn']}'>\n
	<input type='hidden' name='promo_id' value='{$req['promo_id']}'>\n
	<input type='hidden' name='site_type' value='$site_type'>\n
	<input type='hidden' name='page' value='app_allinone'>\n
	<input type='hidden' name='promo_sub_code' value='{$req['promo_sub_code']}'>\n
	<input type='hidden' name='process' value='post_react'>\n
	<input type='hidden' name='react_app_id' value='{$req['react_app_id']}'>\n
	<input type='hidden' name='react_type' value='{$req['react_type']}'>\n
	<input type='hidden' name='company_id' value='{$req['company_id']}'>\n
	<input type='hidden' name='loan_type' value='{$req["loan_type"]}'>\n
	{$loan_data}
	<input type='submit' name='Reactivate' value='Reactivate'>\n
	</div>


	</td></tr>
	</table>
	</div>



	");




	// Display Soap Error Messages for Form Vlaidation
	$error_vis = isset($_SESSION["RESPONSE_ERRORS"]) ? "visible" : "hidden";

	print("<div id='error_div' onClick=\"this.style.visibility='hidden';\" style='position: absolute; right: 2px; bottom: 2px;z-index:0;height:125px;visibility:{$error_vis};'>
			<table cellpadding='0' cellspacing='0' width=100% height=100%><tr>
			<td class='border' align='left'' valign='top' height=100%>
				<div style='height:125px;overflow:auto;'>
				<table class='customer_service' width=100% height=100% cellpadding='0' cellspacing='0'>
				<tr><td bgcolor='red' valign=top>");
	if(isset($_SESSION["RESPONSE_ERRORS"]))
	{
		if(is_array($_SESSION["RESPONSE_ERRORS"]))
		{
			for($i=0; $i<count($_SESSION["RESPONSE_ERRORS"]); $i++)
			{
				print("<font color=white>{$_SESSION["RESPONSE_ERRORS"][$i]}</font><br>");
			}
		}
		else
		{
			print("<font color=white>{$_SESSION["RESPONSE_ERRORS"]}</font><br>");
		}
	}
	print("<div id=error_text></div></td></tr></table></div></td></tr></table><button onClick=\"this.parentNode.style.visibility='hidden'; return false;\">Close</button></div>");



	// Pay date widget
	print("<div id='paydate_div' style='position: absolute; left: 0px; top: 0px;z-index:0;visibility:hidden;'>
	<table cellpadding='0' cellspacing='0'>
	<tr>
		<td class='border' align='left'' valign='top'>
	<table class='customer_service'>
	<tr><th class='customer_service'>PayDate Information:</th><th>[<a href='#' onClick='javascript:if(PayDate_To_String()){ShowAllHidePayDate();}'>return</a>]</th></tr>
	<tr><td class='align_left_bold' id='paydate_parent'>
	");

	$url_paydate_widget = eCash_Config::getInstance()->URL_PAYDATE_WIDGET;
	readfile($url_paydate_widget."?" . join("&", $qs));
	print("</td></tr></table></td></tr></table></div>");

	print('</form>');
	print('</body></html>');

}


function SOAP_Respose()
{

	print('<html><head>');
	print('<link rel="stylesheet" href="css/style.css">');
	print('<link rel="stylesheet" href="css/transactions.css">');
	print('</head><body bgcolor="#E3DFF2"><center>');
	print("<table cellpadding='0' cellspacing='0'>
					<tr>
					<td class='border' align='left'' valign='top'>
						<table class='customer_service' width=340>
						<tr><td class='customer_service'>{$_SESSION["SOAP_RESPONSE"]}</td></tr>
						</table>
					</td>
					</tr>
					</table>");
	print("<center></body></html>");
}
/*Process */


// GF #13190: We need the server variable here, since this file is accessed
// directly by the browser, I'm pretty sure I need this. [benb]
$session_id =  isset($_REQUEST['ssid']) ? $_REQUEST['ssid'] : null;
$request = (object) $_REQUEST;
$server = Server_Factory::get_server_class(null,$session_id);

// Track Agent Action [Future 3.7 Release
if(isset($_REQUEST["track_agent_action"]))
{
	// Track React Button agent action
	$agent = ECash::getAgent();
	$agent->getTracking()->add("reactivate", $_REQUEST["react_app_id"]);
}

$server->Process_Data($request);

switch($_REQUEST["process"])
{
	case "qualify":
		$amount = QualifyAmount($_REQUEST["income_monthly_net"],$_REQUEST["application_id"]);
		print($amount);
		break;
	case "start":
		ReactForm();
		break;
		//case "start":
	case "post_react":
		if(Process_React($server) == TRUE)
		{
			SOAP_Respose();
		}
		else
		{
			ReactForm();
		}
		break;
}
?>



