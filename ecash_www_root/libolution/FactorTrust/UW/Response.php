<?php

/**
 * Handles a response from a FactorTrust call
 *
 * @author Stephan soileau <stephan.soileau@sellingsource.com>
 */
abstract class FactorTrust_UW_Response implements FactorTrust_UW_IResponse
{
	/**
	 * Like an error
	 *
	 * @var string
	 */
	protected $error;

	/**
	 * Like an error code?
	 *
	 * @var string
	 */
	protected $error_code;

	/**
	 * Like a dom document
	 *
	 * @var DOMDocument
	 */
	protected $dom_doc;

	/**
	 * Like a xpath
	 *
	 * @var DOMXpath
	 */
	protected $xpath;
	protected $increase_time = 900; //15 minutes
    protected $decision_key_map = array('Response' => array('Code'));
    protected $report_key_map = array(
    'ApplicationInfo' => array('ReportLink'),
    'Response' => array('Code'),
        'LoanPerformance' => array( 
            'NumberofLoansCurrent',   'NumberofLoansInCollections', 'TotalNumberofLoansOriginated', 'NumberofApplications30', 'NumberofOpenLoans',   'NumberofLoansPastDue',
            'NumberofMerchants30',    'NumberofNewLoansOriginated', 'TotalNumberofLoansDelinquent', 'NumberofLoansPaidOff',   'BankAccountMismatch', 'SSNMismatch',
            'ResidenceStateMismatch', 'PayrollFrequencyMismatch',   'CustomerDDACount6Months',      'CustomerDDACount',       'LastReturnCode',      'LastReturnDate',
            'NumberOfNewLoansOriginatedLastYear', 'TotalNumberofLoansDelinquentLastYear',       'LastDelinquentDateLastYear', 'BankAccountMismatch', 'HighRiskABA'),
        'BLJ' => array('BankruptcyFileDate', 'JudgementFileDate', 'LienFileDate'),
        'DDA' => array('DDAResponseCode', 'DDAResponseDescript'),
        'DDAPlus' => array('DDAPlusResponse'),
        'ACHLogic' => array('BankItems', 'PaymentToDate'),
        'IDV' => array(
            'AddressisCurrent',            'AddressisDeliverable',    'AddressisHighRisk', 'AddressisMailDrop', 'DriverLicenseMatchFullName', 'FirstNamePartialAddressMatch',
            'FullNameAddressMatch',        'FullNameDOBMatch',        'LastNameDOBMatch',  'FullNameYOBMatch',  'LastNameAddressMatch',       'FullNamePartialAddressMatch',
            'PhoneMatchesAddress',         'PhoneMatchesName',        'NameinInfractions', 'PropertyOwner',     'SSNDeathRecord',             'LastNamePartialAddressMatch',
            'SSNFirstNameAddressMatch',    'SSNFirstNameExactMatch',  'SSNFirstNameMatch', 'SSNFullNameMatch',  'SSNFullNameAddressMatch',    'SSNFullNameDateofBirthMatch',
            'SSNLastNameDateofBirthMatch', 'SSNLastNameAddressMatch', 'SSNIssueDateMatch', 'SSNLastNameMatch',  'SSNLastNameExactMatch', 'SSNYearofBirthMatch', 'SSNVerified'),
        'StabilityLogic' => array('BankAccounts', 'CellPhones', 'Emails', 'HomePhone', 'IPAddresses', 'ConsumersIDs'),
        'EmploymentLogic_v12' => array(
            'EmployerDomainMatches', 'MonthlyIncomes',     'PayDateMatchCount', 'PayDateMatchPercentage', 'PayDateMatchProjected', 'PayDateMatchProjected',
            'PayFrequencies',        'EmployerMatchRatio', 'PayDateMatchRatio', 'PayFrquencyMatchRatio',  'Score',                 'ScorePercentage'));
    protected $keys_done = array();

	/**
	 * Parse an XML response from FactorTrust and handle
	 * whatever information is there
	 *
	 * @param string $xml
	 * @return bool
	 */
	public function parseXML($xml)
	{
		try
		{
			$this->dom_doc = new DOMDocument();
			$this->dom_doc->loadXML($xml);
			$this->xpath = new DOMXPath($this->dom_doc);
		}
		catch (Exception $e)
		{
			throw new FactorTrust_UW_TransportException($e->getMessage(), 0);
		}

		return !$this->searchForError();
	}

	/**
	 * Do we have an error?
	 *
	 * @return boolean
	 */
	public function hasError()
	{
		return $this->error || $this->error_code;
	}

	/**
	 * Return some sort of In the form of MSG
	 *
	 * @return string
	 */
	public function getErrorMsg()
	{
		return $this->error;
	}

	/**
	 * MSG Free version of return code.. Totally healthy.
	 *
	 * @return string
	 */
	public function getErrorCode()
	{
		return $this->error_code;
	}

