<?php

/**
 * Handles a response from a Tribal call
 */
abstract class TSS_Tribal_Response implements TSS_Tribal_IResponse
{
	/**
	 * Like an error
	 *
	 * @var string
	 */
	protected $error;

	/**
	 * Like an error code
	 *
	 * @var string
	 */
	protected $error_code;

	/**
	 * Like a dom document
	 *
	 * @var DOMDocument
	 */
	protected $dom_doc;

	/**
	 * Like a xpath
	 *
	 * @var DOMXpath
	 */
	protected $xpath;

	/**
	 * Parse an XML response from datax and handle
	 * whatever information is there
	 *
	 * @param string $xml
	 * @return bool
	 */
	public function parseXML($xml)
	{
		try
		{
			$this->dom_doc = new DOMDocument();
			$this->dom_doc->loadXML($xml);
			$this->xpath = new DOMXPath($this->dom_doc);
		}
		catch (Exception $e)
		{
			throw new TSS_Tribal_TransportException($e->getMessage(), 0);
		}

		//return !$this->searchForError();
	}

	/**
	 * Do we have an error?
	 *
	 * @return boolean
	 */
	public function hasError()
	{
		return $this->error || $this->error_code;
	}

	/**
	 * Return some sort of In the form of MSG
	 *
	 * @return string
	 */
	public function getErrorMsg()
	{
		return $this->error;
	}

	public function getErrorCode()
	{
		return $this->error_code;
	}

	/**
	 * Runs a couple xpath queries to try and find
	 * errors in the packet
	 *
	 * @return boolean
	 */
	protected function searchForError()
	{
		$code = $this->findNode('/response/code');
		$msg = $this->findNode('/response/message');

		if ($code !== NULL || $msg !== NULL)
		{
			$this->error = $msg;
			$this->error_code = $code;
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Finds a single node from an xpath query, and returns its content
	 *
	 * If multiple nodes are returned from the query,
	 * the value of the first is returned. If no nodes are
	 * found, it returns NULL.
	 *
	 * @param string $query
	 * @return string|null
	 */
	protected function findNode($query)
	{
		$nodes = $this->xpath->query($query);

		if ($nodes->length > 0)
		{
			return $nodes->item(0)->textContent;
		}
		return NULL;
	}
}
