<?php

/**
 * The clarity payment script class
 *
 * @package ECashCra
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */
class ECashCra_Scripts_Payments extends ECashCra_Scripts_Base
{
	public function processApplications(ECashCra_IDriver $driver)
	{
		$apps = $driver->getClarityPayments($this->date);
        
        // add the rating and rating history via code per individual application
	/*
        foreach($apps as &$ap){
            $ap_status = $this->buildPaymentHistory($ap['LoanID'],$driver);
            
            $ap['Rating'] = substr($ap_status,0,1);
            $ap['History'] = substr($ap_status,1);
        }
	*/
		echo "Exporting " . count($apps) . " payments.\n";

		$this->processApplicationBase($apps);
	}
}

?>
