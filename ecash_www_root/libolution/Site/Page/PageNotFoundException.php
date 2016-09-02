<?php

/**
 * An exception that can be thrown if a Site_Page_Dispatcher cannot find a 
 * page for the given request.
 *
 */
class Site_Page_PageNotFoundException extends RuntimeException 
{
	
	/**
	 * @var Site_Request
	 */
	private $request;
	
	/**
	 * Creates a new "Page Not Found" exception.
	 * 
	 * Pass the request used for the page as the first parameter. If values 
	 * are not passed for the message or code than the following defaults will 
	 * be used:
	 * 
	 * <ul>
	 *   <li><b>$message</b>: "Could not find the requested page"</li>
	 *   <li><b>$code</b>: (int)0</li>
	 * </ul>
	 *
	 * @param Site_Request $request
	 * @param string $message
	 * @param int $code
	 */
	public function __construct(Site_Request $request, $message = "Could not find requested page", $code = 0)
	{
		parent::__construct($message, $code);
		$this->request = $request;
	}
	
	/**
	 * Returns the request
	 *
	 * @return unknown
	 */
	public function getRequest()
	{
		return $this->request;
	}
}
?>