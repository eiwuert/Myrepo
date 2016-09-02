<?php
/**
 * LenderAPI_Transport_Email
 *
 * @package LenderAPI
 * @version $Id: Transport.php 36911 2009-06-17 02:02:11Z dan.ostrowski $
 */
class LenderAPI_Transport_Email extends LenderAPI_Transport
{
	/**
	 * The request xsl object
	 *
	 * @var LenderAPI_Response
	 */
	protected $request_xsl;
	public function getRequestXsl() { return $this->request_xsl; }
	public function setRequestXsl($data) { $this->request_xsl = $data; }
	
	/**
	 * The response xsl object
	 *
	 * @var LenderAPI_Response
	 */
	protected $response_xsl;
	public function getResponseXsl() { return $this->response_xsl; }
	public function setResponseXsl($data) { $this->response_xsl = $data; }
	
	/**
	 * constructor
	 *
	 * @param LenderAPI_Response $response
	 * @param LenderAPI_XslTransformer $request_xsl
	 * @param LenderAPI_XslTransformer $request_xsl
	 */
	public function __construct(LenderAPI_Response $response, LenderAPI_XslTransformer $request_xsl, LenderAPI_XslTransformer $response_xsl)
	{
		$this->setMethod('email');
		$this->setResponse($response);
		$this->setRequestXsl($request_xsl);
		$this->setResponseXsl($response_xsl);
		$this->setAgent(new tx_Mail_Client(FALSE));
	}

	/**
	 * send the data
	 *
	 * @param array|string $data
	 * @return LenderAPI_Response
	 */
	public function send($data)
	{
		$template = $this->parseTemplate($data);
		$tokens = $this->parseTokens($data);
		$recipients = $this->parseRecipients($data);
		
		$this->sendEmails($recipients, $template, $tokens);
		
		$this->response_xsl->transform($this->request_xsl->getSourceDocument());
		$this->response->setDataSent($data);
		$this->response->setDataReceived(
			$this->request_xsl->getSourceDocument()->saveXml()
		);

		return $this->response;
	}
	
	/**
	 * Takes care of the actual email sending for this class
	 *
	 * @param array $recipients
	 * @param string $template
	 * @param array $tokens
	 * @return void
	 */
	protected function sendEmails($recipients, $template, $tokens)
	{
		foreach ($recipients as $to)
		{
			$tokens['email_primary'] = $to;
			$this->agent->sendMessage('live', $template, $to, "", $tokens);
		}
	}
	
	/**
	 * gets the email template to use from the xml string
	 *
	 * @param string $data
	 * @return string
	 */
	protected function parseTemplate($data)
	{
		$xml = new SimpleXMLElement($data);
		
		$template = 'PW_BRICK_AND_MORTAR';
		foreach ($xml->template as $value)
		{
			if (!empty($value))
			{
				$template = trim(strval($value));
			}
		}
		
		return $template;
	}
	
	/**
	 * Returns an array of email addresses to send alerts
	 *
	 * @param string $data
	 * @return array
	 */
	protected function parseRecipients($data)
	{
		$xml = new SimpleXMLElement($data);
		
		$recipients = array();
		foreach ($xml->recipients->children() as $value)
		{
			foreach (array_map('trim', explode(',', strval($value))) as $email)
			{
				if (!empty($email))
				{
					$recipients[] = $email;
				}
			}
		}
		
		return array_unique($recipients);
	}
	
	/**
	 * Returns an array of tokens from xml input data
	 *
	 * @param string $data
	 * @return array
	 */
	protected function parseTokens($data)
	{
		$xml = new SimpleXMLElement($data);
		
		$tokens = array();
		foreach ($xml->tokens->children() as $key => $value)
		{
			if (!isset($tokens[$key]))
			{
				$tokens[$key] = trim(strval($value));
			}
		}
						
		return $tokens;
	}
}

?>