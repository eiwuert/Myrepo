<?php

/**
 * Helper for building the response XML. Its main purpose is to provide
 * convenience methods for a {@link CM_ResponseFormat} implementation.
 * @author Andrew Minerd
 */
class CM_ResponseBuilder {
	const ELEMENT_RESPONSE = "tss_loan_response";
	const ELEMENT_SIGNATURE = "signature";
	const ELEMENT_ERRORS = "errors";
	const ELEMENT_CONTENT = "content";
	const ELEMENT_COLLECTION = "collection";
	const ELEMENT_SECTION = "section";
	const ELEMENT_VERBIAGE = "verbiage";

	/**
	 * @var DOMDocument
	 */
	private $doc;
	/**
	 * @var DOMElement
	 */
	private $root;
	/**
	 * @var DOMElement
	 */
	private $signature;
	/**
	 * @var DOMElement
	 */
	private $errors;
	/**
	 * @var DOMElement
	 */
	private $content;
	/**
	 * @var DOMElement
	 */
	private $collection;

	public function __construct() {
		$this->doc = new DOMDocument();
	}

	/**
	 * Returns the built response as an XML string. If pretty is
	 * true, the output will be formatted. This strips the XML header
	 * from generated XML.
	 * @param boolean $pretty Whether to format the output XML
	 * @return string XML output
	 */
	public function toXML($pretty = FALSE) {
		$this->doc->formatOutput = ($pretty === TRUE);

		$out = $this->doc->saveXML();
		return substr($out, strpos($out, "\n"));
	}

	/**
	 * Returns the root tss_loan_response element.
	 * @return DOMElement
	 */
	public function getResponse() {
		if (!$this->root) {
			$this->root = $this->appendElement($this->doc, self::ELEMENT_RESPONSE);
		}
		return $this->root;
	}

	/**
	 * Returns the signature element of the response.
	 * @return DOMElement
	 */
	public function getSignature() {
		if (!$this->signature) {
			$this->signature = $this->appendElement($this->getResponse(), self::ELEMENT_SIGNATURE);
		}
		return $this->signature;
	}

	/**
	 * Sets basic information in the signature based on the given request and response.
	 * This will set the page, site_type, license_key, promo_id, and unique_id elements.
	 * @param CM_Request $request Incoming request
	 * @param CM_Response $response Response from the flow
	 * @return void
	 */
	public function createSignature(CM_Request $request, CM_Response $response) {
		$sig = $this->getSignature();

		$this->createData($sig, "page", $response->getPage());
		$this->createData($sig, "site_type", $request->getSiteType());
		$this->createData($sig, "license_key", $request->getLicenseKey());
		$this->createData($sig, "promo_id", $request->getPromoId());
		$this->createData($sig, "promo_sub_code", $request->getPromoSubCode());
		$this->createData($sig, "unique_id", $response->getSessionId());
	}


	/**
	 * Returns the errors element of the response, creating it if necessary.
	 * @return DOMElement Errors element
	 */
	public function getErrors() {
		if (!$this->errors) {
			$this->errors = $this->appendElement($this->getResponse(), self::ELEMENT_ERRORS);
		}
		return $this->errors;
	}

	/**
	 * Returns the content element of the response, creating it if necessary.
	 * @return DOMElement Content element
	 */
	public function getContent() {
		if (!$this->content) {
			$this->content = $this->appendElement($this->getResponse(), self::ELEMENT_CONTENT);
		}
		return $this->content;
	}

	/**
	 * Returns the collection element of the response, creating it if necessary.
	 * @return DOMElement Collection element
	 */
	public function getCollection() {
		if (!$this->collection) {
			$this->collection = $this->appendElement($this->getResponse(), self::ELEMENT_COLLECTION);
		}
		return $this->collection;
	}

	/**
	 * Creates a new section child in the content element. This will create the
	 * content element if it has not yet been created.
	 * @param DOMNode $after Element to append the new section after
	 * @return DOMElement Section element
	 */
	public function createSection(DOMNode $after = null) {
		if ($after) {
			return $this->appendElementAfter($after, self::ELEMENT_SECTION);
		}
		return $this->appendElement($this->getContent(), self::ELEMENT_SECTION);
	}

	/**
	 * Creates and populates the errors element with the given list of invalid fields.
	 * The messages for each error are sourced from the given {@link CM_ErrorMessages}
	 * instance.
	 * @param array $errors
	 * @param CM_ErrorMessages $messages
	 * @return void
	 */
	public function createErrors(array $errors, CM_ErrorMessages $messages) {
		$el = $this->getErrors();
		foreach ($errors as $field) {
			$description = $messages->getDescription($field);
			$this->createData($el, $field, $description);
		}
	}

	/**
	 * Creates the collection element populated with the given data.
	 * @param array $data
	 * @return void
	 */
	public function createCollection(array $data, array $keys = null) {
		$collection = $this->getCollection();
		if ($keys === null) {
			foreach ($data as $name=>$value) {
				$this->createData($collection, $name, $value);
			}
		} else {
			foreach ($keys as $name) {
				$value = isset($data[$name]) ? $data[$name] : null;
				$this->createData($collection, $name, $value);
			}
		}
	}

	/**
	 * Appends a new section containing a verbiage element. The verbiage element
	 * contains text data that should be displayed to the user.
	 * @param string $text Verbiage to display to the user
	 * @return DOMElement
	 */
	public function createVerbiageSection($text) {
		$section = $this->createSection();
		return $this->appendElement($section, self::ELEMENT_VERBIAGE, $text);
	}

	/**
	 * Appends a new child element to the given node.
	 * @param DOMNode $parent Parent node
	 * @param string $name Element name
	 * @param string $value Initial value, or null
	 * @return DOMElement
	 */
	public function appendElement(DOMNode $parent, $name, $value = null) {
		$el = $this->createElement($name, $value);
		return $parent->appendChild($el);
	}

	/**
	 * Appends a new child element after the given node. The element will be
	 * a child of the given node's parent. The provided node must have a parent,
	 * or an exception will be thrown.
	 * @param DOMNode $after Node to insert the element after
	 * @param string $name Element name
	 * @param string $value Initial value, or null
	 * @return DOMElement New element
	 * @throws InvalidArgumentException if the given node has no parent
	 */
	public function appendElementAfter(DOMNode $after, $name, $value) {
		if (!$after->parentNode) {
			throw new InvalidArgumentException("Node must have a parent");
		}
		$el = $this->creatElement($name, $value);

		$parent = $after->parentNode;
		if ($after->nextSibling) {
			return $parent->insertBefore($el, $after->nextSibling);
		}
		return $parent->appendChild($el);
	}

	/**
	 * Creates a new DOMElement. The new element will be of type $name and will
	 * be initialized with $value, unless it is null, in which case it will be an
	 * empty element.
	 * @param DOMNode $parent
	 * @param string $name Element name
	 * @param string $value Initial value, or null
	 * @param DOMNode $after Optional element to add the new node after
	 * @return DOMElement New element
	 */
	public function createElement($name, $value = null) {
		if ($value !== null) {
			return $this->doc->createElement($name, $value);
		}
		return $this->doc->createElement($name);
	}

	/**
	 * Creates a new data element and appends it as a child to the given parent. The
	 * new element will have a name attribute set to $name, and the value will be
	 * initialized to $value.
	 * @param DOMElement $parent
	 * @param string $name Value of the name attribute
	 * @param string $value Initial value
	 * @return DOMElement New element
	 */
	public function createData(DOMElement $parent, $name, $value) {
		$el = $this->appendElement($parent, "data", $value);
		$el->setAttribute("name", $name);
		return $el;
	}
}