<?php

/**
 * Reads in an XML config file and converts it into an easily accessible array.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_Config_XML extends Collections_List_1
{
	/**
	 * Loads an XML file.
	 *
	 * @param string $xml_filename
	 * @param bool $append
	 * @return bool
	 */
	public function loadXMLFile($xml_filename, $append = FALSE)
	{
		$loaded = FALSE;
		
		$dom_xml = new DOMDocument();
		if (@$dom_xml->load($xml_filename))
		{
			$this->loadDOMDocument($dom_xml, $append);
			$loaded = TRUE;
		}
		
		return $loaded;
	}
	
	/**
	 * Loads in a string containing XML.
	 *
	 * @param string $xml_string
	 * @param bool $append
	 * @return bool
	 */
	public function loadXMLString($xml_string, $append = FALSE)
	{
		$loaded = FALSE;
		
		$dom_xml = new DOMDocument();
		if (@$dom_xml->loadXML($xml_string))
		{
			$this->loadDOMDocument($dom_xml, $append);
			$loaded = TRUE;
		}
		
		return $loaded;
	}
	
	/**
	 * Loads in a DOMDocument.
	 *
	 * @param DOMDocument $simple_xml
	 * @param bool $append
	 * @return void
	 */
	public function loadDOMDocument(DOMDocument $dom_xml, $append = FALSE)
	{
		$data = $this->domDocumentToArray($dom_xml);
		
		$this->processAllExtends($data);
		
		if ($append)
		{
			$this->items = $this->mergeArrayOverwrite($this->items, $data);
		}
		else
		{
			$this->items = $data;
		}
	}
	
	/**
	 * Merges two arrays together, overwriting any non-array differences.
	 *
	 * @param array $original
	 * @param array $appending
	 * @return array
	 */
	protected function mergeArrayOverwrite(array $original, array $appending)
	{
		foreach ($appending AS $key => $value)
		{
			if (isset($original[$key]) && is_array($value) && is_array($original[$key]))
			{
				$original[$key] = $this->mergeArrayOverwrite($original[$key], $value);
			}
			else
			{
				$original[$key] = $value;
			}
		}
		
		return $original;
	}
	
	/**
	 * Returns the array of config data.
	 *
	 * @return array
	 */
	public function getAsArray()
	{
		return $this->items;
	}
	
	/**
	 * Processes all extends, depth first.
	 *
	 * @param array &$data
	 * @param array $parent_chain
	 * @return void
	 */
	protected function processAllExtends(array &$data, array $parent_chain = array())
	{
		$current = $this->getDataFromChain($data, $parent_chain);
		
		if (is_array($current))
		{
			foreach ($current AS $key => $value)
			{
				$current_chain = array_merge($parent_chain, array($key));
				
				$this->processAllExtends($data, $current_chain);
				
				if (isset($value['@attributes'], $value['@attributes']['extends']))
				{
					// Process our extends target first
					$this->processAllExtends($data, $this->getChainFromExtend($value['@attributes']['extends'], $current_chain));
					
					// Now process us
					$this->processSingleExtend($data, $current_chain);
				}
			}
		}
	}
	
	/**
	 * Processes the extends on one object, recursively.
	 *
	 * @param array &$data
	 * @param array $chain
	 * @param array $history
	 * @return void
	 */
	protected function processSingleExtend(array &$data, array $chain, array $history = array())
	{
		$serialized_chain = serialize($chain);
		
		if (array_key_exists($serialized_chain, $history))
		{
			throw new UnexpectedValueException("Could not process config extends; hit recursion.");
		}
		
		$history[$serialized_chain] = TRUE;
		
		$current = &$data;
		foreach ($chain AS $item)
		{
			if (isset($current[$item]))
			{
				$current = &$current[$item];
			}
			else
			{
				throw new UnexpectedValueException("Extended location does not exist.");
			}
		}
		
		if (isset($current['@attributes'], $current['@attributes']['extends']))
		{
			// Find our extended locations
			$extends = array_map('trim', explode(',', $current['@attributes']['extends']));
			foreach ($extends AS $extend)
			{
				$extend_chain = $this->getChainFromExtend($extend, $chain);
				$extend_data = $this->getDataFromChain($data, $extend_chain);
				
				if (isset($extend_data['@attributes'], $extend_data['@attributes']['extends']))
				{
					// If what we are extending extends something else, need to process that first
					$this->processSingleExtend($data, $extend_chain, $history);
					
					$extend_data = $this->getDataFromChain($data, $extend_chain);
				}
				
				if (is_array($extend_data))
				{
					$current = $this->mergeArrayOverwrite($extend_data, $current);
				}
				else
				{
					$current = $extend_data;
				}
			}
			
			// Clean up that this extend is done.
			if (is_array($current))
			{
				unset($current['@attributes']['extends']);
				if (empty($current['@attributes'])) unset($current['@attributes']);
			}
		}
	}
	
	/**
	 * Return the data found for a call chain.
	 *
	 * @param array $data
	 * @param array $chain
	 * @param bool $use_base
	 * @return array
	 */
	protected function getDataFromChain(array $data, array $chain, $use_base = TRUE)
	{
		$found = TRUE;
		$current = $data;
		
		foreach ($chain AS $item)
		{
			if (isset($current[$item]))
			{
				$current = $current[$item];
			}
			else
			{
				$found = FALSE;
				break;
			}
		}
		
		if (!$found)
		{
			if ($use_base)
			{
				$current = $this->getDataFromChain($this->items, $chain, FALSE);
			}
			else
			{
				throw new UnexpectedValueException("Passed in an invalid parent chain: " . print_r($chain, TRUE));
			}
		}
		
		return $current;
	}
	
	/**
	 * Converts an extends string to an extends chain.
	 *
	 * @param string $extend
	 * @param array $chain
	 * @return array
	 */
	protected function getChainFromExtend($extend, array $chain)
	{
		$position = strpos($extend, '/');
		if ($position === 0)
		{
			// If we start with a /, use absolute path.
			$extend_chain = array_map('trim', explode('/', substr($extend, 1)));
		}
		elseif ($position !== FALSE)
		{
			// If we have a /, it is relative to our current chain
			$extend_chain = $chain;
			array_pop($extend_chain);
			$extend_chain = array_merge(
				$extend_chain,
				array_map('trim', explode('/', $extend))
			);
		}
		else
		{
			// If no /, it is a sibling of our current chain
			$extend_chain = $chain;
			array_pop($extend_chain);
			array_push($extend_chain, $extend);
		}
		
		return $extend_chain;
	}
	
	/**
	 * Converts a DOMDocument into an array.
	 *
	 * @param DOMDocument $dom_xml
	 * @return array
	 */
	protected function domDocumentToArray(DOMDocument $dom_xml)
	{
		$simple_array = $this->domNodeToArray($dom_xml->firstChild);
		
		if (!is_array($simple_array))
		{
			$simple_array = array($simple_array);
		}
		
		return $simple_array;
	}
	
	/**
	 * Converts a DOMNode into an array by hand.
	 *
	 * @param DOMNode $node
	 * @return array
	 */
	protected function domNodeToArray(DOMNode $node)
	{
		$data = FALSE;
		
		if ($node->nodeType == XML_ELEMENT_NODE)
		{
			$data = array();
			
			foreach ($node->childNodes AS $child)
			{
				if ($child->nodeType == XML_ELEMENT_NODE)
				{
					$child_data = $this->domNodeToArray($child);
					
					if (isset($data[$child->nodeName]))
					{
						if (!is_array($data[$child->nodeName]))
						{
							$data[$child->nodeName] = array($data[$child->nodeName]);
						}
						
						$data[$child->nodeName][] = $child_data;
					}
					else
					{
						$data[$child->nodeName] = $child_data;
					}
				}
			}
			
			if (empty($data) && !empty($node->nodeValue))
			{
				$data = (string)$node->nodeValue;
			}
			elseif ($node->hasAttributes())
			{
				$data['@attributes'] = array();
				
				foreach ($node->attributes AS $attribute)
				{
					$data['@attributes'][$attribute->nodeName] = $attribute->nodeValue;
				}
			}
		}
		
		return $data;
	}
}

?>
