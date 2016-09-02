<?php
/**
 *
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 *
 */

class VendorAPI_Actions_SetContactFields extends VendorAPI_Actions_GetContactFields
{
	public function __construct(VendorAPI_IDriver $driver,
		VendorAPI_IApplicationFactory $application_factory
	)
	{
		parent::__construct($driver,$application_factory);
		$this->application_factory = $application_factory;
	}

	public function execute($application_id, $field_array)
	{
		if ($result=$this->setFields($application_id, $field_array))
		{
			$status = VendorAPI_Response::SUCCESS;
		}
		else
		{
			$status = VendorAPI_Response::ERROR;
		}
		
		return new VendorAPI_Response(new VendorAPI_StateObject(), $status, $result);
	}
	
	/**
	 * Determines which fields got changed, and appropriatly sets or unsets the changes
	 * returns fields array on success or false on failure.
	 */
	public function setFields($application_id, $field_array)
	{
		if ((!isset($this->fields)) && (!($this->fields = $this->getFields($application_id)))) {
			return false;
		}

		foreach ($this->fields_list as $field){
			foreach ($this->attrib_list as $value => $attrib){
				if (isset($field_array[$field][$attrib]) && ($field_array[$field][$attrib])) $this->setField($application_id,$field,$attrib);
				else $this->unsetField($application_id,$field,$attrib);
				if ((!(strpos($field,'ref_phone_') === false)) && ($attrib == 'do_not_contact')){
					if (isset($field_array[$field][$attrib]) && ($field_array[$field][$attrib])) $this->setReference($application_id,substr($field,strlen('ref_phone_')));
					else $this->unsetReference($application_id,substr($field,(strlen('ref_phone_')*1)));
				}
				$this->fields->$field->$attrib = $value;
			}
		}

		return $this->fields;
	}
	
	/**
	 * Makes a new application field for a given attribute if it doesn't already exist
	 * false on failure.
	 */
	public function setField($application_id, $field, $attrib)
	{
		$atrib_array = array_flip($this->attrib_list);
		
		if (($application_id<=0) || (!in_array($field,$this->fields_list)) || (!in_array($attrib,$this->attrib_list)))
			return false;
		
		$factory = $this->driver->getFactory();
		
		$application_field = $factory->getModel('ApplicationField');
		
		$ap_field = $application_field->loadBy(array(
			'table_row_id' => $application_id,
			'column_name' => $field,
			'application_field_attribute_id' => $atrib_array[$attrib]
		));

		if ($ap_field) return true;
		
		$application_field->date_created = time();
		$application_field->date_modified = time();
		$application_field->company_id = ECash::getCompany()->company_id;
		$application_field->table_name = "application";
		$application_field->column_name = $field;
		$application_field->table_row_id = $application_id;
		$application_field->application_field_attribute_id = $atrib_array[$attrib];
		$application_field->agent_id = ECash::getAgent()->getAgentId();

		return $application_field->save();		
	}
	
		
	/**
	 * Removes an existing application field for a given attribute if it already exists
	 * false on failure.
	 */
	public function unsetField($application_id, $field, $attrib)
	{
		if (($application_id<=0) || (!in_array($field,$this->fields_list)) || (!in_array($attrib,$this->attrib_list)))
			return false;
		
		$factory = $this->driver->getFactory();
		
		$application_field = $factory->getModel('ApplicationField');
		
		$field = $application_field->loadBy(array(
			'table_row_id' => $application_id,
			'column_name' => $field,
			'application_field_attribute_id' => $atrib_array[$attrib]
		));

		if ($field) return $field->delete();
		else return false;
	}
	
	/**
	 * Sets a personal reference to do_not_contact
	 * false on failure.
	 */
	public function setReference($application_id, $ref_num)
	{
		if (($application_id<=0) || ($ref_num<=0))
			return false;
		
		$factory = $this->driver->getFactory();
		
		$reference_model = $factory->getModel('PersonalReference');
		
		$references = $reference_model->loadAllBy(array('application_id' => $application_id));

		$ref_idx = 1;
		foreach ($references as $ref) {
			if ($ref_idx == $ref_num) {
				$ref_col = $ref->getColumnData();

				if ($ref_col['contact_pref'] != 'do not contact') {
					$ref->contact_pref = 'do not contact';
					$ret = $ref->save();
				} else $ret = true;
			}
			$ref_idx++;
		}
		return $ret;
	}
	
		
	/**
	 * Un-Sets a personal reference from do_not_contact (or ok to contact)
	 * false on failure.
	 */
	public function unsetReference($application_id, $ref_num)
	{
		if (($application_id<=0) || ($ref_num<=0))
			return false;
		
		$factory = $this->driver->getFactory();
		
		$reference_model = $factory->getModel('PersonalReference');
		
		$references = $reference_model->loadAllBy(array('application_id' => $application_id));

		$ref_idx = 1;
		foreach ($references as $ref) {
			if ($ref_idx == $ref_num) {
				$ref_col = $ref->getColumnData();

				if ($ref_col['contact_pref'] != 'ok to contact') {
					$ref->contact_pref = 'ok to contact';
					$ret = $ref->save();
				} else $ret = true;
			}
			$ref_idx++;
		}
		return $ret;
	}
}
