<?php
/**
 * Transforms the data array to XML
 *
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
interface TSS_DataX_IRequest
{
	/**
	 * Returns the DataX call type being made
	 *
	 * @return string
	 */
	public function getCallType();

	/**
	 * Transform the given array into an XML request
	 * @param array $data
	 * @return string
	 */
	public function transformData(array $data);
}
