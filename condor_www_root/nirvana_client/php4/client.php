<?php

	/**
	 * php4/client.php
	 * 
	 * PHP4 implementation of nirvana client base class
	 * 
	 * @author John Hargrove
	 */
	

	require_once('/virtualhosts/lib/prpc/server.php');
	
	class Nirvana_Client extends Prpc_Server
	{
		/**
		 * Constructor
		 * 
		 * Your super class may override this method, but be sure
		 * to make a parent::Nirvana_Client() call from within your
		 * overriden method.
		 *
		 */		
		function Nirvana_Client()
		{
			parent::Prpc_Server();
		}
		
		/**
		 * Stub function for Fetch_Multiple()
		 * 
		 * This method should be overriden in your super class.
		 * PHP4 does not support abstract methods, so an empty
		 * method is used instead.
		 *
		 */
		function Fetch_Multiple($track_keys)
		{
			user_error("This method should be overriden.", E_ERROR);
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
		function Fetch($track_key)
		{
			return $this->Fetch_Multiple(array($track_key));
		}
	}
?>