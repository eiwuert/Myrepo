<?php

	require_once('proClient.php');
	class statProClient extends proClient
	{
		function statProClient ($exeKey)
		{
			parent::proClient('/opt/statpro/var/', $exeKey);
		}

		function createTrack ($custKey, $custPass, $space_key = 'blank')
		{
			$track_key = $this->hash(microtime().mt_rand().uniqid(mt_rand(), true));
			//$this->recordEvent($custKey, $custPass, $track_key, $space_key, '___init');

			return array ("key" => $track_key);
		}

		function getSpaceKey ($custKey, $custPass, $spaceDef)
		{
			ksort($spaceDef);
			$s = '';
			foreach ($spaceDef as $k => $v)
			{
				$s .= strtolower(trim($v)).'|';
			}
			$spaceKey = $this->hash($s);

			$this->doJournal(time(), 2, $custKey, $custPass, $spaceKey, serialize($spaceDef));

			return $spaceKey;
		}

		function recordEvent ($custKey, $custPass, $trackKey, $spaceKey, $eventTypeKey, $dateOccured = NULL, $eventAmount = NULL)
		{
			if (! $dateOccured)
			{
				$dateOccured = time();
			}

			$this->doJournal (time(), 1, $custKey, $custPass, $trackKey, $spaceKey, $eventTypeKey, $dateOccured, $eventAmount);

			return TRUE;
		}

		function setTrackSpace ($custKey, $custPass, $trackKey, $trackSpaceKeyA, $trackSpaceKeyB = NULL, $trackSpaceKeyC = NULL)
		{
			$this->doJournal (time(), 4, $custKey, $custPass, $trackKey, $trackSpaceKeyA, $trackSpaceKeyB, $trackSpaceKeyC);

			return TRUE;
		}

		function setGlobal ($custKey, $custPass, $trackKey, $globalKey = NULL)
		{
			$globalKey = (is_null ($globalKey) ? $this->Hash(microtime().mt_rand().uniqid(mt_rand(), true)) : $globalKey);

			$this->doJournal (time(), 3, $custKey, $custPass, $trackKey, $globalKey);

			return array ("key" => $globalKey);
		}

		function pwEvent ($custKey, $custPass, $pwadvid, $eventTypeKey, $dateOccured = NULL)
		{
			if (! $dateOccured)
			{
				$dateOccured = time();
			}

			$this->doJournal (time(), 5, $custKey, $custPass, $pwadvid, $eventTypeKey, $dateOccured);

			return TRUE;
		}

		function urlEvent ($custKey, $custPass, $url, $dateOccured = NULL)
		{
			if (! $dateOccured)
			{
				$dateOccured = time();
			}

			$this->doJournal (time(), 6, $custKey, $custPass, $url, $dateOccured);

			return TRUE;
		}

		function pwLead ($custKey, $custPass, $pwadvid, $dateOccured = NULL)
		{
			return $this->pwEvent($custKey, $custPass, $pwadvid, 'lead', $dateOccured);
		}

		function pwFund ($custKey, $custPass, $pwadvid, $dateOccured = NULL)
		{
			return $this->pwEvent($custKey, $custPass, $pwadvid, 'fund', $dateOccured);
		}
	}

?>
