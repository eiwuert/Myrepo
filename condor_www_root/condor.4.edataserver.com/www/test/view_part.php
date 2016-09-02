<?php
	
	$document_id = isset($_GET['document']) ? $_GET['document'] : FALSE;
	$part_id = isset($_GET['part']) ? $_GET['part'] : FALSE;
	
	if ($document_id && $part_id)
	{
		
		$link = mysql_connect('localhost', 'root', '');
		mysql_select_db('condor_2', $link);
		
		$query = "
			SELECT
				uri,
				content_type,
				compression,
				file_name
			FROM
				document_part
			WHERE
				document_id = '{$document_id}' AND
				document_part_id = '{$part_id}'
		";
		$result = mysql_query($query, $link);
		
		if ($result && ($part = mysql_fetch_assoc($result)))
		{
			
			$part['data'] = file_get_contents($part['file_name']);
			
			if ($part['compression'] == 'GZ')
			{
				$part['data'] = gzuncompress($part['data']);
			}
			
			$query = "
				SELECT
					document_part_id,
					uri,
					content_type
				FROM
					document_part
				WHERE
					document_id = '{$document_id}' AND
					parent_id = '{$part_id}'
			";
			$result = mysql_query($query, $link);
			
			while ($rec = mysql_fetch_assoc($result))
			{
				
				$rec['url'] = $_SERVER['PHP_SELF'].'?document_id='.$document_id.'&part_id='.$rec['document_part_id'];
				
				// replace any references in the main document
				$part['data'] = str_replace($rec['uri'], $rec['url'], $part['data']);
				
				// add to an array of attachments for display later
				$attachments[] = $rec;
				
			}
			
			header('Content-Type: '.$part['content_type']);
			die($part['data']);
			
		}
		
	}
	
?>