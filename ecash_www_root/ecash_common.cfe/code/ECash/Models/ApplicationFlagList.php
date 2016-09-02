<?php

class ECash_Models_ApplicationFlagList extends ECash_Models_IterativeModel
{
	public function getClassName()
	{
		return 'ECash_Models_ApplicationFlag';
	}
	
	public function getTableName()
	{
		return 'application_flag';
	}

	public function loadByApplicationID($application_id, &$response)
	{
		$query = "select * from application_flag where application_id = {$application_id}";

		$db = $this->getDatabaseInstance();
		$this->statement = $db->query($query);

		$kvps = array('name_short', 'name');
		$response->flags = array();
		
		//peer inside ourself for the actual row
		while($row = $this->current_row())
		{
			ECash_Models_LegacyDisplayHandler::loadByKVPs($row, $kvps, 'flags', $response);
			$this->next_row();
		}
		//be kind...
		$this->rewind();
	}
	

}

?>