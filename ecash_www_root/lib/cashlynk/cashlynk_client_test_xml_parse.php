<?php

include_once( 'dlhdebug.php' );

// Sample Cardholder Statement XML Document:

$xml009 = '

<?xml version="1.0"?>
<cashlynk func="009">
   <p1 errnumber="000" errdescription=""/>
     <cardholderstatement>
       <cardpan account="6034110004000018" startdate="1/1/2001" enddate="2/1/2001" reportdate="2/7/2001 5:48:55 PM">
    <cardaccount number="00040000100000001">
      <summary>
    <beginbalance>$1,857.10</beginbalance>
    <totalcredits>$0.00</totalcredits>
    <totaldedits>$3.00</totaldedits>
    <totalfees>$0.00</totalfees>
    <endbalance>$1,854.10</endbalance>
       </summary>
    <transactionlist>
       <transaction>
      <date>2/1/2001 11:02:00 AM</date>
      <authnumber>68270</authnumber>
              <descriptionlist>
           <description>Card Account Transfer From</description>
           <description>Card#: 6034110004000018</description>
           <description>Transfer from CA#: 00040000100000004</description>
           <description>Transfer to CA#: 00040000100000004</description>
         </descriptionlist>
      <amount>-$3.00</amount>
      <fee>$0.00</fee>
      <balance>$1,854.10</balance>
       </transaction>
    </transactionlist>
     </cardaccount>
      <cardaccount number="00040000100000004">
        <summary>
     <beginbalance>$291.00</beginbalance>
     <totalcredits>$3.00</totalcredits>
     <totaldedits>$0.00</totaldedits>
     <totalfees>$0.00</totalfees>
     <endbalance>$294.00</endbalance>
    </summary>
         <transactionlist>
      <transaction>
          <date>2/1/2001 11:02:00 AM</date>
      <authnumber>68272</authnumber>
      <descriptionlist>
      <description>Card Account Transfer To</description>
      <description>Card#: 6034110004000018</description>
        <description>Transfer from CA#: 00040000100000001</description>
        <description>Transfer to CA#: 00040000100000001</description>
      </descriptionlist>
      <amount>$3.00</amount>
      <fee>$0.00</fee>
      <balance>$294.00</balance>
       </transaction>
     </transactionlist>
  </cardaccount>
</cardpan>
</cardholderstatement>
</cashlynk>

';


// Sample Card Account XML Document:

$xml010 = '

<?xml version="1.0" ?>
<cashlynk func="010">
  <p1 errnumber="000" errdescription="" />
  <cardpan account = "6034110004000018" memberno = "0">
    <cardaccount number = "00040000100000001">
      <primarycardaccount>True</primarycardaccount >
      <applicationname>Checking</applicationname>
      <currentbalance>$1,129.00</currentbalance>
    </cardaccount>
    <cardaccount number = "00040000100000004">
      <primarycardaccount>False</primarycardaccount >
      <applicationname>Checking</applicationname>
      <currentbalance>$1,070.80</currentbalance>
    </cardaccount>
  </cardpan>
</cashlynk>

';


// Sample Get Card Transaction Detail XML Document:

$xml020 = '

<?xml version="1.0"?>
<cashlynk func="020">
   <p1 errnumber="000" errdescription=""/>
     <cardholderstatement>
       <cardpan account="6034110004000018" startdate="1/1/2001" enddate="2/1/2001" reportdate="2/7/2001 5:48:55 PM">
    <cardaccount number="00040000100000001">
    <transactionlist>
       <transaction>
      <date>2/1/2001 11:02:00 AM</date>
      <authnumber>68270</authnumber>
              <descriptionlist>
           <description>Card Account Transfer From</description>
           <description>Card#: 6034110004000018</description>
           <description>Transfer from CA#: 00040000100000004</description>
           <description>Transfer to CA#: 00040000100000004</description>
         </descriptionlist>
      <amount>-$3.00</amount>
      <fee>$0.00</fee>
      <balance>$1,854.10</balance>
       </transaction>
    </transactionlist>
     </cardaccount>
      <cardaccount number="00040000100000004">
         <transactionlist>
      <transaction>
          <date>2/1/2001 11:02:00 AM</date>
      <authnumber>68272</authnumber>
      <descriptionlist>
      <description>Card Account Transfer To</description>
      <description>Card#: 6034110004000018</description>
        <description>Transfer from CA#: 00040000100000001</description>
        <description>Transfer to CA#: 00040000100000001</description>
      </descriptionlist>
      <amount>$3.00</amount>
      <fee>$0.00</fee>
      <balance>$294.00</balance>
       </transaction>
     </transactionlist>
  </cardaccount>
</cardpan>
</cardholderstatement>
</cashlynk>

