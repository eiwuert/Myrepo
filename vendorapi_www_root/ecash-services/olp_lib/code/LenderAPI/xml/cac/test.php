<?php

$xml = new DOMDocument();
$xml->loadXML(file_get_contents('../olp.request.xml'));

$xsl = new DOMDocument();
$xsl->loadXML(file_get_contents('olp_cac.request.xslt'));

$processor = new XSLTProcessor();
$processor->importStylesheet($xsl);

$xml = $processor->transformToXml($xml);

echo "----------------> Request <---------------------\n";
echo $xml;
echo "\n\n\n\n";


$xml = new DOMDocument();
$xml->loadXML(file_get_contents('cac.response.accepted.xml'));

$xsl = new DOMDocument();
$xsl->loadXML(file_get_contents('cac_olp.response.xslt'));

$processor = new XSLTProcessor();
$processor->importStylesheet($xsl);

$xml = $processor->transformToXml($xml);

echo "----------------> Response <---------------------\n";
echo $xml;




?>
