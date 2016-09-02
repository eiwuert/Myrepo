<?php

	/**
	 * This includes all of our common functions for the CMS
	 * 
	 * @package Luminance v3.1
	 */

	function lum_checkCorePlugins()
	{
		$core = array('Pages', 'Languages', 'Strings');
		
		foreach ($core as $plugin)
		{
			if (!is_file(PLUGINS_PATH . $plugin.'/luminance.plugin.class.php'))
			{
				die("Core Plugin: '$plugin' is missing. Aborting.");
			}
		}
		
	}
	
	function lum_filterInput($str)
	{
		return preg_replace('`[^\-\_0-9a-zA-Z]`','', $str);
	}
	
	function lum_getCurrentLanguage()
	{
		global $lumRegistry;
		return $lumRegistry->language->lang_code;
	}
	
	function lum_isDefaultLanguage()
	{
		global $lumRegistry;
		return $lumRegistry->language->is_default;
	}	
	
	function lum_setDateFormat()
	{
		$date_format = plugin_call('StringEditor', 'getStringByCode', array('string_code'=>'[DATE_FORMAT]', 'lang_code'=>'en'));
		$format = str_replace('dd', 'd', $date_format);
		$format = str_replace('mm', 'm', $format);
		$format = str_replace('yyyy', 'Y', $format);
		define('DATE_FORMAT', $format);		
	}	

	//our XSS safe echo function
	function lum_e($string)
	{
		echo htmlentities($string, ENT_QUOTES, 'UTF-8') ;
	} 

	function lum_redirect($redirect = null, $redirect_301 = false)
	{
		if (!$redirect)
		{
			$redirect = "/";
			if (isset($_REQUEST['redirect']))
				$redirect = $_REQUEST['redirect'];
		}
	
		if ($redirect_301)
		{
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: $redirect");
			exit();
		}
		header("location: $redirect");
		exit;
	}

	function lum_go404($url)
	{
		header("HTTP/1.0 404 Not Found");
		echo "<html><head><title>Page Missing</title></head><h1>Error 404</h1><p>Page missing</p></body></html>";
	}

	// if we use defines that are serialized arrays
	// this function makes it easy to retrieve elements from the array
	function lum_carray($const=null, $element=null)
	{
		if (!$const)
			return null;
		
		if (strpos($const, "{") === false)
		{
			if (gettype($const) == "string")
			{
				if (defined($const))
				{
					$const = constant($const);
					if (!$const)
					{
						return "";
					}
				}
				else 
				{
					return "";
				}
			}
		}
				
		$arr = unserialize($const);
		if ($arr == null)
			return null;
	
		if ($element === null)
			return $arr;
		else
		{
			if (isset($arr[$element]))
				return $arr[$element];
			else 
				return "";
		}
	}

	function lum_requireLogin()
	{
		return lum_isLoggedIn(true);
	}
	
	function lum_isLoggedIn($require_login = false)
	{
		global $lumRegistry;
		
		
		$gAuth = new LuminanceAuth($gDB);
		$user = $gAuth->CheckAuth();
		if (!$user)
		{
			if ($gAuth->change_password)
				lum_logIn(true);
				
			if ($require_login)
				lum_logIn();
				
			return false;
		}
	
		return $user;
	}
	
	function lum_logIn($bChangePassword = false)
	{
		if ($bChangePassword)
		{
			header('Location: /sign-in?change_password=1&redirect='.$_REQUEST['redirect']);
		}
		else
		{
			header('Location: /sign-in?redirect='.$_SERVER['REQUEST_URI']);
		}
		exit;
	}

	function lum_pbkdf2( $p, $s, $c, $kl, $a = 'sha256' ) {
	
		$hl = strlen(hash($a, null, true));	# Hash length
		$kb = ceil($kl / $hl);				# Key blocks to compute
		$dk = '';							# Derived key
	
		# Create key
		for ( $block = 1; $block <= $kb; $block ++ ) {
	
			# Initial hash for this block
			$ib = $h = hash_hmac($a, $s . pack('N', $block), $p, true);
	
			# Perform block iterations
			for ( $i = 1; $i < $c; $i ++ ) 
	
				# XOR each iterate
				$ib ^= ($h = hash_hmac($a, $h, $p, true));
	
			$dk .= $ib; # Append iterated block
		}
	
		# Return derived key of correct length
		return substr($dk, 0, $kl);
	}	


	function lum_hasPermission($perm, $permissions = null)
	{
		return true;
		global $gAuth;
		$user = lum_isLoggedIn();
		return $gAuth->HasPermission($perm, $permissions);
	}

   	function lum_clearPageCache()
   	{
		$cache_path = lum_getSitePath().'cms-htmlcache';
		$lang_path = lum_getSitePath().'cms-lang';

   		$c = new LuminanceCache($cache_path, _USE_DEBUG);
   		$c->clear_path($cache_path);
   		$c = new LuminanceCache($lang_path, _USE_DEBUG);
   		$c->clear_path($lang_path);
   	}

	// an easy way to resort associative arrays based on an array element
	function lum_namedRecordsSort($named_recs, $order_by, $rev=false, $flags=0)
	{
	    $named_hash = array();
	     foreach($named_recs as $key=>$fields)
	             $named_hash["$key"] = $fields[$order_by];
	 
	    if($reverse) arsort($named_hash,$flags=0) ;
	    else asort($named_hash, $flags=0);
	 
	    $sorted_records = array();
	    foreach($named_hash as $key=>$val)
	           $sorted_records["$key"]= $named_recs[$key];
	
		return $sorted_records;
	}

	function lum_showError($error)
	{
		return array($error, WEB_SERVICE_ERROR);
	}
	
	function lum_showSuccess($records = array(), $num = 0, $return_raw = false)
	{
		if ($return_raw)
			return $records;
	
		return array($records, $num);
	}

	function lum_htmlEncode($var, $double_encode = true)
	{
		$var = utf8_encode($var);
		$str = htmlentities($var, ENT_QUOTES, 'UTF-8') ;
		if (!$double_encode)
			$str = str_replace(array("&lt;", "&gt;", '&lt;', '&gt;'), array("<", ">",'<','>'), $str);
 
		return $str; 
	}
	
	function lum_htmlDecode($var)
	{	
		return html_entity_decode($var, ENT_QUOTES, 'UTF-8') ;
	}	
	
	function lum_prepareInputForEmail($str)
	{
		return stripslashes(utf8_decode(lum_htmlDecode($str)));
	}
	
	function lum_getThemeCssUrl($common = true)
	{
		if ($common)
		{
			echo THEME_URL.'css';
		}
		else 
		{
			echo THEME_URL.'css/'.lum_getCurrentLanguage();
		}
	}
	
	function lum_getThemeImageUrl($common = true, $return = false)
	{
		if ($common)
		{
			if ($return)
				return THEME_URL.'images';
			else
				echo THEME_URL.'images';
		}
		else 
		{
			if ($return)
				return THEME_URL.'images/'.lum_getCurrentLanguage();
			else
				echo THEME_URL.'images/'.lum_getCurrentLanguage();
		}
	}
		
	function lum_getThemeJsUrl($common = true)
	{
		if ($common)
		{
			echo THEME_URL.'js';
		}
		else 
		{
			echo THEME_URL.'js/'.lum_getCurrentLanguage();
		}
	}
	
	/**
	Validate an email address.
	Provide email address (raw input)
	Returns true if the email address has the email 
	address format and the domain exists.
	*/
	function lum_validEmail($email)
	{
	   $isValid = true;
	   $atIndex = strrpos($email, "@");
	   if (is_bool($atIndex) && !$atIndex)
	   {
	      $isValid = false;
	   }
	   else
	   {
	      $domain = substr($email, $atIndex+1);
	      $local = substr($email, 0, $atIndex);
	      $localLen = strlen($local);
	      $domainLen = strlen($domain);
	      if ($localLen < 1 || $localLen > 64)
	      {
	         // local part length exceeded
	         $isValid = false;
	      }
	      else if ($domainLen < 1 || $domainLen > 255)
	      {
	         // domain part length exceeded
	         $isValid = false;
	      }
	      else if ($local[0] == '.' || $local[$localLen-1] == '.')
	      {
	         // local part starts or ends with '.'
	         $isValid = false;
	      }
	      else if (preg_match('/\\.\\./', $local))
	      {
	         // local part has two consecutive dots
	         $isValid = false;
	      }
	      else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))
	      {
	         // character not valid in domain part
	         $isValid = false;
	      }
	      else if (preg_match('/\\.\\./', $domain))
	      {
	         // domain part has two consecutive dots
	         $isValid = false;
	      }
	      else if
	(!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
	                 str_replace("\\\\","",$local)))
	      {
	         // character not valid in local part unless 
	         // local part is quoted
	         if (!preg_match('/^"(\\\\"|[^"])+"$/',
	             str_replace("\\\\","",$local)))
	         {
	            $isValid = false;
	         }
	      }
	      if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A")))
	      {
	         // domain not found in DNS
	         $isValid = false;
	      }
	   }
	   return $isValid;
	}

	function lum_sendEmail($from, $fname, $to, $tname, $body, $subject, $bcc = null, $is_html = false)	
	{
		$mail 		= new PHPMailer();

		if (!is_array($to))
		{
			if (!lum_validEmail($to))
				return false;
			
			$mail->AddAddress($to, $tname);
		}
		else
		{
			foreach ($to as $address=>$name)
			{
				if (!lum_validEmail($address))
					return false;
				
				$mail->AddAddress($address, $name);
			}
		}
		
		$mail->From     = $from;
		$mail->FromName = $fname;
		$mail->Subject  = $subject;
		$mail->Body	= $body;
		$mail->Host     = "";
		$mail->Mailer   = "smtp";
		$mail->IsHTML($is_html);    
		
		if ($bcc && is_array($bcc))
		{
			foreach ($bcc as $address=>$name)
			{
				$mail->AddBCC($address, $name);				
			}
		}			

		return $mail->Send();
 	}	
 	
	function lum_sendEmailGoDaddy($from, $fname, $to, $tname, $body, $subject, $bcc = null, $is_html = false)
	{
	
		if (!lum_validEmail($to))
			return false;

		$headers  = 'MIME-Version: 1.0' . "\r\n";			
		if ($is_html)
		{
			$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";	
		}
		else 
		{
			$headers .= 'Content-type: text/plain; charset=utf-8' . "\r\n";	
		}
		
		$headers .= "To: $tname <$to>\r\n";
		$headers .= "From: $fname <$from>\r\n";
		
		if ($bcc && is_array($bcc))
		{
			foreach ($bcc as $address=>$name)
			{
				$headers .= "Bcc: $name <$address>\r\n";				
			}
		}
		
		return mail($to, $subject, $body, $headers);
	}

	// === Logging functions
	
	function lum_logMe($msg)
	{
		if (_USE_DEBUG)
		{
			$filename = _MAIN_LOG;
	
			// get the caller's class and functions
			$backtrace = debug_backtrace(); 
	
			$script = explode('/', $_SERVER['SCRIPT_FILENAME']);
			$script = $script[count($script)-1];
	
			$pid = getmypid();
	
			$caller = '{ ' .  $pid . '::' . $script . ' ';
	
			// only get the class if one exists!
			if (isset($backtrace[1]['class']) && $backtrace[1]['class'] != '')
				$caller .= $backtrace[1]['class'] .'::';
	
			// only get the function if one exists!
			if (isset($backtrace[1]))
				$caller .= $backtrace[1]['function'] . ' } ';
			else
				$caller .= ' } ';
	
			// add the caller to the message
			$msg = $caller . $msg . "\r\n";
	
			lum_logSomething($msg, $filename);
		}
	}
	
	function lum_logVar($v)
	{
		if (_USE_DEBUG)
		{
			ob_start(); 
			var_dump($v);
			$msg = ob_get_contents(); 
			ob_end_clean(); 
	
			// get the caller's class and functions
			$backtrace = debug_backtrace(); 
	
			$script = explode('/', $_SERVER['SCRIPT_FILENAME']);
			$script = $script[count($script)-1];
	
			$pid = getmypid();
	
			$caller = '{ ' .  $pid . '::' . $script . ' ';
	
			// only get the class if one exists!
			if (isset($backtrace[1]['class']) && $backtrace[1]['class'] != '')
				$caller .= $backtrace[1]['class'] .'::';
	
			// only get the function if one exists!
			if (isset($backtrace[1]))
				$caller .= $backtrace[1]['function'] . ' } ';
			else
				$caller .= ' } ';
	
			// add the caller to the message
			$msg = $caller . $msg . "\r\n";
	
			$filename = _MAIN_LOG;
	
			lum_logSomething($msg, $filename);
		}
	}
	
	function lum_logSomething($msg, $filename)
	{
		if (_USE_DEBUG)
		{
			if ($handle = fopen($filename, 'a'))
			{
				$msg .= "\n\n";
				fwrite($handle, $msg);
				fclose($handle);
			}
			
			if (_ENABLE_LOUD_DEBUG)
				lum_e($msg);
		}
	}

	function lum_logError($msg)
	{
		if (_ENABLE_LOUD_DEBUG)
			lum_e($msg);
			
		error_log($msg, 3, _ERROR_LOG);
	}
	
	
	// === performance functions
	
	function lum_checkMemoryUsage()
	{
		return;
		$mem_usage = memory_get_peak_usage();
		
		//if (_USE_DEBUG)
			//lum_logMe("Memory usage is: $mem_usage");
		
		if (_DISPLAY_MEM_USAGE)
			echo "Memory usage is: $mem_usage";
	}
	
	function lum_checkDbQueries()
	{
		if (_DISPLAY_QUERIES)
		{
			echo 'Number of queries is: '.$_SESSION['NUM_SQL_QUERIES'].' - ';
			echo 'Number of failed queries is: '.$_SESSION['NUM_SQL_QUERIES_FAILED'];
		}
			
		unset($_SESSION['NUM_SQL_QUERIES']);		
	}

	function lum_getFormHash($prefix = '')
	{
		$_SESSION[$prefix.'form_hash'] = sha1(uniqid(srand(time())));
		return '<input type="hidden" name="form_hash" value="'.$_SESSION[$prefix.'form_hash'].'"/>';
	}
	
	function lum_verifyFormHash($prefix = '')
	{
		if (!isset($_REQUEST['form_hash']))
		{
		    return false;
		}
		
		if ($_REQUEST['form_hash'] != $_SESSION[$prefix.'form_hash'])
		{
		    return false;
		}
		
		return true;
	}

	function lum_errorHandler($errno, $errstr, $errfile, $errline) 
	{
		$time = date('Y-m-d h:i:s');
		$errstr_echo = $time . "<br>";
		$errstr_echo .= "Fatal error in line $errline of file" . $errfile . "<br>";
		$errstr_echo .= "- " . $errstr . "<br><br>";
        
		if ($errno == E_STRICT)
			return;		
					
		if (strpos($errstr, 'empty IV') !== false)
		{
			return;
		}
		
		if (strpos($errstr, 'Undefined') !== false)
		{
			return; //don't show notices for now about undefined indexes.
		}
		
		$time = date("r", time());
	
		$errstr_echo = $time . "<br>";
		$errstr_echo .= "Fatal error in line $errline of file" . $errfile . "<br>";
		$errstr_echo .= "- " . $errstr . "<br><br>";
		
		$errstr_log = $time . "\r\n";
		$errstr_log .= "Fatal error in line $errline of file" . $errfile . "\r\n";
		$errstr_log .= "- " . $errstr . "\r\n\r\n";
	
		if (mysql_errno() || mysql_error())
		{
			$MYSQL_ERRNO = mysql_errno();
            $MYSQL_ERROR = mysql_error();
            $errstr_log .= "MySQL error: $MYSQL_ERRNO : $MYSQL_ERROR";
		}
		lum_logError($errstr_log);
	}
	
