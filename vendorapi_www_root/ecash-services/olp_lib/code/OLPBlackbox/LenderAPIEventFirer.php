<?php
class OLPBlackbox_LenderAPIEventFirer implements OLPBlackbox_Rule_IObserver
{
	/**
	 * @var OLP_IEventBus
	 */
	protected $eventbus;
	
	/**
	 * 
	 * @param OLP_IEventBus $eventbus
	 * @return void
	 */
	public function __construct(OLP_IEventBus $eventbus)
	{
		$this->eventbus = $eventbus;
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
			//$data->application_id, $data->campaign_name, $data->post_type, $data->response
			case OLPBlackbox_Rule_LenderPost::EVENT_RECEIVED_RESPONSE:
				$event = new OLPBlackbox_Event(
					OLPBlackbox_Event::TYPE_LENDERAPI_RESPONSE,
					array('decision', 'offer', 'campaign', 'application_id', 'response', 'post_type')
				);
				$event->decision = $data->response->getDecision();
				$persistent_data = $data->response->getPersistentData();
				if ($persistent_data['offer'])
				{
					$event->offer = $persistent_data['offer'];
				}
				$event->application_id = $data->application_id;
				$event->campaign_name = $data->campaign_name;
				$event->response = $data->response;
				$event->post_type = $data->post_type;
				$this->eventbus->notify($event);
				break;
		}
	}
}