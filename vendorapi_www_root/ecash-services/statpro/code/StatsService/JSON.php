<?php

class StatsService_JSON implements Site_IRequestProcessor {
	
	/**
	 * @var StatsService_MessageProcessor_IMessageProcessor
	 */
	private $message_processor;

	/**
	 * @var Log_ILog_1
	 */
	private $log;

	/**
	 * Set the message processor in the constructor
	 * @param StatsService_MessageProcessor_IMessageProcessor $message_processor
	 * @param Log_ILog_1 $logging provider
	 */
	public function __construct(
			StatsService_MessageProcessor_IMessageProcessor $message_processor,
			Log_ILog_1 $log) {
		$this->message_processor = $message_processor;
		$this->log = $log;
	} 
	
	/**
	 * Processes the incoming request.
	 *
	 * This method decodes the incoming post data as a JSON stream and
	 * attempts to process it as a stat message. If the message is missing
	 * or invalid, a 500 error will be returned.
	 *
	 * @param Site_Request $request
	 * @return Site_IResponse
	 */
	public function processRequest(Site_Request $request) {
		if ($request->getMethod() !== Site_Request::METHOD_POST) {
			$caller = empty($_SERVER['REMOTE_HOST']) 
				? "unknown" 
				: gethostbyaddr($_SERVER['REMOTE_HOST']);
			$this->log->write(
				"Invalid request from host " . $caller,
				Log_ILog_1::LOG_WARNING);
			return new Site_Response_Http(
				"Invalid request",
				500,
				"Invalid request");
		}

		$data = $request->getPostData();
		$message = json_decode($data);

		if (empty($message) || !$this->message_processor->isValidMessage($message)) {
			$this->log->write("Invalid post data: ".$data, Log_ILog_1::LOG_WARNING);
			return new Site_Response_Http(
				"Invalid post data",
				500,
				"Invalid post data");
		}

		try {
			$this->message_processor->processMessage($message);
		} catch (Exception $e) {
			$this->log->write(
				sprintf(
					"Exception while processing message: [%s] with processor %s : %s",
					var_export($message, TRUE),
					get_class($this->message_processor),
					$e->getMessage()),
				Log_ILog_1::LOG_CRITICAL);
			return new Site_Response_Http(
				"Error processing post.  Details have been logged",
				500,
				"Error processing post");
		}
			
		return new Site_Response_Http("Success");
	}
}
