<?php

class AALM_FactorTrust_Responses_Perf extends FactorTrust_UW_Response implements FactorTrust_UW_IPerformanceResponse, FactorTrust_UW_ILoanAmountResponse, FactorTrust_UW_IAutoFundResponse
{
    const LOAN_AMOUNT_INCREASE = 'INCREASE';

    public function isValid()
    {
        return $this->getDecision() == 'Y';
    }

    public function getDecision()
    {
        // we translate the FT result 'A' & 'D' to dataX 'Y' and 'N'
        if ($this->findNode('/ApplicationResponse/ApplicationInfo/TransactionStatus') == 'A') return 'Y';
        else return 'N';
    }

    public function getScore()
    {
        return $this->findNode('/ApplicationResponse/ApplicationInfo/LendProtectScore');
    }

    public function getDecisionBuckets()
    {
        return $this->getGlobalDecisionBuckets();
    }

    public function getReportingBuckets()
    {
        return $this->getGlobalReportingBuckets();
    }

    public function isIDVFailure()
    {
        $result = $this->findNode('/ApplicationResponse/ApplicationInfo/IDV/');
        return $result == 'N';
    }

    public function getLoanAmountDecision()
    {
        $decision = $this->findNode('/ApplicationResponse/ApplicationInfo/ApprovedAmount');
        if(strcasecmp($decision, self::LOAN_AMOUNT_INCREASE) == 0)
        {
            return TRUE;
        }
        else if(is_numeric($decision) && $decision > 0)
        {
            return $decision;
        }
        else
        {
            return FALSE;
        }

    }

    /**
     * Used to determine whether or not the loan should be auto funded
     */
    public function getAutoFundDecision()
    {
        return $this->getScore() > 0;
    }

    public function update_bureau_xml_fields($db, $application_id, $bureau_inquiry_id = 0, $bureau_inquiry_failed_id = 0) {
        $record_data = $this->getReportingBuckets();
        $record_data['AutoFund'] = 'N';
        if($this->getLoanAmountDecision()) {
            $record_data['AutoFund'] = 'Y';
        } elseif($this->getAutoFundDecision()) {
            $record_data['AutoFund'] = 'Auto';
        }
        
        if (!$application_id) {
            $ssn = $bureau_inquiry_id;
                $sql = "SELECT application_id, bureau_inquiry_failed_id FROM bureau_inquiry_failed WHERE ssn = ".$ssn
                    ." ORDER BY application_id DESC";
                $statement = $db->query($sql);
            $row = $statement->fetchAll();
                if ($row && (count($row) > 0)) {
                    $application_id = $row[0]['application_id'];
                    $bureau_inquiry_failed_id = $row[0]['bureau_inquiry_failed_id'];
                    $bureau_inquiry_id = 0;
                } 
            $sql = "SELECT application_id, bureau_inquiry_id FROM application ap"
                    ." JOIN bureau_inquiry USING(application_id)"
                    ." WHERE ap.ssn = ".$ssn
                    ." ORDER BY application_id DESC";
            $statement = $db->query($sql);
            $row = $statement->fetchAll();
            if ($row && (count($row) > 0)) {
                if (!$application_id || ($application_id < $row[0]['application_id'])) {
                    $application_id = $row[0]['application_id'];
                    $bureau_inquiry_failed_id = 0;
                    $bureau_inquiry_id = $row[0]['bureau_inquiry_id'];
                }
            }
        }
        if ($application_id && ($application_id > 0)) {
            $row_fields = array_keys($record_data);
            foreach ($row_fields as $field) {
                $sql = "INSERT INTO bureau_xml_fields (`application_id`, `bureau_inquiry_id`, ".
                    "`bureau_inquiry_failed_id`, `fieldname`, `value`) VALUES (".$application_id.", ".
                    $bureau_inquiry_id.", ".$bureau_inquiry_failed_id.", '".$field."', '".$record_data[$field]."')";
                $statement =  $db->prepare($sql);
                $statement->execute();
            }
        }
    }

}

?>
