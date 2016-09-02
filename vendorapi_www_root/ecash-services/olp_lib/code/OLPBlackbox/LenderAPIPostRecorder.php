<?php
/**
 * Records LendorAPI posts 
 * @author stephan soileau <stephan.soileau@sellingsource.com>
 *
 */
class OLPBlackbox_LenderAPIPostRecorder implements OLPBlackbox_Rule_IObserver
{
	/**
	 * @var OLP_Factory
	 */
	protected $olp_factory;
	
	/**
	 * @var Applog
	 */
	protected $applog;
	
	/** 
	 * @var OLP_Message_Factory 
	 */
	protected $message_factory;
	
	/**
	 * 
	 * @param OLP_Factory $factory
	 * @param OLP_Message_Factory $message_factory
	 * @param Applog $applog
	 */
	public function __construct(OLP_Factory $factory, OLP_Message_Factory $message_factory, $applog)
	{
		$this->olp_factory = $factory;
		$this->applog = $applog;
		$this->message_factory = $message_factory;
	}
	
	/**
	 * Used if we're attached to a rule and are recording observers.
	 * @param OLPBlackbox_Rule_IObservable $observable
	 * @param string $event
	 * @param mixed $data
	 * @return void
	 */
	public function onNotification(OLPBlackbox_Rule_IObservable $observable, $event, $data)
	{
		switch ($event)
		{
			case OLPBlackbox_Rule_LenderPost::EVENT_RECEIVED_RESPONSE:
				$this->recordPost($data->application_id, $data->campaign_name, $data->post_type, $data->response);
				break;
		}
	}
	
	/**
	 * Records a post 
	 * @param integer $application_id
	 * @param string $campaign_name
	 * @param string $post_type
	 * @param LenderAPI_Response $response
	 * @return boolean
	 */
	public function recordPost($application_id, $campaign_name, $post_type, LenderAPI_Response $response)
	{
		$post_model = $this->olp_factory->getModel('BlackboxPost');
				
		$post_model->application_id = $application_id;
		$post_model->winner = $campaign_name;
		
		$post_model->post_time = $response->getPostTime();
		$post_model->vendor_decision = $response->getDecision();
		$post_model->vendor_reason = $response->getReason();
		$post_model->success = $response->getSuccess() ? 'TRUE' : 'FALSE';
		$post_model->compression = 'GZ';
		$post_model->encrypted = 1;
		$post_model->type = $post_type;
		
		try 
		{
			$post_model->save();
			$message = $this->message_factory->getMessage('record-bbx-post');
			$message->setBlackboxPostId($post_model->blackbox_post_id);
			$message->setDataSent($response->getDataSent());
			$message->setDataReceived($response->getDataReceived());
			$message->send();
			return TRUE;
		}
		catch (Exception $e)
		{
			$this->applog->Write('failed inserting into blackbox_post: ' . $e->getMessage());
			return FALSE;
		}
	}
}