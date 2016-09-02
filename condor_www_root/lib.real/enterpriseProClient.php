<?php

	require_once('proClient.php');

	class enterpriseProClient extends proClient
	{
		function enterpriseProClient ($exeKey)
		{
			parent::proClient('/opt/enterprisepro/var/', $exeKey);
		}

		function getSpaceKey ($custKey, $custPass, $spaceDef)
		{
			ksort($spaceDef);
			$s = '';
			foreach ($spaceDef as $k => $v)
			{
				$s .= trim($v).'|';
			}
			$spaceKey = $this->hash($s);

			$this->doJournal(time(), 2, $custKey, $custPass, $spaceKey, serialize($spaceDef));

			return $spaceKey;
		}
	}
?>
