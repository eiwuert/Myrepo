<?php 
	class ECash_Models_APIPayment extends ECash_Models_WritableModel
	{
		public $Company;
		public $Application;
		public $EventType;
		
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'api_payment_id',
				'company_id', 'application_id', 'event_type_id', 'amount',
				'date_event', 'active_status'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('api_payment_id');
		}
		public function getAutoIncrement()
		{
			return 'api_payment_id';
		}
		public function getTableName()
		{
			return 'api_payment';
		}
		protected function getColumnData()
		{
			$data = $this->column_data;
			$data['date_created'] = date("Y-m-d H:i:s", $this->column_data['date_created']);
			$data['date_event'] = date("Y-m-d H:i:s", $this->column_data['date_event']);
			return $data;
		}
		protected function setColumnData($data)
		{
			$this->column_data = $data;
			$this->column_data['date_created'] = strtotime($data['date_created']);
			$this->column_data['date_event'] = strtotime($data['date_event']);
		}

		/**
		 * Gets the first active api_payment for the given application
		 *
		 * @param int $applicatio_id
		 * @return bool whether or not this model loaded
		 */
		public function loadByFirstPayment($application_id)
		{
			/** @TODO should this be ordered a certain way?  It wasn't in scheduling.funk::Fetch_API_Payments */
			$query = "
				SELECT
					*
				FROM
					api_payment
				WHERE
					application_id = ?
				AND
					active_status = 'active'
				limit 1
			";

			$db = $this->getDatabaseInstance(self::DB_INST_READ);

			$row = DB_Util_1::querySingleRow($db, $query, array(
				'application_id' => $application_id,
			));

			if ($row !== FALSE)
			{
				$this->fromDbRow($row);
				return TRUE;
			}
			return FALSE;
		}
		
	}