function encodeURIComponent($string) {
   $result = "";
   for ($i = 0; $i < strlen($string); $i++) {
      $result .= encodeURIComponentbycharacter(urlencode($string[$i]));
   }
   return $result;
}

function encodeURIComponentbycharacter($char) {
   if ($char == "+") { return "%20"; }
   if ($char == "%21") { return "!"; }
   if ($char == "%27") { return '"'; }
   if ($char == "%28") { return "("; }
   if ($char == "%29") { return ")"; }
   if ($char == "%2A") { return "*"; }
   if ($char == "%7E") { return "~"; }
   if ($char == "%80") { return "%E2%82%AC"; }
   if ($char == "%81") { return "%C2%81"; }
   if ($char == "%82") { return "%E2%80%9A"; }
   if ($char == "%83") { return "%C6%92"; }
   if ($char == "%84") { return "%E2%80%9E"; }
   if ($char == "%85") { return "%E2%80%A6"; }
   if ($char == "%86") { return "%E2%80%A0"; }
   if ($char == "%87") { return "%E2%80%A1"; }
   if ($char == "%88") { return "%CB%86"; }
   if ($char == "%89") { return "%E2%80%B0"; }
   if ($char == "%8A") { return "%C5%A0"; }
   if ($char == "%8B") { return "%E2%80%B9"; }
   if ($char == "%8C") { return "%C5%92"; }
   if ($char == "%8D") { return "%C2%8D"; }
   if ($char == "%8E") { return "%C5%BD"; }
   if ($char == "%8F") { return "%C2%8F"; }
   if ($char == "%90") { return "%C2%90"; }
   if ($char == "%91") { return "%E2%80%98"; }
   if ($char == "%92") { return "%E2%80%99"; }
   if ($char == "%93") { return "%E2%80%9C"; }
   if ($char == "%94") { return "%E2%80%9D"; }
   if ($char == "%95") { return "%E2%80%A2"; }
   if ($char == "%96") { return "%E2%80%93"; }
   if ($char == "%97") { return "%E2%80%94"; }
   if ($char == "%98") { return "%CB%9C"; }
   if ($char == "%99") { return "%E2%84%A2"; }
   if ($char == "%9A") { return "%C5%A1"; }
   if ($char == "%9B") { return "%E2%80%BA"; }
   if ($char == "%9C") { return "%C5%93"; }
   if ($char == "%9D") { return "%C2%9D"; }
   if ($char == "%9E") { return "%C5%BE"; }
   if ($char == "%9F") { return "%C5%B8"; }
   if ($char == "%A0") { return "%C2%A0"; }
   if ($char == "%A1") { return "%C2%A1"; }
   if ($char == "%A2") { return "%C2%A2"; }
   if ($char == "%A3") { return "%C2%A3"; }
   if ($char == "%A4") { return "%C2%A4"; }
   if ($char == "%A5") { return "%C2%A5"; }
   if ($char == "%A6") { return "%C2%A6"; }
   if ($char == "%A7") { return "%C2%A7"; }
   if ($char == "%A8") { return "%C2%A8"; }
   if ($char == "%A9") { return "%C2%A9"; }
   if ($char == "%AA") { return "%C2%AA"; }
   if ($char == "%AB") { return "%C2%AB"; }
   if ($char == "%AC") { return "%C2%AC"; }
   if ($char == "%AD") { return "%C2%AD"; }
   if ($char == "%AE") { return "%C2%AE"; }
   if ($char == "%AF") { return "%C2%AF"; }
   if ($char == "%B0") { return "%C2%B0"; }
   if ($char == "%B1") { return "%C2%B1"; }
   if ($char == "%B2") { return "%C2%B2"; }
   if ($char == "%B3") { return "%C2%B3"; }
   if ($char == "%B4") { return "%C2%B4"; }
   if ($char == "%B5") { return "%C2%B5"; }
   if ($char == "%B6") { return "%C2%B6"; }
   if ($char == "%B7") { return "%C2%B7"; }
   if ($char == "%B8") { return "%C2%B8"; }
   if ($char == "%B9") { return "%C2%B9"; }
   if ($char == "%BA") { return "%C2%BA"; }
   if ($char == "%BB") { return "%C2%BB"; }
   if ($char == "%BC") { return "%C2%BC"; }
   if ($char == "%BD") { return "%C2%BD"; }
   if ($char == "%BE") { return "%C2%BE"; }
   if ($char == "%BF") { return "%C2%BF"; }
   if ($char == "%C0") { return "%C3%80"; }
   if ($char == "%C1") { return "%C3%81"; }
   if ($char == "%C2") { return "%C3%82"; }
   if ($char == "%C3") { return "%C3%83"; }
   if ($char == "%C4") { return "%C3%84"; }
   if ($char == "%C5") { return "%C3%85"; }
   if ($char == "%C6") { return "%C3%86"; }
   if ($char == "%C7") { return "%C3%87"; }
   if ($char == "%C8") { return "%C3%88"; }
   if ($char == "%C9") { return "%C3%89"; }
   if ($char == "%CA") { return "%C3%8A"; }
   if ($char == "%CB") { return "%C3%8B"; }
   if ($char == "%CC") { return "%C3%8C"; }
   if ($char == "%CD") { return "%C3%8D"; }
   if ($char == "%CE") { return "%C3%8E"; }
   if ($char == "%CF") { return "%C3%8F"; }
   if ($char == "%D0") { return "%C3%90"; }
   if ($char == "%D1") { return "%C3%91"; }
   if ($char == "%D2") { return "%C3%92"; }
   if ($char == "%D3") { return "%C3%93"; }
   if ($char == "%D4") { return "%C3%94"; }
   if ($char == "%D5") { return "%C3%95"; }
   if ($char == "%D6") { return "%C3%96"; }
   if ($char == "%D7") { return "%C3%97"; }
   if ($char == "%D8") { return "%C3%98"; }
   if ($char == "%D9") { return "%C3%99"; }
   if ($char == "%DA") { return "%C3%9A"; }
   if ($char == "%DB") { return "%C3%9B"; }
   if ($char == "%DC") { return "%C3%9C"; }
   if ($char == "%DD") { return "%C3%9D"; }
   if ($char == "%DE") { return "%C3%9E"; }
   if ($char == "%DF") { return "%C3%9F"; }
   if ($char == "%E0") { return "%C3%A0"; }
   if ($char == "%E1") { return "%C3%A1"; }
   if ($char == "%E2") { return "%C3%A2"; }
   if ($char == "%E3") { return "%C3%A3"; }
   if ($char == "%E4") { return "%C3%A4"; }
   if ($char == "%E5") { return "%C3%A5"; }
   if ($char == "%E6") { return "%C3%A6"; }
   if ($char == "%E7") { return "%C3%A7"; }
   if ($char == "%E8") { return "%C3%A8"; }
   if ($char == "%E9") { return "%C3%A9"; }
   if ($char == "%EA") { return "%C3%AA"; }
   if ($char == "%EB") { return "%C3%AB"; }
   if ($char == "%EC") { return "%C3%AC"; }
   if ($char == "%ED") { return "%C3%AD"; }
   if ($char == "%EE") { return "%C3%AE"; }
   if ($char == "%EF") { return "%C3%AF"; }
   if ($char == "%F0") { return "%C3%B0"; }
   if ($char == "%F1") { return "%C3%B1"; }
   if ($char == "%F2") { return "%C3%B2"; }
   if ($char == "%F3") { return "%C3%B3"; }
   if ($char == "%F4") { return "%C3%B4"; }
   if ($char == "%F5") { return "%C3%B5"; }
   if ($char == "%F6") { return "%C3%B6"; }
   if ($char == "%F7") { return "%C3%B7"; }
   if ($char == "%F8") { return "%C3%B8"; }
   if ($char == "%F9") { return "%C3%B9"; }
   if ($char == "%FA") { return "%C3%BA"; }
   if ($char == "%FB") { return "%C3%BB"; }
   if ($char == "%FC") { return "%C3%BC"; }
   if ($char == "%FD") { return "%C3%BD"; }
   if ($char == "%FE") { return "%C3%BE"; }
   if ($char == "%FF") { return "%C3%BF"; }
   return $char;
}

