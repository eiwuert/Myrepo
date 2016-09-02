#! /usr/bin/perl -wT

#-----------------------------------------------------------
# this script accepts credit card info and tries to verify
# it's validity by authorizing a $1.00 charge.  if successful,
# it then voids the transaction.
#
# input:
#	data is passed as a urlencoded query so this script
#	doesn't have to know if it's called via cgi or via
#	a command line interface.
# output:
#	output is simply lines of text separated by newlines.
#	the first line has a specfic structure and all subsequent
#	lines are simply considered as debug info.  the first
#	line is a comma separated list of fields:
#		response code (RespCode), 00 = approved
#		response text (RespCode Definition),
#		avs response code (AVSRespCd)
#-----------------------------------------------------------

use strict;
use CGI;

use Paymentech::Utils::Configuration ':alias';
use Paymentech::Utils::DebugLogging 'debugLog';
use Paymentech::eCommerce::RequestTypes ':constants';
use Paymentech::eCommerce::TransactionProcessor ':alias';
use Paymentech::eCommerce::RequestBuilder ':alias';
use Paymentech::XTTF;
#use Paymentech::eCommerce::RequestBuilder;
#use Paymentech::eCommerce::RequestTypes qw(CC_AUTHORIZE_REQUEST VOID_REQUEST);
#use Paymentech::eCommerce::Request;
use Data::Dumper;

#------------------------------------------------------------
# dump the input request
#------------------------------------------------------------

my ($q,$name,$value,$buf,$sep,$gatewayResponse);

$q = new CGI;
#print Dumper($q);

foreach $name ( $q->param ) {
	$buf .= "[$name] =>";
	$sep = ' ';
	foreach $value ( $q->param($name) ) {
		$buf .= $sep.$value;
		$sep = ', '
	}
	$buf .= "\n";
}

#------------------------------------------------------------
# first step, issue an authorize request
#------------------------------------------------------------
# create an authorize object
my $a = requestBuilder()->make(CC_AUTHORIZE_REQUEST);
defined ($a) or die 'unable to make an authorize request';

# populate with merchant specific stuff
$a->MerchantID('311225');	# assigned by paymentech to ge
$a->BIN('000001');			# paymentech platform, salem, nh
#$a->BIN('000002');			# paymentech platform, tampa, fl
$a->CurrencyCode('840');	# currency = US Dollars
$a->TzCode('708');			# tss timezone, pacific timezone
$a->Amount('100');			# tss assigned dollar value, always $1.00
$a->OrderID($q->param('orderid'));	# tss assigned order id; 16 char max, unique in first 8

# ... cardholder specific stuff
$a->AccountNum($q->param('cardnumber'));
$a->Exp($q->param('expiremonth').$q->param('expireyear'));

# ... cardholder specific stuff needed for AVS
$a->AVSname($q->param('firstname').' '.$q->param('lastname'));
$a->AVSaddress1($q->param('address1'));
$a->AVSaddress2($q->param('address2'));
$a->AVScity($q->param('city'));
$a->AVSstate($q->param('state'));
$a->AVSzip($q->param('zip'));

#$buf .= "Here is the rendered Authorize request:\n".$a->renderAsXML()."\n";

# create a transaction object
my $tp = new Paymentech::eCommerce::TransactionProcessor(config());
die 'no transaction processor!' unless defined ($tp);

# send the transaction and wait for the response
$buf .= "Sending Auth to Gateway ". scalar(localtime)."\n";
$gatewayResponse = $tp->process($a);
$buf .= "          Finished Time ". scalar(localtime)."\n";

# print out response related information
$buf .= "Approved:   [". ($a->response->approved ? 'Yes' : 'No'). "]\n";
$buf .= "Error:      [". ($a->response->error ? 'Yes' : 'No'). "]\n";
$buf .= "Declined:   [". ($a->response->declined ? 'Yes' : 'No'). "]\n";
$buf .= "TxRefNum:   [". ($a->response->TxRefNum || 'not defined'). "]\n";
$buf .= "Status:     [". ($a->response->status || 'not defined'). "]\n";
$buf .= "AVSRespCode:[". ($a->response->value('AVSRespCode') || 'not defined'). "]\n";
$buf .= "RespCode:   [". ($a->response->value('RespCode')|| 'not defined'). "]\n";
$buf .= "ProcStatus  [". ($a->response->value('ProcStatus')|| 'not defined'). "]\n";
$buf .= "CVV2RespCode[". ($a->response->value('CVV2RespCode')|| 'not defined'). "]\n";
#$buf .= "Raw: ". ($gatewayResponse || 'bad response') . "\n";
#$buf .= "-----\n".Dumper($a->response)."\n-----\n";

if ( ! $a->response->approved ) {
	print $a->response->value('RespCode')		||'',"\n";
	print $a->response->status					||'wierd error',"\n";
	print $a->response->value('AVSRespCode')	||'',"\n";
	print $a->response->value('ProcStatus')		||'',"\n";
	print $a->response->value('CVV2RespCode')	||'',"\n";
	print $buf;
	exit;
}

#------------------------------------------------------------
# if the authorization request is approved, then be a
# a good citizen and void it
#------------------------------------------------------------

# create the request object
my $v = requestBuilder()->make(VOID_REQUEST);
$v->BIN('000001');
$v->MerchantID('311225');
$v->TxRefNum($a->response->TxRefNum);

#$buf .= "\nHere is the rendered Void request:\n".$v->renderAsXML()."\n";

# send the void and wait for the response
$buf .= "Sending Void to Gateway ". scalar(localtime)."\n";
$gatewayResponse = $tp->process($v);
$buf .= "          Finished Time ". scalar(localtime)."\n";


# print out response related information
$buf .= "Approved:   [". ($v->response->approved ? 'Yes' : 'No'). "]\n";
$buf .= "Error:      [". ($v->response->error ? 'Yes' : 'No'). "]\n";
$buf .= "Declined:   [". ($v->response->declined ? 'Yes' : 'No'). "]\n";
$buf .= "TxRefNum:   [". ($v->response->TxRefNum || 'not defined'). "]\n";
$buf .= "Status:     [". ($v->response->status || 'not defined'). "]\n";
$buf .= "AVSRespCode:[". ($v->response->value('AVSRespCode') || 'not defined'). "]\n";
$buf .= "RespCode:   [". ($v->response->value('RespCode') || 'not defined'). "]\n";
$buf .= "ProcStatus  [". ($v->response->value('ProcStatus')|| 'not defined'). "]\n";
$buf .= "CVV2RespCode[". ($a->response->value('CVV2RespCode')|| 'not defined'). "]\n";
#$buf .= "Raw: ". ($gatewayResponse || 'bad response') . "\n";
#$buf .= "-----\n".Dumper($a->response)."\n-----\n";
$buf .= "==================================================\n";

print $a->response->value('RespCode')		||'',"\n";
print $a->response->status					||'wierd error',"\n";
print $a->response->value('AVSRespCode')	||'',"\n";
print $a->response->value('ProcStatus')		||'',"\n";
print $a->response->value('CVV2RespCode')	||'',"\n";
print $buf;
exit;
