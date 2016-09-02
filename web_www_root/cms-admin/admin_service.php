<?php
	define ('ROOT_PATH', str_replace('cms-admin' , '', realpath(dirname(__FILE__))));
	require_once('../cms-includes/defines.inc.php');
	require_once '../cms-includes/functions.inc.php';
	require_once('../cms-includes/init.inc.php');


	// any custom defines for this site
	if (is_file(ROOT_PATH.'cms-includes/site.defines.inc.php'))
	    require_once '../cms-includes/site.defines.inc.php';

	// any custom functions for this site
	if (is_file(ROOT_PATH.'cms-includes/site.functions.inc.php'))
	    require_once '../cms-includes/site.functions.inc.php';

	// in init.inc.php
	lum_start_admin_rpc();
	lum_setNoCacheHeaders();
	
	$ret = array("success"=>false);	
	
	$user = lum_call('Users', 'isSignedIn');
	
	if (!$user)
	{
		$ret['success'] = true;
		$ret['session_timeout'] = true;
		echo json_encode($ret);
		exit;
	}
	
	$json_data = file_get_contents('php://input');
	$data = json_decode($json_data);	

	// if we have don't have valid JSON data
	// we'll process the POST data instead
	if (!$data)
	{
		$ret['errors'] = 'Invalid JSON data. Make sure to encode any special characters.';
	}	
	else
	{
		$params = array();

		if (is_object($data->params))
		{
			$params = get_object_vars($data->params);
		}
		else
		{
			$params = $data->params;
		}
		
		// decode all of the values
		if ($params)
		{
			if (isset($params['start']))
			{
				$_SESSION['REMEMBER_START'] = $params['start'];
			}

			$params['signed_in'] = array();
			$params['signed_in']['username'] = $user->username;
			$params['signed_in']['user_id'] = $user->user_id;
			$params['signed_in']['user_email'] = $user->user_id;
			$params['signed_in']['name'] = $user->first_name .' '. $user->last_name;
			
			foreach ($params as $param=>$value)
			{
				if ($param == 'signed_in')
					 continue;
					
				if (is_string($value))
				{
					$params[$param] = decodeURI($value);
				}
				elseif (is_array($value))
				{
					for ($i=0;$i<count($params[$param]);$i++)
					{
						$params[$param][$i] = decodeURI($params[$param][$i]);
					}
					
				}
			}
		}

		list($msg, $ret_val) = lum_call($data->plugin, $data->method, $params);
		
		// don't return json just output whatever was returned
		if ($msg == 'no_json')
		{
			echo $msg;
			exit;
		}
		
		if ($ret_val == WEB_SERVICE_ERROR)
		{
			$ret['success'] = false;
			$ret['errors'] = $msg;
			$ret['rows'] = null;
			$ret['num_records'] = 0;
		}
		else
		{
			$ret['success'] = true;
			$ret['rows'] = $msg;
			$ret['num_records'] = $ret_val;
			
		}



//		$ret['user_id'] = $user_id;
	}	
	
	$ret['pid'] = getmypid();
	$ret['remember_start'] = $_SESSION['REMEMBER_START'];
	echo json_encode($ret);
?>
