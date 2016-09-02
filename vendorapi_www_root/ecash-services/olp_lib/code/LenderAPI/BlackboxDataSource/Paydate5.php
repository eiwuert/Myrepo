<?php
/**
 * Paydate5 Delegate
 *
 * @package VendorAPI
 * @version $Id$
 */
class LenderAPI_BlackboxDataSource_Paydate5 extends LenderAPI_BlackboxDataSource_BaseDelegate
{
	/**
	 *
	 * @return mixed Usually a string value, to be used in XSLT transforms.
	 * @see LenderAPI_BlackboxDataSource_IDelegate::value()
	 */
	public function value ()
	{
		return isset($this->data->paydates[4]) ? $this->data->paydates[4] : NULL;
	}

	public function setValue ($value)
	{
		// OLPBlackbox_Data dosen't seem to initialize this
		if (! is_array($this->data->paydates)) $this->data->paydates = array();

		// can't indirectly modify an overloaded property. ie. $this->data->paydates[0] = $value
		$pd = $this->data->paydates;
		$pd[4] = $value;
		$this->data->paydates = $pd;
	}
}
?>
