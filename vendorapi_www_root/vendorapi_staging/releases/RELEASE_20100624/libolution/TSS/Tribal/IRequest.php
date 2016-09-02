<?php
/**
 * Transforms the data array to XML
 */
interface TSS_Tribal_IRequest
{
	/**
	 * Returns the Tribal call type being made
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
