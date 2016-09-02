<?php
/**
 * DocumentService API interface defines the methods required to support the service's WSDL (doc.wsdl)
 *
 * @copyright Copyright &copy; 2014 aRKaic Equipment
 * @package ApplicationService
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */
interface ECash_Service_DocumentService_IAPI
{
    /**
     * Test the service connection
     *
     * @return bool
     */
    public function testConnection();

    /**
     * Finds all of the documents related by application id 
     *
     * @returns true is successful
     */
    public function findAllDocumentsByApplicationId($application_id);

    /**
     * Finds all of the documents related by archive id
     *
     * @returns true is successful
     */
    public function findDocumentByArchiveId($application_id);

    /**
     * Finds all of the documents related by document id
     *
     * @returns true is successful
     */
    public function findDocumentById($application_id);

    /**
     * Save a document
     *
     * @returns true is successful
     */
    public function saveDocuments($documentObj);


}
