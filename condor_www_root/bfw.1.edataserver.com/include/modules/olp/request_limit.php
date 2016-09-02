<?php
	class Request_Limit
	{
		
		/**
			Class properties
		*/
		private static $limit_array;	// (array) A hash table style array with the promo_id as a key and the cap/limit at the value
		private $promo_id;				// (int) Promo ID
		private $is_over_limit;			// (boolean) TRUE if this vendor is over or at there limit
		private $event_count;			// (int) the current number of events this vendor has
		private $sql;					// (object) Sql object
		private $database;				// (string) What database to use
		
		/**
			@publicsection
			@public
			@fn Vendor_Request_Limit __construct( &$sql, $database, $promo_id)
			@brief
				Initialize's object
				
			This is where the list of capped promo_id's is stored for the time being

			@param &$sql object
				A valid SQL object
			@param $database string
				The name of the database you want to use with the SQL object, e.g. 'OLP'
			@param $promo_id int
				This is an id used by the vernders to id theyselves and it's stored along with the event that's we counting
			@return Vendor_Request_Limit
				
		*/
		public function __construct( &$sql, $database, $promo_id )
		{
			
			// initialize static vars
			
			// array of promo IDs and their limits
			$limits = array();
			
//			if (time() >= strtotime('2005-08-05'))
//			{
//				$limits['26799'] = 125; // webfastcash.com
//				$limits['26695'] = 110; // webfastcash.com
//				$limits['26753'] = 176; // quickadvances.com
//				$limits['25787'] = 18;  // quickadvances.com
//				$limits['26780'] = 50;  // personalcashadvance.com
//				$limits['26745'] = 100; // 911paydayadvance.com
//				$limits['27146'] = 130; // wegivecash.com
//				$limits['27147'] = 20;  // credit.com
//			}
//			
//			if (time() >= strtotime('2005-08-06'))
//			{
//				$limits['25128'] = 180; // rapidcashproviderapp.com
//			}
			
			self::$limit_array = $limits;
			
			// initialize object vars
			$is_over_limit = NULL;

			// Set passed params
			$this->sql	 	= $sql;
			$this->database = $database;
			$this->promo_id = $promo_id;
			
		}
		
		/**
			@publicsection
			@public
			@fn mixed __get( $name )
			@brief
				A proxy method to access properties

			@param $name string
				The name of the propertie you want to get the value of		
			@return mixed
				This is a proxy method to access properties so will return whatever they are
		*/
		public function __get( $name )
		{
			
			$get_return;

			switch( $name )
			{
				case 'Is_Over_Limit':
				{
					if( is_null( $this->is_over_limit ) )
					{
						$this->Is_Over_Limit();
					}
					
					$get_return = $this->is_over_limit;
					
					break;
				}
			}
			
			return $get_return;
			
		}
		
		/**
			@privatesection
			@private
			@fn VOID Is_Over_Limit( )
			@brief
				Tells us if this promo_id is over or at it's limit
				
			Will get the current count of the accepted events and see if that
			is over the limit set in the class, it will store the result and
			not do the count again in the same script it will just return the
			previous result
	
			@return VOID
				Dosn't return anything
		*/
		private function Is_Over_Limit()
		{
			
			$this->is_over_limit = FALSE;
			
			if (isset(self::$limit_array[$this->promo_id]))
			{
				
				// Get the current count
				$this->event_count = App_Campaign_Manager::Get_Todays_Campaign_Request($this->sql, $this->database, $this->promo_id);
				
				// Are they at or over their limit ?
				if( $this->event_count >= self::$limit_array[$this->promo_id])
				{
					$this->is_over_limit = TRUE;
				}
				
			}
			
		}
		
	}
?>
