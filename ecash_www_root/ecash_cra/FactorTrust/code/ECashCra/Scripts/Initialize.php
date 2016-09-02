<?php

/**
 * The factor trust new loan script class
 *
 * @package ECashCra
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */
class ECashCra_Scripts_Initialize extends ECashCra_Scripts_Base
{
    private function time_elapsed($secs){
        if ($secs == 0) return '0s';
        $bit = array(
            'y' => $secs / 31556926 % 12,
            'w' => $secs / 604800 % 52,
            'd' => $secs / 86400 % 7,
            'h' => $secs / 3600 % 24,
            'm' => $secs / 60 % 60,
            's' => $secs % 60
            );
        $ret = array();  
        foreach($bit as $k => $v)
            if($v > 0)$ret[] = $v . $k;
           
        return join(' ', $ret);
    }
	/**
	 * Processes fund updates for reacts
	 *
	 * @param ECashCra_IDriver $driver
	 * @return null
	 */
	public function processApplications(ECashCra_IDriver $driver)
	{
        $t0 = time();
        $div = 10;
print_r(date("Y-m-d H:i:s")."\n");
        $ap_count = count($driver->getFactorTrustActiveLoans($this->date,'_active_subset'));
        
        $a_idx = 0;
        for( $i = 0; $i < $div; $i ++) {
print_r('Prepping database for round '. $i);
            $driver->prepFactorTrustInitialRound('active_subset',$i,$div,$this->date);
$tx = time()-$t0;
echo " - took " . $this->time_elapsed($tx) . " so far.\n";

print_r('Gathering apps - ');
            $apps = $driver->getFactorTrustActiveLoans($this->date,'_active_subset_'.$i);
print_r('returned :' . count($apps));
$tx = time()-$t0;
echo " took " . $this->time_elapsed($tx) . " so far.\n";
print_r('Gathering payments - ');
            $paymnts = $driver->getFactorTrustActiveLoanPaymentsBalance($this->date,NULL,'_active_subset_'.$i);
print_r('returned :' . count($paymnts));
$tx = time()-$t0;
echo " took " . $this->time_elapsed($tx) . " so far.\n";
print_r('Gathering timing - ');
            $timing = $driver->getFactorTrustActiveLoanPaymentsTiming($this->date,NULL,'_active_subset_'.$i);
print_r('returned :' . count($timing));
$tx = time()-$t0;
echo " took " . $this->time_elapsed($tx) . " so far.\n";
            
            
            $p_idx = 0;
            $t_idx = 0;
            
            foreach ($apps as $app) {
//print_r('Application - New - ');
//print_r($app);
                $app_list = array();
                $app_list [] = $app;
                while(($paymnts[$p_idx]['LoanID'] < $app['LoanID']) && ($p_idx < count($paymnts))) $p_idx++;
                if($paymnts[$p_idx]['LoanID'] == $app['LoanID']){
                    $t_ap = array();
                    $t_ap['SSN'] = $app['SSN'];
                    $t_ap['AppID'] = $app['AppID'];
                    $t_ap['BankABA'] = $app['BankABA'];
                    $t_ap['BankAcct'] = $app['BankAcct'];
                    $ro_number = '';
        
                    while($paymnts[$p_idx]['LoanID'] == $app['LoanID']) {
                        while (($timing[$t_idx]['LoanID'] < $app['LoanID']) && ($t_idx < count($timing))) $t_idx++;
                        // payment
                        $t_ap['Type'] = 'PM';
                        $t_ap['TranDate'] = $paymnts[$p_idx]['TranDate'];
                        $t_ap['LoanID'] = $ro_number>0 ? $app['LoanID'].'V'.$ro_number : $app['LoanID'];
                        $t_ap['LoanDate'] = '';
                        $t_ap['DueDate'] = '';
                        $t_ap['PaymentAmt'] = $paymnts[$p_idx]['PaymentAmt'];
                        $t_ap['Balance'] = $paymnts[$p_idx]['Balance'] > 0 ? $paymnts[$p_idx]['Balance'] : 0;
                        $t_ap['ReturnCode'] = '';
                        $t_ap['RollOverRef'] = $ro_number>0 ? ($ro_number>1 ? $app['LoanID'].'V'.($ro_number-1) : $app['LoanID']) : '' ;
                        $t_ap['RollOverNumber'] = $ro_number;
                
//print_r('payment: '.$ro_number.' ');
//print_r($t_ap);
                        $app_list [] = $t_ap;
                        if( $paymnts[$p_idx]['Balance'] > 0) {
                            $ro_number ++;
                            // rollover payment
                            $t_ap['PaymentAmt'] = $paymnts[$p_idx]['Balance'];
                            $t_ap['Balance'] = '0.00';
                            
//print_r('rollover payment: '.$ro_number.' ');
//print_r($t_ap);
                            $app_list [] = $t_ap;
        
                            // rollover
                            $t_ap['Type'] = 'RO';
                            $t_ap['LoanID'] = $app['LoanID'].'V'.$ro_number;
                            $t_ap['LoanDate'] = $paymnts[$p_idx]['TranDate'];
                            if($timing[$t_idx]['LoanID'] == $app['LoanID']) $timing_val = $timing[$t_idx]['DueDate'];
                            else $timing_val = date('Y-m-d',time($app['DueDate'])-time($app['LoanDate'])+time($paymnts[$p_idx]['TranDate']));
                            $t_ap['DueDate'] = $paymnts[$p_idx+1]['LoanID'] != $paymnts[$p_idx]['LoanID'] ? (isset($timing_val) ? $timing_val: $paymnts[$p_idx]['TranDate'] ) : $paymnts[$p_idx+1]['TranDate'];
                            $t_ap['PaymentAmt'] = '0.00';
                            $t_ap['Balance'] = $paymnts[$p_idx]['Balance'];
                            $t_ap['RollOverRef'] = $ro_number>1 ? $app['LoanID'].'V'.($ro_number-1) : $app['LoanID'];
                            $t_ap['RollOverNumber'] = $ro_number;
//print_r('rollover: '.$ro_number.' ');
//print_r($t_ap);
        
                            $app_list [] = $t_ap;
                        }
                        $p_idx ++;
                    }
                }
                $a_idx ++;
                
                $this->processApplicationBase($app_list);
                echo "Exported ap #$a_idx id=" . $app['LoanID'] . " with " . (count($app_list) - 1) . " payments and rollovers.  ";
                $t3 = time()-$t0;
                $t4 = $t3/$a_idx;
                echo " Export taking " . $this->time_elapsed($t3) . " total or " . $this->time_elapsed($t4) . " each ap.  ";
                $t2 = ($ap_count - $a_idx) * $t4;
                echo "  est: " . $this->time_elapsed($t2) . " remaining.\n";
                unset($app_list);
            }
        }
    }
}

?>
