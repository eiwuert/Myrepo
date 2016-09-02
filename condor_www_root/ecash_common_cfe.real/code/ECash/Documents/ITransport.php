<?php

interface ECash_Documents_ITransport
{
	public function send(ECash_Documents_Document $doc);
	public function getType();
	
}


?>