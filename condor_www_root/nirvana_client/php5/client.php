<?php
	/**
	 * php5/client.php
	 * 
	 * PHP5 Version of the nirvana client base class
	 * 
	 * @author John Hargrove
	 */

	require_once('/virtualhosts/lib5/prpc/server.php');

	abstract class Nirvana_Client extends Prpc_Server
	{
		public abstract function Fetch_Multiple($track_keys);
		
		/**
		 * Constructor
		 * 
		 * Your super class may override this method, but be sure
		 * to make a parent::__construct() call from within your
		 * overriden method.
		 *
		 */
		public function __construct()
		{
			parent::__construct();
		}
		
		/**
		 * Single record fetch.
		 * 
		 * Performs a single record fetch. This merely wraps the method
		 * implemented by the super class, and should be of no concern
		 * to a nirvana client.  The nirvana server may require this method
		 * for certain actions.
		 *
		 * @param track_key $track_key
		 * @return array
		 */
		public final function Fetch($track_key)
		{
			return $this->Fetch_Multiple(array($track_key));
		}
	}
?>
