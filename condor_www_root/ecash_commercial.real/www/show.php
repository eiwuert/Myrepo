<?php
/**
 * The purpose of this script is to display condor documents or their attachments if a part_id is given.
 * 
 * 
 */

//================== TAKEN FROM show_pdf.php =============================//

// include our config file
require_once('config.php');
require_once(LIB_DIR.'common_functions.php');
require_once(COMMON_LIB_DIR."pay_date_calc.3.php");
require_once(SQL_LIB_DIR . "util.func.php");
require_once(SERVER_CODE_DIR . "server_factory.class.php");
require_once (LIB_DIR . "/Document/Document.class.php");
require_once (LIB_DIR . "/Document/DeliveryAPI/Condor.class.php");

$session_id =  isset($_REQUEST['ssid']) ? $_REQUEST['ssid'] : null;

$request = (object) $_REQUEST;

$server = Server_Factory::get_server_class(null,$session_id);
$server->Process_Data($request);

//========================================================================//


// Get the condor document
$condor_document = eCash_Document_DeliveryAPI_Condor::Prpc()->Find_Email_By_Archive_Id($request->archive_id, TRUE);

//Check to see that we got something
if(empty($condor_document))	
{
	throw new Exception("No Document Found");
}
else
{
	//If the part id is passed in the request, then display the respective attachment, else display the main document.
	if($document_to_show = empty($request->part_id) ? $condor_document : getAttachment($condor_document,$request->part_id))
	{
		out($document_to_show);
	}
}


function out($document)
{
	header("Content-type: ".$document->content_type);
	header('Content-Disposition: attachment; filename=attachment_'.$document->part_id.'_'.Get_Extension($document));
	echo $document->data;
}

function getAttachment($condor_document,$part_id)
{
	foreach($condor_document->attached_data as $attachment)
	{
		if($attachment->part_id == $part_id)
		{
			return $attachment;
		}
	}
	
	return null;
}



//======== Taken from Condor Api class ==========/

/**
 * Returns a file name based on the document's Condor data object
 *
 * @param object $document The Condor data object
 * return string The file extension
 */
function Get_Extension($document)
{
	if ( !empty($document->uri) && $document->uri != 'NULL' )
	{
		return $document->uri;
	}

	$extensions = array(
	                   'text/html'       => '.html',
	                   'text/plain'      => '.txt',
	                   'text/rtf'        => '.rtf',
	                   'text/rtx'        => '.rtf',
	                   'application/pdf' => '.pdf',
	                   'image/tif'       => '.tif',
	                   'image/jpeg' 	 =>  '.jpg',
	                   'image/gif'		 => '.gif'
	                   );

	$filename = ($document->template_name != 'NULL' ? $document->template_name : 'Document');

	if ( isset($extensions[$document->content_type]) )
	{
		return $extensions[$document->content_type];
	}
}

	
	
	