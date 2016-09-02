<?php

	defined('DIR_LIB')   || define ('DIR_LIB', '/virtualhosts/lib/');

	require_once(DIR_LIB . 'dlhdebug.php');
	require_once(DIR_LIB . 'applog.controller.01.php');

	$message               = '';

	$dir                   = getHttpVar('dir');
	$regex                 = getHttpVar('regex');
	$docs                  = getHttpVar('docs');
	$submitUpdate          = getHttpVar('submitUpdate');
	$submitDelete          = getHttpVar('submitDelete');
	$submitRefresh         = getHttpVar('submitRefresh');
	$submitUpdateAllValues = getHttpVar('submitUpdateAllValues');
	$file_new_submit       = getHttpVar('file_new_submit');
	$class_new_submit      = getHttpVar('class_new_submit');
	$method_new_submit     = getHttpVar('method_new_submit');
	$function_new_submit   = getHttpVar('function_new_submit');
	$submitUpdateSpecial   = getHttpVar('submitUpdateSpecial');
	

	if ( $docs != '' )
	{
		// no need to do anything, html with embedded php will take care of this
	}
	else if ( $dir != '' )
	{
		$fileversion = $global_all_checked = $global_none_checked = $global_some_checked = $unklevel = $filerows = $classrows = $methodrows = $functionrows = $special = '';

		$applog_controller = new Applog_Controller_01($dir);
		$config = $applog_controller->Get_Config_Array();

		inquire( $config, $fileversion, $global_all_checked, $global_none_checked, $global_some_checked, $unklevel, $filerows, $classrows, $methodrows, $functionrows, $special );
		
		if ( $file_new_submit != '' )
		{
			$mod_name_new = getHttpVar('file_name_new');
			$mod_level_new = getHttpVar('file_level_new');
			if ( $mod_name_new != '' )
			{
				if ( !is_numeric($mod_level_new) ) $mod_level_new = 0;
				$applog_controller->Add_File( $mod_name_new, $mod_level_new );
				$config = $applog_controller->Rewrite_Config();
				inquire( $config, $fileversion, $global_all_checked, $global_none_checked, $global_some_checked, $unklevel, $filerows, $classrows, $methodrows, $functionrows, $special );
			}
		}
		else if ( $class_new_submit != '' )
		{
			$mod_name_new = getHttpVar('class_name_new');
			$mod_level_new = getHttpVar('class_level_new');
			if ( $mod_name_new != '' )
			{
				if ( !is_numeric($mod_level_new) ) $mod_level_new = 0;
				$applog_controller->Add_Class( $mod_name_new, $mod_level_new );
				$config = $applog_controller->Rewrite_Config();
				inquire( $config, $fileversion, $global_all_checked, $global_none_checked, $global_some_checked, $unklevel, $filerows, $classrows, $methodrows, $functionrows, $special );
			}
		}
		else if ( $method_new_submit != '' )
		{
			$mod_name_new = getHttpVar('method_name_new');
			$mod_level_new = getHttpVar('method_level_new');
			if ( $mod_name_new != '' )
			{
				if ( !is_numeric($mod_level_new) ) $mod_level_new = 0;
				$applog_controller->Add_Method( $mod_name_new, $mod_level_new );
				$config = $applog_controller->Rewrite_Config();
				inquire( $config, $fileversion, $global_all_checked, $global_none_checked, $global_some_checked, $unklevel, $filerows, $classrows, $methodrows, $functionrows, $special );
			}
		}
		else if ( $function_new_submit != '' )
		{
			$mod_name_new = getHttpVar('function_name_new');
			$mod_level_new = getHttpVar('function_level_new');
			if ( $mod_name_new != '' )
			{
				if ( !is_numeric($mod_level_new) ) $mod_level_new = 0;
				$applog_controller->Add_Function( $mod_name_new, $mod_level_new );
				$config = $applog_controller->Rewrite_Config();
				inquire( $config, $fileversion, $global_all_checked, $global_none_checked, $global_some_checked, $unklevel, $filerows, $classrows, $methodrows, $functionrows, $special );
			}
		}
		else if ( $submitUpdate != '' )
		{
			Modify_Stuff( $applog_controller, 'file' );
			Modify_Stuff( $applog_controller, 'class' );
			Modify_Stuff( $applog_controller, 'method' );
			Modify_Stuff( $applog_controller, 'function' );

			$global   = getHttpVar('global');
			$unklevel = getHttpVar('unklevel');

			$applog_controller->Set_Global_Control($global);
			$applog_controller->Set_Unknown_Msg_Level($unklevel);
			
			$config = $applog_controller->Rewrite_Config();
			inquire( $config, $fileversion, $global_all_checked, $global_none_checked, $global_some_checked, $unklevel, $filerows, $classrows, $methodrows, $functionrows, $special );
		}
		else if ( $submitDelete != '' )
		{
			Delete_Stuff( $applog_controller, 'file' );
			Delete_Stuff( $applog_controller, 'class' );
			Delete_Stuff( $applog_controller, 'method' );
			Delete_Stuff( $applog_controller, 'function' );
			$config = $applog_controller->Rewrite_Config();
			inquire( $config, $fileversion, $global_all_checked, $global_none_checked, $global_some_checked, $unklevel, $filerows, $classrows, $methodrows, $functionrows, $special );
		}
		else if ( $submitRefresh != '' )
		{
			// no need to do anything.  already re-inquired on config data.
		}
		else if ( $submitUpdateAllValues != '' )
		{
			$updateAllValues = getHttpVar('updateAllValues');
			if ( is_numeric($updateAllValues) )
			{
				Modify_Stuff( $applog_controller, 'file', $updateAllValues );
				Modify_Stuff( $applog_controller, 'class', $updateAllValues );
				Modify_Stuff( $applog_controller, 'method', $updateAllValues );
				Modify_Stuff( $applog_controller, 'function', $updateAllValues );
				$config = $applog_controller->Rewrite_Config();
				inquire( $config, $fileversion, $global_all_checked, $global_none_checked, $global_some_checked, $unklevel, $filerows, $classrows, $methodrows, $functionrows, $special );
			}
		}
		else if ( $submitUpdateSpecial != '' )
		{
			$special_key = getHttpVar('special_key_0');
			$special_val = getHttpVar('special_val_0');

			$i = 0;
			
			do
			{
				$i++;
				
				if ( $special_key != '' ) $applog_controller->Set_Special_Vals($special_key, $special_val);
				
				$special_key = getHttpVar("special_key_$i");
				$special_val = getHttpVar("special_val_$i");
			}
			while ( $special_key != '' );
			
			$config = $applog_controller->Rewrite_Config();
			inquire( $config, $fileversion, $global_all_checked, $global_none_checked, $global_some_checked, $unklevel, $filerows, $classrows, $methodrows, $functionrows, $special );
		}
	}


	function Modify_Stuff( &$applog_controller, $category, $set_all_levels = -1 )
	{
		// The add function overlays if the module is already present so I can use it for update/modify.
	
		$mod = strtolower($category);
		$function_name = 'Add_' . ucfirst($category);
		$i = 1;
		$level = $set_all_levels;
		do
		{
			if ( $set_all_levels == -1 )
			{
				// $level defaults to -1 when called by the "Submit Update" button.  In this case, the level
				// comes from the field for each module.
				//
				// If $level is not -1 then this is being called from the "Set All Values" button and
				// all levels will be set to a specified value.
				$level = getHttpVar($mod . '_level_' . $i);
			}
			$mod_name = getHttpVar($mod . '_name_' . $i);
			if ( !is_numeric($level) ) $level = 0;
			if ( $mod_name != '' ) $applog_controller->$function_name($mod_name, $level);
			$i++;
		}
		while ( $mod_name != '' );
	}


	function Delete_Stuff( &$applog_controller, $category )
	{
		$mod = strtolower($category);
		$function_name = 'Delete_' . ucfirst($category);
		$i = 1;
		do
		{
			$checkbox = getHttpVar($mod . '_checkbox_' . $i);
			$mod_name = getHttpVar($mod . '_name_' . $i);
			if ( $checkbox != '' && $mod_name != '' ) $applog_controller->$function_name($mod_name);
			$i++;
		}
		while ( $mod_name != '' );
	}


	function inquire( &$config, &$fileversion, &$global_all_checked, &$global_none_checked, &$global_some_checked, &$unklevel, &$filerows, &$classrows, &$methodrows, &$functionrows, &$special )
	{
		$fileversion         = $config['VERSION'];

		$global              = $config['ALL'];
		$global_all_checked  = $global == 'all' ? ' CHECKED ' : '';
		$global_none_checked = $global == 'none' ? ' CHECKED ' : '';
		$global_some_checked = $global == 'all' || $global == 'none' ? '' : ' CHECKED ';

		$unklevel            = $config['SCRIPT'];

		$filerows            = $config['FILE'];
		$classrows           = $config['CLASS'];
		$methodrows          = $config['METHOD'];
		$functionrows        = $config['FUNCTION'];
		$special             = $config['SPECIALVALS'];

		return $config;
	}


	function Check_Regex( $regex, &$i, $name )
	{
		if ( $regex == '' )
		{
			$i++;
			return true;
		}

		if ( preg_match( $regex, $name ) )
		{
			$i++;
			return true;
		}

		return false;
	}
	

	function getHttpVar( $var, $default='' ) {
		return
		'POST' == $_SERVER['REQUEST_METHOD']
			? (isset($_POST[$var]) ? trim($_POST[$var]) : $default)
			: (isset($_GET[$var])  ? trim($_GET[$var])  : $default);
	}

	function makeNotNull($s) { return isset($s) ? trim($s) : ''; }

