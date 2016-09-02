<?php

/**
 * Handles a response from a Clarity call
 *
 * @author Stephan soileau <stephan.soileau@sellingsource.com>
 */
abstract class Clarity_UW_Response implements Clarity_UW_IResponse
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
    protected $decision_key_map = array('xml-response' => array('deny-codes'));
    protected $report_key_map = array('xml-response' => array(
                                        'clear-subprime-idfraud' => array(
                                            'clear-subprime-idfraud-max-numbers-of-ssns-with-bank-account'
                                            ,'clear-subprime-idfraud-score'
                                            ,'clear-subprime-idfraud-reason' => array('descriptions')
                                            ,'clear-subprime-idfraud-possible-manipulated-data' => array(
                                                'work-previously-listed-as-cell'
                                                ,'work-previously-listed-as-home')
                                            ,'clear-subprime-idfraud-validation' => array('descriptions')
                                            ,'clear-subprime-idfraud-ssn-frequency' => array(
                                                'inquiries-with-social-24-hours-ago'
                                                ,'inquiries-with-social-7-days-ago'
                                                ,'inquiries-with-social-30-days-ago'
                                                ,'inquiries-with-social-90-days-ago'
                                                ,'inquiries-with-social-1-year-ago')
                                            ,'clear-subprime-idfraud-stability' => array(
                                                'stability-bank' => array(
                                                    'one-minute-ago'
                                                    ,'ten-minutes-ago'
                                                    ,'one-hour-ago'
                                                    ,'twentyfour-hours-ago'
                                                    ,'seven-days-ago'
                                                    ,'fifteen-days-ago'
                                                    ,'thirty-days-ago'
                                                    ,'ninety-days-ago')
                                                ,'stability-home-phone' => array(
                                                    'one-minute-ago'
                                                    ,'ten-minutes-ago'
                                                    ,'one-hour-ago'
                                                    ,'twentyfour-hours-ago'
                                                    ,'seven-days-ago'
                                                    ,'fifteen-days-ago'
                                                    ,'thirty-days-ago'
                                                    ,'ninety-days-ago')
                                                ,'stability-address' => array(
                                                    'one-minute-ago'
                                                    ,'ten-minutes-ago'
                                                    ,'one-hour-ago'
                                                    ,'twentyfour-hours-ago'
                                                    ,'seven-days-ago'
                                                    ,'fifteen-days-ago'
                                                    ,'thirty-days-ago'
                                                    ,'ninety-days-ago'))
                                            ,'clear-subprime-idfraud-identity-profile' => array(
                                                'ssn-crosstab' => array(
                                                    'ssns'
                                                    ,'bank-accounts'
                                                    ,'drivers-license-numbers'
                                                    ,'emails'
                                                    ,'home-phones'
                                                    ,'cell-phones'
                                                    ,'addresses')
                                                ,'email-crosstab' => array(
                                                    'ssns'
                                                    ,'bank-accounts'
                                                    ,'drivers-license-numbers'
                                                    ,'emails'
                                                    ,'home-phones'
                                                    ,'cell-phones'
                                                    ,'addresses')
                                                ,'home-phone-crosstab' => array(
                                                    'ssns'
                                                    ,'bank-accounts'
                                                    ,'drivers-license-numbers'
                                                    ,'emails'
                                                    ,'home-phones'
                                                    ,'cell-phones'
                                                    ,'addresses'))
                                            ,'clear-subprime-idfraud-associated-socials' => array(
                                                'clear-subprime-idfraud-associated-social' => array('possible-fraud')))
                                        
                                        ,'clear-bank' => array(
                                            'supplier-bank' => array (
                                                'clear-bank-reason-code-description'
                                                ,'clear-bank-score'
                                                ,'number-of-accounts-closed'
                                                ,'number-of-accounts-currently-in-use'
                                                ,'number-of-drivers-license-numbers'
                                                ,'number-of-historical-accounts'
                                                ,'primary-account-severity-code'
                                                ,'early-warning-histories' => array(
                                                    'early-warning-history' => array(
                                                        'account-age-code'
                                                        ,'account-behavior-code'
                                                        ,'account-type'
                                                        ,'bank-default-rate'
                                                        ,'bank-name'
                                                        ,'bank-routing-number'
                                                        ,'days-since-first-clarity-activity'
                                                        ,'days-since-last-clarity-activity'
                                                        ,'high-risk-bank'
                                                        ,'inquiry-received-at-order'
                                                        ,'main-office'
                                                        ,'miskey'
                                                        ,'number-of-miskeyed-accounts'
                                                        ,'number-of-times-seen-by-clarity'
                                                        ,'primacy'
                                                        ,'masked-bank-account-number'
                                                        ,'masked-real-bank-account-number'
                                                        ,'social-security-number'))))
                                        
                                        ,'clear-bank-profile' => array(
                                            'stability-bank-profile' => array(
                                                'clear-bank-profile-reason-code-description'
                                                ,'openings-bank-profile' => array(
                                                    'thirty-days-ago'
                                                    ,'ninety-days-ago'
                                                    ,'one-hundred-eighty-days-ago'
                                                    ,'one-year-ago'
                                                    ,'two-years-ago'
                                                    ,'three-years-ago')
                                                ,'closings-for-cause-bank-profile' => array(
                                                    'thirty-days-ago'
                                                    ,'ninety-days-ago'
                                                    ,'one-hundred-eighty-days-ago'
                                                    ,'one-year-ago'
                                                    ,'two-years-ago'
                                                    ,'three-years-ago')
                                                ,'overdrawn-bank-profile' => array(
                                                    'thirty-days-ago'
                                                    ,'ninety-days-ago'
                                                    ,'one-hundred-eighty-days-ago'
                                                    ,'one-year-ago'
                                                    ,'two-years-ago'
                                                    ,'three-years-ago')
                                                ,'primary-profile-ach-returns-summary' => array(
                                                    'twentyfour-hours-ago'
                                                    ,'seven-days-ago'
                                                    ,'thirty-days-ago'
                                                    ,'ninety-days-ago'
                                                    ,'one-hundred-eighty-days-ago')
                                                ,'total-profile-ach-returns-summary' => array(
                                                    'twentyfour-hours-ago'
                                                    ,'seven-days-ago'
                                                    ,'thirty-days-ago'
                                                    ,'ninety-days-ago'
                                                    ,'one-hundred-eighty-days-ago')
                                                ,'loan-history-bank-profiles' => array(
                                                    'loan-history-bank-profile' => array(
                                                        'account-number'
                                                        ,'account-number'
                                                        ,'approved-applications'
                                                        ,'denied-applications'
                                                        ,'max-number-of-ssns'
                                                        ,'primacy'
                                                        ,'routing-number'
                                                        ,'twentyfour-hours-ago'
                                                        ,'seven-days-ago'
                                                        ,'thirty-days-ago'
                                                        ,'ninety-days-ago'
                                                        ,'one-hundred-eighty-days-ago'))))

                                        ,'clear-payday-tradeline' => array(
                                            'supplier-payday-tradeline' => array(
                                                'summary-payday-tradeline' => array(
                                                    'amount-loans-charged-off'
                                                    ,'amount-loans-in-collections'
                                                    ,'days-since-last-collection-inquiry'
                                                    ,'in-bank-account-watch'
                                                    ,'in-collections-watch'
                                                    ,'last-charge-off'
                                                    ,'last-collection'
                                                    ,'loans-charged-off'
                                                    ,'loans-in-collections'
                                                    ,'non-conformant-loans'
                                                    ,'spml-average-rollovers'
                                                    ,'summary-matrix-payday-tradelines' => array(
                                                        'summary-matrix-payday-tradeline' => array(
                                                            'number-closed-lines'
                                                            ,'number-current-lines'
                                                            ,'number-open-lines'
                                                            ,'number-past-due'
                                                            ,'total-open-balance'
                                                            ,'total-past-due-amount'
                                                            ,'tradeline-type'))))
                                            ,'transaction-patterns-payday-tradeline' => array(
                                                'action'
                                                ,'deny-codes'
                                                ,'deny-descriptions'
                                                ,'exception-descriptions'
                                                ,'primary-account-fundings-payday-tradeline' => array(
                                                    'twentyfour-hours-ago'
                                                    ,'seven-days-ago'
                                                    ,'thirty-days-ago'
                                                    ,'ninety-days-ago'
                                                    ,'one-hundred-eighty-days-ago')
                                                ,'primary-account-payments-payday-tradeline' => array(
                                                    'twentyfour-hours-ago'
                                                    ,'seven-days-ago'
                                                    ,'thirty-days-ago'
                                                    ,'ninety-days-ago'
                                                    ,'one-hundred-eighty-days-ago')
                                                ,'primary-account-payoffs-payday-tradeline' => array(
                                                    'twentyfour-hours-ago'
                                                    ,'seven-days-ago'
                                                    ,'thirty-days-ago'
                                                    ,'ninety-days-ago'
                                                    ,'one-hundred-eighty-days-ago')
                                                ,'primary-account-failed-payments-payday-tradeline' => array(
                                                    'twentyfour-hours-ago'
                                                    ,'seven-days-ago'
                                                    ,'thirty-days-ago'
                                                    ,'ninety-days-ago'
                                                    ,'one-hundred-eighty-days-ago')
                                                ,'secondary-accounts-fundings-payday-tradeline' => array(
                                                    'twentyfour-hours-ago'
                                                    ,'seven-days-ago'
                                                    ,'thirty-days-ago'
                                                    ,'ninety-days-ago'
                                                    ,'one-hundred-eighty-days-ago')
                                                ,'secondary-accounts-payments-payday-tradeline' => array(
                                                    'twentyfour-hours-ago'
                                                    ,'seven-days-ago'
                                                    ,'thirty-days-ago'
                                                    ,'ninety-days-ago'
                                                    ,'one-hundred-eighty-days-ago')
                                                ,'secondary-accounts-payoffs-payday-tradeline' => array(
                                                    'twentyfour-hours-ago'
                                                    ,'seven-days-ago'
                                                    ,'thirty-days-ago'
                                                    ,'ninety-days-ago'
                                                    ,'one-hundred-eighty-days-ago')
                                                ,'secondary-accounts-failed-payments-payday-tradeline' => array(
                                                    'twentyfour-hours-ago'
                                                    ,'seven-days-ago'
                                                    ,'thirty-days-ago'
                                                    ,'ninety-days-ago'
                                                    ,'one-hundred-eighty-days-ago')
                                                ,'total-fundings-payday-tradeline' => array(
                                                    'twentyfour-hours-ago'
                                                    ,'seven-days-ago'
                                                    ,'thirty-days-ago'
                                                    ,'ninety-days-ago'
                                                    ,'one-hundred-eighty-days-ago')
                                                ,'total-payments-payday-tradeline' => array(
                                                    'twentyfour-hours-ago'
                                                    ,'seven-days-ago'
                                                    ,'thirty-days-ago'
                                                    ,'ninety-days-ago'
                                                    ,'one-hundred-eighty-days-ago')
                                                ,'total-payoffs-payday-tradeline' => array(
                                                    'twentyfour-hours-ago'
                                                    ,'seven-days-ago'
                                                    ,'thirty-days-ago'
                                                    ,'ninety-days-ago'
                                                    ,'one-hundred-eighty-days-ago')
                                                ,'total-failed-payments-payday-tradeline' => array(
                                                    'twentyfour-hours-ago'
                                                    ,'seven-days-ago'
                                                    ,'thirty-days-ago'
                                                    ,'ninety-days-ago'
                                                    ,'one-hundred-eighty-days-ago'))
                                            ,'total-inquiries-payday-tradeline' => array(
                                                'ten-minutes-ago'
                                                ,'twenty-minutes-ago'
                                                ,'thirty-minutes-ago'
                                                ,'one-hour-ago'
                                                ,'twentyfour-hours-ago'
                                                ,'seven-days-ago'
                                                ,'thirty-days-ago'
                                                ,'one-year-ago'
                                                ,'two-years-ago')
                                            ,'total-inquiry-clusters-payday-tradeline' => array(
                                                'twentyfour-hours-ago'
                                                ,'seven-days-ago'
                                                ,'thirty-days-ago'
                                                ,'one-year-ago'
                                                ,'two-years-ago'))));
    protected $keys_done = array();

	/**
	 * Parse an XML response from Clarity and handle
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
			throw new Clarity_UW_TransportException($e->getMessage(), 0);
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
	 * Returns the Clarity track hash
	 * @see code/Clarity/UW/Clarity_UW_IResponse#getTrackHash()
	 * @return string
	 */
	public function getTrackHash()
	{
//error_log(__METHOD__.' || '.__FILE__);
//$nodes = $this->xpath->query('/tracking-number');

//foreach($nodes as $node)
//{
//error_log(print_r($node->nodeValue));;
//}
		// very specific...
        	$link = $this->findNode('/xml-response/tracking-number');
		return $link;
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
        $this->buildBuckets($this->decision_key_map,$this->xpath,'',$buckets,false);
//error_log('Getting desicions in: '.__FILE__.' || '.__METHOD__);
//error_log(print_r($buckets,true));
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
//print_r($this->dom_doc->saveXML());
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
		$code = $this->findNode('/xml-response/action');
        if ($code == 'Exception') $msg = 'Clarity Service Errror, Try again in 10 to 15 minutes :: '.$this->findNode('/xml-response/exception-descriptions');
		else $code = NULL;

		if ($code !== NULL)
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
//print_r('Build bucket: '.$key."\n");
//print_r('Using: '.print_r($key_map,true));
//print_r('Restricted: '.print_r($this->keys_done,true));
        $all_set = false;
	if (!in_array($key,$this->keys_done)) {

	    $nodes = $xdoc->query('/'.$key.'*');

	// initialize local search array
	unset($key_stream);
	$key_stream = array();
	if ($nodes->length > 0) {
		$key_stream = $key_map;
//print_r("Key: ".$key."\n");
//print_r("Explode: ".print_r(explode('/',$key),true)."\n");
		foreach(explode('/',$key) as $key_elm){
			if (strlen($key_elm)>1){
				if (isset($key_stream[$key_elm])) {
//print_r("Found key :".$key_elm."\n");
					$key_stream = $key_stream[$key_elm];
				} else {
//print_r("Failed key :".$key_elm."\n");
					unset($key_stream);
					$key_stream = array();
				}
			}
		}
	}
        foreach ($nodes as $node) {
            $new_set = false;
            $new_capture = false;

//print_r('Looking for: '.$node->tagName." in key stream.\n");
//print_r($key_stream);
//print_r("\n");
	    if (in_array($node->tagName,$key_stream)) $new_capture = true;
            //if (is_array($key_map[$node->parentNode->tagName]) && in_array($node->tagName,$key_map[$node->parentNode->tagName])) $new_capture = true;
            $new_key = $key . $node->tagName.'/';
//print_r('calling build for: '.$new_key."\n");
            if (!in_array($new_key,$this->keys_done)) $new_set = $this->buildBuckets($key_map, $xdoc, $new_key ,$buckets ,$new_capture);
//else print_r('recursion not called for: '.$new_key."\n");
            $all_set = ($new_set || $all_set);
//print_r('build called for: '.$new_key."\n");
//print_r('cap: '.print_r($capture,true)."\n");
//print_r('new: '.print_r($new_capture,true)."\n");
//print_r('set: '.print_r($new_set,true)."\n");
            if(($capture || $new_capture) && (!$new_set)) {
                $bucket_key = str_replace('/','::',substr($new_key,0,-1));
                if ($node->nodeValue) {
			if (isset($buckets[$bucket_key])) $prev_val = $buckets[$bucket_key] . ' ';
			else $prev_val = '';
			$buckets[$bucket_key] = $prev_val . strtoupper($node->nodeValue);
		};
                $all_set = true;
//print_r('Bucket added'."\n");
            }
	    }
            if (!in_array($key,$this->keys_done)) $this->keys_done[] = $key;
//print_r('bucket built: '.$key."\n");
	} else {
//print_r('bucket passed: '.$key."\n");
	}
//print_r('Bucket: '.print_r($buckets,true));
//print_r('returning: '.$all_set);
	return $all_set;
	}
}
