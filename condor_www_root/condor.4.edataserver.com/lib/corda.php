<?php
require('../CordaEmbedder.php');

/**
 * PHP Wrapper for the CordaEmbedder class for Condor 2.0.
 * 
 * @author Brian Feaver
 * @copyright Copyright 2006 The Selling Source, Inc.
 */
class Corda
{
	private $corda;
	
	const EXTERNAL_SERVER_ADDRESS = 'http://10.0.1.16:2001';
	const INTERNAL_COMM_PORT_ADDRESS = '10.0.1.16:2002';
	
	/**
	 * Corda constructor.
	 */
	public function __construct()
	{
		$this->corda = new CordaEmbedder();
		$this->corda->externalServerAddress = self::EXTERNAL_SERVER_ADDRESS;
		$this->corda->internalCommPortAddress = self::INTERNAL_COMM_PORT_ADDRESS;
	}
	
	/**
	 * Converts the given HTML to a PDF and returns the PDF.
	 *
	 * @param string $html
	 * @return string
	 */
	public function Convert_to_PDF($html)
	{
		$this->corda->setDoc($html, $base_url);
		return $this->corda->getBytes();
	}
}
?>
