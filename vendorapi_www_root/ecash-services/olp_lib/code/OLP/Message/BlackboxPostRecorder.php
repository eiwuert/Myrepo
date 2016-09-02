<?php
/**
 * Message that records blackbox post records
 * @author stephan soileau <stephan.soileau@sellingsource.com>
 *
 */
class OLP_Message_BlackboxPostRecorder extends OLP_Message_Container
{
	/** 
	 * @var OLP_Factory 
	 */
	protected $olp_factory;
	
	/**
	 * Sets the datasent of the blackbox post
	 * @param string $sent
	 * @return void
	 */
	public function setDataSent($sent)
	{
		$this->body()->data_sent = $sent;
	}
	
	/**
	 * Set the data received of the blackbox post
	 * @param string $rec
	 * @return void
	 */
	public function setDataReceived($rec)
	{
		$this->body()->data_received = $rec;
	}
	
	/**
	 * Set the blackbox post id of post we're saving
	 * data for.
	 * @param integer $id
	 * @return void
	 */
	public function setBlackboxPostId($id)
	{
		$this->body()->blackbox_post_id = $id;
	}
	
	/**
	 * return or create a new body class
	 * @return stdClass
	 */
	protected function body() 
	{
		if (!$this->body instanceof stdClass)
		{
			$this->body = new stdClass;
		}
		return $this->body;
	}
	
	/**
	 * Process this message.
	 * @return void
	 */
	public function handle()
	{
		if (is_numeric($this->body->blackbox_post_id))
		{
			/** 
			 * @var OLP_Models_Blackboxpost 
			 */
			$post_model = $this->olp_factory->getModel('BlackboxPost');
			if ($post_model->loadBy(array('blackbox_post_id' => $this->body->blackbox_post_id)))
			{
				if (!empty($this->body->data_sent))
				{				
					$post_model->setDataSent($this->body->data_sent);
				}
				if (!empty($this->body->data_received))
				{
					$post_model->setDataReceived($this->body->data_received);
				}
				$post_model->save();
			}
		}
	}
	
	/**
	 * Injects an OLP factory into this thing.
	 * @param OLP_Factory $factory
	 * @return void
	 */
	public function setOlpFactory(OLP_Factory $factory)
	{
		$this->olp_factory = $factory;
	}
}