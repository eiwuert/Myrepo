<?php

	define ('ROOT_PATH', str_replace('cms-plugins/Gallery/admin' , '', realpath(dirname(__FILE__))));

	include ROOT_PATH.'cms-includes/defines.inc.php';
        include ROOT_PATH.'cms-includes/functions.inc.php';
	include ROOT_PATH.'cms-includes/init.inc.php';
	
	
	if (!is_integer(intval($_POST['id'])))
	{
		if (!preg_match('/^\{?[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}\}?$/', $_POST['id']))
		{
			returnError("Upload path has not been set");
		}
	}


	$targetPath = ROOT_PATH.'cms-plugins/Gallery/temp/'.$_POST['id'];	

	if (!is_dir($targetPath))
	{
		mkdir($targetPath);
		chmod($targetPath, 0775);
	}

	function returnError($msg)
	{
		echo "error|$msg";
		exit(0);	
	}

	if (!empty($_FILES))
	{
		$tempFile = $_FILES['Filedata']['tmp_name'];
		$targetFile = rtrim($targetPath) .'/'. $_FILES['Filedata']['name'];

		// Validate the file type
		$fileTypes = array('jpg','jpeg','gif','png'); // File extensions
		$fileParts = pathinfo($_FILES['Filedata']['name']);
		
		if (in_array($fileParts['extension'],$fileTypes)) {
			if (!move_uploaded_file($tempFile,$targetFile))
				returnError("Could not move uploaded file");
				
			chmod($targetFile, 0664);
			echo '1';
		} else {
			returnError("Invalid file type");
		}
	}
?>