function decodeURIComponent($string) {
   $result = "";
   for ($i = 0; $i < strlen($string); $i++) {
       $decstr = "";
       for ($p = 0; $p <= 8; $p++) {
          $decstr .= $string[$i+$p];
       } 
       list($decodedstr, $num) = decodeURIComponentbycharacter($decstr);
       $result .= urldecode($decodedstr);
       $i += $num ;
   }
   return $result;
}

function decodeURIComponentbycharacter($str) {

   $char = $str;
   
   if ($char == "%E2%82%AC") { return array("%80", 8); }
   if ($char == "%E2%80%9A") { return array("%82", 8); }
   if ($char == "%E2%80%9E") { return array("%84", 8); }
   if ($char == "%E2%80%A6") { return array("%85", 8); }
   if ($char == "%E2%80%A0") { return array("%86", 8); }
   if ($char == "%E2%80%A1") { return array("%87", 8); }
   if ($char == "%E2%80%B0") { return array("%89", 8); }
   if ($char == "%E2%80%B9") { return array("%8B", 8); }
   if ($char == "%E2%80%98") { return array("%91", 8); }
   if ($char == "%E2%80%99") { return array("%92", 8); }
   if ($char == "%E2%80%9C") { return array("%93", 8); }
   if ($char == "%E2%80%9D") { return array("%94", 8); }
   if ($char == "%E2%80%A2") { return array("%95", 8); }
   if ($char == "%E2%80%93") { return array("%96", 8); }
   if ($char == "%E2%80%94") { return array("%97", 8); }
   if ($char == "%E2%84%A2") { return array("%99", 8); }
   if ($char == "%E2%80%BA") { return array("%9B", 8); }

   $char = substr($str, 0, 6);

   if ($char == "%C2%81") { return array("%81", 5); }
   if ($char == "%C6%92") { return array("%83", 5); }
   if ($char == "%CB%86") { return array("%88", 5); }
   if ($char == "%C5%A0") { return array("%8A", 5); }
   if ($char == "%C5%92") { return array("%8C", 5); }
   if ($char == "%C2%8D") { return array("%8D", 5); }
   if ($char == "%C5%BD") { return array("%8E", 5); }
   if ($char == "%C2%8F") { return array("%8F", 5); }
   if ($char == "%C2%90") { return array("%90", 5); }
   if ($char == "%CB%9C") { return array("%98", 5); }
   if ($char == "%C5%A1") { return array("%9A", 5); }
   if ($char == "%C5%93") { return array("%9C", 5); }
   if ($char == "%C2%9D") { return array("%9D", 5); }
   if ($char == "%C5%BE") { return array("%9E", 5); }
   if ($char == "%C5%B8") { return array("%9F", 5); }
   if ($char == "%C2%A0") { return array("%A0", 5); }
   if ($char == "%C2%A1") { return array("%A1", 5); }
   if ($char == "%C2%A2") { return array("%A2", 5); }
   if ($char == "%C2%A3") { return array("%A3", 5); }
   if ($char == "%C2%A4") { return array("%A4", 5); }
   if ($char == "%C2%A5") { return array("%A5", 5); }
   if ($char == "%C2%A6") { return array("%A6", 5); }
   if ($char == "%C2%A7") { return array("%A7", 5); }
   if ($char == "%C2%A8") { return array("%A8", 5); }
   if ($char == "%C2%A9") { return array("%A9", 5); }
   if ($char == "%C2%AA") { return array("%AA", 5); }
   if ($char == "%C2%AB") { return array("%AB", 5); }
   if ($char == "%C2%AC") { return array("%AC", 5); }
   if ($char == "%C2%AD") { return array("%AD", 5); }
   if ($char == "%C2%AE") { return array("%AE", 5); }
   if ($char == "%C2%AF") { return array("%AF", 5); }
   if ($char == "%C2%B0") { return array("%B0", 5); }
   if ($char == "%C2%B1") { return array("%B1", 5); }
   if ($char == "%C2%B2") { return array("%B2", 5); }
   if ($char == "%C2%B3") { return array("%B3", 5); }
   if ($char == "%C2%B4") { return array("%B4", 5); }
   if ($char == "%C2%B5") { return array("%B5", 5); }
   if ($char == "%C2%B6") { return array("%B6", 5); }
   if ($char == "%C2%B7") { return array("%B7", 5); }
   if ($char == "%C2%B8") { return array("%B8", 5); }
   if ($char == "%C2%B9") { return array("%B9", 5); }
   if ($char == "%C2%BA") { return array("%BA", 5); }
   if ($char == "%C2%BB") { return array("%BB", 5); }
   if ($char == "%C2%BC") { return array("%BC", 5); }
   if ($char == "%C2%BD") { return array("%BD", 5); }
   if ($char == "%C2%BE") { return array("%BE", 5); }
   if ($char == "%C2%BF") { return array("%BF", 5); }
   if ($char == "%C3%80") { return array("%C0", 5); }
   if ($char == "%C3%81") { return array("%C1", 5); }
   if ($char == "%C3%82") { return array("%C2", 5); }
   if ($char == "%C3%83") { return array("%C3", 5); }
   if ($char == "%C3%84") { return array("%C4", 5); }
   if ($char == "%C3%85") { return array("%C5", 5); }
   if ($char == "%C3%86") { return array("%C6", 5); }
   if ($char == "%C3%87") { return array("%C7", 5); }
   if ($char == "%C3%88") { return array("%C8", 5); }
   if ($char == "%C3%89") { return array("%C9", 5); }
   if ($char == "%C3%8A") { return array("%CA", 5); }
   if ($char == "%C3%8B") { return array("%CB", 5); }
   if ($char == "%C3%8C") { return array("%CC", 5); }
   if ($char == "%C3%8D") { return array("%CD", 5); }
   if ($char == "%C3%8E") { return array("%CE", 5); }
   if ($char == "%C3%8F") { return array("%CF", 5); }
   if ($char == "%C3%90") { return array("%D0", 5); }
   if ($char == "%C3%91") { return array("%D1", 5); }
   if ($char == "%C3%92") { return array("%D2", 5); }
   if ($char == "%C3%93") { return array("%D3", 5); }
   if ($char == "%C3%94") { return array("%D4", 5); }
   if ($char == "%C3%95") { return array("%D5", 5); }
   if ($char == "%C3%96") { return array("%D6", 5); }
   if ($char == "%C3%97") { return array("%D7", 5); }
   if ($char == "%C3%98") { return array("%D8", 5); }
   if ($char == "%C3%99") { return array("%D9", 5); }
   if ($char == "%C3%9A") { return array("%DA", 5); }
   if ($char == "%C3%9B") { return array("%DB", 5); }
   if ($char == "%C3%9C") { return array("%DC", 5); }
   if ($char == "%C3%9D") { return array("%DD", 5); }
   if ($char == "%C3%9E") { return array("%DE", 5); }
   if ($char == "%C3%9F") { return array("%DF", 5); }
   if ($char == "%C3%A0") { return array("%E0", 5); }
   if ($char == "%C3%A1") { return array("%E1", 5); }
   if ($char == "%C3%A2") { return array("%E2", 5); }
   if ($char == "%C3%A3") { return array("%E3", 5); }
   if ($char == "%C3%A4") { return array("%E4", 5); }
   if ($char == "%C3%A5") { return array("%E5", 5); }
   if ($char == "%C3%A6") { return array("%E6", 5); }
   if ($char == "%C3%A7") { return array("%E7", 5); }
   if ($char == "%C3%A8") { return array("%E8", 5); }
   if ($char == "%C3%A9") { return array("%E9", 5); }
   if ($char == "%C3%AA") { return array("%EA", 5); }
   if ($char == "%C3%AB") { return array("%EB", 5); }
   if ($char == "%C3%AC") { return array("%EC", 5); }
   if ($char == "%C3%AD") { return array("%ED", 5); }
   if ($char == "%C3%AE") { return array("%EE", 5); }
   if ($char == "%C3%AF") { return array("%EF", 5); }
   if ($char == "%C3%B0") { return array("%F0", 5); }
   if ($char == "%C3%B1") { return array("%F1", 5); }
   if ($char == "%C3%B2") { return array("%F2", 5); }
   if ($char == "%C3%B3") { return array("%F3", 5); }
   if ($char == "%C3%B4") { return array("%F4", 5); }
   if ($char == "%C3%B5") { return array("%F5", 5); }
   if ($char == "%C3%B6") { return array("%F6", 5); }
   if ($char == "%C3%B7") { return array("%F7", 5); }
   if ($char == "%C3%B8") { return array("%F8", 5); }
   if ($char == "%C3%B9") { return array("%F9", 5); }
   if ($char == "%C3%BA") { return array("%FA", 5); }
   if ($char == "%C3%BB") { return array("%FB", 5); }
   if ($char == "%C3%BC") { return array("%FC", 5); }
   if ($char == "%C3%BD") { return array("%FD", 5); }
   if ($char == "%C3%BE") { return array("%FE", 5); }
   if ($char == "%C3%BF") { return array("%FF", 5); }
   
   $char = substr($str, 0, 3);
   if ($char == "%20") { return array("+", 2); }
   
   $char = substr($str, 0, 1);
   
   if ($char == "!") { return array("%21", 0); }
   if ($char == "\"") { return array("%27", 0); }
   if ($char == "(") { return array("%28", 0); }
   if ($char == ")") { return array("%29", 0); }
   if ($char == "*") { return array("%2A", 0); }
   if ($char == "~") { return array("%7E", 0); }

   if ($char == "%") {
      return array(substr($str, 0, 3), 2);
   } else {
      return array($char, 0);
   }
}

