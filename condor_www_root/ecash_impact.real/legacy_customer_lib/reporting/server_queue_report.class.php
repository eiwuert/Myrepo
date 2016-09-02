<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

require_once(SERVER_MODULE_DIR."/reporting/report_generic.class.php");


class Customer_Report extends Report_Generic
{
	private $search_query;

	public function Generate_Report()
	{
		// Generate_Report() expects the following from the request form:
		//
		// criteria start_date YYYYMMDD
		// criteria end_date   YYYYMMDD
		// company_id
		//
		try
		{
			$this->search_query = new Customer_Queue_Report_Query($this->server);
	
			$data = new stdClass();
	
			// Save the report criteria
			$data->search_criteria = array(
			  'company_id'      => $this->request->company_id,
			  'queue_name'      => $this->request->queue_name
			);
	
			$_SESSION['reports']['queue']['report_data'] = new stdClass();
			$_SESSION['reports']['queue']['report_data']->search_criteria = $data->search_criteria;
			$_SESSION['reports']['queue']['url_data'] = array('name' => 'Queue Summary', 'link' => '/?module=reporting&mode=queue');
	
			$data->search_results = $this->search_query->Fetch_Queue_Data( $this->request->queue_name, $this->request->company_id);
		}
		catch (Exception $e)
		{
			$data->search_message = "Unable to execute report. Reporting server may be unavailable.";
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}

		// we need to prevent client from displaying too large of a result set, otherwise
		// the PHP memory limit could be exceeded;
		if(!empty($data->search_results) && count($data->search_results) > $this->max_display_rows)
		{
			$data->search_message = "Your report would have more than " . $this->max_display_rows . " lines to display. Please narrow the date range.";
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels("message");
			return;
		}

		// This is messing things up
		$data = $this->Sort_Data($data);

		ECash::getTransport()->Add_Levels("report_results");
		ECash::getTransport()->Set_Data($data);
		$_SESSION['reports']['queue']['report_data'] = $data;
	}
}

require_once( SERVER_CODE_DIR . "base_report_query.class.php" );
require_once( SQL_LIB_DIR . "app_stat_id_from_chain.func.php" );

class Customer_Queue_Report_Query extends Base_Report_Query
{
	private static $TIMER_NAME    = "Queue Report Query";

	public function __construct(Server $server)
	{
		parent::__construct($server);
	}

