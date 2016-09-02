<?php

class JmsStats_Dispatcher extends Site_Page_Dispatcher {
	protected $prefix;

	public function __construct($prefix) {
		$this->prefix = $prefix;
	}

	protected function getPage(Site_Request $request) {
		$page_name =  $this->filterName(basename($request->getURI()));

		$class_name = $this->prefix.$page_name;
		if (!class_exists($class_name)) {
			throw new Exception("Could not find page, {$class_name}");
		}
		return new $class_name();
	}

	protected function filterName($name) {
		return preg_replace('/[^a-zA-Z0-9_]/', '', $name);
	}
}