function encodeURI($string) {
   $result = "";
   for ($i = 0; $i < strlen($string); $i++) {
      $result .= encodeURIbycharacter(urlencode($string[$i]));
   }
   return $result;
}

function encodeURIbycharacter($char) {
   if ($char == "+") { return "%20"; }
   if ($char == "%21") { return "!"; }
   if ($char == "%23") { return "#"; }
   if ($char == "%24") { return "$"; }
   if ($char == "%26") { return "&"; }
   if ($char == "%27") { return "\""; }
   if ($char == "%28") { return "("; }
   if ($char == "%29") { return ")"; }
   if ($char == "%2A") { return "*"; }
   if ($char == "%2B") { return "+"; }
   if ($char == "%2C") { return ","; }
   if ($char == "%2F") { return "/"; }
   if ($char == "%3A") { return ":"; }
   if ($char == "%3B") { return ";"; }
   if ($char == "%3D") { return "="; }
   if ($char == "%3F") { return "?"; }
   if ($char == "%40") { return "@"; }
   if ($char == "%7E") { return "~"; }
   if ($char == "%80") { return "%E2%82%AC"; }
   if ($char == "%81") { return "%C2%81"; }
   if ($char == "%82") { return "%E2%80%9A"; }
   if ($char == "%83") { return "%C6%92"; }
   if ($char == "%84") { return "%E2%80%9E"; }
   if ($char == "%85") { return "%E2%80%A6"; }
   if ($char == "%86") { return "%E2%80%A0"; }
   if ($char == "%87") { return "%E2%80%A1"; }
   if ($char == "%88") { return "%CB%86"; }
   if ($char == "%89") { return "%E2%80%B0"; }
   if ($char == "%8A") { return "%C5%A0"; }
   if ($char == "%8B") { return "%E2%80%B9"; }
   if ($char == "%8C") { return "%C5%92"; }
   if ($char == "%8D") { return "%C2%8D"; }
   if ($char == "%8E") { return "%C5%BD"; }
   if ($char == "%8F") { return "%C2%8F"; }
   if ($char == "%90") { return "%C2%90"; }
   if ($char == "%91") { return "%E2%80%98"; }
   if ($char == "%92") { return "%E2%80%99"; }
   if ($char == "%93") { return "%E2%80%9C"; }
   if ($char == "%94") { return "%E2%80%9D"; }
   if ($char == "%95") { return "%E2%80%A2"; }
   if ($char == "%96") { return "%E2%80%93"; }
   if ($char == "%97") { return "%E2%80%94"; }
   if ($char == "%98") { return "%CB%9C"; }
   if ($char == "%99") { return "%E2%84%A2"; }
   if ($char == "%9A") { return "%C5%A1"; }
   if ($char == "%9B") { return "%E2%80%BA"; }
   if ($char == "%9C") { return "%C5%93"; }
   if ($char == "%9D") { return "%C2%9D"; }
   if ($char == "%9E") { return "%C5%BE"; }
   if ($char == "%9F") { return "%C5%B8"; }
   if ($char == "%A0") { return "%C2%A0"; }
   if ($char == "%A1") { return "%C2%A1"; }
   if ($char == "%A2") { return "%C2%A2"; }
   if ($char == "%A3") { return "%C2%A3"; }
   if ($char == "%A4") { return "%C2%A4"; }
   if ($char == "%A5") { return "%C2%A5"; }
   if ($char == "%A6") { return "%C2%A6"; }
   if ($char == "%A7") { return "%C2%A7"; }
   if ($char == "%A8") { return "%C2%A8"; }
   if ($char == "%A9") { return "%C2%A9"; }
   if ($char == "%AA") { return "%C2%AA"; }
   if ($char == "%AB") { return "%C2%AB"; }
   if ($char == "%AC") { return "%C2%AC"; }
   if ($char == "%AD") { return "%C2%AD"; }
   if ($char == "%AE") { return "%C2%AE"; }
   if ($char == "%AF") { return "%C2%AF"; }
   if ($char == "%B0") { return "%C2%B0"; }
   if ($char == "%B1") { return "%C2%B1"; }
   if ($char == "%B2") { return "%C2%B2"; }
   if ($char == "%B3") { return "%C2%B3"; }
   if ($char == "%B4") { return "%C2%B4"; }
   if ($char == "%B5") { return "%C2%B5"; }
   if ($char == "%B6") { return "%C2%B6"; }
   if ($char == "%B7") { return "%C2%B7"; }
   if ($char == "%B8") { return "%C2%B8"; }
   if ($char == "%B9") { return "%C2%B9"; }
   if ($char == "%BA") { return "%C2%BA"; }
   if ($char == "%BB") { return "%C2%BB"; }
   if ($char == "%BC") { return "%C2%BC"; }
   if ($char == "%BD") { return "%C2%BD"; }
   if ($char == "%BE") { return "%C2%BE"; }
   if ($char == "%BF") { return "%C2%BF"; }
   if ($char == "%C0") { return "%C3%80"; }
   if ($char == "%C1") { return "%C3%81"; }
   if ($char == "%C2") { return "%C3%82"; }
   if ($char == "%C3") { return "%C3%83"; }
   if ($char == "%C4") { return "%C3%84"; }
   if ($char == "%C5") { return "%C3%85"; }
   if ($char == "%C6") { return "%C3%86"; }
   if ($char == "%C7") { return "%C3%87"; }
   if ($char == "%C8") { return "%C3%88"; }
   if ($char == "%C9") { return "%C3%89"; }
   if ($char == "%CA") { return "%C3%8A"; }
   if ($char == "%CB") { return "%C3%8B"; }
   if ($char == "%CC") { return "%C3%8C"; }
   if ($char == "%CD") { return "%C3%8D"; }
   if ($char == "%CE") { return "%C3%8E"; }
   if ($char == "%CF") { return "%C3%8F"; }
   if ($char == "%D0") { return "%C3%90"; }
   if ($char == "%D1") { return "%C3%91"; }
   if ($char == "%D2") { return "%C3%92"; }
   if ($char == "%D3") { return "%C3%93"; }
   if ($char == "%D4") { return "%C3%94"; }
   if ($char == "%D5") { return "%C3%95"; }
   if ($char == "%D6") { return "%C3%96"; }
   if ($char == "%D7") { return "%C3%97"; }
   if ($char == "%D8") { return "%C3%98"; }
   if ($char == "%D9") { return "%C3%99"; }
   if ($char == "%DA") { return "%C3%9A"; }
   if ($char == "%DB") { return "%C3%9B"; }
   if ($char == "%DC") { return "%C3%9C"; }
   if ($char == "%DD") { return "%C3%9D"; }
   if ($char == "%DE") { return "%C3%9E"; }
   if ($char == "%DF") { return "%C3%9F"; }
   if ($char == "%E0") { return "%C3%A0"; }
   if ($char == "%E1") { return "%C3%A1"; }
   if ($char == "%E2") { return "%C3%A2"; }
   if ($char == "%E3") { return "%C3%A3"; }
   if ($char == "%E4") { return "%C3%A4"; }
   if ($char == "%E5") { return "%C3%A5"; }
   if ($char == "%E6") { return "%C3%A6"; }
   if ($char == "%E7") { return "%C3%A7"; }
   if ($char == "%E8") { return "%C3%A8"; }
   if ($char == "%E9") { return "%C3%A9"; }
   if ($char == "%EA") { return "%C3%AA"; }
   if ($char == "%EB") { return "%C3%AB"; }
   if ($char == "%EC") { return "%C3%AC"; }
   if ($char == "%ED") { return "%C3%AD"; }
   if ($char == "%EE") { return "%C3%AE"; }
   if ($char == "%EF") { return "%C3%AF"; }
   if ($char == "%F0") { return "%C3%B0"; }
   if ($char == "%F1") { return "%C3%B1"; }
   if ($char == "%F2") { return "%C3%B2"; }
   if ($char == "%F3") { return "%C3%B3"; }
   if ($char == "%F4") { return "%C3%B4"; }
   if ($char == "%F5") { return "%C3%B5"; }
   if ($char == "%F6") { return "%C3%B6"; }
   if ($char == "%F7") { return "%C3%B7"; }
   if ($char == "%F8") { return "%C3%B8"; }
   if ($char == "%F9") { return "%C3%B9"; }
   if ($char == "%FA") { return "%C3%BA"; }
   if ($char == "%FB") { return "%C3%BB"; }
   if ($char == "%FC") { return "%C3%BC"; }
   if ($char == "%FD") { return "%C3%BD"; }
   if ($char == "%FE") { return "%C3%BE"; }
   if ($char == "%FF") { return "%C3%BF"; }
   return $char;
}

