<?

	require_once('../include/code/server.php');
	require_once('mysql.4.php');
	require_once('mysqli.1.php');

	$which_db=$_REQUEST['which_db'];
	$test=isset($_REQUEST['test'])?$_REQUEST['test']:'default';
	$type = array('db1'=>'MYSQL','ds001'=>'BLACKBOX');
	$db = Server::Get_Server('LIVE', $type[$which_db]);
	if (!$which_db || !$db) die('Invalid DB Argument');

	switch ($test) {

		case 'insert':
			switch ($which_db) {
				case 'db1':
					$sql = new MySQLi_1('db1.clkonline.com', 'norbinnr', 'Lw0CGQIR', 'test', 13306);
					if ($r = $sql->Query('INSERT INTO disk_check () VALUES ()')) {
						$id = $sql->Insert_Id();
						echo "Insert Record Successful (id ".$id.")<br>\n";
						if ($r = $sql->Query('DELETE FROM disk_check WHERE row_id='.$id))
							echo "Delete Record Successful";
						else
							echo "Delete Record Unsuccessful!!!";

					}
					else {
						echo "Insert Record Unsuccessful!!!";
					}
					break;
				case 'ds001':
					if (isset($db['port'])) $db['host'] .= ':'.$db['port'];
					$sql = new MySQL_4($db['host'], $db['user'], $db['password']);
					$sql->Connect();
					if ($r = $sql->Query('test', 'INSERT INTO disk_check () VALUES ()')) {
						$id = $sql->Insert_Id();
						echo "Insert Record Successful (id ".$id.")<br>\n";
						if ($r = $sql->Query('test', 'DELETE FROM disk_check WHERE row_id='.$id))
							echo "Delete Record Successful";
						else
							echo "Delete Record Unsuccessful!!!";

					}
					else {
						echo "Insert Record Unsuccessful!!!";
					}
					break;
			}
			break;

		case 'default':
		default:
			if (isset($db['port'])) $db['host'] .= ':'.$db['port'];
			$sql = new MySQL_4($db['host'], $db['user'], $db['password']);
			$sql->Connect();
			if (!$sql->Is_Connected()) {
				header("HTTP/1.1 500 DB Connection Error");
			}

			$r = $sql->Query($db['db'], 'select * from application limit 1');
			if ($sql->Row_Count($r)>0) {
				header("HTTP/1.0 200 OK");
				flush();
			}
			else {
				header("HTTP/1.0 500 DB Connection Error");
				flush();
			}
			break;

	}

?>
