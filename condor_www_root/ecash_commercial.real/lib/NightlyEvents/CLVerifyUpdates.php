<?php

	/**
	 * CL Verify requires updates similar to CRA for the following conditions:
	 * 
	 * - Paid Accounts
	 * - ChargeOffs
	 * - Cancellations
	 * - Past Due Accounts
	 * - Accounts that went from Past Due to Active
	 * 
	 * This is almost an exact copy of the ECash_NightlyEvent_TeletrackUpdates
	 * class since the functionality is the same. [BR]
	 * 
	 * Related Ticket: GForge #20017 - CL Verify
	 * Related Ticket: GForge #16875 - TeleTrack Updates
	 * Related Ticket: GForge #22243 - Add past due and active reporting
	 */
	class ECash_NightlyEvent_CLVerifyUpdates extends ECash_Nightly_Event
	{
		// Parameters used by the Cron Scheduler
		protected $business_rule_name = 'cl_verify_updates';
		protected $timer_name = 'clverify_update_timer';
		protected $process_log_name = 'clverify_updates';
		protected $use_transaction = FALSE;

		protected $status_list;

		public function __construct()
		{
			$this->classname = __CLASS__;

			parent::__construct();

			$this->status_list = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
		}
		
		/**
		 * The main function for pulling in all of the data and reporting it to DataX.
		 * 
		 * This pulls in all of the application data for Paid, Chargeoff / 2nd Tier, and 
		 * Cancellations and then sends the updates to DataX one at a time.  Afterwards
		 * both the request and the response are stored in the bureau_inquiry table
		 * so that it can be referenced later on.
		 */
		public function run()
		{
			// Sets up the Applog, any other pre-requisites in the parent
			parent::run();
			
			$start_date = $this->start_date . " 00:00:00";
			$end_date = $this->end_date . ' 23:59:59';

			//Paid off status list
			$paid_status_list = array();
			$paid_status_list[] = $this->status_list->toId('paid::customer::*root');

			
			//Sometimes these statuses don't exist or apply to a company. So we only want to search for them if they
			//exist [W!-12-09-2008][#20017]
			$chargeoff_status_list = array();
			if( $chargeoff_status = $this->status_list->toId('chargeoff::collections::customer::*root'))
			{
				$chargeoff_status_list[] = $chargeoff_status;
			}
			if ($second_tier_sent_status = $this->status_list->toId('sent::external_collections::*root'))
			{
				$chargeoff_status_list[] = $second_tier_sent_status;
			}
			
			//Active statuses
			$active_status_list = array();
			if($active_status = $this->status_list->toId('active::servicing::customer::*root'))
			{
				$active_status_list[] = $active_status;
			}
			
			//Transition statuses.  We may want to report on a specific status change, but sometimes one of these intermediate
			//statuses get's thrown in there, example:
			//When an application is past due, the typical status transition could be Active->Past Due or Active->Collections New
			//However, sometimes the status transition can look like  Active->Made Arrangements->Collections New or Active->Default->Collections New
			//So we need these statuses when trying determine whether or not to report an app based on a 3-part status history [W!-12-19-2008][#22243]
			$transition_status_list = array();
			if($made_arrangements_status = $this->status_list->toId('current::arrangements::collections::customer::*root'))
			{
				$transition_status_list[] = $made_arrangements_status;
			}
			
			if($default_status = $this->status_list->toId('default::collections::customer::*root'))
			{
				$transition_status_list[] = $default_status;
			}
			
			//Past due status list.  This is mostly collections statuses, but it's anything that could denote the application is 
			//has missed a payment and is past due
			$past_due_status_list = array();
			if ($collections_new_status = $this->status_list->toId('new::collections::customer::*root')) 
			{
				$past_due_status_list[] = $collections_new_status;	
			}
			if ($past_due_status = $this->status_list->toId('past_due::servicing::customer::*root')) 
			{
				$past_due_status_list[] = $past_due_status;	
			}
			if ($collections_contact_status = $this->status_list->toId('queued::contact::collections::customer::*root'))
			{
				$past_due_status_list[] = $collections_contact_status;
			}
			if($second_tier_pending_status = $this->status_list->toId('pending::external_collections::*root'))
			{
				$past_due_status_list[] = $second_tier_pending_status;
			}
			
			$applications = array();
			$active_apps = $this->findStatusChanges($start_date,$end_date,$past_due_status_list,$transition_status_list,$active_status_list,'active');
			//It's also possible for an application to become past due by moving from inactive(paid) to collections.  Gonna add inactive to the active status list for this
			$active_status_list[] = $this->status_list->toId('paid::customer::*root');
			$past_due_apps = $this->findStatusChanges($start_date,$end_date,$active_status_list,$transition_status_list,$past_due_status_list,'past_due');
			
			$paid_apps = $this->findApplications($start_date, $end_date, $paid_status_list, 'paid_off');
			$chargeoff_apps = $this->findApplications($start_date, $end_date, $chargeoff_status_list, 'chargeoff');
			$cancelled_apps = $this->getCancellations($start_date, $end_date);
			
			
			$applications = array_merge($applications, $paid_apps);
			$applications = array_merge($applications, $chargeoff_apps);
			$applications = array_merge($applications, $cancelled_apps);
			$applications = array_merge($applications, $past_due_apps);
			$applications = array_merge($applications, $active_apps);
			
			$bureau = ECash::getFactory()->getModel('Bureau');
			$bureau->loadBy(array('name_short' => 'datax'));
			
			$bureau_id = $bureau->bureau_id;
		
			foreach($applications as $application_data)
			{
				$xml = $this->getUpdateXMLPacket($application_data);

				$response = $this->sendDataXRequest($xml, EXECUTION_MODE);

				/**
				 * Save the response to the bureau_inquiry table
				 */
				if(!empty($bureau_id) && !empty($xml) && !empty($response))
				{
					$response_xml = simplexml_load_string($response);
					
					$bi_record = ECash::getFactory()->getModel('BureauInquiry');
					$bi_record->company_id 		 = $application_data->company_id;
					$bi_record->application_id 	 = $application_data->application_id;
					$bi_record->bureau_id 		 = $bureau_id;
					$bi_record->inquiry_type 	 = $application_data->call_type;
					$bi_record->sent_package 	 = $xml;
					$bi_record->received_package = $response;
					$bi_record->date_created 	 = time();

					if(isset($response_xml->Response->ErrorCode))
					{
						$bi_record->error_condition = 'other';
						$bi_record->outcome         = $response_xml->Response->ErrorCode;
						$bi_record->trace_info      = $response_xml->Response->ErrorMsg;
					}
					else
					{
						$bi_record->outcome         = 'Success';
						$bi_record->trace_info      = $response_xml->TransactionId;
					}

					$bi_record->save();
				}
			}
		}

		/**
		 * Method to search for applications within a list of statuses
		 * 
		 * This method is pretty generic since we can use it for both Paid
		 * and Chargeoff scenarios.  That's why the teletrack_status is 
		 * passed in.
		 *
		 * @param string $start_date  Y-m-d h:i:s
		 * @param string $end_date    Y-m-d h:i:s
		 * @param array $status_list  array of integers
		 * @param string $teletrack_status 'paid_off'
		 * @return array of objects containing application data
		 */
		public function findApplications($start_date, $end_date, $status_list, $teletrack_status)
		{
			$statuses = implode(',', $status_list);
			
			$sql = "
				SELECT 	sh.application_id,
						sh.company_id,
						a.ssn, 
						a.fund_actual as fund_amount,
						rscpv.parm_value AS call_type, 
						'{$teletrack_status}' as status,
						(
							SELECT MAX(date_effective)
							FROM transaction_register
							WHERE 
								application_id = sh.application_id) AS due_date
				FROM 
						status_history AS sh
						JOIN application AS a ON a.application_id = sh.application_id
						JOIN rule_set_component_parm_value AS rscpv ON rscpv.rule_set_id = a.rule_set_id
						JOIN rule_component AS rc ON rc.rule_component_id = rscpv.rule_component_id
						JOIN rule_component_parm AS rcp ON rcp.rule_component_parm_id = rscpv.rule_component_parm_id
				WHERE
						sh.company_id = {$this->company_id}
				AND 
						sh.date_created BETWEEN '{$start_date}' AND '{$end_date}' 
				AND 
						sh.application_status_id  IN ( $statuses )
				AND 
						rc.name_short = 'datax_call_types'
				AND 
						rcp.parm_name = 'cl_verify_update' 
				AND
					NOT EXISTS (
						SELECT 'X'
						FROM transaction_register AS tr
						JOIN transaction_type AS tt ON tt.transaction_type_id = tr.transaction_type_id
						WHERE tr.application_id = sh.application_id
						AND tt.name_short IN ( 'cancel_fees', 'cancel_principal' )
					)
				GROUP BY
					application_id	";

			$result = $this->db->Query($sql);
			$applications = array();
			while($row = $result->fetch(PDO::FETCH_OBJ))
			{
				$applications[] = $row;
			}

			return $applications;
		}

		
		/**
		 * This finds statuses based on a specific status transition.
		 * This is needed to track when applications go past_due (a typical application in collections will shift through numerous
		 * collections statuses, we do not want these to report past_due multiple times if the application did no go 'active' in the mean-time.
		 * This also tracks if an application goes active from being past due.  Neither active, nor past due are terminal statuses
		 * so it is possible for it to change back and forth. [W!-12-19-2008][#22243]
		 * @param string $start_date  Y-m-d h:i:s
		 * @param string $end_date    Y-m-d h:i:s
		 * @param array $start_status_list array of integers denoting the status the app needed to be in prior to the end_status
		 * @param array $intermediate_status_list array of integers denoting the status the app may have passed through
		 * @param array $end_status_list array of integers denoting the status you want the app to currently be in
		 * @param string $teletrack_status 'active'/'past_due'
		 * @return array of objects containing application data
		 */
		public function findStatusChanges($start_date, $end_date, $start_status_list, $intermediate_status_list, $end_status_list, $teletrack_status)
		{
			$start_statuses = implode(',', $start_status_list);
			$intermediate_statuses = implode(',', $intermediate_status_list);
			$end_statuses = implode(',', $end_status_list);
			$sql = "
				SELECT 	sh.application_id,
						sh.company_id,
						a.ssn, 
						a.fund_actual as fund_amount,
						rscpv.parm_value AS call_type, 
						'{$teletrack_status}' as status,
						(
							SELECT MAX(date_effective)
							FROM transaction_register
							WHERE application_id = sh.application_id
						) AS due_date,
                        sh.application_status_id AS current_status,
                       (
                           SELECT application_status_id
                           FROM status_history 
                           WHERE date_created < sh.date_created
                           ORDER BY date_created desc
                           LIMIT 1
                       ) AS last_status,
                       (
                           SELECT application_status_id
                           FROM status_history 
                           WHERE date_created < sh.date_created
                           ORDER BY date_created desc
                           LIMIT 1,1
                       ) AS penultimate_status
				FROM 
						status_history AS sh
						JOIN application AS a ON a.application_id = sh.application_id
						JOIN rule_set_component_parm_value AS rscpv ON rscpv.rule_set_id = a.rule_set_id
						JOIN rule_component AS rc ON rc.rule_component_id = rscpv.rule_component_id
						JOIN rule_component_parm AS rcp ON rcp.rule_component_parm_id = rscpv.rule_component_parm_id
				WHERE
						sh.company_id = {$this->company_id}
				AND 
						sh.date_created BETWEEN '{$start_date}' AND '{$end_date}' 
				AND 
						sh.application_status_id  IN ( $end_statuses )
				AND 
						rc.name_short = 'datax_call_types'
				AND 
						rcp.parm_name = 'cl_verify_update' 
				AND
					NOT EXISTS (
						SELECT 'X'
						FROM transaction_register AS tr
						JOIN transaction_type AS tt ON tt.transaction_type_id = tr.transaction_type_id
						WHERE tr.application_id = sh.application_id
						AND tt.name_short IN ( 'cancel_fees', 'cancel_principal' )
					)
				GROUP BY
					application_id	
				HAVING
						(last_status IN ( $start_statuses ))
					OR
						(last_status IN ( $intermediate_statuses )
						AND
						penultimate_status IN ( $start_statuses )
						)";
			
			$result = $this->db->Query($sql);
			$applications = array();
			while($row = $result->fetch(PDO::FETCH_OBJ))
			{
				$applications[] = $row;
			}

			return $applications;
		}
		
		/**
		 * Method to retrieve applications that have cancelled
		 * 
		 * This is similar to findApplications except it looks only
		 * for accounts that have had complete cancellation transactions.
		 *
		 * @param string $start_date  Y-m-d h:i:s
		 * @param string $end_date    Y-m-d h:i:s
		 * @return array of objects containing application data
		 */
		public function getCancellations($start_date, $end_date)
		{
			$sql = "
				SELECT 	tl.application_id,
						tl.company_id,
						a.ssn, 
						a.fund_actual AS fund_amount,
						rscpv.parm_value AS call_type, 
						'cancel' as status,
						(
							SELECT MAX(date_effective)
							FROM transaction_register
							WHERE 
								application_id = a.application_id) AS due_date
				FROM
					transaction_ledger AS tl
					JOIN application AS a ON tl.application_id = a.application_id
					JOIN rule_set_component_parm_value AS rscpv ON rscpv.rule_set_id = a.rule_set_id
					JOIN rule_component AS rc ON rc.rule_component_id = rscpv.rule_component_id
					JOIN rule_component_parm AS rcp ON rcp.rule_component_parm_id = rscpv.rule_component_parm_id
				WHERE
					    tl.date_created BETWEEN '{$start_date}' AND '{$end_date}'
					AND 
						tl.company_id = {$this->company_id}
					AND 
						tl.transaction_type_id IN (
							SELECT transaction_type_id
							FROM transaction_type
							WHERE name_short IN ('cancel_fees', 'cancel_principal')
						)
					AND 
							rc.name_short = 'datax_call_types'
					AND 
							rcp.parm_name = 'cl_verify_update'
					ORDER BY
						tl.date_created ASC ";

			
			$result = $this->db->Query($sql);
			$applications = array();
			while($row = $result->fetch(PDO::FETCH_OBJ))
			{
				$applications[] = $row;
			}

			return $applications;

		}
		
		/**
		 * Creates a DataX XML packet for updating
		 * the CL Verify status.
		 *
		 * @param stdClass object $application_data
		 * @return string XML data
		 */
		public function getUpdateXMLPacket($application_data)
		{
			$xml = new DOMDocument('1.0','utf-8');
			$xml->formatOutput = true;
			
			$xmlRoot = new DOMElement('DATAXINQUERY');
			$xml->appendChild($xmlRoot);

			/**
			 * Authentication
			 */
			$auth_elem = new DOMElement('AUTHENTICATION');
			$xmlRoot->appendChild($auth_elem);
			
			$key  = ECash_Config::getInstance()->DATAX_LICENSE_KEY;
			$pass = ECash_Config::getInstance()->DATAX_PASSWORD;

			$auth_elem->appendChild(new DOMElement('LICENSEKEY', $key));
			$auth_elem->appendChild(new DOMElement('PASSWORD',   $pass));
			
			$xmlRoot->appendChild($auth_elem);

			/**
			 * Required for the update packet
				<DATAXINQUERY>
					<AUTHENTICATION>
						<LICENSEKEY>xxxxxxxxxxxxxxx</LICENSEKEY>
						<PASSWORD>xxxxxxxx</PASSWORD>
					</AUTHENTICATION>
					<QUERY>
						<TYPE>opm-statusupd</TYPE>
						<TRACKID>900068911</TRACKID>
						<DATA>
							<SSN>123456789</SSN>
							<DUEDATE>2008-08-25</DUEDATE>
							<FUNDAMOUNT>300.00</FUNDAMOUNT>
							<STATUS>cancel</STATUS>
						</DATA>
					</QUERY>
				</DATAXINQUERY>
			 */

			/**
			 * The Query
			 */
			$query_elem = new DOMELement('QUERY');
			$xmlRoot->appendChild($query_elem);
			
			$query_elem->AppendChild(new DOMElement('TYPE', $application_data->call_type));
			
			/**
			 * Data in the Query
			 */
			$data_elem = new DOMELement('DATA');
			$query_elem->AppendChild(new DOMElement('TRACKID', $application_data->application_id));
			$query_elem->appendChild($data_elem);
			
			$data_elem->AppendChild(new DOMElement('SSN', $application_data->ssn ));
			$data_elem->AppendChild(new DOMElement('DUEDATE', $application_data->due_date ));
			$data_elem->AppendChild(new DOMElement('FUNDAMOUNT', $application_data->fund_amount));
			$data_elem->AppendChild(new DOMElement('STATUS', $application_data->status ));
			
			return $xml->saveXML();
			
		}
		
		/**
		 * Sends the XML to datax returns the response
		 * 
		 * - This was stolen from lib5/datax.2.php
		 *
		 * @param string $xml
		 * @param string $mode
		 * @return string XML Response
		 */
		public function sendDataXRequest($xml, $mode)
		{
			switch(strtoupper($mode))
			{
				case 'LIVE':
					$url = 'http://verihub.com/datax/index.php';
					$timeout = 15;
				break;
				case 'RC':
				default:
					$url = 'http://rc.verihub.com/datax/';
					$timeout = 5;
				break;
			}
	
			$curl = curl_init();
			curl_setopt( $curl, CURLOPT_URL, $url );
			curl_setopt( $curl, CURLOPT_VERBOSE, 0 );
			curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $curl, CURLOPT_POST, 1 );
			curl_setopt( $curl, CURLOPT_POSTFIELDS, trim( $xml ));
			curl_setopt( $curl, CURLOPT_HEADER, 0 );
			curl_setopt( $curl, CURLOPT_HTTPHEADER, 
				array( 'Content-Type: text/xml' ) );
			curl_setopt( $curl, CURLOPT_TIMEOUT, $timeout );
			curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, FALSE );
			curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, 0 );
	
			$result = curl_exec( $curl );
			$result = preg_replace('/^[\s\r\n\t]*(?=<\?xml)/', '', $result);
	
			return $result;
		}
	
	}

?>
