<?php

/**
 * Incoming SOAP request.
 * @author Andrew Minerd
 */
class CM_Request {
	/**
	 * @var SimpleXmlElement
	 */
	private $xml;

	public function __construct(SimpleXmlElement $xml) {
		$this->xml = $xml;
	}

	/**
	 * Returns the license key.
	 * @return string License key
	 */
	public function getLicenseKey() {
		return $this->getValue("signature/data[@name='license_key']");
	}

	/**
	 * Returns the session ID.
	 * @return string Session ID
	 */
	public function getSessionId() {
		return $this->getValue("signature/data[@name='unique_id']");
	}

	/**
	 * Returns the name of the site type.
	 * @return string Site type name
	 */
	public function getSiteType() {
		return $this->getValue("signature/data[@name='site_type']");
	}

	/**
	 * Returns the requested page.
	 * @return string Page name
	 */
	public function getPage() {
		return $this->getValue("signature/data[@name='page']");
	}

	/**
	 * Returns the root URL of the originating site.
	 * @return string Site URL
	 */
	public function getClientUrlRoot() {
		return $this->getValue("collection/data[@name='client_url_root']");
	}

	/**
	 * Returns the IP address of the use (not the SOAP client).
	 * @return string User's IP Address
	 */
	public function getClientIpAddress() {
		return $this->getValue("collection/data[@name='client_ip_address']");
	}

	/**
	 * Returns the request promo ID.
	 * @return string Promo ID
	 */
	public function getPromoId() {
		return $this->getValue("signature/data[@name='promo_id']");
	}

	/**
	 * Returns the value of a single field in the data collection, or null
	 * if the requested field is not in the collection.
	 * @return string Field value
	 */
	public function getField($name) {
		return $this->getValue("collection/data[@name='{$name}']");
	}

	/**
	 * Merges all data fields from the signature and collection sections of the
	 * request and returns them.
	 * @return array Request data
	 */
	public function getData() {
		$data = array();
		$this->loadValues("signature/data", $data);
		$this->loadValues("collection/data", $data);
		return $data;
	}

	private function getValue($xpath) {
		$results = $this->xml->xpath($xpath);
		if ($results) {
			return (string)$results[0];
		}
		return null;
	}

	private function loadValues($xpath, array &$data) {
		$results = $this->xml->xpath($xpath);
		foreach ($results as $v) {
			$name = (string)$v['name'];

			if (isset($v->subdata)) {
				foreach ($v->subdata as $sub) {
					$data[$name.'__'.(string)$sub['name']] = (string)$sub;
				}
			} else {
				$data[$name] = (string)$v;
			}
		}
	}
}