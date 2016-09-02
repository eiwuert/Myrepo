<?php

require_once('conversion.class.php');
require_once(LIB_DIR.'AgentAffiliation.php');

class Cashline_Conversion extends Conversion
{

	function Get_Queue_Count()
	{
		$status_map = Fetch_Status_Map(false);
		$cashlineQueued = Search_Status_Map('queued::cashline::*root', $status_map);
		$cashlineDequeued = Search_Status_Map('dequeued::cashline::*root', $status_map);
		$managerQueued = Search_Status_Map('manager_queued::cashline::*root', $status_map);
		$managerDequeued = Search_Status_Map('manager_dequeued::cashline::*root', $status_map);
		
		$query = "
			(
				SELECT
					clp.cashline_status,
					COUNT(DISTINCT ap.application_id) queue_count
				FROM
					application ap
					JOIN cl_customer clc ON ap.archive_cashline_id = clc.cashline_id AND ap.company_id = clc.company_id
					JOIN cl_pending_data clp ON (clp.application_id = clc.application_id)
				WHERE
					clp.company_id = {$this->server->company_id} AND
					(
						application_status_id = {$cashlineQueued} OR
						(
							application_status_id = {$cashlineDequeued} AND 
							ap.date_application_status_set < DATE_SUB(
								current_timestamp, interval ".self::DEQUEUE_TIMEOUT." minute
							)
						)
					)
				GROUP BY
					clp.cashline_status
			)
			UNION
			(
				SELECT
					'conversion_manager' cashline_status,
					COUNT(DISTINCT ap.application_id) queue_count
				FROM
					application ap FORCE INDEX (idx_app_status_co_stsdate)
				WHERE
					ap.company_id = {$this->server->company_id} AND
					(
						application_status_id = {$managerQueued} OR
						(
							application_status_id = {$managerDequeued} AND 
							ap.date_application_status_set < DATE_SUB(
								current_timestamp, interval ".self::DEQUEUE_TIMEOUT." minute
							)
						)
					)
			)
		";


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
		
		
		$queue_counts['returns'] = count_queue('Cashline Return');
		$queue_counts['returns_collections_contact'] = count_queue('Cashline Return Collections Contact');
		$queue_counts['returns_collections_new'] = count_queue('Cashline Return Collections New');
		$queue_counts['returns_past_due'] = count_queue('Cashline Return Past Due');

		return (object) $queue_counts;
	}

	function updateConversionStatus($application_id, $status)
	{
		$query = "
			UPDATE application
			SET
				application_status_id = {$status},
				date_application_status_set = NOW(),
				modifying_agent_id = '{$_SESSION['agent_id']}'
			WHERE
				application_id = {$application_id}
		";

		$this->db->exec($query);
	}

	function Lock_Application($status, &$new_status)
	{
		$status_map = Fetch_Status_Map();
		$cashlineQueued = Search_Status_Map('queued::cashline::*root', $status_map);
		$cashlineDequeued = Search_Status_Map('dequeued::cashline::*root', $status_map);
		$managerQueued = Search_Status_Map('manager_queued::cashline::*root', $status_map);
		$managerDequeued = Search_Status_Map('manager_dequeued::cashline::*root', $status_map);
		// App that are in the conversion manager are not in the cashline conversion
		if($status == "conversion_manager")
		{
			$queued = $managerQueued;
			$dequeued = $managerDequeued;
			$cashline_status_string = "";
			$new_status = $managerDequeued;
		}
		else
		{
			$queued = $cashlineQueued;
			$dequeued = $cashlineDequeued;
			if ($status == 'other') $cashline_status_string = "AND clp.cashline_status not in ('active','hold','collection')";
			else $cashline_status_string = "AND clp.cashline_status = '$status'";
			$new_status = $cashlineDequeued;
		}
		$query = "
			UPDATE cl_conversion_queue
			SET
				`lock` = CONNECTION_ID()
			WHERE
				application_id IN
				(
					SELECT
						ap.application_id
					FROM
						application ap
						JOIN cl_customer clc USING (application_id)
						JOIN cl_pending_data clp USING (application_id)
					WHERE
						clp.company_id = 2 AND
						(
							application_status_id = {$queued} OR
							(
								application_status_id = {$dequeued} AND
								ap.date_application_status_set < DATE_SUB(
									current_timestamp, interval 10 minute
								)
							)
						) {$cashline_status_string}
					ORDER BY
						application_id DESC
				) AND `lock` = 0
			ORDER BY application_id DESC
			LIMIT 1;
		";

		$this->db->exec($query);

		$query = "
			SELECT application_id FROM cl_conversion_queue WHERE `lock` = CONNECTION_ID()
		";

		$result = $this->db->query($query);
		if ($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			return $row['application_id'];
		}
		else 
		{
			return false;
		}
	}

	function removeLocks()
	{
		$query = "
			UPDATE cl_conversion_queue SET `lock` = 0 WHERE `lock` = CONNECTION_ID()
		";

		$this->db->exec($query);
	}
	
	function Get_Next_Application($status)
	{
		try 
		{
			$new_status = '';
			$application_id = $this->Lock_Application($status, $new_status);
			
			if ($application_id === false)
			{
				ECash::getLog()->Write("Conversion Queue Pull: Did not find a valid application");
				return null;
			}
	
			ECash::getLog()->Write("[Agent:{$_SESSION['Server_state']['agent_id']}] Pulled App {$application_id} : {$new_status}");

			// This app is in the conversion manager and may need to give a seperate status
			if($status == "conversion_manager")
			{
				$this->updateConversionStatus($application_id, $new_status);
				eCash_AgentAffiliation::expireApplicationAffiliations($application_id, 'conversion', 'owner');
			} 
			else
			{
				$this->updateConversionStatus($application_id, $new_status);
			}
			$this->removeLocks();
		}
		catch (Exception $e)
		{
			ECash::getLog()->Write("Error in conversion pull: {$e->getMessage()}");
			$this->removeLocks();
			throw $e;
		}
		//Remove_Unregistered_Events_From_Schedule($next_obj->application_id);
		$this->Remove_All_Events($application_id);
		$app_data = $this->ld->Fetch_Loan_All($application_id);		
		$app_data->queue_count = $this->Get_Queue_Count();
		$app_data->conversion_mode = 'cashline';
		$_SESSION['queue_count'] = $app_data->queue_count;
			
		return $app_data;
	}	
	
}
?>