	/**
	 * Fetches data for the Manual Payment Report
	 * @param   string $start_date YYYYmmdd
	 * @param   string $end_date   YYYYmmdd
	 * @param   string $loan_type  standard || card
	 * @param   mixed  $company_id array of company_ids or 1 company_id
	 * @returns array
	 */
	public function Fetch_Queue_Data($queue_name, $company_id)
	{
		$data = null;
		$this->timer->startTimer(self::$TIMER_NAME);
		$company_list = $this->Format_Company_IDs($company_id);
		$application_ids = array();
		$rows = array();
		$order = 1;
		$db = ECash_Config::getMasterDbConnection();

		$qm         = ECash::getFactory()->getQueueManager();
		$vqueue     = $qm->getQueue($queue_name);
		$table_name = $vqueue->getQueueEntryTableName();
		$time = localtime(time(), TRUE);
		if($vqueue instanceof ECash_Queues_TimeSensitiveQueue)
		{
			$time_zone_sql = "(nqe.start_hour <= HOUR(NOW()) and nqe.end_hour > HOUR(NOW()))";
					
		}
		else
		{
			$time_zone_sql = " 1=1 ";
		}
		
		$query = " SELECT	nqe.related_id						  				 AS application_id,
							(UNIX_TIMESTAMP() - UNIX_TIMESTAMP(nqe.date_queued)) AS time_in_queue,
							app.name_first										 AS name_first,
							app.name_last										 AS name_last,
							app.ssn												 AS ssn,
							app.street											 AS street,
							app.city											 AS city,
							app.county											 AS county,
							app.state											 AS state,
							app.application_status_id							 AS application_status_id,
							c.company_id										 AS company_id,
							upper(c.name_short)									 AS company_name,
							ass.name											 AS status,
							IFNULL((
								SELECT
									SUM(tr.amount)
								FROM
									transaction_register tr
								WHERE
									tr.application_id = app.application_id
								AND		
									tr.transaction_status = 'complete'
							),0)												 AS balance,
							nq.name												 AS queue_name,
							(CASE WHEN ".$time_zone_sql." THEN 'Available' ELSE 'Unavailable' END ) as availablity
					FROM    
							{$table_name} 						  				 AS nqe
					JOIN    
							n_queue nq ON (nq.queue_id = nqe.queue_id)
					JOIN
							application app ON (app.application_id = nqe.related_id)
					JOIN
							company c ON (c.company_id = app.company_id)
					JOIN
							application_status ass ON (ass.application_status_id = app.application_status_id)
				    WHERE	
							nq.name_short = " . $db->quote($queue_name) . "
					AND		
							nqe.date_available <= NOW()
					AND
							(nqe.date_expire IS NULL OR nqe.date_expire >= now())
					AND		
							(
								nq.company_id IS NULL OR
							 	nq.company_id IN $company_list
							)
					AND
							app.company_id IN $company_list
					ORDER BY
						priority DESC, time_in_queue DESC
				 ";

	//	echo " queue query: {$query} ";
		$result = $db->query($query);
		while ($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$this->Get_Module_Mode($row, $row['company_id']);

			$company_name = $row['company_name'];
			$row["order"] = $order;

			$data[$company_name][$order-1] = $row;
			
			$order++;
		}
		
		$this->timer->stopTimer(self::$TIMER_NAME);

		//echo "<!-- ", print_r($data, TRUE), " -->";
		return $data;
	}

	/**
	 * 
	 * Fetches data for the Manual Payment Report
	 * @param   string $start_date YYYYmmdd
	 * @param   string $end_date   YYYYmmdd
	 * @param   string $loan_type  standard || card
	 * @param   mixed  $company_id array of company_ids or 1 company_id
	 * @returns array
	 */
	public function Fetch_Fraud_Queue_Data($queue_name, $company_id)
	{
		$data = null;
		$this->timer->startTimer(self::$TIMER_NAME);

		$company_list = $this->Format_Company_IDs($company_id);

		$application_ids = array();
		$rows = array();

		/** For some reason (that I didn't care to investigate)
		 *  queries that are run through this interface on the slave
		 *  will not throw errors if there's a problem with the query
		 *  -- JRF
		 */ 		
		$db = ECash_Config::getSlaveDbConnection();

        $qm         = ECash::getFactory()->getQueueManager();
        $vqueue     = $qm->getQueue($queue_name);
        $table_name = $vqueue->getQueueEntryTableName();
		$time = localtime(time(), TRUE);
		if($vqueue instanceof ECash_Queues_TimeSensitiveQueue)
		{
			$time_zone_sql = " and nqe.start_hour <= {$time['tm_hour']} and nqe.end_hour > {$time['tm_hour']} ";
					
		}
		else
		{
			$time_zone_sql = " ";
		}
		
		$query = "
			SELECT
				ap.application_id,
				(UNIX_TIMESTAMP() - UNIX_TIMESTAMP(nqe.date_queued)) AS time_in_queue,
				ap.application_id,
				ap.name_first,
				ap.name_last,
				ap.ssn,
				ap.street,
				ap.city,
				ap.county,
				ap.state,
				ap.application_status_id,
				ap.company_id,				
				asf.level0_name status,
				(
					SELECT GROUP_CONCAT(fr.name SEPARATOR ';')					
					FROM fraud_rule fr
					INNER JOIN fraud_application fa on (fa.fraud_rule_id = fr.fraud_rule_id)
					WHERE fa.application_id = ap.application_id
					GROUP BY fa.application_id
				) as rules_display,
				UPPER(c.name_short) company_name,
				(
					SELECT SUM(tr.amount)
					  FROM transaction_register tr
					  WHERE
					  	tr.application_id = ap.application_id AND
					  	tr.transaction_status = 'complete'
				) balance
			FROM application ap
			INNER JOIN {$table_name} nqe on (ap.application_id = nqe.related_id)
			INNER JOIN n_queue nq on (nq.queue_id = nqe.queue_id)
			INNER JOIN application_status_flat asf on (asf.application_status_id = ap.application_status_id)
			INNER JOIN company c on (c.company_id = ap.company_id)
			WHERE nq.name_short = ".$db->quote($queue_name)."
			AND nqe.date_available <= NOW()
			AND     (nq.company_id IS NULL OR
                     nq.company_id IN $company_list)
			AND ap.company_id IN {$company_list}" .
					$time_zone_sql ."
			ORDER BY ap.application_id
		";

	//	echo "<!-- {$query} -->";
		
		$result = $db->query($query);
		while ($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$this->Get_Module_Mode($row, $row["company_id"]);
			$data[$row["company_name"]][] = $row;
		}

		//echo "<!-- ", print_r($data, TRUE), " -->";
		$this->timer->stopTimer(self::$TIMER_NAME);

		return $data;
	}
	

