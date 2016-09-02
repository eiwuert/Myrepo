<?php
class OLPBlackbox_PricePointRecurListener implements OLPBlackbox_IListener, OLP_ISubscriber
{
	/**
	 * @var string
	 */
	protected $price_group;
	
	/**
	 * 
	 * @var valid?
	 */
	protected $child;
	
	/**
	 * @var OLPBlackbox_LenderAPIPostRecorder
	 */
	protected $postRecorder;
	
	public function __construct(OLPBlackbox_LenderAPIPostRecorder $recorder, $price_group)
	{
		$this->price_group = $price_group;
		$this->recorder = $recorder;
	}
	
	/**
	 * Sets the child this listener is acting upon.
	 * @param mixed $child
	 * @return void
	 */
	public function setChild($child)
	{
		$this->child = $child;
	}
	
	/**
	 * Pass this listener and eventbus it can subscribe
	 * to events on.
	 * @param OLP_IEventBus $eventbus
	 * @return void
	 */
	public function subscribeToEvents(OLP_IEventBus $eventbus)
	{
		$eventbus->subscribeTo(OLPBlackbox_Event::TYPE_LENDERAPI_RESPONSE, $this);	
	}
	
	/**
	 * Notify the subscriber of an event which took place.
	 *
	 * @param OLP_IEvent $event The event that "happened."
	 * @return void
	 */
	public function notify(OLP_IEvent $event)
	{
		switch ($event->getType())
		{
			case OLPBlackbox_Event::TYPE_LENDERAPI_RESPONSE:
				$decision = $event->decision;
				if ($decision == 'REJECTED')
				{
					$offer_price = $this->getOfferPrice($event->offer);
					$lead_cost = $this->getLeadCost();
					if ($offer_price === FALSE || (is_numeric($lead_cost) && $lead_cost > $offer_price))
					{
						if (method_exists($this->child, 'getName'))
						{
							$this->postRecorder->recordPost(
								$event->application_id,
								$this->child->getName(),
								$event->post_type,
								$event->response);
						}
						if (method_exists($this->child, 'invalidate'))
						{
							$this->child->invalidate();
						}
					}
				}
				break;
		}
	}
	
	/**
	 * Returns an offer price
	 * @param mixed $offer
	 * @return integer|FALSE
	 */
	protected function getOfferPrice($offer)
	{
		if (is_numeric($offer))
		{
			return $offer;
		}
		elseif (is_array($offer))
		{
			if (isset($offer[$this->price_group]))
			{
				return $offer[$this->price_group]['price'];
			}
		}
		return FALSE;
		
	}
	
	/**
	 * returns a campaign price
	 * @return integer / false
	 */
	protected function getLeadCost()
	{
		if (method_exists($this->child, 'getLeadCost'))
		{
			$lead_cost = $this->child->getLeadCost() !== FALSE ? $this->child->getLeadCost() : 'FALSE';
		}
		else
		{
			throw new RuntimException("No leadcost method?");
		}
				
	}
}