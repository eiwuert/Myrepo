<?php

/**
 * @publicsection
 * @public
 * @author
 *     Kevin Kragenbrink
 * @brief
 *     Writes the errors array to a mysql database.
 *
 *     This class will pack up the errors information and fork out another process
 *     to load the errors into the errors database.
 * @version
 *     1.0.0        2005-04-01 - Kevin Kragenbrink
 * @updates
 *
 * @todo
 *     - This may not really need to be forked out.  Analyze error_thread.2.php and
 *       determine whether this process could be handled more efficiently right here.
 */

class Error_Logging
{
	protected $error_pack;

	public function __construct()
	{
		return;
	}
	
   /**
    * @publicsection
    * @public
    * @fn void Pack_Errors( $errors, $page, $property, $session_id, $created_date, $application, $site_type )
    * @brief
    *     Packs the errors up into a single array, then sends it off to Write.
    * @param    errors          array:      The errors array from BFW.
    * @param    page            string:     The name of the page the user is currently on.
    * @param    property        string:     The abbreviation of the site property. (OPTIONAL)
    * @param    session_id      string:     The user's Session ID. (OPTIONAL)
    * @param    created_date    int:        The time the user began their session. (OPTIONAL)
    * @param    application     string:     The filename of the application type. (OPTIONAL)
    * @param    site_type       string:     The name of the site_type. (OPTIONAL)
    * @return 
    *     Nothing.
    * @todo
    */
	public function Pack_Errors($errors, $page, $property = NULL, $session_id = NULL, $created_date = NULL, $application = NULL, $site_type = NULL)
	{
	    if( is_array( $errors ) && count( $errors ) > 0 )
	    {
	        $this->error_pack['created_date']  = $created_date ? $created_date : $_SESSION['config']->created_date;
	        $this->error_pack['property']      = $property ? $property : $_SESSION['config']->property_short;
	        $this->error_pack['sessid']        = $session_id ? $session_id : $_SESSION['data']['unique_id'];
	        $this->error_pack['application']   = $application ? $application : $_SESSION['config']->site_type_obj->documents->application;
	        $this->error_pack['formtype']      = $site_type ? $site_type : $_SESSION['site_type'];
	        $this->error_pack['page']          = $page;
	        $this->error_pack['errors']        = $errors;

	        $this->Write_Errors();
	    }
	    return;
	}
	
   /**
    * @publicsection
    * @public
    * @fn void Write_Errors()
    * @brief
    *     Forks out the error_thread.2.php feature, which will push the data into a database.
    * @return
    *     Nothing
    * @todo 
    *     - Analyze error_thread and determine whether to remove the exec call and make that happen here, instead.
    */
	public function Write_Errors()
	{
	    if( !defined( PHP_EXE ) )
	    {
	       define("PHP_EXE","php");
	    }

	    exec( PHP_EXEC . " /virtualhosts/exec/error_thread.2.php" . " '" . serialize( $this->error_pack ) . "' &" );
	    
	    $_SESSION['error_trace'][$this->error_pack['page']][] = $this->error_pack['errors'];
    }
}

?>
