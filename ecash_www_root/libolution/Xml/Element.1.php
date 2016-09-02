<?php

/**
 * Xml_Element_1 
 * 
 * @author everyone@tss
 * @uses SimpleXMLElement
 * @package Xml
 * @version $Id$
 */
class Xml_Element_1 extends SimpleXMLElement
{
	/**
	 * addCdata 
	 * 
	 * @param mixed $text 
	 * @return void
	 */
	public function addCdata($text)
	{
		$node = dom_import_simplexml($this);
		$node->appendChild($node->ownerDocument->createCDATASection($text));
	}

	/**
	 * toXml 
	 * 
	 * @param mixed $xsl 
	 * @param mixed $formatoutput 
	 * @param mixed $noxmldecl 
	 * @return string
	 */
	public function toXml($xsl = NULL, $formatoutput = FALSE, $noxmldecl = FALSE)
	{
		$doc = new DOMDocument();
		$doc->formatOutput = (bool)$formatoutput;
		if ($xsl)
			$doc->appendChild($doc->createProcessingInstruction(
				'xml-stylesheet', "type=\"text/xsl\" href=\"{$xsl}\""
			));
		$node = dom_import_simplexml($this);
		$node = $doc->importNode($node, TRUE);
		$node = $doc->appendChild($node);
		$xml = $doc->saveXml();
		if ($noxmldecl)
			$xml = preg_replace("/^<\?xml version=\"1.0\"\?\>\n?/", '', $xml);

		return $xml;
	}
}

?>
