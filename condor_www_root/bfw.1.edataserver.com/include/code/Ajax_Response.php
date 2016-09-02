<?php
/**
 * An response to an Ajax request?
 *
 */

class Ajax_Response
{
	protected $data;
	
	public function __construct($data = NULL)
	{
		$this->data = $data instanceof stdClass ? $data : new stdClass;
	}
	
	public function __set($key, $val)
	{
		$this->data->$key = $val;
	}
	
	public function __get($key)
	{
		return isset($this->data->$key) ? $this->data->$key : NULL;
	}
	
	public function __unset($key)
	{
		if(isset($this->data->$key)) 
		{ 
			unset($this->data->$key);
		}
	}
	
	public function __isset($key)
	{
		return isset($this->data->$key);
	}
	
	/**
	 * Recursively adds $data to the element. $data is 
	 * an object or array. It'll loop through the key->val
	 * pairs and use key as the XML element name and add them
	 * to the document or some such.
	 *
	 * @param DOMElement $root
	 * @param mixed $data
	 */
	private function _gen_dom_object(DOMElement $root, $data)
	{
		if(is_object($data) || is_array($data))
		{
			foreach($data as $key => $val)
			{
				if(is_object($val) || is_array($val))
				{
					$elem = new DOMElement($key);
					$root->appendChild($elem);
					$this->_gen_dom_object($elem, $val);
					
				}
				else
				{
					$elem = new DOMElement($key, $val);
					$root->appendChild($elem);
				}
			}
		}
	}
	
	/**
	 * Creates a DOM Document to return.
	 *
	 * @return DOMDocument
	 */
	public function Generate_DOM_Object()
	{
		$doc = new DOMDocument();
		$root_element = new DOMElement('Response');
		$doc->appendChild($root_element);
		
		$this->_gen_dom_object($root_element, $this->data);
		
		return $doc;
	}
	
	/**
	 * Returns a representation of the data as XML
	 *
	 * @param boolean $formatted
	 * @return string
	 */
	public function Get_As_XML($formatted = false)
	{
		$doc = $this->Generate_DOM_Object();
		$doc->formatOutput = $formatted;
		return $doc->saveXML();
	}
	
	/**
	 * Returns the object as in JSON
	 *
	 */
	public function Get_As_JSON()
	{
		require_once('Zend/Json.php');
		return Zend_Json::Encode($this->data);
	}
}
