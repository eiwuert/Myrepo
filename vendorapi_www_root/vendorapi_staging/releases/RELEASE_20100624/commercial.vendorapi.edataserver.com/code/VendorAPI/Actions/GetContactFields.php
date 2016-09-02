<?php
/**
 *
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 *
 */

class VendorAPI_Actions_GetContactFields extends VendorAPI_Actions_Base
{
	protected $application_factory;
	
	/**
	 * ECash_Models_Customer
	 */
	
	protected $fields;

	protected $fields_list = array('phone_home', 'phone_cell', 'phone_work', 'customer_email', 'ref_phone_1', 'ref_phone_2', 'ref_phone_3', 'ref_phone_4', 'ref_phone_5', 'ref_phone_6', 'street');
	protected $attrib_list = array(1 => 'bad_info', 2 => 'do_not_contact', 3 => 'best_contact', 4 => 'do_not_market');

	public function __construct(VendorAPI_IDriver $driver,
		VendorAPI_IApplicationFactory $application_factory
	)
	{
		parent::__construct($driver);
		$this->application_factory = $application_factory;
	}

	public function execute($application_id)
	{
		if ($result=$this->getFields($application_id))
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
	 * Finds customer. Returns a customer model on success and
	 * false on failure.
	 *
	 * @param string $username
	 * @return ECash_Models_Customer
	 */
	public function getFields($application_id)
	{

		if (!isset($this->fields))
		{
			$factory = $this->driver->getFactory();
			
			$application_fields = $factory->getModel('ApplicationField');
	
			if (!($rows = $application_fields->loadAllBy(array("table_row_id" => $application_id))))
			{
				return false;
			}
			else
			{
				$ap_fields = array();
				foreach ($rows as $row) {
					$ap_fields[$row->column_name][$row->application_field_attribute_id] = TRUE;
				}
				
				$fields = array();
				foreach ($this->fields_list as $field){
					$fields[$field] = array();
					foreach ($this->attrib_list as $val => $key){
						if (isset($ap_fields[$field][$val])) $fields[$field][$key] = TRUE;
						else $fields[$field][$key] = FALSE;
					}
				}
			}
			$this->fields = $fields;
		}	
		return $this->fields;
	}
}
