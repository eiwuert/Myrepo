<?php
/** 			
	@version:
			1.0.0 2005-04-16 - return visitor logic for olp
	@author:	
			Jason Duffy - version 1.0.0
	@Updates:	
	
	@Todo:  
*/


class Return_Handler
{
	/**
	* @return Return_Handler
	* @param $config object
	* @param $page_trace array
	* @param $applog object
	* @desc constructor
	*
	**/
	function __construct(&$config, &$page_trace, &$applog)
	{
		$this->config = $config;
		$this->page_trace = $page_trace;
		$this->applog = $applog;
	}

	/**
	* @return string (page to go to)
	* @param $app_completed boolean
	* @desc figure out where the return_visitor should go
	*
	**/
	function Get_Return_Page( $app_completed, $unique_id )
	{
		// Two cases:
		//   Returning but didn't finish
		//   Returning but did finish
		// Find the correct place to send them back to
		$reversed_page_order = array_reverse($this->config->site_type_obj->page_order);
		if (!$app_completed)
		{
			// Find the newest page in page_trace that 
			//  a) exists in site_type_obj->pages AND
			//  b) has a next_page
			$visited_pages = array_unique($this->page_trace);
			$found_page = null;
			foreach ($reversed_page_order as $page_name)
			{
				if (in_array($page_name, $visited_pages)) {
					$found_page = $page_name;
					break;
				}				
			}
			
			if($found_page==null)
			{
				if( $_SESSION['application_id'] )
				{
					$this->applog->Write( 'Unable to return visitor to last page due to no valid page trace. [App_ID: ' . $_SESSION['application_id'] . '][Unique ID: ' . $unique_id . ']' );
				}
				elseif( $unique_id )
				{
					$this->applog->Write( 'Unable to return visitor to last page due to no valid page trace.  Cannot find application ID. [Unique ID: ' . $unique_id . ']' );
				}
				else 
				{
					$this->applog->Write( 'Unable to return visitor to last page due to empty session and no unique ID.' );
				}
				$found_page = reset($this->config->site_type_obj->page_order);
			}

		}
		else
		{
			// Find the thankyou page they got.  Go to the last page
			// in site_type_obj->pages and put them there.
			$found_page = end($this->config->site_type_obj->page_order); 
		}

		return $found_page;
	}
}
