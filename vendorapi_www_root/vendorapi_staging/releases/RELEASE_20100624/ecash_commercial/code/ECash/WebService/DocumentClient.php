<?php

/**
 *  Make calls to the Document Service
 *
 * @author Bill Szerdy <bill.szerdy@sellingsource.com>
 * @package WebService
 */
class ECash_WebService_DocumentClient extends WebServices_Client_DocumentClient
{
    /**
     * Constructor for the ECash_WebService_DocumentClient object (Use NULL when possible for defaults)
     *
     * @param Applog $log
     * @param ECash_WebService $webservice
     * @return void
     */
    public function __construct(Applog $log = NULL, WebServices_WebService $webservice = NULL)
    {
        $log = is_null($log) ? get_log('document_service') : $log;
        if(empty($webservice))
        {
            $url = is_null($url) ? DOCUMENT_SERVICE_URL : $url;
            $user = is_null($user) ? $GLOBALS["APP_SERVICE_COMPANY_LOGINS"][ECash::getCompanyName()]['user'] : $user;
            $pass = is_null($pass) ? $GLOBALS["APP_SERVICE_COMPANY_LOGINS"][ECash::getCompanyName()]['pwd'] : $pass;
            $document_service = new ECash_WebService(
                $log,
                $url,
                $user,
                $pass
            );
        }
        else
        {
            $document_service = $webservice;
        }
        parent::__construct($log, $document_service);
    }
}

?>
