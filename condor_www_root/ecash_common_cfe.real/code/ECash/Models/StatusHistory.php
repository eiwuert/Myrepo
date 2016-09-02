<?php

	require_once 'WritableModel.php';

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_StatusHistory extends ECash_Models_WritableModel implements ECash_Models_IApplicationFriend
	{
		public $Company;
		public $Application;
                public $StatusHistoryRelated;
                public $Agent;
                public $ApplicationStatus;

                /**
                 *
                 * @return ECash_Models_ApplicationStatusFlat
                 */
                public function getStatus()
                {
                        $asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
                        return $asf[$this->application_status_id];
                }

                /**
                 * This function is not static, as it assumes you've set
                 * application_id, date_created and application_status_id on
                 * this instance [JustinF]
                 */
		public function updateDateCreated()
		{
			$db = $this->getDatabaseInstance(self::DB_INST_WRITE);

			$query = "UPDATE status_history
					  SET date_created=?
					  WHERE application_id=?
						AND application_status_id=?";

			$values = array($this->date_created, $this->application_id, $this->application_status_id);

			$st = $db->prepare($query);
			$st->execute($values);

                        $this->setDataSynched();
                }

                /**
                 * @param int $application_id
                 * @param int $status_id
		 * @param string $date_created
                 * @return bool
                 */
		public function getStatusExists($application_id, $status_id, $date_created = NULL)
                {
			$values = array($application_id, $status_id);
                        $query = "SELECT
                                        1
                                FROM
                                        status_history
                                WHERE
                                        application_id  = ?
					AND application_status_id = ?";
			if (!empty($date_created))
			{
				$query.= " AND date_created = ? ";
				$values[] = $date_created;
			}
			$query .= " LIMIT 1";

                        $exists = DB_Util_1::querySingleValue(
                                        $this->getDatabaseInstance(),
                                        $query,
				$values
                        );
                        return (bool)$exists;
                }

                public function getColumns()
		{
			static $columns = array(
				'date_created', 'company_id', 'application_id',
				'status_history_id', 'status_history_related_id',
				'agent_id', 'application_status_id'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('status_history_id');
		}
		public function getAutoIncrement()
		{
			return 'status_history_id';
		}
		public function getTableName()
		{
			return 'status_history';
		}

		public function setApplicationData(ECash_Models_Application $application)
		{
			$this->application_id = $application->application_id;
			$this->company_id = $application->company_id;
			$this->agent_id = $application->agent_id;
		}
		
		public function setApplicationStatus($name_short)
		{
			$status = ECash::getFactory()->getModel('ApplicationStatus');
			$status->loadBy(array('name_short'=>$name_short));
			$this->application_status_id = $status->application_status_id;
		}
	}
?>
