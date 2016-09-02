<?php
	
	require_once 'WritableModel.php';
	require_once 'IApplicationFriend.php';
	
	class ECash_Models_Document extends ECash_Models_WritableModel  implements ECash_Models_IApplicationFriend
	{
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'company_id',
				'application_id', 'document_id', 'document_list_id',
				'document_method_legacy', 'document_event_type',
				'name_other', 'document_id_ext', 'agent_id',
				'signature_status', 'sent_to', 'document_method',
				'transport_method', 'archive_id'
			);
			return $columns;
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
