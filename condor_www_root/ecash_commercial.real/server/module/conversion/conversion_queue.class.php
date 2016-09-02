<?php
require_once(LIB_DIR.'AgentAffiliation.php');

class Conversion_Queue
{
	protected $server;
	protected $db;
	protected $loan_data_obj;
	
	const DEQUEUE_TIMEOUT = 30;
	
	function __construct(Server $server)
	{
		$this->server = $server;
		$this->db = ECash_Config::getMasterDbConnection();
		$this->loan_data_obj = new Loan_Data($server);
	}
	
	function Get_Queue_Count()
	{
		$query = "
			(select
				clp.cashline_status cashline_status, count(distinct ap.application_id) queue_count
			from application ap
			left join cl_conversion_queue clq on (clq.application_id = ap.application_id)
			join cl_pending_data clp on (clp.application_id = ap.application_id)
			join application_status_flat asf on (asf.application_status_id = ap.application_status_id)
			where
			( 
				(asf.level0='queued'   and asf.level1='cashline' and asf.level2='*root') or
				(asf.level0='dequeued' and asf.level1='cashline' and asf.level2='*root' and ap.date_application_status_set < date_sub(current_timestamp, interval ".self::DEQUEUE_TIMEOUT." minute) )
			)
			and ap.company_id = {$this->server->company_id}
			group by clp.cashline_status)
			UNION
			(SELECT 'conversion_manager' as cashline_status, count(distinct aga.application_id) as queue_count  
    		 FROM agent_affiliation aga, application_status_flat asf, application app
    		 WHERE app.application_id = aga.application_id
             AND asf.application_status_id = app.application_status_id
             AND (asf.level0 = 'dequeued' and asf.level1='cashline' and asf.level2='*root')
    		 AND aga.company_id = {$this->server->company_id}
        	 AND aga.affiliation_area = 'conversion'
        	 AND aga.affiliation_type = 'owner')";


		$result = $this->db->query($query);

		$queue_counts = array(
			'conversion_manager' => 0,
			'active' => 0,
			'hold' => 0,
			'collection' => 0,
			'other' => 0
			
		);
		
		while ( $count_object = $result->fetch(PDO::FETCH_OBJ))
		{
			if ( in_array($count_object->cashline_status, array('hold', 'active', 'collection','conversion_manager')) )
			{
				$queue_counts[$count_object->cashline_status] = $count_object->queue_count;
			}
			else 
			{
				$queue_counts['other'] += $count_object->queue_count;
			}
		}
		

		return (object) $queue_counts;
	}

	function Get_Next_Application($status)
	{
		// App that are in the conversion manager are not in the cashline conversion
		if($status == "conversion_manager")
		{
			$query = "
				SELECT
					a.application_id,
					1 as count,
					a.agent_id,
					a.application_status_id,
					a.date_application_status_set,
					a.date_next_contact
				FROM
					application a
				WHERE
					a.application_id = 
					(
                       	SELECT	distinct(aga.application_id)
                       	FROM agent_affiliation aga, application app, application_status_flat asf
						WHERE aga.company_id = {$this->server->company_id}
                         	AND aga.affiliation_area = 'conversion'
                       	AND	aga.affiliation_type = 'owner'
                        AND app.application_id = aga.application_id
                        AND asf.application_status_id = app.application_status_id
                        AND ((asf.level0 = 'queued' and asf.level1='cashline' and asf.level2='*root')
                              OR 
						    (asf.level0 = 'dequeued' and asf.level1='cashline' and asf.level2='*root'))
                        ORDER BY app.date_application_status_set
                        LIMIT 1
					)
			";
		}
		else
		{
			if ($status == 'other') $cashline_status_string = "clp.cashline_status not in ('active','hold','collection')";
			else $cashline_status_string = "clp.cashline_status = '$status'";
			$query = "
				SELECT
					 a.application_id,
					 a.count,
					a2.agent_id,
					a2.application_status_id,
					a2.date_application_status_set,
					a2.date_next_contact
				FROM
					(
					select
						min(ap.application_id) as application_id,
						count(*) as count,
						clq.date_relevant
					from application ap
					join application_status_flat asf on (asf.application_status_id = ap.application_status_id)
					left join cl_conversion_queue clq on (clq.application_id = ap.application_id)
					join cl_pending_data clp on (clp.application_id = ap.application_id)
					where
						ap.application_status_id = asf.application_status_id
						and ap.company_id = {$this->server->company_id}
						and
						(
							(asf.level0='queued'   and asf.level1='cashline' and asf.level2='*root') or
							(asf.level0='dequeued' and asf.level1='cashline' and asf.level2='*root' and ap.date_application_status_set < date_sub(current_timestamp, interval ".self::DEQUEUE_TIMEOUT." minute) )
						)
						and $cashline_status_string
					group by clq.date_relevant
					order by clq.date_relevant desc, ap.application_id asc
					limit 1
					)
					a
					join application a2 ON a.application_id = a2.application_id
				";
		}
		$q_obj = $this->db->query($query);
		$next_obj = $q_obj->fetch(PDO::FETCH_OBJ);
		if ($next_obj == null) return null;

		ECash::getLog()->Write("[Agent:{$_SESSION['Server_state']['agent_id']}] Pulled App {$next_obj->application_id}");

		// This app is in the conversion manager and may need to give a seperate status
		if($status == "conversion_manager")
		{
			Remove_Agent_Affiliation($next_obj->application_id, NULL, "conversion","owner");
		}

		Update_Status($this->server, $next_obj->application_id, 'dequeued::cashline::*root', null, $this->server->agent_id);
		$app_data = $this->loan_data_obj->Fetch_Loan_All($next_obj->application_id);		
		$app_data->queue_count = $this->Get_Queue_Count();
		$_SESSION['queue_count'] = $app_data->queue_count;
			
		return $app_data;
	}
}

?>