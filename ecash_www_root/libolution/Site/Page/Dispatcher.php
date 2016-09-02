<?php

/**
 * Abstract class for dispatching requests to the correct pages.
 *
 * @author Mike Lively <mike.lively@sellingsource.com>
 * @package Site
 */
abstract class Site_Page_Dispatcher implements Site_IRequestProcessor
{

	/**
	 * Processes a request object.
	 * 
	 * @param Site_Request $request
	 * @return Site_IResponse
	 */
	public function processRequest(Site_Request $request)
	{
		$page = $this->getPage($request);
		
		return $page->processRequest($request);
	}

	/**
	 * Factories a page object, given a request.
	 * 
	 * @param Site_Request $request
	 * @return Site_IRequestProcessor
	 * @throws Site_Page_PageNotFoundException
	 */
	abstract protected function getPage(Site_Request $request);
}

?>
