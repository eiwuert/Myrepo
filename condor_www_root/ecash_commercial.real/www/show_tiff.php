<?php

// include our config file
require_once('config.php');

// xmlrpc stuff
require_once ("debug.1.php");
require_once ("xmlrpc.1.php");

//condor stuff to retrieve legacy stored copia docs from condor
require_once LIB_DIR . "/Document/Document.class.php";
require_once eCash_Document_DIR . "/DeliveryAPI/Condor.class.php";
require_once LIB_DIR . "/Config.class.php";

try {
	
	$check = eCash_Document_DeliveryAPI_Condor::Prpc()->Copia_File_Exists( array($_GET['dnis'] => array($_GET['tiff'])) );
	
	if( $check[$_GET['dnis']][$_GET['tiff']] && $check[$_GET['dnis']][$_GET['tiff']] !==  TRUE ) 
	{
		throw new Exception();
	}
	
	$data = eCash_Document_DeliveryAPI_Condor::Prpc()->Get_Copia_Document($_GET['dnis'], $_GET['tiff']);
	if ($data === false) {
		throw new Exception();
	}
	
	header("Content-Type: application/pdf");
	header("Content-Disposition: inline; filename=received_doc.pdf");
	echo $data;
	
	
} catch (Exception $e) {

	// Get a list of documents 
	$object = (object) array();
	$object->dnis = $_GET ["dnis"];
	$object->tiff = $_GET ["tiff"];

	$xmlrpc_envelope = (object) array();
	$xmlrpc_envelope->passed_data = base64_encode (serialize ($object));

	$copia_host = eCash_Config::getInstance()->COPIA_HOST;
	$copia_port = eCash_Config::getInstance()->COPIA_PORT;
	$copia_path = eCash_Config::getInstance()->COPIA_PATH;
				
	$result = @Xmlrpc_Request ($copia_host, $copia_port, $copia_path, "Get_Tiff", $xmlrpc_envelope);
	
	//don't ask my why this works and $result[0] doesn't
	list($key, $rpc_result) = each($result);

	// Copia Document List
	$tiff_obj = unserialize (base64_decode ($rpc_result));
	
	if ($tiff_obj->type == "tiff")
	{
		// Stream to the browser
		header("Content-Type: image/tif");
		header("Content-Disposition: inline; filename=received_doc.tif");
		echo $tiff_obj->tiff;
	}
	else
	{
		echo "<html><head></head><body onLoad=\"alert('Unable to find document');history.back();\">{$tiff_obj->error}</body></html>";

	}

}
