<?php
	// Version 1.0.0
	// 3rd party offers

	class Post_Offer_1
	{

		var $offer_id = 0;
		var $offer_code = '';		// short string to identify the offer
		// include promo_id && promo_sub_code in this as necessary
		var $remote_url = '';
		// key: field name; value: field values that will activate the trigger
		var $trigger_data = array();
		// set up events according to site config; e.g, visitor, prequal, submit, etc
		var $trigger_events = array();
		var $templates = array(
								'gateway'  => '',
								'fields'   => '',
								'reply'    => '');
		// string to look for in response to determine if submission was ok
		var $success_token = '';
		var $success = FALSE;
		var $result = '';

		function Offer_1 ($offer_id, $offer_code, $remote_url, $trigger_data, $trigger_events, $templates)
		{
			if(!isnull($offer_id))
			{
				$this->offer_id = $offer_id;
			}
			if(!isnull($offer_code))
			{
				$this->offer_code = $offer_code;
			}
			if(!isnull($remote_url))
			{
				$this->remote_url = $remote_url;
			}
			if(is_array($trigger_data))
			{
				$this->trigger_data = $trigger_data;
			}
			if(is_array($trigger_events))
			{
				$this->trigger_events = $trigger_events;
			}
			if(is_array($templates))
			{
				$this->templates['gateway'] = (isset($templates['gateway']) ? $templates['gateway'] : '');
				$this->templates['fields'] = (isset($templates['fields']) ? $templates['fields'] : '');
				$this->templates['reply'] = (isset($templates['reply']) ? $templates['reply'] : '');
			}
			return TRUE;
		}
	
		// posts data to remove server, stores the result in $this, sets boolean for success

		function Post_Offer ($data)
		{
			$result = $this->_Fetch_Remote_Response($data, $this->remote_url);

			$this->result = $result;

			if(isset($this->result['errno']))
			{
				$this->success = FALSE;
			}
			else
			{
				$got_headers = FALSE;
				$response = array();
				$response['body'] = '';
				// loop through each line of _Post_Data() result
				while (list($key, $line) = each($result))
				{
					if ($key == 0)
					{
						// grab response code from first header
						$response['http_code'] = intval(substr($line, 9, 3));
					}
					else if ($line == "\r\n")
					{
						// stop after last header
						$got_headers = TRUE;
					}
					else if(preg_match('/^(.+?): (.+?)$/', $line, $match))
					{
						// put each header into $response
						$response[$match[1]] = trim($match[2]);
					}
					else
					{
						// put each line of document into $respose['body']
						$response['body'] .= $line;
					}
				}
				if(strstr($response['body'], $this->success_token) != FALSE)
				{
					$this->success = TRUE;
				}
				else
				{
					$this->success = FALSE;
				}
			}
		}
	}


// wrapper for _Post_Data()
function _Fetch_Femote_Fesponse($field_array, $url)
{
	$redirect_count = 0;

	// only allow up to 5 redirects
	while($redirect_count < 5)
	{
		$result = $this->_Post_Data($field_array, $url);
		if(isset($result["errno"]))
		{
			return $result;
		}
		$got_headers = FALSE;
		$response = array();
		$response['body'] = '';
		// loop through each line of _Post_Data() result
		while (list($key, $line) = each($result))
		{
			if ($key == 0)
			{
				// grab response code from first header
				$response['http_code'] = intval(substr($line, 9, 3));
			}
			else if ($line == "\r\n")
			{
				// stop after last header
				$got_headers = TRUE;
			}
			else if(preg_match('/^(.+?): (.+?)$/', $line, $match))
			{
				// put each header into $response
				$response[$match[1]] = trim($match[2]);
			}
			else
			{
				// put each line of document into $respose['body']
				$response['body'] .= $line;
			}
		}
		// parse out enough of the cookie data to send back
		list($cookie_name_value_pair, $cookie_other) = explode('; ', $response['Set-Cookie'], 2);
		$response['cookie'] = $cookie_name_value_pair;

		if($response['http_code'] == 302)
		{
			// increment redirect counter
			$redirect_count++;
			$url = $response['Location'];
		}
		else{
			break;
		}
	}
	return $result;
}

function _Post_Data($field_array, $url) {
	$url_array = parse_url ($url);
	$host = $url_array["host"];
	$port = $url_array["port"] ? $url_array["port"] : 80;
	$path = $url_array["path"];
	$path .= $url_array["query"] ? "?".$url_array["query"] : "";


	$field_str = "";
	foreach($field_array as $key => $val)
	{
		if (!empty($field_str))
		{
	  		$field_str .= "&";
		}
		$field_str .= $key."=".urlencode($val);
	}
	$field_str_length = strlen($field_str);

	$post_header  = "POST ".$path." HTTP/1.1\r\n";
	$post_header .= "Host: ".$host."\n";
	$post_header .= "User-Agent: SellingSource\r\n";
	$post_header .= "Content-Type: application/x-www-form-urlencoded\r\n";
	$post_header .= "Content-Length: ".$field_str_length."\r\n";
	$post_header .= "Connection: close\r\n\r\n";
	$post_header .= $field_str."\r\n\r\n";

	$post_header_length = strlen($post_header);

	$socket = fsockopen($host, $port, $errno, $errstr, 3);

	if (!$socket) {
		$result["errno"] = $errno;
		$result["errstr"] = $errstr;
		return $result;
	}

	fputs($socket, $post_header, $post_header_length);

	stream_set_timeout ($socket, 1);

	$empty_count = 0;
	while (!feof($socket)) {
		$result[] = fgets($socket, 4096);
	}

	fclose($socket);
	return $result;
}

?>
