<?php
/**
 * Interface for rule observers to put in there
 * @author stephan soileau <stephan.soileau@sellingsource.com>
 *
 */
interface OLPBlackbox_Rule_IObserver 
{
	/**
	 * notify the observer object of an event from the observable
	 * @param OLPBlackbox_Rule_IObservable $observable
	 * @param string $event
	 * @param mixed $data
	 * @return void
	 */
	public function onNotification(OLPBlackbox_Rule_IObservable $observable, $event, $data);
}