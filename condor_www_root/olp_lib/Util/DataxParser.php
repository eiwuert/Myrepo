<?php

/** An easy to use class to parse DataX XML packets. Contains the common
 * functions to find values in the DataX packet without requring you to know
 * which packet type it is. Supports running simple XPath queries as well to
 * find simple values in the packet.
 *
 * Sample usage of searchOneNode() can be found in the test script.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Util_DataxParser
{
	/** PHP's DOMDocument for the XML packet.
	 *
	 * @var DOMDocument
	 */
	protected $domdoc;
	
	/** Load an XML document. By default, will ignore exceptions thrown
	 * because of invalid XML since all functions safely handle the case when
	 * the DOMDocument is empty.
	 *
	 * @param string $xml_string The XML document to load.
	 * @param bool $ignore_exceptions If to ignore loading exceptions.
	 */
	public function __construct($xml_string, $ignore_exceptions = TRUE)
	{
		try
		{
			$this->domdoc = DOMDocument::loadXML(trim($xml_string));
		}
		catch (Exception $e)
		{
			if (!$ignore_exceptions) throw $e;
		}
	}
	
	/** Returns if the XML packet is valid.
	 *
	 * @return boolean TRUE if packet is usable, FALSE otherwise.
	 */
	public function isValid()
	{
		return (bool)$this->domdoc;
	}
	
	/** Determines if there was an DataX error. If so, returns the error
	 * string. Otherwise, returns NULL.
	 *
	 * @return string The combined DataX error string.
	 */
	public function getError()
	{
		// If DOMDocument is invalid, return failure.
		if (!$this->domdoc) return NULL;
		
		$result = NULL;
		
		// Failure codes will always be in these exact spots
		$error_code = $this->searchOneNode('/DataxResponse/Response/ErrorCode');
		$error_message = $this->searchOneNode('/DataxResponse/Response/ErrorMsg');
		
		// I was informed that these will both exist if one exists, so not
		// formatting the string in a empty-string safe way, but still
		// checking to see if either exists.
		if ($error_code !== NULL || $error_message !== NULL)
		{
			$result = "{$error_code} - {$error_message}";
		}
		
		return $result;
	}
	
	/** Looks into the packet and returns a string of the decision buckets.
	 * If cannot find decision bucket values, returns an empty string.
	 *
	 * @return string A comma separated list of decision buckets.
	 */
	public function getDecisionCode()
	{
		// If DOMDocument is invalid, return failure.
		if (!$this->domdoc) return NULL;
		
		$xpath = new DOMXPath($this->domdoc);
		
		// Different ways to search for bucket strings
		$xpath_queries = array(
			'//Summary/DecisionBucket', // impact*-idv
			'//Summary/DecisionBuckets//Bucket', // aalm-perf
			"//GlobalDecision/*[name(.)!='Result' and name(.)!='Buckets']", // idv-l*
			'//GlobalDecision/Buckets//Bucket', // fbod-perf
		);
		
		$nodes = $xpath->evaluate(implode(' | ', $xpath_queries));
		
		$reason_codes = array();
		foreach ($nodes AS $node)
		{
			$code = NULL;
			
			if ($node->parentNode->tagName == 'GlobalDecision')
			{
				if (preg_match('/^(\w+)bucket$/i', $node->tagName, $matches) && $node->textContent)
				{
					$code = "{$matches[1]}-{$node->textContent}";
				}
			}
			elseif ($node->parentNode->tagName == 'DecisionBuckets' || $node->tagName == 'DecisionBucket')
			{
				$code = $node->textContent;
			}
			elseif ($node->tagName == 'Bucket')
			{
				$code = "{$node->parentNode->tagName}-{$node->textContent}";
			}
			
			if ($code)
			{
				$reason_codes[] = strtoupper($code);
			}
		}
		
		$reason = implode(',', $reason_codes);
		
		return $reason;
	}
	
	/** Finds the final result from DataX for this packet. If cannot find one,
	 * returns NULL.
	 *
	 * @return string 'Y' or 'N' is what DataX normally return for pass/fail.
	 */
	public function getDecisionResult()
	{
		// If DOMDocument is invalid, return failure.
		if (!$this->domdoc) return NULL;
		
		// Different ways to search for results
		$xpath_queries = array(
			'//Summary/Decision', // idv-*
			'//GlobalDecision/Result', // impact*-*, *-perf
		);
		
		$result = $this->searchOneNode(implode(' | ', $xpath_queries));
		
		return $result;
	}
	
	/** Runs XPath with this query and returns the first node's value. If
	 * cannot find a node, returns NULL. If finds more than one, ignores
	 * all the rest and only returns the first.
	 *
	 * @param string $xpath_query The XPath query to run.
	 * @param bool $strict Throw an except if found more than one.
	 * @return string The value in this node. NULL if not found.
	 */
	public function searchOneNode($xpath_query, $strict = FALSE)
	{
		// If DOMDocument is invalid, return failure.
		if (!$this->domdoc) return NULL;
		
		$text = NULL;
		
		$xpath = new DOMXPath($this->domdoc);
		$nodes = $xpath->query($xpath_query);
		
		if ($nodes->length)
		{
			if ($strict && $nodes->length > 1) throw new Exception('More than one node found in XML.');
			
			$text = strtoupper($nodes->item(0)->textContent);
		}
		
		return $text;
	}
}

?>
