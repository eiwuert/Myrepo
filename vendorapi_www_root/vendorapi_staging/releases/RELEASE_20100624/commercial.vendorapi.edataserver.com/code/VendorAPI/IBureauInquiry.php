<?php
/**
 * Behavior related to Bureau Inquiries
 *
 * Implementations interface for sending inquiries to the app service
 *
 * @author Richard Bunce <richard.bunce@sellingsource.com>
 */
interface VendorAPI_IBureauInquiry
{
	/**
	 * Builds and sents data packet to the app service for bureau inquiry
	* @param VendorAPI_StateObject $state
	* @param ECash_WebService_InquiryClient $inquiry_client
	* @param $data 
	* @return boolean
	 */
	public function sendInquiryToAppService(VendorAPI_StateObject $state, $data, ECash_WebService_InquiryClient $inquiry_client, VendorAPI_TemporaryPersistor $persistor);


}

?>