function decodeURI($string) {
	if (!is_string($string))
		return $string;
   $result = "";
   for ($i = 0; $i < strlen($string); $i++) {
       $decstr = "";
       for ($p = 0; $p <= 8; $p++)
	   {
			if (isset($string[$i+$p]))
			{
			  $decstr .= $string[$i+$p];
			}
       } 
       list($decodedstr, $num) = decodeURIbycharacter($decstr);
       $result .= urldecode($decodedstr);
       $i += $num ;
   }
   return $result;
}

function decodeURIbycharacter($str) {

   $char = $str;

   if ($char == "%E2%82%AC") { return array("%80", 8); }
   if ($char == "%E2%80%9A") { return array("%82", 8); }
   if ($char == "%E2%80%9E") { return array("%84", 8); }
   if ($char == "%E2%80%A6") { return array("%85", 8); }
   if ($char == "%E2%80%A0") { return array("%86", 8); }
   if ($char == "%E2%80%A1") { return array("%87", 8); }
   if ($char == "%E2%80%B0") { return array("%89", 8); }
   if ($char == "%E2%80%B9") { return array("%8B", 8); }
   if ($char == "%E2%80%98") { return array("%91", 8); }
   if ($char == "%E2%80%99") { return array("%92", 8); }
   if ($char == "%E2%80%9C") { return array("%93", 8); }
   if ($char == "%E2%80%9D") { return array("%94", 8); }
   if ($char == "%E2%80%A2") { return array("%95", 8); }
   if ($char == "%E2%80%93") { return array("%96", 8); }
   if ($char == "%E2%80%94") { return array("%97", 8); }
   if ($char == "%E2%84%A2") { return array("%99", 8); }
   if ($char == "%E2%80%BA") { return array("%9B", 8); }

   $char = substr($str, 0, 6);

   if ($char == "%C2%81") { return array("%81", 5); }
   if ($char == "%C6%92") { return array("%83", 5); }
   if ($char == "%CB%86") { return array("%88", 5); }
   if ($char == "%C5%A0") { return array("%8A", 5); }
   if ($char == "%C5%92") { return array("%8C", 5); }
   if ($char == "%C2%8D") { return array("%8D", 5); }
   if ($char == "%C5%BD") { return array("%8E", 5); }
   if ($char == "%C2%8F") { return array("%8F", 5); }
   if ($char == "%C2%90") { return array("%90", 5); }
   if ($char == "%CB%9C") { return array("%98", 5); }
   if ($char == "%C5%A1") { return array("%9A", 5); }
   if ($char == "%C5%93") { return array("%9C", 5); }
   if ($char == "%C2%9D") { return array("%9D", 5); }
   if ($char == "%C5%BE") { return array("%9E", 5); }
   if ($char == "%C5%B8") { return array("%9F", 5); }
   if ($char == "%C2%A0") { return array("%A0", 5); }
   if ($char == "%C2%A1") { return array("%A1", 5); }
   if ($char == "%C2%A2") { return array("%A2", 5); }
   if ($char == "%C2%A3") { return array("%A3", 5); }
   if ($char == "%C2%A4") { return array("%A4", 5); }
   if ($char == "%C2%A5") { return array("%A5", 5); }
   if ($char == "%C2%A6") { return array("%A6", 5); }
   if ($char == "%C2%A7") { return array("%A7", 5); }
   if ($char == "%C2%A8") { return array("%A8", 5); }
   if ($char == "%C2%A9") { return array("%A9", 5); }
   if ($char == "%C2%AA") { return array("%AA", 5); }
   if ($char == "%C2%AB") { return array("%AB", 5); }
   if ($char == "%C2%AC") { return array("%AC", 5); }
   if ($char == "%C2%AD") { return array("%AD", 5); }
   if ($char == "%C2%AE") { return array("%AE", 5); }
   if ($char == "%C2%AF") { return array("%AF", 5); }
   if ($char == "%C2%B0") { return array("%B0", 5); }
   if ($char == "%C2%B1") { return array("%B1", 5); }
   if ($char == "%C2%B2") { return array("%B2", 5); }
   if ($char == "%C2%B3") { return array("%B3", 5); }
   if ($char == "%C2%B4") { return array("%B4", 5); }
   if ($char == "%C2%B5") { return array("%B5", 5); }
   if ($char == "%C2%B6") { return array("%B6", 5); }
   if ($char == "%C2%B7") { return array("%B7", 5); }
   if ($char == "%C2%B8") { return array("%B8", 5); }
   if ($char == "%C2%B9") { return array("%B9", 5); }
   if ($char == "%C2%BA") { return array("%BA", 5); }
   if ($char == "%C2%BB") { return array("%BB", 5); }
   if ($char == "%C2%BC") { return array("%BC", 5); }
   if ($char == "%C2%BD") { return array("%BD", 5); }
   if ($char == "%C2%BE") { return array("%BE", 5); }
   if ($char == "%C2%BF") { return array("%BF", 5); }
   if ($char == "%C3%80") { return array("%C0", 5); }
   if ($char == "%C3%81") { return array("%C1", 5); }
   if ($char == "%C3%82") { return array("%C2", 5); }
   if ($char == "%C3%83") { return array("%C3", 5); }
   if ($char == "%C3%84") { return array("%C4", 5); }
   if ($char == "%C3%85") { return array("%C5", 5); }
   if ($char == "%C3%86") { return array("%C6", 5); }
   if ($char == "%C3%87") { return array("%C7", 5); }
   if ($char == "%C3%88") { return array("%C8", 5); }
   if ($char == "%C3%89") { return array("%C9", 5); }
   if ($char == "%C3%8A") { return array("%CA", 5); }
   if ($char == "%C3%8B") { return array("%CB", 5); }
   if ($char == "%C3%8C") { return array("%CC", 5); }
   if ($char == "%C3%8D") { return array("%CD", 5); }
   if ($char == "%C3%8E") { return array("%CE", 5); }
   if ($char == "%C3%8F") { return array("%CF", 5); }
   if ($char == "%C3%90") { return array("%D0", 5); }
   if ($char == "%C3%91") { return array("%D1", 5); }
   if ($char == "%C3%92") { return array("%D2", 5); }
   if ($char == "%C3%93") { return array("%D3", 5); }
   if ($char == "%C3%94") { return array("%D4", 5); }
   if ($char == "%C3%95") { return array("%D5", 5); }
   if ($char == "%C3%96") { return array("%D6", 5); }
   if ($char == "%C3%97") { return array("%D7", 5); }
   if ($char == "%C3%98") { return array("%D8", 5); }
   if ($char == "%C3%99") { return array("%D9", 5); }
   if ($char == "%C3%9A") { return array("%DA", 5); }
   if ($char == "%C3%9B") { return array("%DB", 5); }
   if ($char == "%C3%9C") { return array("%DC", 5); }
   if ($char == "%C3%9D") { return array("%DD", 5); }
   if ($char == "%C3%9E") { return array("%DE", 5); }
   if ($char == "%C3%9F") { return array("%DF", 5); }
   if ($char == "%C3%A0") { return array("%E0", 5); }
   if ($char == "%C3%A1") { return array("%E1", 5); }
   if ($char == "%C3%A2") { return array("%E2", 5); }
   if ($char == "%C3%A3") { return array("%E3", 5); }
   if ($char == "%C3%A4") { return array("%E4", 5); }
   if ($char == "%C3%A5") { return array("%E5", 5); }
   if ($char == "%C3%A6") { return array("%E6", 5); }
   if ($char == "%C3%A7") { return array("%E7", 5); }
   if ($char == "%C3%A8") { return array("%E8", 5); }
   if ($char == "%C3%A9") { return array("%E9", 5); }
   if ($char == "%C3%AA") { return array("%EA", 5); }
   if ($char == "%C3%AB") { return array("%EB", 5); }
   if ($char == "%C3%AC") { return array("%EC", 5); }
   if ($char == "%C3%AD") { return array("%ED", 5); }
   if ($char == "%C3%AE") { return array("%EE", 5); }
   if ($char == "%C3%AF") { return array("%EF", 5); }
   if ($char == "%C3%B0") { return array("%F0", 5); }
   if ($char == "%C3%B1") { return array("%F1", 5); }
   if ($char == "%C3%B2") { return array("%F2", 5); }
   if ($char == "%C3%B3") { return array("%F3", 5); }
   if ($char == "%C3%B4") { return array("%F4", 5); }
   if ($char == "%C3%B5") { return array("%F5", 5); }
   if ($char == "%C3%B6") { return array("%F6", 5); }
   if ($char == "%C3%B7") { return array("%F7", 5); }
   if ($char == "%C3%B8") { return array("%F8", 5); }
   if ($char == "%C3%B9") { return array("%F9", 5); }
   if ($char == "%C3%BA") { return array("%FA", 5); }
   if ($char == "%C3%BB") { return array("%FB", 5); }
   if ($char == "%C3%BC") { return array("%FC", 5); }
   if ($char == "%C3%BD") { return array("%FD", 5); }
   if ($char == "%C3%BE") { return array("%FE", 5); }
   if ($char == "%C3%BF") { return array("%FF", 5); }
   
   $char = substr($str, 0, 3);
   if ($char == "%20") { return array("+", 2); }
   
   $char = substr($str, 0, 1);

   if ($char == "!") { return array("%21", 0); }
   if ($char == "#") { return array("%23", 0); }
   if ($char == "$") { return array("%24", 0); }
   if ($char == "&") { return array("%26", 0); }
   if ($char == "\"") { return array("%27", 0); }
   if ($char == "(") { return array("%28", 0); }
   if ($char == ")") { return array("%29", 0); }
   if ($char == "*") { return array("%2A", 0); }
   if ($char == "+") { return array("%2B", 0); }
   if ($char == ",") { return array("%2C", 0); }
   if ($char == "/") { return array("%2F", 0); }
   if ($char == ":") { return array("%3A", 0); }
   if ($char == ";") { return array("%3B", 0); }
   if ($char == "=") { return array("%3D", 0); }
   if ($char == "?") { return array("%3F", 0); }
   if ($char == "@") { return array("%40", 0); }
   if ($char == "~") { return array("%7E", 0); }

   if ($char == "%") {
      return array(substr($str, 0, 3), 2);
   } else {
      return array($char, 0);
   }
}

