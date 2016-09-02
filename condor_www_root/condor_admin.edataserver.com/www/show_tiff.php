<?php

// xmlrpc stuff
require_once (DIR_LIB . "debug.1.php");
require_once (DIR_LIB . "xmlrpc.1.php");

// include our config file
require_once('config.php');
	
// Get a list of documents 
$object = (object) array();
$object->dnis = $_GET ["dnis"];
$object->tiff = $_GET ["tiff"];

$xmlrpc_envelope = (object) array();
$xmlrpc_envelope->passed_data = base64_encode (serialize ($object));

$result = @Xmlrpc_Request (COPIA_HOST, COPIA_PORT, COPIA_PATH, "Get_Tiff", $xmlrpc_envelope);
	

//don't ask my why this works and $result[0] doesn't
list($key, $rpc_result) = each($result);

// Copia Document List
$tiff_obj = unserialize (base64_decode ($rpc_result));
	
if ($tiff_obj->type == "tiff")
{
	// Stream to the browser
	header("Content-Type: image/tif");
	header("Content-Disposition: inline; filename=recieved_doc.tif");
	echo $tiff_obj->tiff;
}
else
{
	echo "<html>
<head>
</head>
<body onLoad=\"alert('Unable to find document');history.back();\">";
	echo $tiff_obj->error;
	echo "</body>
</html> ";

}

?>