	/**
	 * Returns the FactorTrust track hash
	 * @see code/FactorTrust/UW/FactorTrust_UW_IResponse#getTrackHash()
	 * @return string
	 */
	public function getTrackHash()
	{
		// very specific...
        	$link = $this->findNode('/ApplicationResponse/ApplicationInfo/ReportLink');
        	$hash = str_replace('-','',substr($link,strlen($link)-36));
		return substr($hash,0,27);
	}

	/**
	 * Extracts the buckets under //GlobalDecision/
	 * @return array
	 */
	protected function getGlobalDecisionBuckets() {
		$buckets = array();
	$this->keys_done = array();
	$time = ini_get('max_execution_time');
	ini_set('max_execution_time',$this->increase_time);
        $nodes = $this->xpath->query('/*/*');
        $this->buildBuckets($this->decision_key_map,$this->xpath,'',$buckets,false);
	ini_set('max_execution_time',$time);
		return $buckets;
	}

	/**
	 * Extracts the buckets under //GlobalDecision/
	 * @return array
	 */
	protected function getGlobalReportingBuckets() {
	$buckets = array();
	$this->keys_done = array();
        $time = ini_get('max_execution_time');
	ini_set('max_execution_time',$this->increase_time);
        $nodes = $this->xpath->query('/*/*');
        $this->buildBuckets($this->report_key_map,$this->xpath,'',$buckets,false);
//error_log('Getting reports in: '.__FILE__.' || '.__METHOD__);
//error_log(print_r($buckets,true));
	ini_set('max_execution_time',$time);
		return $buckets;
	}

	/**
	 * Runs a couple xpath queries to try and find
	 * errors in the packet
	 *
	 * @return boolean
	 */
	protected function searchForError()
	{
		$code = $this->findNode('/ApplicationResponse/ApplicationInfo/LendProtectScore');
        if ($code == '333') $msg = 'FactorTrust Service Errror, Try again in 10 to 15 minutes';
		else $code = NULL;

		if ($code !== NULL || (isset($msg) && $msg !== NULL))
		{
			$this->error = $msg;
			$this->error_code = $code;
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Finds a single node from an xpath query, and returns its content
	 *
	 * If multiple nodes are returned from the query,
	 * the value of the first is returned. If no nodes are
	 * found, it returns NULL.
	 *
	 * @param string $query
	 * @return string|null
	 */
	protected function findNode($query)
	{
		$nodes = $this->xpath->query($query);

		if ($nodes->length > 0)
		{
			return $nodes->item(0)->textContent;
		}
		return NULL;
	}
	/**
	 * Recursively stacks items to be gathered from the returned xml into the decision buckets.
	 *  Which items are included are determined by the key_map. 
	 *  key_map is a simple two dimensional array which is the parent child pair in the returned xml string.
	 *  any parent child list will return the value of the child and all grand children nodes.
	 *
	 * @param string $query
	 * @return string|null
	 */
	protected function buildBuckets($key_map, $xdoc, $key , &$buckets ,$capture)
	{
//error_log('Build bucket: '.$key);
//error_log('Using: '.print_r($key_map,true));
//error_log('Restricted: '.print_r($this->keys_done,true));
        $all_set = false;
	if (!in_array($new_key,$this->keys_done)) {

	    $nodes = $xdoc->query('/*/'.$key.'*');
//error_log('found nodes count: '.$nodes->length);
            foreach ($nodes as $node) {
	        $new_set = false;
	        $new_capture = false;
                if (is_array($key_map[$node->parentNode->tagName]) && in_array($node->tagName,$key_map[$node->parentNode->tagName])) $new_capture = true;
                $new_key = $key . $node->tagName.'/';
//error_log('calling build for: '.$new_key);
                if (!in_array($new_key,$this->keys_done)) $new_set = $this->buildBuckets($key_map, $xdoc, $new_key ,$buckets ,$new_capture);
	        $all_set = ($new_set || $all_set);
//error_log('build called for: '.$new_key);
//error_log('cap: '.$capture);
//error_log('new: '.$new_capture);
//error_log('set: '.$new_set);
                if(($capture || $new_capture) && (!$new_set) && ($node->nodeValue) && (strlen($node->nodeValue) > 0)) {
                    $bucket_key = str_replace('/','::',substr($new_key,0,-1));
                    if (isset($buckets[$bucket_key])) $prev_val = $buckets[$bucket_key] . ' ';
                    else $prev_val = '';
                    $buckets[$bucket_key] = $prev_val . strtoupper($node->nodeValue);
                    $all_set = true;
//error_log('Bucket added');
                }
	    }
            if (!in_array($key,$this->keys_done)) $this->keys_done[] = $key;
//error_log('bucket built: '.$key);
	} else {
//error_log('bucket passed: '.$key);
	}
//error_log('Bucket: '.print_r($buckets,true));
//error_log('returning: '.$all_set);
	return $all_set;
	}
}
