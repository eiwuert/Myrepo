<?php
/**
	@publicsection
	@public
	@brief
		Handles Site_Type/Validation rules Management
	@version
		1.0.0 2005-03-31 - A class to manage site types, validation, and normalization for base frame work
	@author:
		Don Adriano - version 1.0.0
	@todo


*/

class Site_Type_Manager
{
	public $sql;
	
	function __construct( $sql, $database )
	{
		$this->sql = &$sql;
		$this->database = $database;
	}	
	
	/**
	* @brief
	*    A function to retrieve and assemble the site_type from the new site_types2 db
	* @param $site_type            string:      site type name
    * @return
	*    object replicating the original site_type_obj's
	*/
	public function Get_Site_Type( $site_type )
	{
	  $site_type_obj = new stdClass();
	  
	  try
		{
      
      // set page_order
      $site_type_obj->page_order = $this->Site_Type_Page_Order( $site_type );
      
      // retreive page fields
      $query = "
          SELECT
      		st.*,
      		sp.page_name,sp.stat,
              pv.*
          FROM
      		site_type st
              LEFT JOIN site_pages sp ON (st.st_id = sp.st_id) 
              LEFT JOIN page_validation pv ON (sp.page_id = pv.page_id) 
          WHERE
              st.name = '" . $site_type . "'
      	ORDER BY sp.page_name
               ";
      $results = $this->sql->Query( $this->database, $query );

      // Loop through the result and set the page->fields
      while( $row = $this->sql->Fetch_Array_Row( $results ) )
      {
				
      	// save stat to be hit on this page
      	if(is_string($row["stat"]) && !isset($site_type_obj->pages->{$row['page_name']}->stat))
      	{
      		$site_type_obj->pages->{$row['page_name']}->stat = $row["stat"];
      	}
      	
      	if (!$site_type_id)	$site_type_id = $row['st_id'];
	      
				if ($row['field'])
				{
					
					$rules = array('required' => $row['validation_requirement'],
						'type' => $row['type'],	'min' => $row['min'],
						'max' => $row['max'],	'enum' => unserialize($row['enum_check']));
					
					// for email addresses: check the domain
					if ($row['type'] == 'email') $rules['ck_mx'] = TRUE;
					
					$site_type_obj->pages->{$row['page_name']}->{$row['field']} = $rules;
					
				}
				else 
				{
					$site_type_obj->pages->{$row['page_name']} = new stdClass();
				}
				
      }
      
		}
		catch( MySQL_Exception $e )
		{
			throw $e;
		}
		
    foreach ($site_type_obj->pages as $page_name => $field)
    {
    	
      // see if this page is in the page order array
      // if it is and it is not the last one in the order
      // we need to set the next page
      $page_order_num = array_search($page_name, $site_type_obj->page_order);
      
      if ( is_numeric($page_order_num) )
      {
      	
      	$next_page_key = ($page_order_num + 1);
      	$next_page = $site_type_obj->page_order[$next_page_key];
      	
      	if ($next_page_key < count($site_type_obj->page_order) && $next_page)
      	{
      		// set the next page to the page
      		$site_type_obj->pages->{$page_name}->next_page = $next_page;	
      	}
      	
      }
      
    }
    
    return($site_type_obj);
    
	}
	
	/**
	* @brief
	*    function to retreive page order for a site_type_obj
	* @param $site_type            string:      site type name
    * @return
	*    array of the page names in ascending order
	*/
	public function Site_Type_Page_Order( $site_type )
	{
        $page_order = array();
        
        $query = "
            SELECT
        		sp.*     
            FROM
        		site_type st LEFT JOIN site_pages sp ON (st.st_id = sp.st_id)
            WHERE
                st.name = '" . $site_type . "' 
        	AND sp.order_id > 0  
        	ORDER BY order_id ASC
                 ";
		
        try
        {
        	$results = $this->sql->Query( $this->database, $query );

	        // Loop through the results and populate our object's val_rules and data.
	        while( $row = $this->sql->Fetch_Array_Row( $results ) )
	        {
	        	$page_order[] = $row["page_name"];
	        }
        }
        catch( MySQL_Exception $e )
        {
        	throw $e;
        }
     
        return $page_order;
	}
	
	
	/**
	* @publicsection
	* @public
	* @author:    Kevin Kragenbrink
	* @fn object Get_Validation_Rules($page, $data, $site_type_id)
	* @brief
	*    A function to populate the validation rules for a site type object.
	*    This function will reference the Site_Types2 database and pull out all the necessary validation
	*    rules for the current page.  It will then return those rules in a stdClass object for use in
	*    data validation.
	* @param $page                 string:      The name of the page for which we wish to retrieve
	*                                           validation rules.  If nothing is passed in, we'll use
	*                                           $this->current_page.
    * @param $data                 array:       The data to be validated.  If nothing is passed in, we'll
    *                                           use $this->data_array.
	* @param $site_type_id         int:         An integer identifier for the current site_type located in
	*                                           in the Site_Types2 database.  If nothing is passed in, we'll
	*                                           use $this->site_type_id.
	* @return
	*    An object is returned containing:
	*    1) ->val_rules            array:      Stores all the validation fields and rules for the page.
	*    2) ->data                 array:      Data to be validated.
	*/
	public function Get_Validation_Rules($page = NULL, $site_type_id = NULL)
	{
        $validation_rules = array();
        
        // Make sure we have useable values.
        $page         = $page ? $page : $this->current_page;
        $data         = $data ? $data : $this->data_array;
        $site_type_id = $site_type_id ? $site_type_id : $this->site_type_id;
        
        $query = "
            SELECT
                pv.*    
            FROM
                site_pages              AS  pg
            JOIN page_validation as pv
            ON 
          		(pv.page_id = pg.page_id)
			WHERE
            AND
                pg.st_id = " . $site_type_id . "
            AND
                pg.page_name = '" . $page . "'
                 ";
        
        try
        {
	        
        	$results = $this->sql->Query( $this->database, $query );
	        
        	// Loop through the results and populate our object's val_rules and data.
	        while( $rule = $this->sql->Fetch_Array_Row( $results ) )
	        {
            
	        	$validation_rules[$rule['field']]['required'] = $rule['validation_requirement'];
            $validation_rules[$rule['field']]['type']     = $rule['type'];
            $validation_rules[$rule['field']]['min']      = $rule['min'];
            $validation_rules[$rule['field']]['max']      = $rule['max'];
            $validation_rules[$rule['field']]['enum']     = unserialize($rule['enum_check']);
            
            // for email addresses: check the domain
            if (strtolower($rule['type']) == 'email')
            {
            	$validation_rules[$rule['field']]['ck_mx'] = TRUE;
            }
            
	        }
	        
        }
        catch( MySQL_Exception $e )
        {
        	throw $e;
        }
        
        return $validation_rules;
	}
	
}

?>