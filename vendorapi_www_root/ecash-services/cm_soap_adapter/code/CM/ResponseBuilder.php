<?php

/**
 * Helper for building the response XML. This provides convenience
 * methods for a {@link CM_ResponseFormat} implementation.
 * @author Andrew Minerd
 */
class CM_ResponseBuilder {
	const ELEMENT_RESPONSE = "tss_loan_response";
	const ELEMENT_SIGNATURE = "signature";
	const ELEMENT_ERRORS = "errors";
	const ELEMENT_CONTENT = "content";
	const ELEMENT_COLLECTION = "collection";
	const ELEMENT_SECTION = "section";

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
	 * true, the output will be formatted.
	 * @param boolean $pretty Whether to format the output XML
	 * @return string XML output
	 */
	public function toXML($pretty = FALSE) {
		$this->doc->formatOutput = ($pretty === TRUE);
		return $this->doc->saveXML();
	}

	/**
	 * Returns the root tss_loan_response element.
	 * @return DOMElement
	 */
	public function getResponse() {
		if (!$this->root) {
			$this->root = $this->createElement($this->doc, self::ELEMENT_RESPONSE);
		}
		return $this->root;
	}

	/**
	 * Returns the signature element of the response.
	 * @return DOMElement
	 */
	public function getSignature() {
		if (!$this->signature) {
			$this->signature = $this->createElement($this->getResponse(), self::ELEMENT_SIGNATURE);
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
		$this->createData($sig, "unique_id", $response->getSessionId());
	}


	/**
	 * Returns the errors element of the response, creating it if necessary.
	 * @return DOMElement Errors element
	 */
	public function getErrors() {
		if (!$this->errors) {
			$this->errors = $this->createElement($this->getResponse(), self::ELEMENT_ERRORS);
		}
		return $this->errors;
	}

	/**
	 * Returns the content element of the response, creating it if necessary.
	 * @return DOMElement Content element
	 */
	public function getContent() {
		if (!$this->content) {
			$this->content = $this->createElement($this->getResponse(), self::ELEMENT_CONTENT);
		}
		return $this->content;
	}

	/**
	 * Returns the collection element of the response, creating it if necessary.
	 * @return DOMElement Collection element
	 */
	public function getCollection() {
		if (!$this->collection) {
			$this->collection = $this->createElement($this->getResponse(), self::ELEMENT_COLLECTION);
		}
		return $this->collection;
	}

	/**
	 * Creates a new section child in the content element. This will create the
	 * content element if it has not yet been created.
	 * @return DOMElement Section element
	 */
	public function createSection() {
		return $this->createElement($this->getContent(), self::ELEMENT_SECTION);
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
	public function createCollection(array $data) {
		$collection = $this->getCollection();
		foreach ($data as $name=>$value) {
			$this->createData($collection, $name, $value);
		}
	}

	/**
	 * Creates a new DOMElement and appends it as a child to the given parent. The
	 * new element will be of type $name and will be initialized with $value,
	 * unless it is null, in which case it will be an empty element.
	 * @param DOMNode $parent
	 * @param string $name Element name
	 * @param string $value Initial value, or null
	 * @return DOMElement New element
	 */
	public function createElement(DOMNode $parent, $name, $value = null) {
		if ($value === null) {
			$el = $this->doc->createElement($name);
		} else {
			$el = $this->doc->createElement($name, $value);
		}
		$parent->appendChild($el);
		return $el;
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
		$el = $this->createElement($parent, "data", $value);
		$el->setAttribute("name", $name);
		return $el;
	}
}