	/**
	 * Fetches data for the Manual Payment Report
	 * @param   string $start_date YYYYmmdd
	 * @param   string $end_date   YYYYmmdd
	 * @param   string $loan_type  standard || card
	 * @param   mixed  $company_id array of company_ids or 1 company_id
	 * @returns array
	 */
	public function Fetch_Web_Queue_Data($queue_name, $company_id)
	{
		$data = null;

		$this->timer->startTimer(self::$TIMER_NAME);

		$company_list = $this->Format_Company_IDs($company_id);

        $qm         = ECash::getFactory()->getQueueManager();
        $vqueue     = $qm->getQueue($queue_name);
        $table_name = $vqueue->getQueueEntryTableName();

		$application_ids = array();
		$rows = array();
		$order = 1;
		$db = ECash_Config::getMasterDbConnection();
		$query = " SELECT   nqe.related_id                        AS application_id,
							(UNIX_TIMESTAMP() - UNIX_TIMESTAMP(nqe.date_queued)) AS time_in_queue,
							app.name_first						  AS name_first,
							app.name_last						  AS name_last,
							app.ssn								  AS ssn,
							app.state							  AS state,
							app.city							  AS city,
							app.street							  AS street,
							ass.name							  AS status,
							app.application_status_id			  AS application_status_id,
							IFNULL(et.name,'N/A')				  AS api_event_name,
							IFNULL(apip.amount,'N/A')			  AS api_amount,
							UPPER(co.name_short)						  AS company_name,
							co.company_id						  AS company_id,
							(
								SELECT
									SUM(tr.amount)
		                        FROM 
									transaction_register tr
								WHERE
                          			tr.application_id = nqe.related_id 
								AND
                          			tr.transaction_status = 'complete'
                    		)                               	  AS balance
							

                    FROM    {$table_name}                         AS nqe
                    JOIN    n_queue nq ON (nq.queue_id = nqe.queue_id)
					JOIN	application app ON (app.application_id = nqe.related_id)
					JOIN	application_status ass ON (ass.application_status_id = app.application_status_id)
					JOIN	company co ON (co.company_id = app.company_id)
					LEFT JOIN	api_payment apip ON (apip.application_id = app.application_id AND apip.active_status= 'active')
					LEFT JOIN	event_type et ON (et.event_type_id = apip.event_type_id)
                    WHERE   nq.name_short = " . $db->quote($queue_name) . "
                    AND     nqe.date_available <= NOW()
					AND
						app.company_id IN $company_list
					ORDER BY
						time_in_queue DESC
                 "; 
		//die($query);
		$result = $db->query($query);
		
		while ($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$row["order"] = $order;
			$order++;
			$data[$row["company_name"]][($row['order']-1)] = $row;
			$this->Get_Module_Mode($row, $row["company_id"]);
		}

		$this->timer->stopTimer(self::$TIMER_NAME);

		return $data;
	}	
}

?>
