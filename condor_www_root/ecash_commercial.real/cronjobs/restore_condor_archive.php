<?php

require_once LIB_DIR . "/Document/Document.class.php";
require_once eCash_Document_DIR . "/DeliveryAPI/Condor.class.php";

function Main($args)
{
	global $server;
	$db = ECash_Config::getMasterDbConnection();

	$query = "
		update document
		set archive_id = ?
		where document_id = ?
	";
	$update = $db->prepare($query);

	$query = "
		select document_list.name,
			document_id,
			application_id,
			document.document_list_id
		from document
			join document_list on (document.document_list_id = document_list.document_list_id)
		where archive_id = 0
	";
	$st = $db->query($query);

	while ($row = $st->fetch(PDO::FETCH_OBJ))
	{
		$app = eCash_Document::Get_Application_Data($server, $row->application_id);
		$send_arr = eCash_Document_DeliveryAPI_Condor::Map_Data($server, $app);

		$doc_id = eCash_Document_DeliveryAPI_Condor::Prpc()->Create($row->name, $send_arr, true, $app->application_id, $app->track_id, null);

		if ($doc_id['archive_id'])
		{
			$args = array($doc_id['archive_id'], $row->document_id);
			$update->execute($args);
			var_dump($args);
		}
	}
}