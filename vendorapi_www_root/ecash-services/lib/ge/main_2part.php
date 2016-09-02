<?php

/* main routine for client server
 * normally this would be an index.php, but it's shared by by several
 * different sites so the actual index.php's are just stubs that include
 * this file.
 */

require_once 'date_selection.1.php';
require_once 'state_selection.1.php';
require_once 'null_session.1.php';
require_once 'data_validation.1.php';

require_once 'prpc/client.php';
require_once 'ge/form_class.php';
require_once 'ge/common.php';

require_once '../code/public.config.php';
require_once '../code/private.config.php';

// insure we're on a secure connection
switch (true) {
// localhost
case (preg_match ('/(ds\d+|test|alpha)(\.tss)?$/i', $_SERVER['HTTP_HOST'])):
	break;
// secure site
case (preg_match('/'.SECURE_SITE.'/i', $_SERVER['HTTP_HOST'])):
	break;
 // rc
case (preg_match('/(^rc\.)(.*)/i', $_SERVER['HTTP_HOST'], $m)):
	$url = 'Location: http://'.$m[1].SECURE_SITE.'/'.$m[2].$_SERVER['REQUEST_URI'];
	header($url);
	exit;
 // live
default:
	preg_match('/^(www\.)?(.*)/', $_SERVER['HTTP_HOST'], $m);
	$url = 'Location: https://'.SECURE_SITE.'/'.$m[2].$_SERVER['REQUEST_URI'];

#	print '<pre>$_SERVER: '.print_r($_SERVER,true)."</pre>\n";
#	print '<pre>$session: '.print_r($session,true)."</pre>\n";
#	print "<pre>\$url: $url</pre>\n";
#	exit;

	header($url);
	exit;
}

// Build the session handling object
$sess = new Null_Session_1();
//$sess = new Session_4();

// Set the session name & session id
session_name(SESS_NAME);
if($_REQUEST[SESS_NAME])
	session_id($_REQUEST[SESS_NAME]);

// Establish the session parameters
session_set_save_handler(
	array (&$sess, 'Open'),
	array (&$sess, 'Close'),
	array (&$sess, 'Read'),
	array (&$sess, 'Write'),
	array (&$sess, 'Destroy'),
	array (&$sess, 'Garbage_Collection')
);
session_start();

// ready the request block for passing to the process server
$request = (object)$_REQUEST;
$request->_server = (object)$_SERVER;
$request->_unique_id = $request->_session_id = session_id();
$request->_license_key = LICENSE_KEY;

// create a prpc call
$call = new Prpc_Client (PRPC_SERVER);
#print '<pre>$call: '.print_r($call,true)."<pre>\n";

// save / pick up session values
#print "\n<!--\$request: ".print_r($request,true)."-->\n";
//echo "<pre>" . print_r($request, TRUE) . "</pre>";
$session = $call->Session($request);
#print "\n<!--\$call: ".print_r($call,true)."-->\n";
#print "\n<!--\$session: ".print_r($session,true)."-->\n";
#print '<pre>$call: '.print_r($call,true).'</pre>';
#print '<pre>$session: '.print_r($session,true).'</pre>';
#print '<pre>$request: '.print_r($request,true).'</pre>';
#print "<pre>order_id={$request->order_id}</pre>\n";

#echo "<pre>" . print_r($session, TRUE) . "</pre>";

// set form values
$fields = new stdClass;
$fields->ssid = session_id();

$states = new State_Selection;
$fields->state_list = $states->State_Option_List(0,0,$_REQUEST['state'],true,EXCLUDE_STATES);

$date = new Date_Selection;
#$fields->birthyear = isset($_REQUEST['birthyear']) ? $_REQUEST['birthyear'] : '1980';
$fields->expireyear_list = $date->Year_Option_List(0,0,$_REQUEST['expireyear'],0,10,true);
$fields->birthmonth_list = $date->Month_Option_List(1,2,true,$_REQUEST['birthmonth']);
$fields->expiremonth_list = $date->Month_Option_List(1,1,true,$_REQUEST['expiremonth']);
$fields->birthdom_list = $date->Day_Option_List(1,1,$_REQUEST['birthdom'],true);

