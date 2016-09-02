<?php
require_once 'libolution/AutoLoad.1.php';

class MyWork extends Unix_WorkItem_1
{
	public $id;
	public $return_inline;

	public function __construct($id, $return_inline = TRUE)
	{
		$this->id = $id;
		$this->return_inline = $return_inline;
	}

	public function execute()
	{
		if($this->return_inline)
		{
			$this->result = 'MyResultReturnedViaMessage';
		}
		else
		{
			$this->setResult('MyResultReturnedViaSharedMem');
		}
	}
}


class MyParent extends Unix_WorkQueueMasterProcess_1
{
	public function onStartup()
	{
		parent::onStartup();
		$this->work_queue->addWork(new MyWork(1));
		$this->work_queue->addWork(new MyWork(2, FALSE));
		$this->work_queue->addWork(new Mywork(3));
		$this->work_queue->addWork(new MyWork(4, FALSE));
		$this->work_queue->addWork(new MyWork(5));
	}

	public function tick()
	{
		parent::tick();

		if (!$this->work_queue->HasIncompleteItems)
		{
			$this->log("all work is are finished. exiting.");
			$this->quit();
		}
	}

	public function workerFactory(Unix_IPCKey_1 $key)
	{
		return new MyWorker($key);
	}

	public function onWorkFinish(Unix_WorkItem_1 $item)
	{
		echo __METHOD__.' '.$item->id.' '.$item->getResult()."\n";
	}
}


class MyWorker extends Unix_WorkQueueClientProcess_1
{
}


$ipckey = new Unix_IPCKey_1("/tmp/mq_location");
$parent = new MyParent($ipckey);


$event = $parent->getWorkQueue()->OnWorkFinished;
$event->addDelegate(Delegate_1::fromMethod($parent, 'onWorkFinish'));


$parent->fork();

?>
