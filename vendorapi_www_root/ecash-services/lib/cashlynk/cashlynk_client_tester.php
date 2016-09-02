<?php

	include_once( 'dlhdebug.php' );
	include_once( 'cashlynk_client.php' );
	
	$DEBUG = true;
	
	$mc   = '5499974444444445';
	$disc = '6011000993069248';
	$visa = '4446661234567892';
	$amex = '373235387881007';
	
	$cashlynk = new Cashlynk_Client();
	// $cashlynk->Set_Operating_Mode( Cashlynk_Client::RUN_MODE_NO_CALL );
	// $cashlynk->Set_Operating_Mode( Cashlynk_Client::RUN_MODE_TEST );
	// $cashlynk->Set_Operating_Mode( Cashlynk_Client::RUN_MODE_LIVE );
	
	$runMode                  = getHttpVar('runMode', Cashlynk_Client::RUN_MODE_NO_CALL); // default to "no call"
	$screenInitialCall        = getHttpVar('screenInitialCall');    // not currently used.
	$fieldDisplayType         = getHttpVar('fieldDisplayType', 1);  // default to displaying required fields, not all fields
	$choose_tran_type         = getHttpVar('choose_tran_type', Cashlynk_Client_Data::TYPE_CREATE_CARD_HOLDER);
	$submitGetNewFieldsButton = getHttpVar('submitGetNewFieldsButton');
	$submitButton             = getHttpVar('submitButton');
	
	$cashlynk->Set_Operating_Mode( $runMode );
	$cashlynk->Set_Transaction_Type( $choose_tran_type );
	
	$html_run_mode  = htmlMakeHtmlInput( 'radio', 'runMode', 3, 'No Call',     $runMode, '&nbsp;', ' onchange="this.form.submit()" ' );
	$html_run_mode .= htmlMakeHtmlInput( 'radio', 'runMode', 2, 'Test-500FC',  $runMode, '&nbsp;', ' onchange="this.form.submit()" ' );
	$html_run_mode .= htmlMakeHtmlInput( 'radio', 'runMode', 1, 'Live-500FC',  $runMode, '&nbsp;', ' onchange="this.form.submit()" ' );
	$html_run_mode .= htmlMakeHtmlInput( 'radio', 'runMode', 4, 'FCP Test',    $runMode, '&nbsp;', ' onchange="this.form.submit()" ' );
	$html_run_mode .= htmlMakeHtmlInput( 'radio', 'runMode', 5, 'FCP Live',    $runMode, '&nbsp;', ' onchange="this.form.submit()" ' );
	
	$url = $cashlynk->Get_Url();
	
	$html_field_types  = htmlMakeHtmlInput( 'radio', 'fieldDisplayType', 1, 'Required Fields', $fieldDisplayType, '&nbsp;', ' onchange="this.form.submit()" ' );
	$html_field_types .= htmlMakeHtmlInput( 'radio', 'fieldDisplayType', 3, 'All Fields', $fieldDisplayType, '&nbsp;', ' onchange="this.form.submit()" ' );
	
	$avail_tran_types = $cashlynk->Get_Transaction_Types();
	$html_select_type = htmlMakeHtmlSelectFull( 'choose_tran_type', $choose_tran_type, $avail_tran_types, '', ' onchange="this.form.submit()" ' );
	
	$error_text              = '';
	$result                  = '';
	
	$field_array = array();
	
	if ( $submitGetNewFieldsButton != '' ) {
		// do nothing, will initialize screen
	}
	else if ( $submitButton != '' ) {
		// re-acquire field array for display.
		getAndSaveMostScreenValues( $cashlynk );
		$cashlynk->Validate_Fields_Are_Populated($error_text);  //
		$result = $cashlynk->Run_Transaction();
	}
	
	switch ( $fieldDisplayType ) {
		case 1 : $field_array = $cashlynk->Get_Required_Fields_Array();    break;
		case 3 : $field_array = $cashlynk->Get_All_Fields_Array();         break;
		default: $field_array = $cashlynk->Get_Required_Fields_Array();    break;
	}
	
	$screenInitialCall = 'N';  // not currently used.
	

	function getQueryStringName( $cashlynkObj, $field_name ) {
		$query_name  = '';
		$value       = '';
		$requirement = '';
		$cashlynkObj->Get_Field_Details( $field_name, $query_name, $value, $requirement );
		return $query_name;
	}


	function getFieldRequirement( $cashlynkObj, $field_name ) {
		$query_name  = '';
		$value       = '';
		$requirement = '';
		$cashlynkObj->Get_Field_Details( $field_name, $query_name, $value, $requirement );
		return $requirement;
	}
	
	
	function getAndSaveMostScreenValues( $cashlynkObj, $excludeField='' ) {
		$all_fields_array = $cashlynkObj->Get_All_Fields_Array();
		foreach( $all_fields_array as $key => $val ) {
			if ( $key != $excludeField ) {
				$inputVal = getHttpVar($key);
				$cashlynkObj->Set_Field ( $key, $inputVal );
			}
		}
	}
	
	function getHttpVar( $var, $default='' ) {
		return
		'POST' == $_SERVER['REQUEST_METHOD']
			? (isset($_POST[$var]) ? $_POST[$var] : $default)
			: (isset($_GET[$var]) ? $_GET[$var] : $default);
	}
	
	
	function htmlMakeHtmlSelectFull( $name, $selectedValue, $optionArray, $br='', $js='' ) {
	
		$selectRaw = '<select name="{NAME}" {JS}>{OPTION}</select>{BR}';
		$optionRaw = '<option value="{OPTIONVALUE}" {CHECKED}>{OPTIONDISPLAY}</option>';
	
		$option = '';
	
		while ( list($key, $value) = each($optionArray) ) {
			if ( is_array($selectedValue) ) {
				$checked = ( in_array( $key, $selectedValue ) ) ? 'SELECTED' : '';
			}
			else {
				$checked = ( $key == $selectedValue ) ? 'SELECTED' : '';
			}
		
			$optionTemp = $optionRaw;
			$optionTemp = str_replace( '{OPTIONVALUE}',   $key,     $optionTemp );
			$optionTemp = str_replace( '{OPTIONDISPLAY}', $value,   $optionTemp );
			$optionTemp = str_replace( '{CHECKED}',       $checked, $optionTemp );
			$option .= $optionTemp . "\r\n";
		}
	
		$selectRaw = str_replace( '{NAME}',   $name,    $selectRaw );
		$selectRaw = str_replace( '{OPTION}', $option,  $selectRaw );
		$selectRaw = str_replace( '{BR}',     $br,      $selectRaw );
		$selectRaw = str_replace( '{JS}',     $js,      $selectRaw );
	
		return $selectRaw;
	}
	
	
	function htmlMakeHtmlInput( $type, $name, $value='', $display='', $selectedValue='', $br='', $js='' ) {
	
		$result  = '<input type="{TYPE}" name="{NAME}" value="{VALUE}" {CHECKED} {JS}>{DISPLAY}</input>{BR}';
	
		if ( is_array($selectedValue) ) {
			$checked = ( in_array( $value, $selectedValue ) ) ? 'CHECKED' : '';
		}
		else {
			$checked = ( $value == $selectedValue ) ? 'CHECKED' : '';
		}
		
		$result  = str_replace( '{TYPE}',    $type,    $result );
		$result  = str_replace( '{NAME}',    $name,    $result );
		$result  = str_replace( '{VALUE}',   $value,   $result );
		$result  = str_replace( '{DISPLAY}', $display, $result );
		$result  = str_replace( '{CHECKED}', $checked, $result );
		$result  = str_replace( '{BR}',      $br,      $result );
		$result  = str_replace( '{JS}',      $js,      $result );
		
		return $result;
	}