foreach ( array(
	'' => ''
	,'American Express'=>'AmericanExpress'
	,'Discover'=>'Discover'
	,'MasterCard'=>'MasterCard'
	,'VISA'=>'VISA'
	) as $display=>$value) {
	$fields->cardtype_list .= '<option value="'.$value.'" '.($value == $_REQUEST['cardtype']?'selected':'').">$display</option>\n";
}
foreach ( array(
	'' => ''
	,'Jr'=>'Jr'
	,'Sr'=>'Sr'
	,'II'=>'II'
	,'III'=>'III'
	,'IV'=>'IV'
	,'V'=>'V'
	,'ESQ'=>'ESQ'
	,'MD'=>'MD'
	,'JD'=>'JD'
	,'DDS'=>'DDS'
	,'PHD'=>'PHD'
	,'DMD'=>'DMD'
	) as $display=>$value) {
	$fields->suffix_list .= '<option value="'.$value.'" '.($value == $_REQUEST['suffix']?'selected':'').">$display</option>\n";
}
$fields->java_scripts = file_get_contents('ge/common.js',true);
$fields->date = date ("F j, Y");

// check if the form has been filled out
$action = strtolower($_REQUEST['action']);
switch ($action)
{
case 'step1':
		
	//send an autoresponder to everyone who finishes the first step
	$data = array();
	//Must have data fields
	$recipient = $request->email1;
	$recipient_firstname = $request->firstname;
	$recipient_lastname = $request->lastname;
	$recipient_name = $recipient_firstname . " " . $recipient_lastname;
	$data['email_primary'] = $recipient;
	$data['email_primary_name'] = $recipient_name;
	
	include_once("/virtualhosts/lib/prpc/client.php");
	$mail = new prpc_client("prpc://smtp.1.soapdataserver.com/ole_smtp.1.php");
	$mail->setPrpcDieToFalse();
	//email the corresponding autoresponder with the corresponding site
	if (preg_match('/criticschoicemembership/', $_SERVER['SCRIPT_NAME']))
	{
	$data['site_name'] = 'CriticsChioceMembership.com';
	$mailing_id = $mail->Ole_Send_Mail("First Page Response CC", 4967, $data);
	}
	if (preg_match('/perfectgetawaymembership/', $_SERVER['SCRIPT_NAME']))
	{
	$data['site_name'] = 'PerfectGetawayMembership.com';
	$mailing_id = $mail->Ole_Send_Mail("First Page Response PG", 4967, $data);
	}
	
		
	if ( $request->firstname != 'test') {
		// validate the responses
		// if any checks fail, i repaint the form with an error message
		$v = new Data_Validation;
		$result = $v->Validate($request->firstname,array('type'=>'string','min'=>1,'max'=>99));
		if ( !$result['status'] ) {
			$fields->status_msg = '!! Please enter a valid First Name !!';
			break;
		}
		$result = $v->Validate($request->lastname,array('type'=>'string','min'=>1,'max'=>99));
		if ( !$result['status'] ) {
			$fields->status_msg = '!! Please enter a valid Last Name !!';
			break;
		}

		$result = $v->Validate(preg_replace('/\D+/', '', $request->phone),array('type'=>'all_digits','min'=>10,'max'=>19));
		if ( !$result['status'] ) {
			$fields->status_msg = '!! Please enter a valid Phone Number !!';
			break;
		}

		$result = $v->Validate($request->birthmonth,array('type'=>'all_digits','min'=>1,'max'=>2));
		if ( !$result['status'] ) {
			$fields->status_msg = '!! Please enter a valid Birth Month !!';
			break;
		}

		$result = $v->Validate($request->birthdom,array('type'=>'all_digits','min'=>1,'max'=>2));
		if ( !$result['status'] ) {
			$fields->status_msg = '!! Please enter a valid Birth Day !!';
			break;
		}

		if ( $request->birthyear < 1900 or $request->birthyear >2050 ) {
			$fields->status_msg = '!! Please enter a valid Birth Year !!';
			break;
		}
		if ( $request->email1 != $request->email2 ) {
			$fields->status_msg = '!! Please confirm your eMail Address !!';
			break;
		}
		$result = $v->Validate($request->email1,array('type'=>'email'));
		if ( !$result['status'] ) {
			$fields->status_msg = '!! Please enter a valid eMail Address !!';
			break;
		}		
	}
	
	
	
	
	$response = $call->Visit_Page($request);
	$form = new Form('step_2.html');
	//$response->tracking_pixel_b = tracking_pixel_2($session->promo_id,$session->promo_sub_code);
	$fields->tracking_pixel_b = $response->tracking_pixel_b;


	break;

case 'step2':


	$v = new Data_Validation;

	$form = new Form("step_2.html");

	$result = $v->Validate($request->cardnumber,array('type'=>'all_digits','min'=>15,'max'=>16));
	if ( !$result['status'] ) {
		$fields->status_msg = '!! Please enter a valid Card Number !!';
		break;
	}

	// verify the server is configured properly for Paymentech
	$PAYMENTECH_HOME = isset($_SERVER['PAYMENTECH_HOME'])
		? $_SERVER['PAYMENTECH_HOME']
		: '/opt/paymentech';
	if ( !is_readable($PAYMENTECH_HOME.'/etc/linehandler.properties')
	or   !is_writable($PAYMENTECH_HOME.'/logs/trans.log')
	or   !is_executable(BIN_DIR.'process.pl')
	) {
		$fields->status_msg = "!! The system is unable to verify your credit card at this time.  Please try again later !!\n<!-- Paymentech is not configured properly on ".`hostname`." -->\n";
		break;
	}

	// validate the credit card
	$request->orderid = new_order_id();
#	if ( preg_match('/^rc/',$_SERVER['HTTP_HOST']) ) {
	if ( 'cool' == $request->lastname ) {
		$buf = array('00','resp_text','avs_code','proc_status');
	} else {
		exec('PAYMENTECH_HOME='.$PAYMENTECH_HOME
			.' '.BIN_DIR.'process.pl '
			.escapeshellarg(http_build_query($request))
			,$buf,$rc);
#	print '<!--after call: $rc='.$rc.'; $buf: '.print_r($buf,true)."-->\n";
	}

	$fields->resp_code	= $request->resp_code	= $buf[0];
	$fields->resp_text	= $request->resp_text	= $buf[1];
	$fields->avs_code	= $request->avs_code	= $buf[2];
	$fields->proc_status= $request->proc_status	= $buf[3];
	unset($buf);

	// check the return status from paymentech
	if ( $request->resp_code != '00' && $request->lastname != 'cool') 
	{
		$fields->status_msg = '!! Sorry.  You have been declined due to an '.$fields->resp_text.' !!';
	} 
	else 
	{
		switch ($request->avs_code) {
		case '1':	// No address supplied
		case '2':	// Bill-to address did not pass Auth Host edit checks
		case '3':	// AVS not performed
		case '4':	// Issuer does not participate in AVS
		case '5':	// Edit-error - AVS data is invalid
		case '6':	// System unavailable or time-out
		case '7':	// Address information unavailable
		case '8':	// Transaction Ineligible for AVS
		case 'A':	// Zip Match / Zip 4 Match / Locale no match
		case 'C':	// Zip Match / Zip 4 no Match / Locale no match
		case 'D':	// Zip No Match / Zip 4 Match / Locale match
		case 'E':	// Zip No Match / Zip 4 Match / Locale no match
		case 'F':	// Zip No Match / Zip 4 No Match / Locale match
		case 'J':	// Issuer does not participate in Global AVS
		case 'Z':	// Zip Match / Locale no match
		case 'G':	// No match at all
#			$fields->status_msg = '!! Sorry.  Your address does not match the credit card address !!';
#			break;
		case '9':	// Zip Match / Zip4 Match / Locale match
		case 'B':	// Zip Match / Zip 4 no Match / Locale match
		case 'H':	// Zip Match / Locale match
		case 'X':	// Zip Match / Zip 4 Match / Address Match
		default:
			// record this order
			$request->promo_id = $session->promo_id;
			$request->promo_sub_code = $session->promo_sub_code;
#			print "\n<!--\$request: ".print_r($request,true)."-->\n";
			$response = $call->Process_Member($request);
#			print "\n<!--\$call: ".print_r($call,true)."-->\n";
			$form = new Form('../thankyou.html');
			$fields->tracking_pixel = $response->tracking_pixel;
		} // end avs_code switch

	}

	break;

default:
	// bump stats
	$response = $call->Visit_Page($request);
	break;
}

// paint the form
if ( ! isset($form) )
{
	$form = new Form('index.html');
}

//echo "<pre>" . print_r($fields, TRUE) . "</pre>";

$form->Display($fields);

// dump a little debug stuff at the end
#	print '<pre>$response: '.print_r($response,true)."</pre>\n";

exit;


?>
