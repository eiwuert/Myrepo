<?php

/**
 * Wrapper around a response from customer service.
 * @author Andrew Minerd
 */
class CM_Response {
	private $response;

	public function __construct($response) {
		$this->response = $response;
	}

	/**
	 * Returns the page that should be displayed. In some cases,
	 * the page name is mapped.
	 * @return string Page name
	 */
	public function getPage() {
		$page = $this->response->nextPage;
		if ($page == "bb_thanks") {
			$page = "app_completed";
		}
		return $page;
	}

	/**
	 * Returns the session ID from the response. This may be different
	 * from the request, as it may have been generated.
	 * @return string Session ID
	 */
	public function getSessionId() {
		return $this->response->data['unique_id'];
	}

	/**
	 * Returns the list of invalid fields, if any.
	 * @return array Array of invalid fields
	 */
	public function getErrors() {
		if (isset($this->response->errors)) {
			return (array)$this->response->errors;
		}
		return array();
	}

	/**
	 * Returns all tokens.
	 * @return array Tokens
	 */
	public function getTokens() {
		return $this->response->data;
	}

	/**
	 * Returns the value of a single token if it exists, or null otherwise.
	 * @param string $name Token name
	 * @return string Token value, or null
	 */
	public function getToken($name) {
		if (isset($this->response->data[$name])) {
			return $this->response->data[$name];
		}
		return null;
	}
}