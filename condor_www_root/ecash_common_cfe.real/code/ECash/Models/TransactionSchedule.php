<?php

	/**
	 * @package Ecash.Models
	 */

class ECash_Models_TransactionSchedule extends ECash_Models_ObservableWritableModel 
	{
		const EVENT_STATUS_REGISTERED = 'registered';
		const EVENT_STATUS_SCHEDULED = 'scheduled';
		const EVENT_STATUS_SUSPENDED = 'suspended';

		/** @TODO maybe include these as constants in Transaction biz object */
		const CONTEXT_ARRANGEMENT = 'arrangement';
		const CONTEXT_PARTIAL = 'partial'
		const CONTEXT_MANUAL = 'manual';
		const CONTEXT_GENERATED = 'generated';
		const CONTEXT_PAYDOWN = 'paydown';
		const CONTEXT_PAYOUT = 'payout';
		const CONTEXT_CANCEL = 'cancel';
		const CONTEXT_REATTEMPT = 'reattempt';

		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'company_id',
				'application_id', 'transaction_schedule_id', 'transaction_type_id',
				'origin_id', 'configuration_trace_data', 'event_status',
				'date_event', 'date_effective', 'context', 'source_id',
				'is_shifted'
			);
			return $columns;
		}
		public function getColumnData()
		{
			$modified = $this->column_data;
			//mysql timestamps
			$modified['date_modified'] = date("Y-m-d H:i:s", $modified['date_modified']);
			$modified['date_created'] = date("Y-m-d H:i:s", $modified['date_created']);
			//mysql dates
			$modified['date_event'] = date('Y-m-d', $modified['date_event']);
			$modified['date_effective'] = date('Y-m-d', $modified['date_effective']);

			return $modified;
		}
		public function setColumnData($column_data)
		{
			//mysql timestamps
			$column_data['date_modified'] = strtotime( $column_data['date_modified']);
			$column_data['date_created'] = strtotime( $column_data['date_created']);
			//mysql dates
			$column_data['date_event'] = strtotime( $column_data['date_event']);
			$column_data['date_effective'] = strtotime( $column_data['date_effective']);

			$this->column_data = $column_data;
		}
		public function getPrimaryKey()
		{
			return array('transaction_schedule_id');
		}
		public function getAutoIncrement()
		{
			return 'transaction_schedule_id';
		}
		public function getTableName()
		{
			return 'transaction_schedule';
		}
	}
?>
