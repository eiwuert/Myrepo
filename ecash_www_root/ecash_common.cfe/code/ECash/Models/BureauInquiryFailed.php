<?php
/**
 * The bureau_inquiry_failed model
 *
 * @package Models
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class ECash_Models_BureauInquiryFailed extends ECash_Models_WritableModel
{
	public $Bureau;

	public function __construct($db)
	{
		parent::__construct($db);
		$this->Bureau = ECash::getFactory()->getModel('Bureau');
		$this->Bureau->loadBy(array('name_short' => 'datax'));
		$this->bureau_id = $this->Bureau->bureau_id;
	}

	/**
	 * The tagss in the model
	 *
	 * @return array
	 */
	public function getColumns()
	{
		return array(
			'date_modified',
			'date_created',
			'company_id',
			'application_id',
			'bureau_inquiry_id',
			'bureau_id',
			'inquiry_type',
			'sent_package',
			'received_package',
			'outcome',
			'trace_info',
			'error_condition',
			'decision',
			'reason',
			'timer',
			'score',
			'payrate',
			'ssn',
			'agent_id'
		);
	}

	/**
	 * The name of the model table
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'bureau_inquiry_failed';
	}

	/**
	 * The primary key tagss
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return array('bureau_inquiry_id');
	}

	/**
	 * The auto increment tags
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return 'bureau_inquiry_id';
	}

	public function setColumnData($data)
	{
		if(isset($data['date_created']) && ! is_int($data['date_created']))
		{
			$data['date_created'] = strtotime($data['date_created']);
		}

		if (isset($data['sent_package']) && ! empty($data['sent_package']))
		{
			$data['sent_package'] = gzuncompress(substr($sent_package = $data['sent_package'], 4));
		}

		if(isset($data['received_package']) && ! empty($data['received_package']))
		{
			$data['received_package'] = gzuncompress(substr($data['received_package'], 4));
		}

		$this->column_data = $data;
	}

	public function getColumnData()
	{
		// This method is called twice by canInsert() and insert()
		// so the compression is done both times.  It's not ideal,
		// but oh well.

		$data = $this->column_data;
		$data['sent_package'] = pack('L', strlen($this->column_data['sent_package'])) . gzcompress($this->column_data['sent_package']);
		$data['received_package'] = pack('L', strlen($this->column_data['received_package'])) . gzcompress($this->column_data['received_package']);

		if(is_int($data['date_created']))
		{	
			$data['date_created'] = date('Y-m-d H:i:s', $data['date_created']);
		}

		return $data;
	}
}

?>
