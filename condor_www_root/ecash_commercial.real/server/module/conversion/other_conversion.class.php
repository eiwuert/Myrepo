<?php

require_once('conversion.class.php');
require_once(SQL_LIB_DIR . "scheduling.func.php");
require_once(LIB_DIR.'AgentAffiliation.php');

class Other_Conversion extends Conversion
{

	function Get_Queue_Count()
	{
		$query = "
		(
			SELECT
				COUNT(application_id) AS queue_count,
				'active' as status
			FROM application ap
			JOIN application_status_flat asf ON (asf.application_status_id = ap.application_status_id)
			WHERE
			( 
				(asf.level0='queued'   and asf.level1='cashline' and asf.level2='*root') or
				(asf.level0='dequeued' and asf.level1='cashline' and asf.level2='*root' and ap.date_application_status_set < date_sub(current_timestamp, interval ".self::DEQUEUE_TIMEOUT." minute) )
			)
			AND ap.company_id = {$this->server->company_id}
			GROUP BY status
		)
		UNION
		(
			SELECT 
				COUNT(distinct aga.application_id) AS queue_count,
				'conversion_manager' AS status 
    		 FROM agent_affiliation aga, application_status_flat asf, application app
    		 WHERE app.application_id = aga.application_id
             AND asf.application_status_id = app.application_status_id
             AND (asf.level0 = 'dequeued' and asf.level1='cashline' and asf.level2='*root')
    		 AND aga.company_id = {$this->server->company_id}
        	 AND aga.affiliation_area = 'conversion'
        	 AND aga.affiliation_type = 'owner'
		)";


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
			if ( in_array($count_object->status, array('hold', 'active', 'collection','conversion_manager')) )
			{
				$queue_counts[$count_object->status] = $count_object->queue_count;
			}
			else 
			{
				$queue_counts['other'] += $count_object->queue_count;
			}
		}

		return (object) $queue_counts;
	}

	function Get_Next_Application($status = null)
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
			$query = "
				SELECT
					 	a.application_id,
					 	'1' as count,
						a.agent_id,
					 	a.application_status_id,
					 	a.date_application_status_set,
						a.date_next_contact
				FROM
						application a
				JOIN 	application_status_flat asf USING (application_status_id)
				WHERE
						a.company_id = {$this->server->company_id}
				AND
					(
						(asf.level0='queued'   AND asf.level1='cashline' AND asf.level2='*root') OR
						(asf.level0='dequeued' AND asf.level1='cashline' AND asf.level2='*root' AND a.date_application_status_set < DATE_SUB(CURRENT_TIMESTAMP, INTERVAL ".self::DEQUEUE_TIMEOUT." minute) )
					)
				ORDER BY a.application_id asc
				LIMIT 1
				";
		}
		$q_obj = $this->db->query($query);
		$next_obj = $q_obj->fetch(PDO::FETCH_OBJ);
		if ($next_obj == null) return null;

		ECash::getLog()->Write("[Agent:{$_SESSION['Server_state']['agent_id']}] Pulled App {$next_obj->application_id}");

		// This app is in the conversion manager and may need to give a seperate status
		if($status == "conversion_manager")
		{
				eCash_AgentAffiliation::expireApplicationAffiliations($application_id, 'conversion', 'owner');
		}

		Update_Status($this->server, $next_obj->application_id, 'dequeued::cashline::*root', null, $this->server->agent_id);
		$app_data = $this->ld->Fetch_Loan_All($next_obj->application_id);
		$app_data->queue_count = $this->Get_Queue_Count();
		$app_data->conversion_mode = 'other';
		$_SESSION['queue_count'] = $app_data->queue_count;
			
		return $app_data;
	}	
	
}
?>