?>
<html>
	<head>
		<title>Applog Controller Interface</title>
		<style>
		.result { border: 3px solid red; }
		.categoryHeading { background-color: #eef0f4; height: 3em; font-weight: bold; }
		.head, tr.head td { background: #99cccc; font-weight: bold; }
		.hi { background: #ccccff; }
		.helptext { color: #333333; font-size: .8em; /* font-family: fantasy; */ font-style: italic; }
		.columnLegend { color: #333333; font-size: .8em; }
		td { background: #cccccc; }
		div.heading { font-size: 1.5em; font-weight: bold; color: red; padding: 5px; border: 1px solid; margin-top: 10px;}
		table.docs, table.docs td { padding: 5px;  background-color: #eef0f4; }
		dt { margin-bottom: 0.5em; font-weight: bold; color: #333333; padding: 5px; text-decoration: underline; }
		dd { margin-bottom: 2em; color: #555555;  }
		code { display: block; border: 1px dashed #151515; font-family: monospace; padding: 1em; margin: 1em; }
		code b { color: #4848ff; }
		</style>
		
	</head>
	<body>
    
		<form method="post" action="<? echo $_SERVER['PHP_SELF'] ?>">
		
<? if ( $docs != '' ) { ?>

	<input type="hidden" name="dir" value="<? echo $dir ?>"></input>

	<center>
		<h2>Applog Controller Interface: Documentation</h2>
	</center>

	<table class="docs" align="center" cellpadding="2" cellspacing="2">
		<tr><td>
			<dl>
				<dt>
					What is this applog controller?
				</dt>
					<dd>
						The applog controller provides a simple way to control which debugging statements
						get printed to the log.
						<P>
						Using the applog controller, you can easily configure ALL,
						NONE, or SOME of the debugging statements to print to the log.
						If configuring SOME of the debugging statements to be active, you
						can decide which statements get printed based on the
						class or method containing the statement and the debugging level
						of the statements (levels typically range from 0 - 100).
					</dd>
				
				<dt>
					Why do we need it and how is it helpful?
				</dt>
					<dd>
						Currently, programmers stick in logging messages and die() messages
						while developing new code or debugging existing code.  Once a particular
						bug is solved, logging and die() messages are usually commented out or
						deleted.

						<p>
						When a bug pops up repeatedly in the same section of code, the logging
						and die() messages are recreated (or at best, uncommented) all over
						again.

						<p>
						Creating and deleting and recreating log and die() messages represents
						a lot of work that gets thrown away and then reworked repeatedly.  And
						a good quality log message displaying several fields requires a fair amount
						of effort and time.

						<p>
						Why throw all this work away?

						<p>
						What if the developer could put a reasonable amount of effort into creating
						good logging/debugging messages and then we could activate or disactivate
						those messages dynamically?  What if we could activate certain debugging
						messages in the LIVE environment when a bug is encountered without having to
						edit code?  Wouldn't this make solving bugs much quicker?

						<p>
						If debugging messages are already in place just waiting to be activated, bugs
						will be diagnosed quickly and squashed rapidly with less effort than currently
						required.  Most of the time and effort currently spent fixing bugs on existing
						systems revolves around trying to figure out what happened and what went wrong.
						Once problems are diagnosed, the code fix is usually fairly quick.  A dynamically
						controlled debug message system can help reduce the amount of time and effort
						required in the bug diagnosis stage.

						<p>
						An extra and significant benefit from a dynamically controlled debug message
						system is realized when new programmers are hired.  Using this system, a new hire
						can turn on all debugging messages and quickly see a trace of code executing.
						This will help the new hire get up to speed quickly.
						
					</dd>
				
				<dt>
					How do I code it?
				</dt>
					<dd>
						The applog controller is designed to be drop-in replaceable with the applog.
						Anyplace the applog is instantiated, simply change to instantiate the
						applog controller.

						<p>
						Example:
						<code>
							<b>old applog approach:</b><br>
							$log = new Applog('ccs', APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, 'soap_api', APPLOG_ROTATE);
							<br>&nbsp;<br>
							<b>new applog controller approach:</b><br>
							$log = new Applog_Controller_01('ccs', APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, 'soap_api', APPLOG_ROTATE);
							<br>&nbsp;<br>
							// The next line is optional. It causes the applog controller
							<br>
							// to automatically track all classes and methods that call it
							<br>
							// and add them to the list in the config file.  In other words,
							<br>
							// it's a way for the config file to be built automatically.
							<br>&nbsp;<br>
							if ( $mode == 'LOCAL' ) $log->Set_Dynamic_Config_Building ( true );
						</code>

						After doing this all logging messages will remain exactly as they were before.  The applog controller
						will simply pass along $log->Write() calls to the underlying applog.  Log messages will NOT be
						controlled at this point.

						<p>
						In order to begin controlling the output of your debugging statements, you will need
						to change calls from $log->Write() to $log->Cout() as follows:

						<code>
							<b>applog approach:</b><br>
							$log->Write( "Some kind of debugging message" );
							<br>&nbsp;<br>
							<b>applog controller approach:</b><br>
							$log->Cout( __METHOD__, 10, "Some kind of debugging message" );
						</code>

						The second parameter to Cout() is the number 10.  This is a logging level.  Logging levels
						typically range from 0 to 100 and the higher the number, the less likely you are to want
						to see the output.  If you set the logging level of a particular message to 0 or -1 that
						will nearly guarantee the message will always be printed.

						<p>
						It's recommended that the first debugging statement in any method have a logging level of 1.
						Other debugging messages within a method should have a logging level of 10 or greater.  This
						will make it easy to trace just the entry to every method without cluttering up the log
						with too much detail.

						<p>
						The first parameter to Cout() is the PHP magic field __METHOD__.  This is automatically populated
						by PHP with the class and method name at the current line of code or the function name if not
						in a class object.  This string is considered a module name by the applog controller.  You will
						be able to turn on and off debugging messages for this module name.  Since the __METHOD__ will
						be a string of className::methodName when used from a class, the applog controller takes advantage
						of this fact to allow you to activate or disactivate debugging messages for both the entire className
						as well as the individual methodNames.

						<p>
						You can place anything you like in the first parameter including an empty string.  If you are
						putting debugging messages in a script file and not within a function, you will probably
						want to use the magic field __FIELD__ instead of __METHOD__ and then you'll be able to activate
						and disactivate debugging messages based on the file name of those script files.

						<p>
						What if you have a complicated debugging message that involves printing out an array or
						doing some intensive parsing?  How do you reduce the performance hit from parsing data that
						isn't going to be printed anyway?

						<p>
						There is a method named Check_Cout() that allows you to check if a debug message will
						be printed.  Using Check_Cout() in an if statement allows you to avoid the overhead in
						making a call to Cout() if the debug message isn't going to be printed.

						<p>
						Example:
						<code>
							<b>Reducing performance hit on messages that won't get printed:</b><br>
							if ( $log->Check_Cout( __METHOD__, 10 ) )
							<br>
							{
							<br>
							&nbsp;&nbsp;&nbsp;$log->Cout( __METHOD__, 10, "Some process-intensive debugging message" );
							<br>
							}
						</code>
						
					</dd>
			
				<dt>
					How do I configure which messages get displayed?
				</dt>
					<dd>
						The applog controller automatically creates a log_control file in the same directory
						where the applog creates the "current" log file.  The log_control file is a var_export
						of an array of configuration data.  You can edit the file directly but it's easier
						to use the applog.controller.interface.php script from the browser to update the
						log_control config file.  If you're reading this documentation, you are probably
						using the applog.controller.interface.php script

						<p>
						The interface shows all modules in the config file and their levels.  Any debug
						statement identifying itself with that module will be printed if its level is less
						than or equal to the level in the confif file.

						<p>
						The interface is checked into CVS in the /virtualhosts/lib directory along with
						the applog.controller.01.php.  You'll need to move it underneath a webserver doc
						directory in order to use it from the browser.

						<p>
						The interface is pretty darn intuitive so this is the end of the documentation
						on that.
						
					</dd>
			
			</dl>
		</td></tr>
	</table>

	<center>
		<br>
		<input type="submit" name="submitRefresh" value="Go Back"></input>
	</center>
	

<? } else { ?>

			<center>
				<h2>Applog Controller Interface</h2>
			</center>
		
			<table align="center" cellpadding="2" cellspacing="1" border="0">
				<tr>
					<td class="head">Logging Directory:</td>
					<td><input type="text" name="dir" value="<? echo $dir ?>" size="30"></input></td>
					<td class="helptext">example:&nbsp;&nbsp; ccs | ecash3.0 | olp</td>
				</tr>

				<? if ( $dir != '' ) { ?>
					<tr>
						<td class="head">File Version:</td>
						<td colspan="2"><? echo $fileversion ?></td>
					</tr>
					<tr>
						<td class="head">Global Message Control:</td>
						<td colspan="2">
							<input type="radio" name="global" value="all" <? echo $global_all_checked ?> >
							Display ALL Messages
							<br>
							<input type="radio" name="global" value="none" <? echo $global_none_checked ?> >
							Display NONE of the Messages
							<br>
							<input type="radio" name="global" value="some" <? echo $global_some_checked ?> >
							Display Some Messages Based on Configuration Below
						</td>
					</tr>
					<tr>
						<td class="head">Messages of Unknown Origin:</td>
						<td><input type="text" name="unklevel" value="<? echo $unklevel ?>" size="30"></td>
						<td class="helptext">
							Enter a number from 0 - 100.<br>
							This controls the display of messages<br>
							that have a blank module specifier.
						</td>
					</tr>
					<tr>
						<td class="head">
							Show only modules matching <br>this regular expression:
						</td>
						<td><input type="text" name="regex" value="<? echo $regex ?>" size="30"></td>
						<td>
							<span class="helptext">
								example:&nbsp;&nbsp; /ccs_api_ods/i
							</span>
						</td>
					</tr>
				<? } ?>
				
				<tr>
					<td colspan="3" align="center">
						<input type="submit" name="submit_select_system" value="Get Configuration">
						<input type="submit" name="submitUpdate" value="Submit Updates"></input>
						<input type="submit" name="docs" value="Documentation">
					</td>
				</tr>
			</table>
	
			<? if ( $message != '' ) { ?>
				<div align="center" class="result">
					<? echo $message ?>
				</div>
				<br clear="all">
			<? } ?>

			<br clear="all">
		
			<? if ( $dir != '' ) { ?>

				<table align="center" cellpadding="2" cellspacing="2" border="0">
					<tr><td colspan="3" class="categoryHeading" align="center">Special Values</td></tr>
					<tr>
						<td><input type="text" name="special_key_0" value="" size="30"></td>
						<td><b>&nbsp;=>&nbsp;</b></td>
						<td><input type="text" name="special_val_0" value="" size="30"></td>
					</tr>
					<? $i = 0; foreach ( $special as $key => $val ) { $i++; ?>
						<tr>
							<td><input type="text" name="special_key_<? echo $i ?>" value="<? echo $key ?>" size="30"></td>
							<td><b>&nbsp;=>&nbsp;</b></td>
							<td><input type="text" name="special_val_<? echo $i ?>" value="<? echo $val ?>" size="30"></td>
						</tr>
					<? } ?>
					<tr>
						<td colspan="3" align="center">
							<span class="helptext">
								The first row is for adding a new key/value pair.
								<br>
								To delete a row, blank out the value and click Update.
								<br>&nbsp;<br>
								If special values are entered, <br>then <u>ONLY</u> messages satisfying one of these
								will be printed.
							</span>
							<br>
							<input type="submit" name="submitUpdateSpecial" value="Update">
						</td>
					</tr>
				<table>
			
				<br clear="all">
				
				<table align="center" cellpadding="2" cellspacing="2" border="0">
					
					<tr><td colspan="3" class="categoryHeading" valign="middle" align="center">FILES:</td></tr>
					<tr><td>&nbsp;</td><td class="columnLegend">module name</td><td class="columnLegend">level</td></tr>
					<tr>
						<td align="center"><input type="submit" name="file_new_submit" value="New File"></td>
						<td><input type="text" name="file_name_new" value="" size="80"></td>
						<td><input type="text" name="file_level_new" value="0" size="5" maxlength="5"></td>
					</tr>
					<? $i = 0; foreach ( $filerows as $name => $level ) { if ( Check_Regex( $regex, $i, $name ) ) { ?>
						<tr>
							<td align="center"><input type="checkbox" name="file_checkbox_<? echo $i ?>" value="file_checkbox_<? echo $i ?>"></td>
							<td><input type="text" name="file_name_<? echo $i ?>" value="<? echo $name ?>" size="80"></td>
							<td><input type="text" name="file_level_<? echo $i ?>" value="<? echo $level ?>" size="5" maxlength="5"></td>
						</tr>
					<? } } ?>
	
	
					<tr><td colspan="3" class="categoryHeading" valign="middle" align="center">CLASSES:</td></tr>
					<tr><td>&nbsp;</td><td class="columnLegend">module name</td><td class="columnLegend">level</td></tr>
					<tr>
						<td align="center"><input type="submit" name="class_new_submit" value="New Class"></td>
						<td><input type="text" name="class_name_new" value="" size="80"></td>
						<td><input type="text" name="class_level_new" value="0" size="5" maxlength="5"></td>
					</tr>
					<? $i = 0; foreach ( $classrows as $name => $level ) { if ( Check_Regex( $regex, $i, $name ) ) { ?>
						<tr>
							<td align="center"><input type="checkbox" name="class_checkbox_<? echo $i ?>" value="class_checkbox_<? echo $i ?>"></td>
							<td><input type="text" name="class_name_<? echo $i ?>" value="<? echo $name ?>" size="80"></td>
							<td><input type="text" name="class_level_<? echo $i ?>" value="<? echo $level ?>" size="5" maxlength="5"></td>
						</tr>
					<? } } ?>
					
	
					<tr><td colspan="3" class="categoryHeading" valign="middle" align="center">METHODS:</td></tr>
					<tr><td>&nbsp;</td><td class="columnLegend">module name</td><td class="columnLegend">level</td></tr>
					<tr>
						<td align="center"><input type="submit" name="method_new_submit" value="New Method"></td>
						<td><input type="text" name="method_name_new" value="" size="80"></td>
						<td><input type="text" name="method_level_new" value="0" size="5" maxlength="5"></td>
					</tr>
					<? $i = 0; foreach ( $methodrows as $name => $level ) { if ( Check_Regex( $regex, $i, $name ) ) { ?>
						<tr>
							<td align="center"><input type="checkbox" name="method_checkbox_<? echo $i ?>" value="method_checkbox_<? echo $i ?>"></td>
							<td><input type="text" name="method_name_<? echo $i ?>" value="<? echo $name ?>" size="80"></td>
							<td><input type="text" name="method_level_<? echo $i ?>" value="<? echo $level ?>" size="5" maxlength="5"></td>
						</tr>
					<? } } ?>
	
	
					<tr><td colspan="3" class="categoryHeading" valign="middle" align="center">FUNCTIONS:</td></tr>
					<tr><td>&nbsp;</td><td class="columnLegend">module name</td><td class="columnLegend">level</td></tr>
					<tr>
						<td align="center"><input type="submit" name="function_new_submit" value="New Function"></td>
						<td><input type="text" name="function_name_new" value="" size="80"></td>
						<td><input type="text" name="function_level_new" value="0" size="5" maxlength="5"></td>
					</tr>
					<? $i = 0; foreach ( $functionrows as $name => $level ) { if ( Check_Regex( $regex, $i, $name ) ) { ?>
						<tr>
							<td align="center"><input type="checkbox" name="function_checkbox_<? echo $i ?>" value="function_checkbox_<? echo $i ?>"></td>
							<td><input type="text" name="function_name_<? echo $i ?>" value="<? echo $name ?>" size="80"></td>
							<td><input type="text" name="function_level_<? echo $i ?>" value="<? echo $level ?>" size="5" maxlength="5"></td>
						</tr>
					<? } } ?>
					
				</table>

				<br clear="all">
		
				<center>
					<input type="submit" name="submitUpdate" value="Submit Updates"></input>
					<input type="submit" name="submitDelete" value="Delete Checked"></input>
					<input type="submit" name="submitRefresh" value="Refresh"></input>
				</center>
				
				<br clear="all">
			
				<center>
					<input type="submit" name="submitUpdateAllValues" value="Set All Values"></input>
					<input type="text" name="updateAllValues" value="" size="5" maxlength="5"></input>
				</center>
		
			<? } ?>

<? } ?>
	
		</form>
  
	</body>

</html>
