<?php

class ECash_Models_FollowUp extends ECash_Models_WritableModel
{
	public function getColumns()
	{
		static $columns = array(
			'follow_up_id', 'follow_up_type_id', 'follow_up_time', 'company_id',
			'application_id', 'agent_id', 'comment_id', 'status', 'date_modified',
			'date_created'
		);
		return $columns;
	}
	public function getPrimaryKey()
	{
		return array('follow_up_id');
	}
	public function getAutoIncrement()
	{
		return 'follow_up_id';
	}
	public function getTableName()
	{
		return 'follow_up';
	}
}
?>