';

	$rows = 15;
	$cols = 80;

	$xml          = getHttpVar('xml');
	$submitXML009 = getHttpVar('submitXML009');
	$submitXML010 = getHttpVar('submitXML010');
	$submitXML020 = getHttpVar('submitXML020');
	
	// Sample Cardholder Statement XML Document:
	if ( $submitXML009 != '' ) {
		$xml = $xml009;
	}
	else if ( $submitXML010 != '' ) {
		$xml = $xml010;
	}
	else if ( $submitXML020 != '' ) {
		$xml = $xml020;
	}
	else if ( $xml == '' ) {
		$xml = $xml009;
	}
	
	$cardpans                       = array();
	$cardaccounts                   = array();
	$cardaccount_summary            = array();
	$cardaccount_transactions       = array();
	$transaction_detail             = array();
	$transaction_detail_description = array();
	$cardaccounts_by_cardnumber     = array();
	
	include_once( 'cashlynk_client.php' );
	$cashlynk = new Cashlynk_Client();
	$result   = $cashlynk->Get_XML_Response_Parsed( $xml, $cardpans, $cardaccounts, $cardaccount_summary,$cardaccount_transactions, $transaction_detail, $transaction_detail_description, $cardaccounts_by_cardnumber );
	
	$cardpansDisplay                   = dlhvardump( $cardpans, false );
	$cardaccountsDisplay               = dlhvardump( $cardaccounts, false );
	$summaryDisplay                    = dlhvardump( $cardaccount_summary, false );
	$transactionsDisplay               = dlhvardump( $cardaccount_transactions, false );
	$detailDisplay                     = dlhvardump( $transaction_detail, false );
	$descriptionDisplay                = dlhvardump( $transaction_detail_description, false );
	$cardaccounts_by_cardnumberDisplay = dlhvardump( $cardaccounts_by_cardnumber, false );
	

	function getHttpVar( $var, $default='' ) {
		return
			'POST' == $_SERVER['REQUEST_METHOD']
				? (isset($_POST[$var]) ? $_POST[$var] : $default)
				: (isset($_GET[$var]) ? $_GET[$var] : $default);
	}


?>

<html>
  <head>
    <title>Cashlynk Client Test Xml Parse</title>
    <style>
    .head, tr.head td { background: #99cccc; font-weight: bold; }
    .hi { background: #ccccff; }
    td { background: #cccccc; }
    div.heading { font-size: 1.5em; font-weight: bold; color: red; padding: 5px; border: 1px solid; margin-top: 10px;}
    </style>
    
  </head>
  <body>
    
    <form method="post" action="<? echo $_SERVER['PHP_SELF'] ?>">
  
      <center><h2>Cashlynk Client Test Xml Parse</h2></center>
      
      <table align="center" cellpadding="5" cellspacing="5" border="0">
        <tr>
          <td align="center" colspan="2" class="head">
<textarea name="xml" rows="15" cols="100" wrap="soft">
<? echo $xml ?>
</textarea>
          </td>
        </tr>
        <tr>
          <td colspan="2" align="center">
            <input type="submit" name="submitButton" value="Submit">&nbsp;&nbsp;&nbsp;
            <input type="submit" name="submitXML009" value="Use XML 009">&nbsp;&nbsp;&nbsp;
            <input type="submit" name="submitXML010" value="Use XML 010">&nbsp;&nbsp;&nbsp;
            <input type="submit" name="submitXML020" value="Use XML 020">&nbsp;&nbsp;&nbsp;
          </td>
        <tr>
        <tr>
          <td>result:</td>
          <td><? print $result ?></td>
        </tr>
        <tr>
          <td>$cardpansDisplay:</td>
          <td><textarea rows="<? echo $rows ?>" cols="<? echo $cols ?>" wrap="soft"><? print $cardpansDisplay ?></textarea></td>
        </tr>
        <tr>
          <td>$cardaccountsDisplay:</td>
          <td><textarea rows="<? echo $rows ?>" cols="<? echo $cols ?>" wrap="soft"><? print $cardaccountsDisplay ?></textarea></td>
        </tr>
        <tr>
          <td>$summaryDisplay:</td>
          <td><textarea rows="<? echo $rows ?>" cols="<? echo $cols ?>" wrap="soft"><? print $summaryDisplay ?></textarea></td>
        </tr>
        <tr>
          <td>$transactionsDisplay:</td>
          <td><textarea rows="<? echo $rows ?>" cols="<? echo $cols ?>" wrap="soft"><? print $transactionsDisplay ?></textarea></td>
        </tr>
        <tr>
          <td>$detailDisplay:</td>
          <td><textarea rows="<? echo $rows ?>" cols="<? echo $cols ?>" wrap="soft"><? print $detailDisplay ?></textarea></td>
        </tr>
        <tr>
          <td>$descriptionDisplay:</td>
          <td><textarea rows="<? echo $rows ?>" cols="<? echo $cols ?>" wrap="soft"><? print $descriptionDisplay ?></textarea></td>
        </tr>
        <tr>
          <td>$cardaccounts by cardnumberDisplay:</td>
          <td><textarea rows="<? echo $rows ?>" cols="<? echo $cols ?>" wrap="soft"><? print $cardaccounts_by_cardnumberDisplay ?></textarea></td>
        </tr>
      </table>

      <br clear="all">

      <center><input type="submit" name="submitButton" value="Submit"></input></center>
  
    </form>
    
  </body>
</html>