function escape($string) {
   $result = "";
   for ($i = 0; $i < strlen($string); $i++) {
      $result .= escapebycharacter(urlencode($string[$i]));
   }
   return $result;
}

function escapebycharacter($char) {
   if ($char == '+') { return '%20'; }
   if ($char == '%2A') { return '*'; }
   if ($char == '%2B') { return '+'; }
   if ($char == '%2F') { return '/'; }
   if ($char == '%40') { return '@'; }
   if ($char == '%80') { return '%u20AC'; }
   if ($char == '%82') { return '%u201A'; }
   if ($char == '%83') { return '%u0192'; }
   if ($char == '%84') { return '%u201E'; }
   if ($char == '%85') { return '%u2026'; }
   if ($char == '%86') { return '%u2020'; }
   if ($char == '%87') { return '%u2021'; }
   if ($char == '%88') { return '%u02C6'; }
   if ($char == '%89') { return '%u2030'; }
   if ($char == '%8A') { return '%u0160'; }
   if ($char == '%8B') { return '%u2039'; }
   if ($char == '%8C') { return '%u0152'; }
   if ($char == '%8E') { return '%u017D'; }
   if ($char == '%91') { return '%u2018'; }
   if ($char == '%92') { return '%u2019'; }
   if ($char == '%93') { return '%u201C'; }
   if ($char == '%94') { return '%u201D'; }
   if ($char == '%95') { return '%u2022'; }
   if ($char == '%96') { return '%u2013'; }
   if ($char == '%97') { return '%u2014'; }
   if ($char == '%98') { return '%u02DC'; }
   if ($char == '%99') { return '%u2122'; }
   if ($char == '%9A') { return '%u0161'; }
   if ($char == '%9B') { return '%u203A'; }
   if ($char == '%9C') { return '%u0153'; }
   if ($char == '%9E') { return '%u017E'; }
   if ($char == '%9F') { return '%u0178'; }
   return $char;
}

