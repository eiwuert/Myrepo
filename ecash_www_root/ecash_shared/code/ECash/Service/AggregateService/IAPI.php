<?php
/**
 * LoanActionHistoryService API interface defines the methods required to support the service's WSDL (app.wsdl)
 *
 * @copyright Copyright &copy; 2014 aRKaic Equipment
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */
interface ECash_Service_AggregateService_IAPI
{
    /**
     * Test the service connection
     *
     * @return bool
     */
    public function testConnection();

    /**
     * Saves a loan action history into the table
     *
     * @returns integer (loan action history id)
     */
    public function AggregateCall($aggregateObj);

}
