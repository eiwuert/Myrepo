<?php
include_once("FactorTrust/Autoload.php");
include_once("FactorTrust/FactorTrustStoreLookup.php");

/**
 * Handles all FactorTrust requests and responses
 */
class ECash_FactorTrust
{
    /**
     * ECash FactorTrust Request Object
     *
     * @var ECash_FactorTrust_IRequest
     */
    private $request;

    /**
     * ECash FactorTrust Response Object
     *
     * @var ECash_FactorTrust_IResponse
     */
    private $response;

    /**
     * ECash FactorTrust Result Object
     *
     * @var ECash_FactorTrust_Result
     */
    private $result;
    
    private $store;
    
    private $username;
    
    private $merchant;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $url;

    /**
     * @var array
     */
    private $data;

    /**
     * @var string
     */
    private $call_type;

	public function __construct($store_id)
	{
		if (empty($store_id))
		{
			throw new InvalidArgumentException('empty factor trust store id passed');
		}
        //use the store/inquiry to lookup the merchant, username, and password
        $store = new FactorTrustStore($store_id);

		$this->username = $store->username;
		$this->password = $store->password;
		$this->merchant = $store->merchant;
		$this->store = $store_id;
	}
    /**
     * @param array $data
     * @return ECash_FactorTrust_Result
     */
    public function execute($data) {
        $this->data = (object)$data;

        if(!empty($this->request)) {
            if(!empty($this->response)) {
                    $this->call = new FactorTrust_UW_Call($this->url, $this->request, $this->response);
                    return $this->result = $this->call->execute($data);
            } else {
                    throw new Exception("Error: The response object must be set.");
            }
        } else {
            throw new Exception("Error: The request object must be set.");
        }
    }

    /**
     * Inserts the result into the bureau_inquiry table
     *
     * @param int $agent_id Agent making the request
     * @return boolean
     */
    public function saveResult($agent_id = NULL) {
        $retval = FALSE;

        if(!empty($this->result)) {
            $bureau = ECash::getFactory()->getModel('Bureau');
            if($bureau->loadBy(array('name_short' => 'factortrust'))) {
                $bureau_inquiry = ($this->response->hasError() || $this->response->getDecision() == 'N') ? 'BureauInquiryFailed' : 'BureauInquiry';
                $bi_record      = ECash::getFactory()->getModel($bureau_inquiry);

                $bi_record->bureau_id        = $bureau->bureau_id;
                $bi_record->company_id       = $this->data->company_id;
                $bi_record->application_id   = $this->data->application_id;
                $bi_record->inquiry_type     = $this->request->getCallType();
                $bi_record->sent_package     = $this->result->getRequestXML();
                $bi_record->received_package = $this->result->getResponseXML();
                $bi_record->outcome          = ($this->response->isValid() ? 'Success' : 'Fail');
                //$bi_record->payrate          = $this->response->getPayRate();
                $bi_record->agent_id         = $agent_id;

                if($this->response->hasError()) {
                    $bi_record->outcome         = 'Fail';
                    $bi_record->decision        = 'ERROR';
                    $bi_record->error_condition = 'other';
                    $bi_record->reason          = $this->response->getErrorCode() . ": " . $this->response->getErrorMsg();
                }

                $bi_record->date_created = time();
                $bi_record->save();

                $factorTrustResponse = new ECash_FactorTrust_Responses_Perf();
                $factorTrustResponse->parseXML($bi_record->received_package);
                $factory = ECash::getFactory();
                $db = $factory->getDB();

                $bureau_inquiry_id = ($bureau_inquiry == 'BureauInquiry') ? $bi_record->bureau_inquiry_id : 0;
                $bureau_inquiry_failed_id = ($bureau_inquiry == 'BureauInquiry') ? 0 : $bi_record->bureau_inquiry_id;

                $factorTrustResponse->update_bureau_xml_fields($db, $this->data->application_id, $bureau_inquiry_id, $bureau_inquiry_failed_id);

                $retval = TRUE;
            }

            $datr = array();
            $datr['company_id'] = $this->data->company_id;
            $datr['external_id'] = 0;
            $datr['application_id'] = $this->data->application_id;
            /* bureau is hard-coded in the legacy code as well */
            $datr['bureau'] = 'factortrust';
            $datr['inquiry_type'] = $this->request->getCallType();
            $datr['outcome'] = ($this->response->isValid() ? 'Success' : 'Fail');
            $datr['decision'] = '';
            $datr['error_condition'] = '';
            $datr['sent_package'] = utf8_encode($this->result->getRequestXML());
            $datr['receive_package'] = utf8_encode($this->result->getResponseXML());
            $datr['trace_info'] = '';
            $datr['reason'] = '';
            $datr['timer'] = stripslashes(round($this->result->getRequestLength(), 5));

            if ($this->response->hasError()) {
                $datr['outcome'] = 'Fail';
                $datr['decision'] = 'ERROR';
                $datr['error_condition'] = 'other';
                $datr['reason'] = $this->response->getErrorCode();
            }

            $inquiry_client = ECash::getFactory()->getInquiryClient();
            $inquiry_client->enableBuffer(true);
            $inquiry_client->recordInquiry($datr);
            $inquiry_client->flush();
        }

        return $retval;
    }

    /**
     * Sets the FactorTrust request object
     *
     * @param string $request The name of the request type
     */
    public function setRequest($request)
    {
        $class = "ECash_FactorTrust_Requests_".$request;
        $this->request = new $class($this->license_key, $this->password, $this->call_type);
    }

    /**
     * Sets the FactorTrust response object
     *
     * @param string $response The name of the response type
     */
    public function setResponse($response)
    {
        $class = "ECash_FactorTrust_Responses_".$response;
        $this->response = new $class();
    }

    /**
     * Manually sets the FactorTrust request object
     *
     * @param ECash_FactorTrust_IRequest $request
     */
    public function manuallySetRequest(FactorTrust_UW_IRequest $request)
    {
        $this->request = $request;
    }

    /**
     * Manually sets the FactorTrust response object
     *
     * @param ECash_FactorTrust_IResponse $response
     */
    public function manuallySetResponse(FactorTrust_UW_IResponse $response)
    {
        $this->response = $response;
    }

    /**
     * @return string $password
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return string $license_key
     */
    public function getLicenseKey()
    {
        return $this->license_key;
    }
}
?>
