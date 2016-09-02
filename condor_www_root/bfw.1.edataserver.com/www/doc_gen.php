<?

	// this is needed because headers may get sent prior to us sending the header to the new
	// location which redirects - RSK
	ob_start();
	require_once('automode.1.php');
	$auto_mode = new Auto_Mode();
	$config->mode = $auto_mode->Fetch_Mode($_SERVER['SERVER_NAME']);
	//$config->mode = "LOCAL";
	
	// Required files
	// using config1 for now since doc.1 isn't in the config and I don't want to change on live just yet - rsk
	require_once ('config.php');
	require_once (BFW_MODULE_DIR.'olp/config.php');
	include_once (BFW_CODE_DIR.'server.php');
	include_once (BFW_CODE_DIR.'setup_db.php');
	include_once (BFW_CODE_DIR.'OLP_Applog_Singleton.php');
	include_once('config.4.php');
	include_once('session.8.php');
	
	list($bogus,$prop_short) = split('_', $_GET['database']);

	if ($prop_short && $_GET['application_id'])
	{
		$ent_prop_list = array (
		"PCL" =>"oneclickcash.com",
		"UCL" =>"unitedcashloans.com",
		"CA" =>"ameriloan.com",
		"UFC" =>"usfastcash.com",
		"D1"=>"500fastcash.com"
		);
				
		// applog
		$applog = OLP_Applog_Singleton::Get_Instance(APPLOG_SUBDIRECTORY, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, 'bfw.1.edataserver.com', APPLOG_ROTATE);
		
		// setup_db for olp
		$sql = Setup_DB::Get_Instance('blackbox', $config->mode, $prop_short);
		
		// setup_db for ldb
		$mysql = Setup_DB::Get_Instance('mysql', $config->mode, $prop_short);
		
		// setup session
		$session_id = ($_COOKIE['ssid']) ? $_COOKIE['ssid'] : Create_Session_Id();
		$session = new Session_8(
			$sql,
			$sql->db_info['db'],
			'session',
			$session_id,
			'ssid',
			'gz',
			TRUE
		);
		
		// get application data
		if ($_SESSION['data'] = OLP_MySQL::Doc_Gen($sql, $mysql, $_GET['application_id'], $prop_short))
		{
		
			// set bb winner hack
			$_SESSION['blackbox']['winner'] = $prop_short;
			
			// set application_id
			$_SESSION['transaction_id'] = $_SESSION['application_id'] = $_SESSION['data']['application_id'];
					
			// setup config
			$_SESSION['config'] = Config_4::Get_Site_Config($_SESSION['data']['license_key'], $_SESSION['data']['promo_id'], $_SESSION['data']['promo_sub_code'], 'blackbox.address');
			
			// prop_short hack
			$_SESSION['config']->property_short = $_SESSION['config']->bb_force_winner = strtoupper($prop_short);
			
			$site_mode = (strtoupper($config->mode) == 'RC') ? 'rc.' : null;
			header("Location: http://". $site_mode . $ent_prop_list[$_SESSION['config']->property_short] . "/?page=reprint_docs&unique_id=".$session_id);
		}
		else 
		{
			ob_end_flush();
			echo ("There are no legal docs for application id {$_GET['application_id']} in company <b>{$prop_short}</b>. Try the <b>New</b> link in ecash");
		}	
	} else {
		
		echo ("There are no legal docs for this application. Try the <b>New</b> link in ecash.");
	}
	
	exit;

function display_form()
{
	global $prop_short;
	
	?>
	<br><br>
	<form method=get>
		<table width=400>
			<tr>
				<td>Choose a Company</td>
				<td>
				<select name=database>
					<option value=1_ca <? echo ($prop_short == 'ca') ? 'selected' : null?>>CA</option>
					<option value=1_d1 <? echo ($prop_short == 'd1') ? 'selected' : null?>>D1</option>
					<option value=1_pcl <? echo ($prop_short == 'pcl') ? 'selected' : null?>>PCL</option>
					<option value=1_ucl <? echo ($prop_short == 'ucl') ? 'selected' : null?>>UCL</option>
					<option value=1_ufc <? echo ($prop_short == 'ufc') ? 'selected' : null?>>UFC</option>
				</select>
				</td>
			</tr>
			<tr>
				<td>Application ID</td>
				<td><input type=text name=application_id value='<?=$_GET['application_id']?>'></td>
			</tr>
			<tr>
				<td></td>
				<td><input type='submit' ></td>
			</tr>
		</table>
	</form>
	<?
}

function Create_Session_Id()
{
	return md5(microtime());
}
