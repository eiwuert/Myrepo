<?php
	/**
		@publicsection
		@public
		@brief
			This file will handel a direct request from the IVR system
		
		This top part of the file is the functional code and sets up the object
		to deal with the class, the second part is the IVR request handling class

		@version 
			Check CVS for version - Don Adriano, Sam Hennessy
	*/

	// Find run mode (Live, Local, RC)
	require_once('automode.1.php');
	$auto_mode = new Auto_Mode();
	$mode = $auto_mode->Fetch_Mode($_SERVER['SERVER_NAME']);

	// use rc_lib/rc_lib5 on RC mode
	if ($mode == 'RC')
	{
		ini_set('include_path', '../pear:/virtualhosts/rc_lib5:/virtualhosts/rc_lib:'.ini_get('include_path'));
	}
	else
	{
		ini_set('include_path', '../pear:'.ini_get('include_path'));
	}

	// Required files
	require_once ('config.php');
	require_once ('prpc/server.php');
	require_once ('prpc/client.php');
	require_once ('bfw.1.php');

	//
	//	NOTE:	There is more logic at the end of this file, hopefully
	//			in the future we can split it out but it don't make 
	//			sens right now
	//
	
	/**
		@public
		@brief
			Handels PRPC requests from our IVR system
		
	*/
	class CM_IVR extends Prpc_Server
	{
		private $collected_data;	// Data collected by the PRPC client and sent to us
		private $license_key;		// License key
		private $site_type;			// What site type to use
		private $bfw;				// Base Frame Work object
		private $mysql_db;
		private $db2_db;
		private $debug;
		
		/**
			@publicsection
			@public
			@fn CM_IVR __construct()
			@brief
				This constructor just calls the parent class's constructor
				
			@return CM_IVR
				Returns instance of CM_IVR
			
		*/
		public function __construct()
		{
			parent:: __construct();
		}
		
		/**
			@publicsection
			@public
			@fn object Process_Data($license_key, $site_type, $session_id, $collected_data, $debug, $extra=0)
			@brief
				This is the method that the PRPC client (this being the server) will call
				
			This is a gateway method that handels pre processing and then hands off to Page_Handler and then returns what ever the Page_Handler give it
				
			@param $license_key string
				Site license key
			@param $site_type string
				What site type to use
			@param $session_id string
				Tells me what set of session data, if any to use
			@param $collected_data array
				All the data collected by the PRPC client and then sent to us
			@param $debug boolen
				Weather to show debug information or not
			@param $extra mixed
				I don't know, maybe used to call extra data
			@return object
				Returns what ever Page_Handler gives it
			
		*/
		public function Process_Data($license_key, $site_type, $session_id, $collected_data, $debug, $extra=0)
		{
			// Set data
			$this->collected_data	= $collected_data;
			$this->license_key		= $license_key;
			$this->site_type		= $site_type;
			$this->debug			= $debug;

			// set the mode (live/local)
			switch(TRUE)
			{
				// RC
				case preg_match('/^rc\./', $_SERVER['SERVER_NAME']):
				{
					$this->mode = "rc";
					break;
				}	
				// Match this _before_ LIVE, otherwise
				// bfw.1.edataserver.com.dsXX.tss would match LIVE
				case preg_match('/(ds\d{2}.tss|gambit.tss)$/', $_SERVER['SERVER_NAME'] ):
				{
					$this->mode = 'local';
					break;
				}
				// LIVE
				case preg_match('/^(?:bfw|nms)\./', $_SERVER['SERVER_NAME']):
				{
					$this->mode = "live";
					break;
				}
				// Default to LOCAL
				default:
				{
					$this->mode = "local";
					break;
				}
			}
			
			// set the framework
			$this->bfw = new Base_Frame_Work($this->license_key, $this->collected_data, $this->mode, $this->site_type);
			// set up site
			$this->bfw->Setup_Site($session_id);

			//Make sure data is ok
			$this->Prepare_Collected_Data();

			// Run page logic
			$ivr_return = $this->Page_Handler();
			
			// Session was commited in "Page_Handler", so don't try and use any session data from here on

			// Send data to RVI
			return $ivr_return;
		}
	
		/**
			@privatesection
			@private
			@fn object Page_Handler()
			@brief
				Looks at the collected data and decides what parts of OLP logic to run

			Depending on the page sent across we have a number of things we do, this
			is mostly a proxy for activites that OLP does. I we make sure the data
			is in the format OLP needs then we take out the small parts of the data we
			need from anything that OLP retuns and send it back

			@return object
				Will examin what is retuned by OLP and send back only what is reqired
		*/
		private function Page_Handler()
		{
			// This is what we will return
			$ivr_return = new stdClass();

			// Make our choice based on the page variable passed in
			switch($this->collected_data['page'])
			{
				// Try to sell this client to the first tier, basically this is like running an OLP one page app
				case 'ivr_decision':
				{
					// Run OLP and catch any exceptions
					try
					{
						$this->collected_data['page'] = 'app_allinone';
						// run and return response from page handler
						$olp_result = $this->bfw->module->Page_Handler($this->collected_data);
					}
					catch ( Exception $e )
					{
						// There was a problem
						if( DEBUG )
						{
							echo "<pre>";
							print_r( $e );
							exit;
						}	
					}

					// Did we get a loan ?
					if( empty( $olp_result->data['qualify_info'] ) )
					{
						$ivr_return->decision = 'declined';
						$ivr_return->datax = $olp_result->data['datax_decision']['DATAX_PERF']=='N'?'adverse':'performance';
					}
					else 
					{
						// We got a load so send load information back
						$ivr_return->decision		= 'accepted';
						$ivr_return->qualify_info	= $olp_result->data['qualify_info'];
						$ivr_return->winner			=  $_SESSION['blackbox']['winner'];
						$ivr_return->pay_frequency	= $this->collected_data['income_frequency'];
					}

					break;
				}

				// The customer want's the loan, "bling", "bling"
				case 'ivr_confirm':
				{
					// update the application as confirmed
					// esig catch any exceptions
					try
					{
						// run and return response from page handler
						$this->collected_data['page'] = 'esig';
						$olp_result = $this->bfw->module->Page_Handler($this->collected_data);
					}
					catch ( Exception $e )
					{
						// There was a problem
						if( DEBUG )
						{
							echo "<pre>";
							print_r( $e );
							exit;
						}	
					}
					
					// Did it go ok?
					if( empty( $olp_result->errors ) )
					{
						$ivr_return->confirmation_received = TRUE;
					}
					else 
					{
						$ivr_return->confirmation_received	= FALSE;
						$ivr_return->errors					= $olp_result->errors;
					}

					break;
				}
				default:
				{
					$ivr_return->errors = array('Invalid page passed (CM_IVR->Page_Handler)');
				}
			}

			// Send back to help with debuging
			$ivr_return->application_id	= $olp_result->data['application_id'];
			$ivr_return->session_id		= session_id();

			// Send back all data we got from OLP if in debug mode
			if( $this->debug === TRUE )
			{
				$ivr_return->olp_retuned = $olp_result;
			}
			
			//
			// NOTE: You can't use ant session data after here, we do this to get round session data getting messed up
			//
			session_commit();

			// Give data to caller
			return $ivr_return;
		}
		
		/**
			@privatesection
			@private
			@fn void Prepare_Collected_Data()
			@brief
				Gets data ready for being sent to OLP

			This method goes thu and makes sure that we have
			all the data we need and it's all in the right format

			@return void
				Nothing is returned
		*/
		private function Prepare_Collected_Data()
		{
			switch($this->collected_data['page'])
			{
				case 'ivr_decision':
				{
					// Simple stuff
					$this->collected_data['income_direct_deposit'] = 'TRUE';
		
					// Resrict to first tier only
					$this->collected_data['use_tier'] = 1;
					
					// For inserting in to customer table
					$dob_parts = explode('-', $this->collected_data['dob']);
					$this->collected_data['date_dob_y'] = $dob_parts[0];
					$this->collected_data['date_dob_m'] = $dob_parts[1];
					$this->collected_data['date_dob_d'] = $dob_parts[2];
					
					
					$this->collected_data['bank_account_type']	= 'CHECKING';
					$this->collected_data['income_type']		= 'EMPLOYMENT';
					$this->collected_data['employer_length']	= 'TRUE';
		
					// More complex stuff stick them in there own function
					$this->Validation_Paydate();
					
					break;
				}
				case 'ivr_confirm':
				{
					// For eSig
					$this->collected_data['legal_approve_docs_1'] 	= 'TRUE';
					$this->collected_data['legal_approve_docs_2'] 	= 'TRUE';
					$this->collected_data['legal_approve_docs_3'] 	= 'TRUE';
					$this->collected_data['legal_approve_docs_4'] 	= 'TRUE';
					$this->collected_data['esignature']				= $_SESSION['data']['name_first'] . ' ' . $_SESSION['data']['name_last'];

					break;
				}
			}
		}

		/**
			@privatesection
			@private
			@fn void Validation_Paydate()
			@brief
				Get paydate information ready for OLP

			This is a helper method for Prepare_Collected_Data and handle
			all things related to getting pay date data in to order

			@return void
				Nothing is returned
		*/
		private function Validation_Paydate()
		{
			// These are required fieds
			if(isset($this->collected_data['pay_date1']) == FALSE)
			{
				$this->collected_data['pay_date1'] = FALSE;
			}
			if(isset($this->collected_data['pay_date2']) == FALSE)
			{
				$this->collected_data['pay_date2'] = FALSE;
			}

			// We need to make sure we have the right fields for the paydate model to do it's thing
			
			// This is for everything that is not a weekly pay date
			if($this->collected_data['income_frequency'] == 'NOT_WEEKLY')
			{
				// Calculate day difference
				$day_diff = (strtotime( $this->collected_data['pay_date2'] ) - strtotime( $this->collected_data['pay_date1'] )) / 86400;

				switch (true)
				{
					// Biweekly
					case ($day_diff >= 13 && $day_diff < 15):
					{
						$this->collected_data['income_frequency'] = 'BI_WEEKLY';
						break;
					}
					// Twice monthly	
					case ($day_diff > 14 && $day_diff < 24):
					{
						$this->collected_data['income_frequency'] = 'TWICE_MONTHLY';
						break;
					}
					// Monthly
					case ($day_diff > 24):
					default:
					{
						$this->collected_data['income_frequency'] = 'MONTHLY';
						break;
					}
				}
			}
			// This is for any weekly pay dates
			else 
			{
				$this->collected_data['paydate'] = array();
				$this->collected_data['paydate']['frequency'] = 'WEEKLY';
				$this->collected_data['paydate']['weekly_day'] = $this->collected_data['weekly_day'];
			}
		}
	} // End of class

	//
	//	NOTE:	This is that logic I told you
	//			about at the top of the file
	//

	// Set up object
	$cm_ivr = new CM_IVR();
	$cm_ivr->_Prpc_Strict = TRUE;

	// Run
	$cm_ivr->Prpc_Process();
?>
