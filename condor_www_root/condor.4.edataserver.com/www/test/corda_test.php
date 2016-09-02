<?php
	
	// Corda PHP library
	include('../CordaEmbedder.php');
	
	$corda = new CordaEmbedder();
	$corda->externalServerAddress = 'http://10.1.42.145:2001';
	$corda->internalCommPortAddress = '10.1.42.145:2002';
	$corda->loadDoc('http://condor2.ds38.tss/documents.html');
	$corda->debugOn = TRUE;
	$corda->outputType = TIFF;
	$corda->saveToAppServer('/tmp/', 'test.tiff');
	
?>