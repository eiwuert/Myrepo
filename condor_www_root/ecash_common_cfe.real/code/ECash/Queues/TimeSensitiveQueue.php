<?php
	class ECash_Queues_TimeSensitiveQueue extends ECash_Queues_BasicQueue
	{
		protected function onItemInsert(ECash_Models_WritableModel $model)
		{
			parent::onItemInsert($model);
		}
		protected function onItemInsertComplete(ECash_Models_WritableModel $model)
		{
			parent::onItemInsertComplete($model);
		}
		protected function onItemDequeue(ECash_Models_WritableModel $model)
		{
			parent::onItemDequeue($model);
		}
		protected function onItemRemove($related_id)
		{
			parent::onItemRemove($related_id);
		}
		protected function queueEntryFactory()
		{
			return new ECash_Models_TimeSensitiveQueueEntry($this->db);
		}
		protected function queueItemFactory(ECash_Models_WritableModel $model)
		{
			return ECash_Queues_TimeSensitiveQueueItem::fromModel($model);
		}
		public function getQueueEntryTableName()
		{
			return 'n_time_sensitive_queue_entry';
		}

		/**
		 * overridable method to fetch and return the next queue_entry (in row form)
		 *
		 * @return ECash_Models_WritableModel
		 */
		protected function getNextRow()
		{
			$time = localtime(time(), TRUE);

			$query = "
				SELECT *
				FROM n_time_sensitive_queue_entry
				WHERE
					queue_id = ?
					AND date_available <= ?
					AND start_hour <= ?
					and end_hour > ?
					AND (date_expire IS NULL OR date_expire >= ?)
				" . $this->getSortOrder() . "
				LIMIT 1
				FOR UPDATE
			";

			$st = $this->db->queryPrepared($query, array(
				$this->model->queue_id,
				date("Y-m-d H:i:s"),
				$time['tm_hour'],
				$time['tm_hour'],
				date("Y-m-d H:i:s")
			));

			return $st->fetch();
		}
		
		/**
		 * returns a count of the number of items in the queue
		 *
		 * @return int
		 */
		public function count()
		{
			$time = localtime(time(), TRUE);
			
			$query = "
				select count(*)
				from " . $this->getQueueEntryTableName() . "
				where
					queue_id = ?
					and date_available <= ?
					and start_hour <= ?
					and end_hour > ?
					and (date_expire IS NULL OR date_expire >= ?)
			";
			return DB_Util_1::querySingleValue($this->db,
				$query,
				array(
					$this->model->queue_id,
					date("Y-m-d H:i:s"),
					$time['tm_hour'],
					$time['tm_hour'],					
					date("Y-m-d H:i:s")
				)
			);
		}		

		public function getNewQueueItem($related_id)
		{
			$application = ECash::getFactory()->getModel('Application');
			$daylight_savings = date('I');
			if ($application->loadBy(array('application_id' => $related_id)))
			{
				$current_year = Date('Y');
				 $query = "
	                        SELECT
	                        (CASE
	                                        WHEN
	                 $daylight_savings AND
	                 dst = 'Y'
	                                        THEN tz - 1
	                                        ELSE tz
	                                END) as offset
	                        FROM zip_tz
	                        WHERE
	                                zip_code = ?
	                        LIMIT 1
	                                ";

				$st = $this->db->queryPrepared($query, array(substr($application->zip_code,0,5)));
				$config = ECash_Config::getInstance();

				if (($row = $st->fetch(PDO::FETCH_OBJ)) !== FALSE)
				{
					$company_tz = new DateTimeZone($config->TIME_ZONE);
					$company_tz_offset = $company_tz->getOffset(new DateTime("now", $company_tz)) /3600;
					$offset = $row->offset + $company_tz_offset;

					$item = new ECash_Queues_TimeSensitiveQueueItem(
						$application->application_id,
						$config->LOCAL_EARLIEST_CALL_TIME + $offset,
						$config->LOCAL_LATEST_CALL_TIME + $offset
					);
					return $item;
				}
				else //default to the companies time zone instead of blowing up
				{
//					$company_tz = new DateTimeZone($config->TIME_ZONE);
//					$company_tz_offset = $company_tz->getOffset(new DateTime("now", $company_tz)) /3600;
//					$offset = $company_tz_offset;

					$item = new ECash_Queues_TimeSensitiveQueueItem(
						$application->application_id,
						$config->LOCAL_EARLIEST_CALL_TIME,
						$config->LOCAL_LATEST_CALL_TIME
					);
					//get_log()->write("Unable to locate timezone in the timezone database for zipcode: ".substr($application->zip_code,0,5));
					return $item;
					//	throw new ECash_Queues_QueueException("Unable to locate timezone in the timezone database for zipcode: ".substr($application->zip_code,0,5));
				}
			}
			else
			{
				throw new ECash_Queues_QueueException("No application found for application_id=$related_id");
			}
		}
		/**
		 * Returns the time zone offset of the given applicaiton.
		 *
		 * This function is used to help build the queue entry for a queue entry.
		 *
		 * @param int $application_id
	 	 * @return string The HHMM of the time zone offset.
		 */
		protected function getApplicationTimeZoneOffset($application_id)
		{
			$application = ECash::getApplicationById($application_id);

			if ($application->exists())
			{
				$daylight_savings = date('I');
		
				$query = "
					SELECT
    					(CASE
   							WHEN
                                 $daylight_savings AND
                                 dst = 'Y'
   							THEN tz - 1
   							ELSE tz
   						END) as offset
  					FROM zip_tz
  					WHERE
  						zip_code = ?
  					LIMIT 1
  						";

				$st = DB_Util_1::queryPrepared(
					ECash_Config::getMasterDbConnection(),
					$query,
					array(substr($application->zip_code,0,5))
				);

				$company_tz = new DateTimeZone(
					ECash_Config::getInstance()->TIME_ZONE
				);
				
				$company_tz_offset = $company_tz->getOffset(new DateTime("now", $company_tz)) /3600;
				return (($row = $st->fetch()) !== FALSE)
					? $row['offset'] + $company_tz_offset
					: 0;
			}
			else
			{
				throw new ECash_Queues_QueueException("No application found for application_id=$application_id");
			}
		}
	}
?>
