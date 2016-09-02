<?php
/**
 * InquiryService API interface defines the methods required to support the service's WSDL (app.wsdl)
 *
 * @copyright Copyright &copy; 2014 aRKaic Equipment
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */
interface ECash_Service_InquiryService_IAPI
{
    /**
     * Test the service connection
     *
     * @return bool
     */
    public function testConnection();
    
    /**
     * Loads all of the inquiries for a specific application
     *
     * @return bool
     */
    public function findInquiriesByApplicationId($application_id);
    
    /**
     * Loads and individual inquiry
     *
     * @return bool
     */
    public function findInquiryById($inquiry_id);
    
    /**
     * Loads the inquiry for the non-react (probably first) application of a customer
     *
     * @return bool
     */
    public function findLastNonReactInquiries($application_id);
    
    /**
     * Get the inquiry failures for an ssn from the skip trace table
     *
     * @return bool
     */
    public function getFailuresBySsn($ssn);
    
    /**
     * Save an inquiry after the results are received
     *
     * @return bool
     */
    public function recordInquiry($inquiryObj);
    
    /**
     * Save the inquiry result (success/fail) in the skip trace table for an ssn
     *
     * @return bool
     */
    public function recordSkipTrace($ssn, $external_id, $source, $call_type, $reason, $status, $contactInfoAry);

}
