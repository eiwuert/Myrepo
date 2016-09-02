<?php
/**
 * Assembles a date_of_birth property.
 *
 * @version $Id: DateOfBirth.php 31819 2009-01-21 04:48:46Z olp_release $
 * @package VendorAPI
 */
class LenderAPI_BlackboxDataSource_DateOfBirth extends LenderAPI_BlackboxDataSource_BaseDelegate
{
	/**
	 * @return mixed Usually a string value, to be used in XSLT transforms. 
	 * @see LenderAPI_BlackboxDataSource_IDelegate::value()
	 */
	public function value()
	{
		return sprintf(
			'%s-%s-%s',
			$this->data->date_dob_y,
			$this->data->date_dob_m,
			$this->data->date_dob_d
		);
	}

	public function setValue ($value)
	{
		$ts = strtotime($value);
		if ($ts !== FALSE)
		{
			$this->data->date_dob_y = date('Y', $ts);
			$this->data->date_dob_m = date('m', $ts);
			$this->data->date_dob_d = date('d', $ts);
			$this->data->dob = date('m/d/Y', $ts);
		}

	}
}
?>
