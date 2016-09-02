<?php

class AALM_DataX_Responses_Perf extends TSS_DataX_Response implements TSS_DataX_IPerformanceResponse, TSS_DataX_ILoanAmountResponse, TSS_DataX_IAutoFundResponse
{
	const LOAN_AMOUNT_INCREASE = 'INCREASE';

	/**
	 * (non-PHPdoc)
	 * @see code/ECash/DataX/TSS_DataX_IResponse#isValid()
	 */
	public function isValid()
	{
		return $this->getDecision() == 'Y';
	}

	/**
	 * (non-PHPdoc)
	 * @see code/ECash/DataX/TSS_DataX_IResponse#getDecision()
	 */
	public function getDecision()
	{
		return $this->findNode('//GlobalDecision/Result');
	}

	/**
	 * (non-PHPdoc)
	 * @see code/ECash/DataX/TSS_DataX_IResponse#getScore()
	 */
	public function getScore()
	{
		return 0;
	}

	/**
	 * Returns an array of the buckets and their decisions
	 * @see code/ECash/DataX/TSS_DataX_IResponse#getDecisionBuckets()
	 * @return array
	 */
	public function getDecisionBuckets()
	{
		return $this->getGlobalDecisionBuckets();
	}

	/**
	 * Gets the decision specific to a segment
	 *
	 * NOTE: The IDV segment is named 'ConsumerIDVerification', and this doesn't work for it
	 * @param string $segment_name
	 * @return null|string
	 */
	public function getSegmentDecision($segment_name)
	{
		switch ($segment_name)
		{
			case 'IDV':
				return $this->findNode('//IDVSegment/CustomDecision/Result');
		}
		return $this->findNode('//'.$segment_name.'Segment/Decision/Result');
	}

	/**
	 * (non-PHPdoc)
	 * @see code/ECash/DataX/TSS_DataX_IResponse#isIDVFailure()
	 */
	public function isIDVFailure()
	{
		$result = $this->findNode('//IDVSegment/CustomDecision/Result');
		return $result != 'Y';
	}

	/**
	 * Used to determine whether or not the loan amount increase applies
	 */
	public function getLoanAmountDecision()
	{
		$decision = $this->findNode('//GlobalDecision/LoanAmount');
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
		$decision = $this->findNode('//AutoFundDecision/Result');
		return $decision == 'Y';
	}

        public function getCreditOptics() {
                $nodes = $this->xpath->query("//IDAIDSPSegment/CreditOptics/*");
                $arr = array();
                foreach ($nodes as $node) {
                       $tag = $node->tagName;
                       if ($tag == 'Score' || $tag == 'Designation') {
                               $arr["CreditOptics.".$tag] = $node->textContent;
                       }
                }
                return $arr;
        }

        public function getBavsegmentCode() {
                $code = $this->findNode("//BAVSegment/Code");
                return $code;
        }

        public function getGlobalDecision() {
                $nodes = $this->xpath->query('//GlobalDecision/*');
                $arr = array();
                foreach ($nodes as $node) {
                       $arr["GlobalDecision.".$node->tagName] = $node->textContent;
                }
                return $arr;
        }

        public function getAutoFundDecisionNodes() {
                $nodes = $this->xpath->query('//AutoFundDecision/*');
                $arr = array();
                foreach ($nodes as $node) {
                       $arr["AutoFundDecision.".$node->tagName] = $node->textContent;
                }
                return $arr;
        }

        public function getComprehensiveVerificationIndex() {
                $verificationIndex = $this->findNode('//ComprehensiveVerificationIndex');
                return $verificationIndex;
        }

        public function update_bureau_xml_fields($db, $bureau_inquiry_id) {
                $creditOptics = $this->getCreditOptics();
                $globalDecision = $this->getGlobalDecision();
                $autoFundDecision = $this->getAutoFundDecisionNodes();
                $record_data = array();
                $record_data['bureau_inquiry_id'] = $bureau_inquiry_id;
                $record_data['AutoFund'] = 'N';
                if($this->getLoanAmountDecision()) {
                        $record_data['AutoFund'] = 'Y';
                } elseif($this->getAutoFundDecision()) {
                        $record_data['AutoFund'] = 'Auto';
                }
                $record_data['creditOptics.Designation'] = $creditOptics['CreditOptics.Designation'];
                $record_data['creditOptics.Score'] =       $creditOptics['CreditOptics.Score'];
                $record_data['Bavsegment.Code'] = $this->getBavsegmentCode();
                $record_data['GlobalDecision.Result'] =     $globalDecision['GlobalDecision.Result'];
                $record_data['GlobalDecision.IDVBucket'] =  $globalDecision['GlobalDecision.IDVBucket'];
                $record_data['GlobalDecision.BAVBucket'] =  $globalDecision['GlobalDecision.BAVBucket'];
                $record_data['GlobalDecision.CRABucket'] =  $globalDecision['GlobalDecision.CRABucket'];
                $record_data['GlobalDecision.IDABucket'] =  $globalDecision['GlobalDecision.IDABucket'];
                $record_data['GlobalDecision.PerfBucket'] =  $globalDecision['GlobalDecision.PerfBucket'];
                $record_data['GlobalDecision.LoanAmount'] = $globalDecision['GlobalDecision.LoanAmount'];
                $record_data['AutoFundDecision.Bucket'] = $autoFundDecision['AutoFundDecision.Bucket'];
                $record_data['AutoFundDecision.Result'] = $autoFundDecision['AutoFundDecision.Result'];
                $record_data['ComprehensiveVerificationIndex'] = $this->getComprehensiveVerificationIndex();

                $row_fields = array_keys($record_data);
                foreach ($row_fields as $id=>$field) {
                        if ($field <> 'bureau_inquiry_id') {
                                $sql = "INSERT INTO bureau_xml_fields (`bureau_inquiry_id`, `fieldname`, `value`) VALUES (".$bureau_inquiry_id.", '".$field."', '".$row[$field]."')";
                                $statement =  $db->prepare($sql);
                                $statement->execute();
                        }
                }
        }

}

?>
