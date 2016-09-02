<?php

//ds66.tss, root, no password, db: condor_2 if you want to browse the db.

error_reporting(E_ALL);
require_once("prpc/client.php");

$form_type = $_REQUEST["form_type"];

$server = "prpc://condor.4.edataserver.com.ds79.tss/condor_api.php";
$result = new Prpc_Client($server);

$templates = $result->Get_Template_Names();
$temp_html = "<select name=template_id>\n";
foreach($templates as $key => $value)
	$temp_html .= "<option value='$value'>$value</option>";
$temp_html .= "</select>\n";

if(trim($_REQUEST["tokens"]) != "")
{
	$tokes = split(",",$_REQUEST["tokens"]);
	for($i=0; $i<count($tokes); $i++)
	{
		$tokemake = split("::",$tokes[$i]);
		$tokens[$tokemake[0]] = $tokemake[1];
		
	}	
}
else
{
	$tokens = array(
					"BODY" => "I got the body",
					"PARAGRAPH" => "When camron was in egypts land.. Let my cameron goooo..",
					);
}				
				
				
switch ($form_type)
{
	case "docgen":
		$doc_obj = $result->Create($_REQUEST["template_id"],$tokens,TRUE,$_REQUEST["app_id"]);
		$doc_id = $doc_obj['archive_id'];
		$document = $doc_obj['document'];
		break;
		
	case "docsbyapp":
		$doc_array = $result->Find_By_Application_Id($_REQUEST["app_id"]);
		foreach($doc_array as $key => $value)
		{
			$template_id = $value['template_name'];
			$doc_id = $value['document_id'];
			$doc_ts = $value['date_created'];
			$docs_html .= "<form method=post>";
			$docs_html .= "<input type=hidden name='form_type' value='docdetails'>";			
			$docs_html .= "Template Name:<b>$template_id</b>  ";
			$docs_html .= "<input type=hidden name='template_id' value='$template_id'>";
			$docs_html .= "Document ID:<b>$doc_id</b>";			
			$docs_html .= "<input type=hidden name='doc_id' value='$doc_id'>\n";
			$docs_html .= "Time: $doc_ts <input type=submit name=View value=View>\n";
			$docs_html .= "</form>";		
		}
		
		break;
		
	case "docdetails":
		$doc_id = $_REQUEST["doc_id"];
		$document = $result->Find_By_Archive_Id($_REQUEST["doc_id"]);
		break;
		
	case "sendfaxcmd":
		$cmd = "/usr/bin/sendfax -d ".$_REQUEST["doc_id"]." /tmp/test.pdf";		
		break;
		
	case "senddoc":
		if($_REQUEST["meth"] == "EMAIL")
		{
			$recp_arr_temp = split(",",$_REQUEST["recp"]);
			for($i=0; $i<count($recp_arr_temp); $i++)
			{
				$recp_arr[$i]['email_primary'] = $recp_arr_temp[$i];
				$recp_arr[$i]['email_primary_name'] = $recp_arr_temp[$i];
			}
		}
		else if($_REQUEST["meth"] == "FAX")
		{
			$recp_arr_temp = split(",",$_REQUEST["recp"]);
			for($i=0; $i<count($recp_arr_temp); $i++)
				$recp_arr[$i]['fax_number'] = $recp_arr_temp[$i];
				
		}				

		$msg = $result->Send($_REQUEST["doc_id"],$recp_arr,$_REQUEST["meth"]) ? "Sent" : "Failed";
		$doc_id = $_REQUEST["doc_id"];		
		$document = $result->Find_By_Archive_Id($_REQUEST["doc_id"]);		
		break;		
	
}

?>


<table border=1>
<!--
<tr>
<td>
<form method=post>
Test Send (Non API/Commandline)
u/p:<input type=text name="useruse" value="">/u/p:<input type=password name="userpass" value="">
IP:<input type=text name="ipuse" value="10.0.1.16">
Phone:<input type="4929871" name="phoneuse">
<input type=submit name=TestFax value="Test Fax">
</form>
</td>
</tr>
-->
<tr>

<td>
<form method=post>
<b>Find Documents</b><br>
<input type=hidden name=form_type value=docsbyapp>
Application ID:
<input type="text" name=app_id value="">
<br>
<input type=submit name=Get value="Gets Documents">
</form>
</td></tr>
<tr><td>

<form method=post>
<b>Create Document</b><br>
<input type=hidden name=form_type value=docgen>
Templates: <?php print($temp_html); ?>
Application ID:
<input type="text" name=app_id value="">
<br>
Tokens: (ex: "token_key::token_value,token_key2::token_value2")<br>
<input type="text" name=tokens value="BODY::You got the body,PARAGRAPH::I got the brains.." size=50><br>
<input type=submit name=Create value="Create Document">
</form>
 </td></tr>
 <tr><td>
<script language="javascript">

function PreviewDocument(preview)
{
	
	 var generator = window.open('','PrevDoc','height=480,width=640');	
	 generator.document.write(preview);
	 generator.document.close();
}
</script>
 <pre>
<form name=output>
<?php 

// Show the Doc
if($document) {
	print("<b>$msg</b>\n");
	print("Send Document (comma seperated):");
	print("<select name=meth>");
	print("<option value=EMAIL>Email</option>");
	print("<option value=FAX>Fax</option>");
	print("</select>:");
	print("<input type=text name=recp>");
	print("<input type=submit name=Send value=Send>\n\n\n");
	print("<input type=hidden name=doc_id value=$doc_id>");
	print("<input type=hidden name=form_type value=senddoc>");
	print("<b>Document:</b><input type=button value=Preview name=Preview onClick='javascript:PreviewDocument(prev.value);'>\n");	
	//print_r($document); 
	print("<textarea name=prev COLS=100 ROWS=40>".$document->data."</textarea>"); 
}



// Show the Doc List
if($doc_array) { 
	print("<b>App Documents:</b>\n");
	print($docs_html);
} ?>
</form>
 </pre>
 </td></tr>
 </table>
