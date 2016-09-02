<?php

/**
 * The base script class
 *
 * @package ECashCra
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */
class ECashCra_Scripts_Base
{
	/**
	 * @var ECashCra_Api
	 */
	private $api;
	
	/**
	 * @var string YYYY-MM-DD
	 */
	protected $date;
	
	/**
	 * creates a new script object
	 *
	 * @param ECashCra_Api $api
	 */
	public function __construct(ECashCra_Api $api)
	{
		$this->api = $api;
	}
	
	/**
	 * Returns the api connection
	 *
	 * @return ECashCra_Api
	 */
	public function getApi()
	{
		return $this->api;
	}
	
	/**
	 * Sets the export date of the application
	 *
	 * @param string $date YYYY-MM-DD
	 * @return null
	 */
	public function setExportDate($date)
	{
		$this->date = $date;
	}

	/**
	 * Outputs a message to stdout
	 *
	 * <b>Revision History</b>
 	 * <ul>
 	 *     <li><b>2008-10-29 - alexanderl</b><br>
 	 *         added application id as an argument [#18902]
 	 *     </li>
 	 * </ul>
	 *
	 * @param bool $success
	 * @param int $external_id
	 * @param ECashCRA_IPacketResponse $response
	 * @param int $application_id
	 * @return null
	 */
	protected function logMessage($success, $external_id, ECashCRA_IPacketResponse $response, $application_id = NULL)
	{
		if (!$success)
		{
			echo "FAIL - externalid: {$external_id}\tapplicationid: {$application_id}\n\t[{$response->getErrorCode()}] {$response->getErrorMsg()}\n"; //#18902
		}
	}
    
	/**
	 * Creates the payment history token for an application right from the transaction data
	 *
	 * @param application id
	 * @return string of clarity payment history profile
	 */
	protected function buildPaymentHistory($app_id,$driver)
	{
        // initialize
        $rtn_str = str_pad('',24,"-");
        $delinquency_days_map = array("0","1","2","3","4","5","6","7","8","9","A","B","C","D","E","F","G","H","I","J","K","L","N","M","P","Q","R","S","T","U");

print_r("Bulding tansaction history for ap:".$app_id." before ".$this->date."\n");
        // determine historical statuses from the transactions for this application
		$trans = $driver->getClarityApTrans($this->date,$app_id);
print_r($trans);
        $delinquency_date = false;
        foreach($trans as $tran) {
            if ($tran['transaction_status'] == 'complete') 
            {   // good payment, set any previous deliquency to false
                $rtn_str = '0'.$rtn_str;
                $delinquency_date = false;
            }elseif (($tran['transaction_status'] == 'failed') &&
                    ($tran['date_modified'] > $this->date))
            {   // failed payment, but not returned yet
                $rtn_str = '0'.$rtn_str;
            }elseif ($tran['transaction_status'] == 'failed')
            {   // fully failed and returned payment
                // is this the first deliquent notice, use old deliquent date if not
                $delinquency_date = $delinquency_date ? $delinquency_date : $tran['date_effective'];
                // status character is based off of the days of deliquency
print_r('Delinquency Date: '.$delinquency_date."\n");
print_r('Due Date: '.$tran['date_modified']."\n");
                $delinquency_days = intval((strtotime($tran['date_modified']) - strtotime($delinquency_date)) / (60*60*24));
print_r($delinquency_days."\n");
                if      ($delinquency_days <=  0)                                 $delinquency_char = '1';
                if     (($delinquency_days > 0) && ($delinquency_days < 30))    $delinquency_char = $delinquency_days_map[$delinquency_days];
                elseif (($delinquency_days >= 30)  && ($delinquency_days < 60))  $delinquency_char = 'V';
                elseif (($delinquency_days >= 60)  && ($delinquency_days < 90))  $delinquency_char = 'W';
                elseif (($delinquency_days >= 90)  && ($delinquency_days < 120)) $delinquency_char = 'X';
                elseif (($delinquency_days >= 120) && ($delinquency_days < 150)) $delinquency_char = 'Y';
                elseif  ($delinquency_days >= 150)                               $delinquency_char = 'Z';
                $rtn_str = $delinquency_char.$rtn_str;
            }
print_r($rtn_str );
print_r("\n");
        }
        
        // find final status settings
	$status_rtn = $driver->getClarityApStatus($this->date,$app_id);
        $status = $status_rtn[0]['status'];
        $status_parent = $status_rtn[0]['status_parent'];
        $last_char = '';
        switch($status_parent){
            case "Customer":
                switch($status){
                    case "Inactive (Paid)":
                    case "Inactive (Settled)":
                    case "Inactive (Paid)":
                        $last_char = '@';
                        break;
                }
                break;
            case "Collections":
            case "Contact":
                $last_char = '#';
                break;
            case "Deceased":
                switch($status){
                    case "Deceased Verified":
                        $last_char = '+';
                        break;
                    default:
                        $last_char = '#';
                        break;
                }
                break;
            case "Bankruptcy":
                switch($status){
                    case "Bankruptcy Verified":
                        $last_char = '+';
                        break;
                    default:
                        $last_char = '#';
                        break;
                }
                break;
            case "External Collections":
                switch($status){
                    case "Second Tier (Sent)":
                        $last_char = '+';
                        break;
                    default:
                        $last_char = '#';
                        break;
                }
                break;
        }
        // tack on final status character if set
        $rtn_str = $last_char.$rtn_str;
        
        return $rtn_str;
	}
	
	/**
	 * Creates a new response object
	 *
	 * @return ECashCra_PacketResponse_UpdateResponse
	 */
	protected function createResponse()
	{
		return new ECashCra_PacketResponse_UpdateResponse();
	}
    
	/**
	 * Processes a series of applications
	 *
	 * @param ECashCra_IDriver $driver
	 * @return null
	 */
	public function processApplicationBase($apps)
	{
		$successful = $failed = 0;
		foreach ($apps as $app_data)
		{
			$response = $this->createResponse();

            $application = new ECashCra_Data_Application($app_data);
			$packet = new ECashCra_Packet_Active($application);
			$this->getApi()->sendPacket($packet, $response);

			$response->isSuccess() ? ++$successful: ++$failed;

			$this->logMessage($response->isSuccess(), $application->getLoanID(), $response, print_r($packet,true));
		}
		echo ($successful + $failed) . " attempted, " . $successful . " good, " . $failed . " bad.\n";
	}
}

?>