function unescape($string) {
   $result = "";
   for ($i = 0; $i < strlen($string); $i++) {
       $decstr = "";
       for ($p = 0; $p <= 5; $p++) {
          $decstr .= $string[$i+$p];
       } 
       list($decodedstr, $num) = unescapebycharacter($decstr);
       $result .= urldecode($decodedstr);
       $i += $num ;
   }
   return $result;
}

function unescapebycharacter($str) {

   $char = $str;

   if ($char == '%u20AC') { return array("%80", 5); }
   if ($char == '%u201A') { return array("%82", 5); }
   if ($char == '%u0192') { return array("%83", 5); }
   if ($char == '%u201E') { return array("%84", 5); }
   if ($char == '%u2026') { return array("%85", 5); }
   if ($char == '%u2020') { return array("%86", 5); }
   if ($char == '%u2021') { return array("%87", 5); }
   if ($char == '%u02C6') { return array("%88", 5); }
   if ($char == '%u2030') { return array("%89", 5); }
   if ($char == '%u0160') { return array("%8A", 5); }
   if ($char == '%u2039') { return array("%8B", 5); }
   if ($char == '%u0152') { return array("%8C", 5); }
   if ($char == '%u017D') { return array("%8E", 5); }
   if ($char == '%u2018') { return array("%91", 5); }
   if ($char == '%u2019') { return array("%92", 5); }
   if ($char == '%u201C') { return array("%93", 5); }
   if ($char == '%u201D') { return array("%94", 5); }
   if ($char == '%u2022') { return array("%95", 5); }
   if ($char == '%u2013') { return array("%96", 5); }
   if ($char == '%u2014') { return array("%97", 5); }
   if ($char == '%u02DC') { return array("%98", 5); }
   if ($char == '%u2122') { return array("%99", 5); }
   if ($char == '%u0161') { return array("%9A", 5); }
   if ($char == '%u203A') { return array("%9B", 5); }
   if ($char == '%u0153') { return array("%9C", 5); }
   if ($char == '%u017E') { return array("%9E", 5); }
   if ($char == '%u0178') { return array("%9F", 5); }
   
   $char = substr($str, 0, 3);
   if ($char == "%20") { return array("+", 2); }
   
   $char = substr($str, 0, 1);

   if ($char == '*') { return array("%2A", 0); }
   if ($char == '+') { return array("%2B", 0); }
   if ($char == '/') { return array("%2F", 0); }
   if ($char == '@') { return array("%40", 0); }
   
   if ($char == "%") {
      return array(substr($str, 0, 3), 2);
   } else {
      return array($char, 0);
   }
}

	function lum_buildSelectOptions($data, $value, $text, $default = null)
	{
		$html = "";
		for ($i=0;$i<count($data);$i++)
		{
			if (is_object($data[$i]))
			{
				$data[$i] = get_object_vars($data[$i]);
			}
	
			$selected = "";
			if (($default && $default == $data[$i][$value]) || $default == '_ALL_')
			{
				$selected = "selected";
			}
			$html .= '<option value="'.urlencode($data[$i][$value]).'" '.$selected.'>'.$data[$i][$text].'</option>
			';
		}
		return $html;
	}

	function lum_requirePermission($perm, $inform_user = true)
	{
		if (!lum_call('Users', 'hasPermission', array('permission'=>$perm)))
		{
			if ($inform_user)
			{
				echo '<p>'.lum_getString('[NO_PERMISSION]').' ('.$perm.')</p>';
			}
			return false;
		}
		return true;
	}

	// lets us know if we need device detection or not
	// if not we don't load the device detection library
	function lum_needDeviceDetection()
	{
		$targets = unserialize(TARGETS);
		if (in_array('DISPLAY_TABLET', $targets) ||
		    in_array('DISPLAY_TV', $targets) ||
		    in_array('DISPLAY_MOBILE_ADVANCED', $targets) ||
		    in_array('DISPLAY_MOBILE_BASIC', $targets))
		{
			return true;
		}
		
		return false;
	}
	
	// checks if we are targeting a certain type of display for this site
	function lum_isDisplayTarget($target)
	{
		$targets = unserialize(TARGETS);
		
		if (in_array($target, $targets))
		{
			return true;
		}
		
		return false;	
	}
	
	function lum_getDefaultDisplayTargetTheme()
	{
		$themes = unserialize(TARGET_THEMES);
		reset($themes);
		return $themes[key($themes)];
	}
	
	function lum_getDisplayTargetTheme($target)
	{
		$themes = unserialize(TARGET_THEMES);
		if (array_key_exists(constant($target), $themes))
			return $themes[constant($target)];
		
		return '';
	}
	
	// this will return the theme based on the device being used to browse the site
	function lum_getTheme()
	{
		global $lumRegistry;
		$themes = unserialize(TARGET_THEMES);

		if (isset($_SERVER['LUM_DISPLAY_TARGET']))
		{
			if (array_key_exists(constant($_SERVER['LUM_DISPLAY_TARGET']), $themes))
			{
				define('CURRENT_DISPLAY_TARGET', $_SERVER['LUM_DISPLAY_TARGET']);
				return $themes[constant($_SERVER['LUM_DISPLAY_TARGET'])];
			}
		}

		// if we cannot figure out what device this is default to PC		
		if (!lum_setupDevice())
		{
			define('CURRENT_DISPLAY_TARGET', 'DISPLAY_PC');
			return $themes[DISPLAY_PC];	
		}

		// is this something other than a pc?
		if (lum_isDisplayTarget('DISPLAY_TABLET') && lum_isTablet())
		{
			define('CURRENT_DISPLAY_TARGET', 'DISPLAY_TABLET');
			return $themes[DISPLAY_TABLET];
		}

		if (lum_getDeviceCapability("is_wireless_device"))
		{
			if (lum_isDisplayTarget('DISPLAY_MOBILE_ADVANCED') && lum_checkMobileCapabilities())
			{
				define('CURRENT_DISPLAY_TARGET', 'DISPLAY_MOBILE_ADVANCED');
				return $themes[DISPLAY_MOBILE_ADVANCED];
			}
			else
			{
				if (lum_isDisplayTarget('DISPLAY_MOBILE_BASIC') && lum_isMobileBasic())
				{
					define('CURRENT_DISPLAY_TARGET', 'DISPLAY_MOBILE_BASIC');
					return $themes[DISPLAY_MOBILE_BASIC];
				}
			}
		}

		if (lum_isDisplayTarget('DISPLAY_TV') && lum_isTV())
		{
			define('CURRENT_DISPLAY_TARGET', 'DISPLAY_TV');
			return $themes[DISPLAY_TV];
		}

		// default to PC
		define('CURRENT_DISPLAY_TARGET', 'DISPLAY_PC');
		return $themes[DISPLAY_PC];

	}
	
	function lum_useLocalDeviceDetection()
	{
		return (REMOTE_DEVICE_DETECTION_URL == '' ? true : false);
	}
	
	function lum_setupDevice()
	{
		if (!lum_needDeviceDetection())
			return false;
		
		global $lumRegistry;
		
		if (!lum_useLocalDeviceDetection())
		{
			// we're using remote detection!
			$json = file_get_contents(REMOTE_DEVICE_DETECTION_URL.'?ua='.rawurlencode($_SERVER['HTTP_USER_AGENT']));
			
			if ($json)
			{
				$json = json_decode($json);

				if ($json)
				{
					$lumRegistry->mobile_cap = $json;
					return true;
				}
			}
			return false;
		}
		else
		{
			// local detection
			$obj = new TeraWurfl();
			if (!$obj->getDeviceCapabilitiesFromAgent())
				return false;
			
			$lumRegistry->mobile_cap = $obj->capabilities;
			return true;
		}
	}
	
	function lum_checkMobileCapabilities()
	{
		global $lumRegistry;
		if (
		    lum_getDeviceCapability('device_claims_web_support') &&
		    lum_getDeviceCapability('cookie_support') &&
		    lum_getDeviceCapability('ajax_manipulate_dom') &&
		    lum_getDeviceCapability('ajax_support_getelementbyid') &&
		    lum_getDeviceCapability('ajax_support_event_listener') &&
		    lum_getDeviceCapability('ajax_support_javascript') &&
		    lum_getDeviceCapability('ajax_support_inner_html') &&
		    lum_getDeviceCapability('ajax_support_events') &&
		    lum_getDeviceCapability('ajax_xhr_type') != 'none'
		    )
		{
			return true;
		}
		return false;
	}
	
	function lum_getDeviceCapability($capability)
	{
		global $lumRegistry;
		
		foreach ( $lumRegistry->mobile_cap as $group ) {
			if ( !is_array($group) ) {
				$group = get_object_vars($group);
			}
			if ( !is_array($group) )
				continue;
			
			while ( list($key, $value)=each($group) ) {
				if ($key==$capability) {
					return $value;
				}
			}
		}
		return null;
	}	
	
	function lum_isTablet()
	{
		global $lumRegistry;
		if (isset($lumRegistry->mobile_cap))
		{
			if (lum_getDeviceCapability("is_tablet"))
			{
				return true;
			}
		}
		return false;
	}

	// for future use
	function lum_isTV()
	{
		return false;
	}

	function lum_isSuperUser($inform_user = false)
	{
		return lum_requirePermission('Users\Super User', $inform_user);
	}

	function lum_getString($string_code, $lang_code = false)
	{
		global $lumRegistry;
		return lum_call('Strings', 'getByCode', array('string_code'=>$string_code, 'lang_code'=>($lang_code ? $lang_code : $lumRegistry->language->lang_code)));
	}
	
	function lum_setString($string_code, $text)
	{
		global $lumPageBuilder;
		$lumPageBuilder->addCustomString(array($string_code=>$text));
	}	

	function lum_replaceStrings($text)
	{
		global $lumPageBuilder;
		return $lumPageBuilder->replaceStrings($text);
	}	


	// we may be loading a special version of this function
	// for remote administration
	if (!function_exists('lum_call')) :
	    
		function lum_call($name, $func, $params = null, $registry = null)
		{
			global $lumRegistry;
			
			if (!$registry)
				$registry = $lumRegistry;
			
			$plugin = 'LuminancePlugin'.$name;
			
			if (!class_exists($name))
			{
				if (!lum_loadPlugin($name))
				{
					echo "Fatal Error: Could not load $name";
					return lum_showError();
				}
			}
			
			if (method_exists($plugin, $func))
			{
				$obj = new $plugin($registry);
				return $obj->$func($params);
			}
			else 
			{
				echo "Fatal Error: $plugin::$func does not exist";
				return lum_showError();
			}
		}
		
	endif;
	
	// deprecated, please use lum_call instead
	function plugin_call($name, $func, $params = null)
	{
		return lum_call($name, $func, $params);
	}
	
	function lum_loadPlugin($plugin)
	{
		if (lum_pluginExists($plugin))
		{
			require_once(PLUGINS_PATH .  $plugin.'/luminance.plugin.class.php');
			return true;
		}
		return false;	
	}
	
	function lum_pluginExists($plugin)
	{
		if (file_exists(PLUGINS_PATH . $plugin.'/luminance.plugin.class.php'))
		{
			return true;
		}
		return false;	
	}	

	function lum_getInstalledPlugins()
	{
		$installed = array();
		$i=0;
		
		$path = PLUGINS_PATH;

		if ($handle = opendir($path)) 
		{
			while (false !== ($file = readdir($handle))) 
			{
				if ($file != '.' && $file != '..' && is_dir($path.$file)) 
				{
					$class = $path.$file.'/luminance.plugin.class.php';
					if (is_file($class))    	
					{
						$installed[] = $file;
					}
				}
			}
		}
		
		return $installed;
			
	}	
	
	function lum_getPluginJavascriptIncludes($plugin, $use_plugins_path = false, $use_plugins_url = false)
	{
		$include = '';
		
		$url = 'cms-plugins/';
		if ($use_plugins_url)
			$url = $use_plugins_url;
			
		$path = PLUGINS_PATH;
		if ($use_plugins_path)
			$path = $use_plugins_path;
			
		$path .= "$plugin/admin/js_include/";
		
		if (is_dir($path))
		{
			if ($handle = opendir($path)) 
			{
				while (false !== ($file = readdir($handle))) 
				{
					if ($file != '.' && $file != '..' && is_file($path.$file)) 
					{
						$include .= '<script type="text/javascript" src="'.$url.$plugin.'/admin/js_include/'.$file.'"></script>'."\r\n";
					}
				}
			}
		}		
		echo $include;
	}

	function lum_getPluginCssIncludes($plugin, $use_plugins_path = false, $use_plugins_url = false)
	{
		$include = '';
		
		$url = 'cms-plugins/';
		if ($use_plugins_url)
			$url = $use_plugins_url;
			
		$path = PLUGINS_PATH;
		if ($use_plugins_path)
			$path = $use_plugins_path;
			
		$path .= "$plugin/admin/css_include/";
	
		if (is_dir($path))
		{
			if ($handle = opendir($path)) 
			{
				while (false !== ($file = readdir($handle))) 
				{
					if ($file != '.' && $file != '..' && is_file($path.$file)) 
					{
						$include .= '<link href="'.$url.$plugin.'/admin/css_include/'.$file.'" rel="stylesheet" type="text/css" />'."\r\n";
					}
				}
			}
		}		
		echo $include;
	}	

	function lum_getOption($option)
	{
		return lum_call('Options', 'getByName', array('name'=>$option));
	}
	
	function lum_setNoCacheHeaders()
	{
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT" ); 
		header("Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . "GMT" ); 
		header("Cache-Control: no-cache, must-revalidate" ); 
		header("Pragma: no-cache" );
		header("Content-Type: text/html; charset=utf-8");
	}
	
	function lum_embedLocalizedContentForEdit($localized, $langs)
	{
		$supported = array();
		foreach ($langs as $language)
		{
			$supported[] = $language['lang_code'];
		}		
		?>
		<div id="localized_content">
		<?php
			$used = array();
			if (is_array($localized))
			{
				foreach ($localized as $lang)
				{
					if (!in_array($lang['lang_code'], $supported))
						continue;
					
					echo '<div id="'.$lang['lang_code'].'" class="lang_code">';
					$used[$lang['lang_code']] = 1;
					foreach ($lang as $param=>$value)
					{
						if ($param != 'lang_code')
						{
							$value = str_replace('[', '&#091;', $value);
							$value = str_replace('[', '&#093;', $value);
							echo '<input type="hidden" id="content-'.$lang['lang_code'].'-'.$param.'" name="'.$lang['lang_code'].'-'.$param.'" value="'.$value.'"/>';
						}
					}
					echo '</div>';
				}
			}
			
			foreach ($langs as $language)
			{
				if (!array_key_exists($language['lang_code'], $used))
				{
					echo '<div id="'.$language['lang_code'].'" class="lang_code"></div>';	
				}
			}
		?>
		</div>
		<?php
	}
	
	function lum_getDefaultLanguageValue($localized, $param, $def_lang)
	{
		foreach ($localized as $lang)
		{
			if ($lang['lang_code'] == $def_lang)
			{
				return str_replace(']', '&#093;', str_replace('[', '&#091;', $lang[$param]));
			}
		}	
	}
	
	if (!function_exists('get_called_class')):
	
	  function get_called_class() {
	    $bt = debug_backtrace();
	    //var_dump($bt);
	    $l = 0;
	    do {
		$l++;
		$lines = file($bt[$l]['file']);
		$callerLine = $lines[$bt[$l]['line']-1];
		preg_match('/([a-zA-Z0-9\_]+)::'.$bt[$l]['function'].'/',
			   $callerLine,
			   $matches);
	    } while ($matches[1] == 'parent' && $matches[1]);
	    return $matches[1];
	  }
	
	endif;
	
	
	if (!function_exists('com_create_guid')):
	
		function com_create_guid() {
			mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
			$charid = strtoupper(md5(uniqid(rand(), true)));
			$hyphen = chr(45);// "-"
			$uuid = chr(123)// "{"
				.substr($charid, 0, 8).$hyphen
				.substr($charid, 8, 4).$hyphen
				.substr($charid,12, 4).$hyphen
				.substr($charid,16, 4).$hyphen
				.substr($charid,20,12)
				.chr(125);// "}"
			return $uuid;
		}
		
	endif;
	
	function lum_getSitePath()
	{
		if (isset($_COOKIE['lum_loadSiteRootPath']) && strpos($_COOKIE['lum_loadSiteRootPath'], 'sites/') !== false)
		{
			global $lumRegistry;
			$sql = "select ftp_folder from master_site.lum_sites where site_key = ?";
			$value_array = array($_COOKIE['lum_loadSiteKey']);
			$row = $lumRegistry->db->getRow($sql, $value_array, true);
			if ($row)
				return '/usr/local/www/vhosts/'.$row['ftp_folder'].'/';
		}
		return ROOT_PATH;
	}
	
	function lum_isMasterSite()
	{
		return  (defined('SITE_KEY') && SITE_KEY == 'MASTER_SITE');
	}
?>
