<?php
	include_once('config.php');
	
	if($_REQUEST['process'])
	{
		$facility = $_REQUEST['process'];
		$company_id = $_REQUEST['company_id'];
		//$keys = array( 	'qc'  => '34534',
		//					'ach' => '24355');

		$queue_name = CUST_DIR . "temp_data/$facility.{$company_id}.queue";
		
		// If the file doesn't exist, create it.
		if(! file_exists($queue_name)) {
			if(! $fp = fopen($queue_name, 'w+'));
				die("Could not create queue file $queue_name!");
				
			fclose($fp);
		}
		
		$queue = msg_get_queue(ftok($queue_name, 'A'),0666 | IPC_CREAT);
	
		//$queue = msg_get_queue($keys[$facility]);
		$stats = msg_stat_queue($queue);
		$num_msgs = $stats['msg_qnum'];
		$message = "";
		
		$percent = 0;
		if( $num_msgs > 0)
		{
			for($x = 0; $x < $num_msgs; $x++)
			{
				if(msg_receive($queue,1,$msg_type,16384,$msg))
				{
					if($msg->percentage != '')
					{
						$percent = $msg->percentage;
					}
					if($msg->message != '')
					{
						$message .= $msg->message . "\n";
					}
				}
				unset($msg);
			}
		}
		if(!empty($message))
		{
			echo "{$percent}%,{$message}";
		}

	}
	else
	{
			echo "No data available.";
	}
?>
