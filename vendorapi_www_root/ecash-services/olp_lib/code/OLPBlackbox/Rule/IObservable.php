<?php
/**
 * Interface for observable rules to implement
 * @author stephan soileau <stephan.soileau@sellingsource.com>
 *
 */
interface OLPBlackbox_Rule_IObservable 
{
	/**
	 * Attach an observer to this observable rule
	 * @param OLPBlackbox_Rule_IObserver $observer
	 * @return void
	 */
	public function attach(OLPBlackbox_Rule_IObserver $observer);
	
	/**
	 * Detach an observer from this observable rule 
	 * @param OLPBlackbox_Rule_IObserver $observer
	 * @return void
	 */
	public function detach(OLPBlackbox_Rule_IObserver $observer);
}