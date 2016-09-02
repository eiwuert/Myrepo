<?php
//die("you must enable this file, don't use on public accessible live sites!");
	// Required Files
	ini_set('include_path', 'virtualhosts/lib:'.ini_get('include_path'));
	require_once("debug.1.php");			// Debug Include
	require_once("error.2.php");			// Error Include
	require_once("mysql.4.php");			// Mysql Include

	/**
		Searches for the documrnts
	*/
	function View_Archive_Document($sql, $server, $application_id)
	{
		$valid = TRUE;
		$document_array = array();
		
		switch ( $server )
		{
			case 'rc':
			{
				$database = 'condor';
				break;
			}
			case 'live':
			case 'local':
			default:
			{
				$database = 'condor';
				break;
			}
		}

		$query = '
				  SELECT
				  A.*, A.date_modified AS archive_date_modified, A.date_created AS archive_date_created, 
				  S.*, S.date_modified AS signature_date_modified, S.date_created AS signature_date_created, 
				  T.*, T.date_modified AS audit_trail_date_modified, T.date_created AS audit_trail_date_created
				  FROM 
				  signature S, document_archive A, audit_trail T 
				  WHERE 
				  S.document_archive_id = A.document_archive_id 
				  AND 
				  S.signature_id = T.signature_id 
				  AND 
				  S.application_id = "'. $application_id.'" 
				  ORDER BY 
				  A.date_created DESC';

		try
		{
			$result = $sql->Query ($database, $query);

			if($sql->Row_Count($result) > 0)
			{
				while($row = $sql->Fetch_Array_Row($result))
				{
					$row['document']	= gzuncompress($row['document']);
					$document_array[]	= $row;
				}
			}
		}
		catch (Exception $e)
		{
			$valid = FALSE;
			$error = $e->getMessage();
		}

		if($valid)
		{
			$return_var = $document_array;
		}
		else 
		{
			$return_var = $error;
		}

		return $return_var;
	}
	/**
		Gets the connection to the database server
	*/
	function Get_Sql( $server )
	{
		switch( $server )
		{
			case 'live':
			{
				$host = 'writer.condor.ept.tss:23306';
				$user = 'condor';
				$pass = 'Nzt04g8a';
				break;
			}
			case 'rc':
			{
				 $host = 'db101.clkonline.com:3308';
				 $user = 'condor'; 
				 $pass = 'Nzt04g8a';
				 break;


			}
			case 'local':
			default:
			{
				$host = 'beast.tss';
				$user = 'root';
				$pass = '';
				break;
			}	
		}
		$sql = new MySQL_4($host, $user, $pass, TRUE);
		$sql->Connect();	
		return $sql;
	}
	/**
	
	*/
	function Is_Selected( $value )
	{
		$return_var = '';
		if( $value == $_REQUEST['server'] )
		{
			$return_var =  'selected';
		}
		return $return_var;
	}
			
	// LOGIC
	
	$document_array = array();
	
	if(isset($_REQUEST['application_id']))
	{
		$app_id = trim( $_REQUEST['application_id'] );
		$server = trim( $_REQUEST['server'] );

		try
		{
			//Get DB connection 
			$sql = Get_Sql($server);

			$response = View_Archive_Document($sql, $server, $app_id);
			
			if(is_array($response) == FALSE)
			{
				$message = $response;
			}
			else 
			{
				if(count($response) < 1 )
				{
					$message = 'No documents found';
				}
				else 
				{
					$document_array = $response;
				}
			}
		}
		catch (Exception $e)
		{
			$message =  '<pre>' . print_r($e, 1);
		}
	}

