<?php
	
	require_once 'WritableModel.php';
	require_once 'IApplicationFriend.php';
	
	class ECash_Models_Document extends ECash_Models_ObservableWritableModel  implements ECash_Models_IApplicationFriend
	{
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'company_id',
				'application_id', 'document_id', 'document_list_id',
				'document_event_type',
				'name_other', 'document_id_ext', 'agent_id',
				'signature_status', 'sent_to', 'document_method',
				'transport_method', 'archive_id'
			);
			return $columns;
		}
		public function __get($name)
		{
			$value = parent::__get($name);
			if (is_numeric($value) && in_array($name, array("date_created", "date_modified")))
			{
				$value = date("Y-m-d H:i:s", $value);
			}
			return $value;
		}

		public function getColumnData()
		{
			$modified = parent::getColumnData();
			if (is_numeric($modified['date_modified']))
			{
				$modified['date_modified'] = date("Y-m-d H:i:s", $modified['date_modified']);
			}
			if (is_numeric($modified['date_created']))
			{
				$modified['date_created'] = date("Y-m-d H:i:s", $modified['date_created']);
			}
			return $modified;
		}

		public function setColumnData($column_data)
		{
			if (!is_numeric($column_data['date_modified']))
			{
				$column_data['date_modified'] = strtotime( $column_data['date_modified']);
			}
			if (!is_numeric($column_data['date_created']))
			{
				$column_data['date_created'] = strtotime( $column_data['date_created']);
			}
			$this->column_data = $column_data;
		}

		/**
		 * Will attempt to load from the application service; if it is unsucessful it will call parent loadby
		 * 
		 * @param array $where_args 
		 * @return bool - Whether the load was successful or not
		 */
		public function loadBy(array $where_args)
		{
			foreach ($where_args as $col => $val)
			{
				$this->column_data[$col] = $val;
			}

			if ($this->loadFromService())
			{
				return TRUE;
			}
			else
			{
				$this->setColumnData(array());
				return parent::loadBy($where_args);
			}
			
		}

		/**
		 * Loads data from the document service
		 * 
		 * @return bool - Whether loading was successful or not
		 */
		public function loadFromService()
		{
			$doc_service = ECash::getFactory()->getDocumentClient();
			if (!empty($this->column_data['archive_id']))
			{
				$doc = $doc_service->findDocumentByArchiveId($this->column_data['archive_id']);
			}
			else if (!empty($this->column_data['document_id']))
			{
				$doc = $doc_service->findDocumentById($this->column_data['document_id']);
			}

			if (empty($doc))
			{
				return FALSE;
			}

			$this->column_data['date_created'] = $doc->date_created;
			$this->column_data['date_modified'] = $doc->date_modified;
			$this->column_data['application_id'] = $doc->application_id;
			$this->column_data['document_id'] = $doc->document_id;
			$this->column_data['document_list_id'] = $doc->document_list_id;
			$this->column_data['document_method_legacy'] = $doc->document_method_legacy;
			$this->column_data['document_event_type'] = $doc->document_event_type;
			$this->column_data['name_other'] = $doc->name_other;
			$this->column_data['document_id_ext'] = $doc->document_id_ext;
			$this->column_data['agent_id'] = $doc->agent_id;
			$this->column_data['signature_status'] = $doc->signature_status;
			$this->column_data['sent_to'] = $doc->sent_to;
			$this->column_data['document_method'] = $doc->document_method;
			$this->column_data['transport_method'] = $doc->transport_method;
			$this->column_data['archive_id'] = $doc->archive_id;

			$this->setDataSynched();

			return TRUE;
		}

		public function getPrimaryKey()
		{
			return array('document_id');
		}
		public function getAutoIncrement()
		{
			return 'document_id';
		}
		public function getTableName()
		{
			return 'document';
		}
		public function setApplicationData(ECash_Models_Application $application)
		{
			$this->application_id = $application->application_id;
			$this->company_id = $application->company_id;
		}	
	}
?>
