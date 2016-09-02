<?PHP
	$outside_web_space = realpath ("../")."/";
	$inside_web_space = realpath ("./")."/";
	define ("OUTSIDE_WEB_SPACE", $outside_web_space);
	define ("DATABASE", "expressgoldcard");

	require_once ("/virtualhosts/lib/debug.1.php");
	require_once ("/virtualhosts/lib/error.2.php");
	require_once ("/virtualhosts/lib/mysql.3.php");
	require_once ("/virtualhosts/lib/crypt.3.php");
     /*
	$server = new stdClass ();
	$server->host = "read1.ds04.tss";
	$server->user = 'root';
	$server->pass = '';
     */
	$server->host = "read1.iwaynetworks.net";
	$server->user = "sellingsource";
	$server->pass = "%selling\$_db";
 	
 	
	// Create sql connection(s)
	$sql = new MySQL_3 ();
	$result = $sql->Connect (NULL, $server->host, $server->user, $server->pass, Debug_1::Trace_Code (__FILE__, __LINE__));
	
	
     echo "here we go:\ncd ";
	for($i=1; $i<2000; $i++)
     {
          $query = "SELECT month,day,year FROM `orders` WHERE cid = '".$i."'";
          $result = $sql->Query('rc_expressgoldcard', $query, Debug_1::Trace_Code (__FILE__, __LINE__));
          $data = $sql->Fetch_Object_Row($result);
                    
          $query = "UPDATE `orders` SET birthday = '19".$data->year."-".$data->month."-".$data->day."' WHERE cid = '".$i."'";
          $result = $sql->Query('rc_expressgoldcard', $query, Debug_1::Trace_Code (__FILE__, __LINE__));
          echo $i;
          echo "\n";
     }
     exit;
	
	/*
	$query = "SELECT transaction_id,transaction_total,transaction_balance FROM `transaction_0` WHERE transaction_type = 'ORDER'";
	$result = $sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));

	while (FALSE !== ($row_data = $sql->Fetch_Object_Row ($result)))
	{
		$acct = $row_data->transaction_id;
		$trans->{"id".$acct}=$row_data;
		$loop = 1;
	}
	
	foreach($trans AS $record)
	{
          $amount = round($record->transaction_total,2)-6;
          
          if(($amount*100)%2)
          {
               $big = round($amount/2,2);
               $small = $amount-$big;
          }
          else
          {
               $big = $small = $amount/2;
          }
          
          $ach = $big+6;
          $cc = $small;
          $total = $ach + $cc;
          
          $query = "UPDATE `transaction_0` SET ach_total = $ach, cc_total = $cc, transaction_total = $total WHERE transaction_id = $record->transaction_id";
          $result = $sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
          
          $query = "UPDATE `transaction_line_item` SET line_item_amount = $cc, line_item_balance = $cc WHERE rel_transaction_id = $record->transaction_id AND line_item_type = 'CC'";
          $result = $sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
          
          $query = "UPDATE `transaction_line_item` SET line_item_amount = $ach WHERE rel_transaction_id = $record->transaction_id AND line_item_type = 'ACH'";
          $result = $sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
          
          $query = "SELECT rel_transaction_id,line_item_balance FROM `transaction_line_item` WHERE line_item_type = 'ACH' AND rel_transaction_id = $record->transaction_id AND line_item_action = 'DOWN PAYMENT'";
          $result = $sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
           
          unset($loop);
          while (FALSE !== ($row_data = $sql->Fetch_Object_Row ($result)))
          {
               $acct = $row_data->transaction_id;
               $line->{"id".$acct}=$row_data;
               $loop = 1;
          }
          
          if($loop)
          {
               foreach ($line AS $rec)
               {
                    $query = "SELECT transaction_total,ach_total,transaction_balance FROM `transaction_0` WHERE transaction_id = $rec->rel_transaction_id";
                    $result = $sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
                    $data = $sql->Fetch_Object_Row ($result);
                    $thing = $data->transaction_total-$data->ach_total;
                    
                    if($rec->line_item_balance == 0)
                    {
                         $query = "UPDATE `transaction_0` SET transaction_balance = transaction_total-ach_total WHERE transaction_id = $rec->rel_transaction_id";
                         $result = $sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
                         echo "updated balance of ".$rec->rel_transaction_id." from ".$data->transaction_balance." to ".$thing."\n";
                    }
                    else
                    {
                         $query = "UPDATE `transaction_0` SET transaction_balance = transaction_total WHERE transaction_id = $rec->rel_transaction_id";
                         $result = $sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
                         echo "updated balance of ".$rec->rel_transaction_id." to the same amount.\n";
                    }
               }
          }
          unset($loop);
         
          echo $ach."\n";
          echo $cc."\n";
          echo $total."\n";
          echo $record->transaction_id."\n\n";
	}
	exit;	
?>