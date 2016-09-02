<?php

	require_once 'WritableModel.php';
	require_once 'IApplicationFriend.php';
	require_once 'Bureau.php';

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_BureauInquiry extends ECash_Models_WritableModel implements ECash_Models_IApplicationFriend
	{
		public $Company;
		public $Application;
		public $Bureau;

		public function __construct($db)
		{
			parent::__construct($db);
			$this->Bureau = ECash::getFactory()->getModel('Bureau');
			$this->Bureau->loadBy(array('name_short' => 'datax'));
			$this->bureau_id = $this->Bureau->bureau_id;
		}
		
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'company_id',
				'application_id', 'bureau_inquiry_id', 'bureau_id',
				'inquiry_type', 'sent_package', 'received_package',
				'outcome', 'trace_info', 'error_condition', 'decision',
				'reason','timer','agent_id',
			);
			return $columns;
		}
		
		public function getPrimaryKey()
		{
			return array('bureau_inquiry_id');
		}
		
		public function getAutoIncrement()
		{
			return 'bureau_inquiry_id';
		}
		
		public function getTableName()
		{
			return 'bureau_inquiry';
		}
		
		public function setApplicationData(ECash_Models_Application $application)
		{
			$this->application_id = $application->application_id;
			$this->company_id = $application->company_id;
		}
		
		/**
		 * Overrides DB_Models_WritableModel_1's method so we can
		 * gzcompress the the sent_package and received_package columns
		 * before they are stored in a blob
		 * 
		 * @return array
		 */
		public function getColumnData()
		{
			// This method is called twice by canInsert() and insert()
			// so the compression is done both times.  It's not ideal,
			// but oh well.
			
			$data = $this->column_data;
			$data['sent_package'] = pack('L', strlen($this->column_data['sent_package'])) . gzcompress($this->column_data['sent_package']);
			$data['received_package'] = pack('L', strlen($this->column_data['received_package'])) . gzcompress($this->column_data['received_package']);
			$data['date_created'] = date('Y-m-d H:i:s', $data['date_created']);

			return $data;
		}

		/**
		 * Overrides DB_Models_WritableModel_1's method so we can
		 * gzuncompress the the sent_package and received_package columns
		 * 
		 * @param array $data
		 */
		public function setColumnData($data)
		{
			$data['date_created'] = strtotime($data['date_created']);
			if (isset($data['sent_package']) && ! empty($data['sent_package']))
			{
				$data['sent_package'] = gzuncompress(substr($sent_package = $data['sent_package'], 4));
			}

			if (isset($data['received_package']) && ! empty($data['received_package']))
			{
				$data['received_package'] = gzuncompress(substr($data['received_package'], 4));
			}

			$this->column_data = $data;
		}
	}
?>
