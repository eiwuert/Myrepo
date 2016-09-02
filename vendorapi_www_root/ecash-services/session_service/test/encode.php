<?php
session_start();
if (isset($argv[1])) {
	$info = eval("return {$argv[1]};");
} else {
	$info = array('fruit' => 'mango');
}

foreach ($info as $key => $value) {
	$_SESSION[$key] = $value;
}

print base64_encode(gzcompress(session_encode()));
?>