?>
<html>
	<head>
		<title>Condor Archive Viewer</title>
		<script language="javascript">
			<?if(count($document_array) > 0){?>

			var doc_array = new Array(<?=count($document_array)?>);
				<?for($i=0;$i<count($document_array);$i++){?>

			doc_array[<?=$i?>] = new Object;
					<?foreach($document_array[$i] as $key => $value){?>
			doc_array[<?=$i?>].<?=$key?> = "<?=str_replace("\n", '\n', addslashes($value))?>";
					<?}?>
				<?}?>
			<?}?>

			function Load_Doc(doc_id)
			{
				document.getElementById("doc_iframe").contentDocument.body.innerHTML = doc_array[doc_id].document;
			}
			
			function Print_Doc()
			{
				var printWindow = open("");
				printWindow.document.open();
				printWindow.document.write(document.getElementById("doc_iframe").contentDocument.body.innerHTML);
				printWindow.document.close();

				if (typeof(printWindow.print) != 'undefined')
				{
				    printWindow.print();
				}
			}
		</script>
		<style type="text/css">
			body, a
			{
				color: #999;
				margin-top:2px;
				margin-bottom:0px;
				font-size: small;
				font-family: sans-serif;
			}
			form
			{
				margin:0;
				padding:0;
			}
			#audit_trail th, #form_title
			{
				font-size: small;
				font-family: sans-serif;
				text-align:left;
				color: #999;
				padding-right:5px;
			}
			#audit_trail td
			{
				font-size: small;
				line-height:0px;
				padding-left:15px;
				padding-right:5px;
				padding-bottom:5px;
			}
			#audit_trail table
			{
				
			}
			
			#docs_title
			{
				float:left;
			}
			
			#main_cotainer
			{
			
			}
			#doc_iframe
			{
				width : 630px;
				height : 83%;
			}

			#navcontainer
			{
			margin-top:10px;
			}
			#navlist
			{
				margin: 0;
				padding: 0 0 20px 10px;
				border-bottom: 1px solid #000;
			}
			
			#navlist ul, #navlist li
			{
				margin: 0;
				padding: 0;
				display: inline;
				list-style-type: none;
			}
			
			#navlist a:link, #navlist a:visited
			{
				float: left;
				line-height: 14px;
				font-size: small;
				font-weight: bold;
				margin: 0 10px 4px 10px;
				text-decoration: none;
				color: #999;
			}
			
			#navlist a:link#current, #navlist a:visited#current, #navlist a:hover, #navlist a:focus
			{
				border-bottom: 4px solid #000;
				padding-bottom: 2px;
				background: transparent;
				color: #000;
			}
			
			#navlist a:hover { color: #000; }
			
html, body { min-height: 100%; width: 100%; height: 100%;}
html>body { height: auto;}
body { margin: 0; padding:0; }
#footer {clear: both;  bottom: 0; left: 0; border: none; width: 100%;}
/* hide from Mac IE5 */
/* \*/
#footer {position: absolute; }
/* */

/* Change in Opera 5+ (and some others) */
html>body div#footer {
    position: static;
}

/* Change back in everything except Opera 5 and 6, still hiding from Mac IE5 */
/* \*/
head:first-child+body div#footer {
    position: absolute;
}
/* */

#nav p, #content p {margin: 1em;}
#nav ul {margin-left: 0; padding-left: 0;}
#nav li {margin: 0 1em 0 2em;}
			
		</style>
	</head>
	<body <?if(count($document_array) > 0) echo 'onload="Load_Doc(0)"'?>>

		<div align="center"  id="main_cotainer">
			<form action="http://<?=$_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']?>" method="POST">
				<span id="form_title">Application Id</span>
				<input type="text" name="application_id" value="<?=$app_id?>">
				<select name="server">
					<option value="live" <?=Is_Selected('live');?>>LIVE</option>
					<option value="rc" <?=Is_Selected('rc');?>>RC</option>
					<option value="local" <?=Is_Selected('local');?>>LOCAL</option>
				</select>
				<input type="submit" name="submit" value="Go">
			</form>
			<?=$message?>
			<?if(count($document_array) > 0){?>
			<table id="audit_trail" cellpadding="0" cellpadding="0">
				<tr>
					<th>Request received</th>
					<th>Response received</th>
					<th>Document saved</th>
					<th>Record saved</th>
				</tr>
				<tr>
					<td><?=$document_array[0]['signature_request_received']?></td>
					<td><?=$document_array[0]['signature_response_received']?></td>
					<td><?=$document_array[0]['legal_document_saved']?></td>
					<td><?=$document_array[0]['signature_record_saved']?></td>
				</tr>
				<tr>
					<th>Agree</th>
					<th>Disagree</th>
					<th>Modified</th>
					<th>Created</th>
				</tr>
				<tr>
					<td><?=$document_array[0]['signature_agree']?></td>
					<td><?=$document_array[0]['signature_disagree']?></td>
					<td><?=$document_array[0]['audit_trail_date_modified']?></td>
					<td><?=$document_array[0]['audit_trail_date_created']?></td>
				</tr>
			</table>
			<div id="navcontainer">
			<span id="docs_title">Documents (<a href="javascript:Print_Doc()">Print</a>)</span>
				<ul id="navlist">
					<?for($i=0;$i<count($document_array);$i++){?>
					<li><a href="javascript:Load_Doc(<?=$i?>)"><?=$document_array[$i]['archive_date_created']?></a></li>
					<?}?>
				</ul>
			</div>
			<br style="line-height:5px">
			<iframe id="doc_iframe"></iframe>
			<?}?>
		</div>
		<div align="center" id="footer">Maintained by <a href="mailto:sam.hennessy@sellingsource.com?subject=Re, Condor Archive Viewer">Sam Hennessy : sam.hennessy@sellingsource.com</a></div>
	</body>
</html>
