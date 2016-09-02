<?php
abstract class LenderAPI_BlackboxDataSource_BaseDelegate implements LenderAPI_BlackboxDataSource_IDelegate
{
	/**
	 * Blackbox data to pull info from.
	 * @var OLPBlackbox_Data
	 */
	protected $data;

	/**
	 * Create a delegate to pull information from OLPBlackbox_Data
	 * @param OLPBlackbox_Data $data The data to reference to create the value
	 * this delegate produces for the Iterable data source for the transform layer.
	 * @return void
	 */
	public function __construct(OLPBlackbox_Data $data)
	{
		$this->data = $data;
	}
}
?>
