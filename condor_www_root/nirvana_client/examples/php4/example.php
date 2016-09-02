<?php
	/**
	 * examples/php4/example.php
	 * 
	 * Example nirvana client implementation
	 * Note that you do not have to include the specific client.php file
	 * Including the client.php in the root nirvana_client/ path will use
	 * your php version to determine which class to use.  It may even throw an error
	 * requesting you to upgrade your php version to a later version of PHP4.
	 * 
	 * Requirements of a fully implemented Nirvana_Client:
	 * 
	 *   1) override the method Fetch_Multiple(array $track_keys)
	 *      this function should access your database to retrieve user information
	 *      for any track_key(s) belonging to your implementation.
	 *   2) When executed, this file should instantiate a single instance of your
	 *      super class.
	 *   3) Parent constructor must be called.
	 * 
	 * @author John Hargrove
	 */
	require_once('/virtualhosts/nirvana_client/client.php');
	
	class Nirvana_Mine extends Nirvana_Client
	{
		function Nirvana_Mine()
		{
			parent::Nirvana_Client();
		}
		
		/**
		 * Example implementation of the Fetch_Multiple method.
		 * 
		 * This function should accept an array of track_keys and return
		 * an associative array with a track_key => personal data key/value
		 * relationship.
		 *
		 * @param array $track_keys
		 * @return array
		 */
		function Fetch_Multiple($track_keys)
		{
			$return_data = array();
			
			foreach ($track_keys as $track_key)
			{
				// This is where you query your database and get the user information for Nirvana
				$return_data[$track_key] = 
					array(
						'name_first' => 'John',
						'name_last' => 'Hargrove'
					);
			}

			return $return_data;
		}
	}
	
	// This line is required, and should be changed to whatever the name of your particular superclass is.
	new Nirvana_Mine();
?>