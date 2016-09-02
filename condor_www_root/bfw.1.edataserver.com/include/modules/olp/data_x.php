<?php

class Data_X
{
	private $log;
	
    public function __construct( $applog )
    {
    	$this->log = $applog;
    	
        return;
    }

   /**
    * The Build_Object function takes input and turns it into a proper XML-encapsulated string.
    *
    * In addition to XML-izing the data, it also adds the license key and password to go to
    * DataX, along with the proper formatting for that call.
    * 
    * @param    string      $track_id           Customer's track ID.
    * @param    array       $data               The normalized_data from the blackbox object.
    *
    * @return   string      $xml                The prepared XML object
    */
    public function Build_IDV_Object( $track_id, $data )
    {
        $fields = array( 
        			'required' => array( 'name_first', 'name_last', 'street1', 'city', 'state', 'zip', 'home_phone', 'email', 'ip_address', 'legal_id', 'legal_state', 'dob_year', 'dob_month', 'dob_day', 'ssn' ),
	  			 	'optional' => array( 'name_middle', 'street2', 'work_phone', 'work_ext', 'bank_name', 'bank_acct', 'bank_aba', 'bank_type', )
	  			 		);
        
        $xml = '<?xml version="1.0" encoding="iso-8859-1"?>
                <DATAXINQUERY>
                    <AUTHENTICATION>
                        <LICENSEKEY>fbe0044486e20d84f67c795619242adc</LICENSEKEY>
                        <PASSWORD>password</PASSWORD>
                        <FLAG>PASS</FLAG>
                    </AUTHENTICATION>
                    <QUERY>
                        <TRACKID>' . $track_id . '</TRACKID>
                        <TYPE>idv</TYPE>
                        <DATA>
                        ';
      	// put track_id inside of data to eliminate having to check for it seperately
      	$data["track_id"] = $track_id;
        
        foreach ( $fields as $type => $list )
        {
            foreach( $list as $field )
	        {
	 			// check for field existence but only log if required
	        	if( array_key_exists( $field, $data ) )
	            {
	                $xml .= '<' . strtoupper( $field ) . '>' . $data[$field] . '</' . strtoupper( $field ) . '>';
	            }
	            elseif( "required" == $type )
	            {
	                $text  = 'Data_X::Build_IDV_Object() [' . $_SESSION['application_id'] . '] Missing field ' . $field . '.';
	               $this->log->Write( $text );
	            }
	        }
        }
                        
        $xml .= '</DATA>
                    </QUERY>
                </DATAXINQUERY>';
        
        return $xml;
    }
    
    public function Build_Performance_Object( $track_hash )
    {
        $xml = '<?xml version="1.0" encoding="iso-8859-1"?>
        <DATAXINQUERY>
            <AUTHENTICATION>
                <LICENSEKEY>fbe0044486e20d84f67c795619242adc</LICENSEKEY>
                <PASSWORD>password</PASSWORD>
                <FLAG>PASS</FLAG>
            </AUTHENTICATION>
            <QUERY>
                <TYPE>perf</TYPE>
                <TRACKHASH>' . $track_hash . '</TRACKHASH>
            </QUERY>
        </DATAXINQUERY>';

        return $xml;
    }
    
   /**
    * @param    string      $xml        XML-Encapsulated Data_X information created by Build_Object().
    */
    public function Send( $xml )
    {
        $curl = curl_init();
        curl_setopt( $curl, CURLOPT_URL, 'http://datax_test.ds04.tss/' );
        curl_setopt( $curl, CURLOPT_VERBOSE, 0 );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $curl, CURLOPT_POST, 1 );
        curl_setopt( $curl, CURLOPT_POSTFIELDS, trim( $xml ));
        curl_setopt( $curl, CURLOPT_HEADER, 0 );
        curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Content-Type: text/xml' ) );
        curl_setopt( $curl, CURLOPT_TIMEOUT, 15 );
        curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, FALSE );
        curl_setopt( $curl, CURL_SSL_VERIFYHOST, 0 );
        
        $result = curl_exec( $curl );
        
        return $result;
    }
}
?>