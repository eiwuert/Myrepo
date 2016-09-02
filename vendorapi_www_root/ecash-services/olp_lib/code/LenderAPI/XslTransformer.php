<?php
/**
 * Transformation object used for Vendor API post.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package LendorAPI
 */
class LenderAPI_XslTransformer
{
	/**
	 * List of Traversable objects indexed by node name (or int).
	 * @var array
	 */
	protected $data_sources = array();
	
	/**
	 * List of xsl parameters to be set from constants to be using in incoming xsl
	 * @var array
	 */
	protected $parameters = array();

	/**
	 * The DOMDocument containing the Xsl.
	 * @var DOMDocument
	 */
	protected $xsl;

	public function getXsl()
	{
		if ($this->xsl)
		{
			return $this->xsl->saveXML();
		}
		return FALSE;
	}

	/**
	 * Add an XSL stylesheet.
	 *
	 * @throws InvalidArgumentException
	 * @param mixed $xsl DOMDocument representing an XSL stylesheet
	 * @return void
	 */
	public function setXsl($xsl)
	{
		if (!$xsl instanceof DOMDocument && $xsl)
		{
			$doc = new DOMDocument();

			// Replace the @@path@@ token with the dir we are running in.  Mainly
			// used from some vendor xslt to be able to access exslt/*.function.xsl
			$xsl = preg_replace('/@@path@@/', dirname(__FILE__), $xsl);

			// @ is used here because some Lenders submit stuff with relative URIs
			// or whatever which throws warnings which can break json output
			$loaded = @$doc->loadXML($xsl);

			if ($loaded == FALSE)
			{
				throw new LenderAPI_XMLParseException(sprintf(
					'arg passed to %s must be string or DOMDocument, got %s',
					__METHOD__,
					var_export($xsl, TRUE))
				);
			}
			$xsl = $doc;
		}

		$this->xsl = $xsl;
	}

	/**
	 * Add a data source which will be turned into XML and then transformed.
	 *
	 * @throws InvalidArgumentException
	 * @param Traversable $source List of data with keynames which are valid
	 * xml tags.
	 * @param string $nodename Without this parameter, data will simply be added
	 * to the top level data element in the generated source XML.
	 * @return void
	 */
	public function addDataSource(Traversable $source, $nodename = NULL)
	{
		if ($nodename && !is_string($nodename))
		{
			throw new InvalidArgumentException(
				'node name must be valid XML tag, not ' . var_export($nodename, TRUE)
			);
		}

		if ($nodename)
		{
			$this->data_sources[$nodename] = $source;
		}
		else
		{
			$this->data_sources[] = $source;
		}
	}
	
	/**
	 * Set a parameter to be used in the xsl
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function addParameter($name, $value)
	{
		$this->parameters[$name] = $value;
	}

	/**
	 * Produces XML based on the loaded XSL Stylesheet and data sources/data XML
	 *
	 * @param string $xml XML to use as data source, which overrides the internal
	 * data sources. (Consider just using XSLTProcessor.)
	 * @return string XML
	 */
	public function transform($xml = NULL)
	{
		if (!$xml instanceof DOMDocument)
		{
			$xml = $this->getSourceDocument($xml);
		}

		if ($this->xsl instanceof DOMDocument)
		{
			$this->xsl->xinclude();
			$processor = new XSLTProcessor();
			$processor->importStylesheet($this->xsl);
			
			// Set parameters in the XSLTProcessor based on the constants.
			// Any parameters not set in the xsl will just be ignored.
			foreach ($this->parameters as $name => $value)
			{
				$processor->setParameter('', $name, $value);
			}

			$xml = $processor->transformToXml($xml);
			if ($xml === FALSE)
			{
				throw new Exception('invalid XML transform: ' . $this->xsl->saveXML());
			}
		}
		else
		{
			/* @var $xml DOMDocument */
			$xml = $xml->saveXML();
		}
		return $xml;
	}

	/**
	 * Returns a DOMDocument from either the XML string passed in or data sources
	 *
	 * @param string $xml Optional, overriding XML.
	 * @return DOMDocument
	 */
	public function getSourceDocument($xml = NULL)
	{
		if ($xml && is_string($xml))
		{
			$source = new DOMDocument();
			
			// [#28304] Added for responses with non-conforming leading xml whitespace
			$xml = trim($xml);
			
			ob_start();
			$was_loaded = $source->loadXML($xml);
			$ob = ob_get_clean();
			if (!$was_loaded)
			{
				throw new InvalidArgumentException(sprintf(
					"loadXML failed with output:\n%s", $ob)
				);
			}
		}
		else
		{
			$source = $this->createDomFromSources();
		}

		return $source;
	}

	/**
	 * Interprets traversable data sources to produce a dom document.
	 *
	 * The structure of this DOM document will be:
	 * <data>
	 * 	<key>value</key>	<!-- when data source is indexed numerically -->
	 * 	<subsection>		<!-- for each non-numeric indexed data source -->
	 * 		<key>value</key>
	 * 	</subsection>
	 * </data>
	 *
	 * @return DOMDocument
	 */
	protected function createDomFromSources()
	{
		$doc = new DOMDocument();

		$data = $doc->createElement('data');

		foreach ($this->data_sources as $node_name => $traversable)
		{
			if (is_numeric($node_name))
			{
				$node = $data;
			}
			else
			{
				/* @var $data DOMElement */
				$node = $doc->createElement($node_name);
				$data->appendChild($node);
			}
			
			if (is_array($traversable) || $traversable instanceof Traversable)
			{
				$this->addDomChildren($doc, $node, $traversable);
			}
		}

		$doc->appendChild($data);

		return $doc;
	}
	
	/**
	 * Adds traversable children to a node.  This is a recursive function that
	 * allows as many sub data elements as necessary in the source xml 
	 * 
	 * @param DOMDocument $doc
	 * @param DOMNode $node
	 * @param mixed $traversable
	 * @return void
	 */
	protected function addDomChildren(DOMDocument $doc, DOMNode $node, $traversable)
	{
		foreach ($traversable as $key => $value)
		{
			if (is_array($value) || $value instanceof Traversable)
			{
				$sub_node = $doc->createElement($key);
				$node->appendChild($sub_node);
				
				$this->addDomChildren($doc, $sub_node, $value);
			}
			else
			{
				$ele = $doc->createElement($key);
				$node->appendChild($ele);
				$ele->appendChild($doc->createTextNode($value));
			}
		}
	}
}
?>