?>

<html>
  <head>
    <title>CashLynk Client Tester</title>
    <style>
      .result { border: 3px solid red; }
      .head, tr.head td { background: #99cccc; font-weight: bold; }
      .hi { background: #ccccff; }
      td { background: #cccccc; }
      div.heading { font-size: 1.5em; font-weight: bold; color: red; padding: 5px; border: 1px solid; margin-top: 10px;}
    </style>
    
  </head>
  <body>
    
    <form method="post" action="<? echo $_SERVER['PHP_SELF'] ?>">

      <input type="hidden" name="screenInitialCall" value="<? echo $screenInitialCall ?>"></input>

      <center>
        <h2>CashLynk Client Tester</h2>
      </center>

		<table align="center" cellpadding="5" cellspacing="5" border="0">
			<tr>
				<td class="head">Transaction Type:</td>
				<td><? echo $html_select_type ?></td>
			</tr>
			<tr>
				<td class="head">Run Mode:</td>
				<td><? echo $html_run_mode ?></td>
			</tr>
			<tr>
				<td class="head">Fields to Display:</td>
				<td><? echo $html_field_types ?></td>
			</tr>
			<tr>
				<td class="head">Url:</td>
				<td><? echo $url ?></td>
			</tr>
			<tr>
				<td align="center" colspan="2">
					<input type="submit" name="submitGetNewFieldsButton" value="Get New Fields"></input>
				</td>
			</tr>
      </table>

      <br clear="all">

      <? if ( $submitButton != '' ) { ?>
      <table class="result" align="center" cellpadding="5" cellspacing="5" border="1">
        <tr>
          <td class="head">result:</td>
          <td><textarea cols="80" rows="7"><? echo $result ?></textarea></td>
        </tr>
        <tr>
          <td class="head">empty Required Fields (errors):</td>
          <td><? echo $error_text ?></td>
        </tr>
      </table>
      <? } ?>
    
      <br clear="all">

      <center><input type="submit" name="submitButton" value="Submit"></input></center>
    
      <br clear="all">

      <table align="center" cellpadding="5" cellspacing="5" border="0">
        <tr class="head">
          <td>field name</td>
          <td>query string name</td>
          <td>value</td>
          <td>required?</td>
        </tr>

        <? foreach( $field_array as $key => $val ) { ?>
        <tr>
          <td><? print $key ?></td>
          <td><? print getQueryStringName($cashlynk,$key) ?></td>
          <td><input type="text" name="<? echo $key ?>" value="<? echo $val ?>"></input></td>
          <td><? print getFieldRequirement($cashlynk,$key) ?></td>
        </tr>
        <? } ?>
      </table>

      <br clear="all">

      <center><input type="submit" name="submitButton" value="Submit"></input></center>
    
      <br clear="all">

    </form>
  
  </body>

</html>
