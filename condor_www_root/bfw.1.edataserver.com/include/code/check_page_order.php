<?
/**
  	@publicsection
	@public
	@brief
		Makes sure an applicant can't skip a page
	@version
		$Revision: 4226 $
	@todo
		
*/

class Check_Page_Order
{
	public $sql;
	public $db2;

	function __construct($sql, $db2)
	{
		$this->sql =& $sql;
		$this->db2 =& $db2;
	}

	/**
	* @return array An array of errors, or NULL if there were no errors
	* @desc Verify the user has completed the pages in the order specified in the site_type 
	*       Set page to default if the order is invalid
	*
	**/
	public static function Check_Page_Order(&$config, &$current_page, $page_trace)
	{
		$errors = Array();

		if (isset($config->site_type_obj->page_order) && isset($page_trace) && count($config->site_type_obj->page_order))
		{
			//  retrieve current page key value in page_order
			if (!is_numeric($current_page_key = array_search( $current_page, $config->site_type_obj->page_order)))
			{
				// if current page was not found in the array_search, run through each value to scour for sub arrays containing multiple pages
				foreach($config->site_type_obj->page_order as $page_order_key => $page_sub_array)
				{
					if (is_array($page_sub_array) && strlen(array_search($current_page, $page_sub_array)))
						$current_page_key = $page_order_key;
				}
			}

			// if the current page was found in the page order array
			// make sure all of the pages prior in the page_order also exist
			// in the page order before the current page
			if (is_numeric($current_page_key) && $current_page_key>0)
			{
				// remove any of the pages after the current page
				$prior_pages = array_slice($config->site_type_obj->page_order, 0, $current_page_key);
				
				// strip time key from the page trace
				$page_trace = array_values($page_trace);
				
				//  loop through the prior pages and make sure they are all in the page_trace
				foreach ($prior_pages as $page)
				{
					//  if the current page is an array  run through each to check
					if (is_array($page))
					{
						foreach($page as $sub_page)
						{
							if ( is_numeric(array_search($sub_page, $page_trace)) )
								$found = TRUE;
						}
					} 
					elseif (is_numeric(array_search($page, $page_trace)))
					{
						$found = TRUE;
					}

					// if there was nothing found then return invalid_session error
					if (!$found)
						$invalid_session = TRUE;
					
					// reset found flag
					$found = FALSE;
				}
				
				if ($invalid_session)
				{
					$errors[] = 'invalid_session';	
				}
			}
			
		}

		if (sizeof($errors))
			return $errors;

		return NULL;
	}


}
