<?php

function data_trap_post($table_name, $variable_name, $data)
{
	$url = "http://data_trap.edataserver.com";
	$table_name = urlencode ($table_name);
	$variable_name = urlencode ($variable_name);
	if (is_array ($data) || is_object ($data))
	{
		$data = print_r ($data, true);
	}
	$data = urlencode ($data);
	// set up the key
	$field_str = "key=".urlencode (md5 (date ("m Y d")."skizzle"));

	$field_str .= "&table_name=".$table_name."&variable_name=".$variable_name."&data=".$data;

//$field_array, $url) {
	$url_array = parse_url ($url);
	$host = $url_array["host"];
	$port = $url_array["port"] ? $url_array["port"] : 80;
	$path = $url_array["path"];
	$path .= $url_array["query"] ? "?".$url_array["query"] : "";

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
